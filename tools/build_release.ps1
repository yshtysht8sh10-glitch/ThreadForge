param(
    [switch]$SkipClientBuild,
    [ValidateSet('image-board', 'file-uploader', 'guide-posts', 'proxy-release', 'materials-library')]
    [string]$FrontendId = 'image-board',
    [string]$Version = ''
)

$ErrorActionPreference = 'Stop'

$repoRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
Set-Location $repoRoot
$frontendDir = Join-Path $repoRoot "frontends\$FrontendId"

if ([string]::IsNullOrWhiteSpace($Version)) {
    $packageFile = Join-Path $frontendDir 'package.json'
    if (Test-Path $packageFile) {
        $Version = (Get-Content $packageFile -Raw | ConvertFrom-Json).version
    } else {
        $versionFile = Join-Path $repoRoot 'VERSION'
        $Version = if (Test-Path $versionFile) { (Get-Content $versionFile -Raw).Trim() } else { 'dev' }
    }
}

$packageName = "threadforge-$FrontendId-$Version"
$deployDirectory = switch ($FrontendId) {
    'image-board' { '11_image_board' }
    'file-uploader' { '12_file_uploader' }
    'materials-library' { '15_materials_library' }
    default { $FrontendId.Replace('-', '_') }
}
$releaseDir = Join-Path $repoRoot 'release'
$stageBase = Join-Path $releaseDir '.stage'
$stageRoot = Join-Path $stageBase $packageName
$appRoot = Join-Path $stageRoot $deployDirectory
$zipPath = Join-Path $releaseDir "$packageName.zip"

function Copy-ReleaseItem {
    param(
        [Parameter(Mandatory = $true)][string]$Source,
        [Parameter(Mandatory = $true)][string]$Destination
    )

    $sourcePath = Join-Path $repoRoot $Source
    if (-not (Test-Path $sourcePath)) {
        return
    }

    $destinationPath = Join-Path $appRoot $Destination
    $destinationParent = Split-Path $destinationPath -Parent
    New-Item -ItemType Directory -Force -Path $destinationParent | Out-Null
    Copy-Item -LiteralPath $sourcePath -Destination $destinationPath -Recurse -Force
}

if (-not $SkipClientBuild) {
    Push-Location $frontendDir
    try {
        $previousApiBase = $env:VITE_API_BASE_URL
        $previousUseMock = $env:VITE_USE_MOCK
        $env:VITE_API_BASE_URL = 'api.php'
        $env:VITE_USE_MOCK = 'false'
        npm run build
    } finally {
        $env:VITE_API_BASE_URL = $previousApiBase
        $env:VITE_USE_MOCK = $previousUseMock
        Pop-Location
    }
}

if (-not (Test-Path (Join-Path $frontendDir 'dist'))) {
    throw "frontends/$FrontendId/dist is missing. Build that frontend first, or run this script without -SkipClientBuild."
}

New-Item -ItemType Directory -Force -Path $releaseDir | Out-Null

$resolvedReleaseDir = (Resolve-Path $releaseDir).Path
if ((Test-Path $stageBase) -and -not ((Resolve-Path $stageBase).Path.StartsWith($resolvedReleaseDir))) {
    throw "Safety check failed: $stageBase"
}

Remove-Item -LiteralPath $stageBase -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Force -Path $appRoot | Out-Null

Copy-Item -Path (Join-Path $frontendDir 'dist\*') -Destination $appRoot -Recurse -Force
Copy-ReleaseItem -Source 'server\api.php' -Destination 'api.php'
Copy-ReleaseItem -Source 'server\db.php' -Destination 'db.php'
Copy-ReleaseItem -Source 'server\cron.php' -Destination 'cron.php'
Copy-ReleaseItem -Source 'server\.user.ini' -Destination '.user.ini'
$storageDir = Join-Path $appRoot 'storage\data'
New-Item -ItemType Directory -Force -Path $storageDir | Out-Null
Set-Content -Path (Join-Path $storageDir '.gitkeep') -Value '' -Encoding UTF8
Copy-ReleaseItem -Source 'docs' -Destination 'docs'
Copy-ReleaseItem -Source 'README.md' -Destination 'README.md'
Copy-ReleaseItem -Source 'README.ja.md' -Destination 'README.ja.md'
Copy-ReleaseItem -Source 'CHANGELOG.md' -Destination 'CHANGELOG.md'
Copy-ReleaseItem -Source 'CHANGELOG.ja.md' -Destination 'CHANGELOG.ja.md'
Set-Content -Path (Join-Path $appRoot 'VERSION') -Value $Version -Encoding ASCII
Set-Content -Path (Join-Path $appRoot 'FRONTEND_ID') -Value $FrontendId -Encoding ASCII

if (Test-Path $zipPath) {
    Remove-Item -LiteralPath $zipPath -Force
}

Compress-Archive -Path (Join-Path $stageRoot '*') -DestinationPath $zipPath -Force
Remove-Item -LiteralPath $stageBase -Recurse -Force

Write-Host "Created: $zipPath"
Write-Host "Frontend: $FrontendId"
Write-Host "Deploy directory: $deployDirectory"
Write-Host 'Runtime DB and uploaded images are not included.'
