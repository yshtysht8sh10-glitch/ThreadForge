import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const repoRoot = resolve(fileURLToPath(new URL('..', import.meta.url)));
const apps = [
  ['Image Board', 'image-board'],
  ['File Uploader', 'file-uploader'],
  ['Document Holder', 'document-holder'],
  ['Materials Library', 'materials-library'],
  ['Proxy Release', 'proxy-release'],
];
const semverPattern = /^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/;

const versions = apps.map(([name, id]) => {
  const source = `frontends/${id}/package.json`;
  const packagePath = resolve(repoRoot, source);
  if (!existsSync(packagePath)) {
    throw new Error(`Missing version source: ${source}`);
  }
  const version = JSON.parse(readFileSync(packagePath, 'utf8')).version;
  if (typeof version !== 'string' || !semverPattern.test(version)) {
    throw new Error(`Invalid SemVer in ${source}: ${String(version)}`);
  }
  return { name, id, version, source };
});

const tableFor = (sourceHeader) => [
  `| App | Current Version | ${sourceHeader} |`,
  '| --- | --- | --- |',
  ...versions.map((app) => `| ${app.name} | ${app.version} | \`${app.source}\` |`),
].join('\n');

const versionBlockPattern = /<!-- app-versions:start -->\r?\n[\s\S]*?\r?\n<!-- app-versions:end -->/;
const readmeTargets = [
  ['README.md', tableFor('Source of Truth')],
  ['README.ja.md', tableFor('正本')],
];

if (process.argv.includes('--write')) {
  for (const [relativePath, table] of readmeTargets) {
    const target = resolve(repoRoot, relativePath);
    const contents = readFileSync(target, 'utf8');
    if (!versionBlockPattern.test(contents)) {
      throw new Error(`Missing app version markers in ${relativePath}`);
    }
    writeFileSync(
      target,
      contents.replace(versionBlockPattern, `<!-- app-versions:start -->\n${table}\n<!-- app-versions:end -->`),
      'utf8',
    );
  }
}

if (process.argv.includes('--check')) {
  const rootPackage = JSON.parse(readFileSync(resolve(repoRoot, 'package.json'), 'utf8'));
  const rootLock = JSON.parse(readFileSync(resolve(repoRoot, 'package-lock.json'), 'utf8'));
  if (Object.hasOwn(rootPackage, 'version')) {
    throw new Error('Root package.json must not define a repository-wide version.');
  }
  if (Object.hasOwn(rootLock, 'version') || Object.hasOwn(rootLock.packages?.[''] ?? {}, 'version')) {
    throw new Error('Root package-lock.json must not define a repository-wide version.');
  }
  if (existsSync(resolve(repoRoot, 'VERSION'))) {
    throw new Error('Root VERSION must not exist; use each app package.json.');
  }
  for (const [relativePath, table] of readmeTargets) {
    const contents = readFileSync(resolve(repoRoot, relativePath), 'utf8');
    const expected = `<!-- app-versions:start -->\n${table}\n<!-- app-versions:end -->`;
    const actual = contents.match(versionBlockPattern)?.[0]?.replace(/\r\n/g, '\n');
    if (actual !== expected) {
      throw new Error(`Version table is stale in ${relativePath}; run npm run versions:update-docs.`);
    }
  }
}

console.log('| App | Current Version | Source |');
console.log('| --- | --- | --- |');
for (const app of versions) {
  console.log(`| ${app.name} | ${app.version} | \`${app.source}\` |`);
}
