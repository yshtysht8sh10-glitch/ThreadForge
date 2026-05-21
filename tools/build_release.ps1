param(
    [switch]$SkipClientBuild,
    [string]$Version = ''
)

$ErrorActionPreference = 'Stop'

$repoRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
Set-Location $repoRoot

if ([string]::IsNullOrWhiteSpace($Version)) {
    $versionFile = Join-Path $repoRoot 'VERSION'
    $Version = if (Test-Path $versionFile) { (Get-Content $versionFile -Raw).Trim() } else { 'dev' }
}

$packageName = "threadforge-$Version"
$releaseDir = Join-Path $repoRoot 'release'
$stageBase = Join-Path $releaseDir '.stage'
$stageRoot = Join-Path $stageBase $packageName
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

    $destinationPath = Join-Path $stageRoot $Destination
    $destinationParent = Split-Path $destinationPath -Parent
    New-Item -ItemType Directory -Force -Path $destinationParent | Out-Null
    Copy-Item -LiteralPath $sourcePath -Destination $destinationPath -Recurse -Force
}

if (-not $SkipClientBuild) {
    Push-Location (Join-Path $repoRoot 'client')
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

if (-not (Test-Path (Join-Path $repoRoot 'client\dist'))) {
    throw 'client/dist is missing. Run npm run build in client, or run this script without -SkipClientBuild.'
}

New-Item -ItemType Directory -Force -Path $releaseDir | Out-Null

$resolvedReleaseDir = (Resolve-Path $releaseDir).Path
if ((Test-Path $stageBase) -and -not ((Resolve-Path $stageBase).Path.StartsWith($resolvedReleaseDir))) {
    throw "Safety check failed: $stageBase"
}

Remove-Item -LiteralPath $stageBase -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Force -Path $stageRoot | Out-Null

Copy-Item -Path (Join-Path $repoRoot 'client\dist\*') -Destination $stageRoot -Recurse -Force
Copy-ReleaseItem -Source 'server\api.php' -Destination 'api.php'
Copy-ReleaseItem -Source 'server\db.php' -Destination 'db.php'
Copy-ReleaseItem -Source 'server\cron.php' -Destination 'cron.php'
Copy-ReleaseItem -Source 'server\storage\data\.gitkeep' -Destination 'storage\data\.gitkeep'
Copy-ReleaseItem -Source 'docs' -Destination 'docs'
Copy-ReleaseItem -Source 'README.md' -Destination 'README.md'
Copy-ReleaseItem -Source 'README.ja.md' -Destination 'README.ja.md'
Copy-ReleaseItem -Source 'CHANGELOG.md' -Destination 'CHANGELOG.md'
Copy-ReleaseItem -Source 'CHANGELOG.ja.md' -Destination 'CHANGELOG.ja.md'
Copy-ReleaseItem -Source 'VERSION' -Destination 'VERSION'

if (Test-Path $zipPath) {
    Remove-Item -LiteralPath $zipPath -Force
}

Compress-Archive -Path (Join-Path $stageRoot '*') -DestinationPath $zipPath -Force
Remove-Item -LiteralPath $stageBase -Recurse -Force

Write-Host "Created: $zipPath"
Write-Host 'Runtime DB and uploaded images are not included.'
