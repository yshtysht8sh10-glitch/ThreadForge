import { mkdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDir = path.dirname(fileURLToPath(import.meta.url));
const rootDir = path.resolve(scriptDir, '..');
const docsDir = path.join(rootDir, 'docs');

const frontends = [
  ['image-board', 'Image Board', '画像掲示板'],
  ['file-uploader', 'File Uploader', 'ファイルアップローダー'],
  ['document-holder', 'Document Holder', 'ドキュメントホルダー'],
  ['proxy-release', 'Proxy Release', '代理公開'],
  ['materials-library', 'Materials Library', '素材ライブラリ'],
];

const pages = [
  page('index.html', 'README.md', 'ja/README.md', 'Documentation', 'ドキュメント'),
  page('SPEC.html', 'SPEC.md', 'ja/SPEC.md', 'Specification', '仕様'),
  page('API.html', 'API.md', 'ja/API.md', 'API', 'API'),
  page('DB.html', 'DB.md', 'ja/DB.md', 'Database', 'データベース'),
  page('ARCHITECTURE.html', 'ARCHITECTURE.md', 'ja/ARCHITECTURE.md', 'Architecture', '構成'),
  page('FRONTEND_ARCHITECTURE.html', 'FRONTEND_ARCHITECTURE.md', 'ja/FRONTEND_ARCHITECTURE.md', 'Frontend Architecture', 'フロントエンド構成'),
  page('VERSIONING.html', 'VERSIONING.md', 'ja/VERSIONING.md', 'Application Versioning', 'アプリ単位のVersion管理'),
  page('MIGRATION.html', 'MIGRATION.md', 'ja/MIGRATION.md', 'Migration', '移行'),
  page('TESTING.html', 'TESTING.md', 'ja/TESTING.md', 'Testing', 'テスト'),
  {
    output: 'CHANGELOG.html',
    en: path.join(rootDir, 'CHANGELOG.md'),
    ja: path.join(rootDir, 'CHANGELOG.ja.md'),
    labelEn: 'Changelog',
    labelJa: '変更履歴',
    absoluteSources: true,
  },
  ...frontends.flatMap(([id, labelEn, labelJa]) => [
    page(`frontends/${id}/index.html`, `frontends/${id}/README.md`, `ja/frontends/${id}/README.md`, labelEn, labelJa, id),
    page(`frontends/${id}/SPEC.html`, `frontends/${id}/SPEC.md`, `ja/frontends/${id}/SPEC.md`, `${labelEn} Spec`, `${labelJa}仕様`, id),
    page(`frontends/${id}/DB.html`, `frontends/${id}/DB.md`, `ja/frontends/${id}/DB.md`, `${labelEn} DB`, `${labelJa} DB`, id),
  ]),
];

function page(output, en, ja, labelEn, labelJa, group = 'core') {
  return { output, en, ja, labelEn, labelJa, group, absoluteSources: false };
}

function escapeHtml(value) {
  return value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');
}

function rewriteHref(href) {
  if (/^(https?:|mailto:|#)/i.test(href)) return href;
  const [target, hash = ''] = href.split('#');
  let next = target.replaceAll('\\', '/');
  if (next === '../index.html') next = 'index.html';
  next = next.replace(/^(\.\.\/)+CHANGELOG(?:\.ja)?\.md$/, 'CHANGELOG.html');
  next = next.replace(/(^|\/)ja\//, '$1');
  next = next.replace(/README(?:\.ja)?\.md$/, 'index.html');
  next = next.replace(/\.md$/, '.html');
  return hash ? `${next}#${hash}` : next;
}

function renderInline(value) {
  const code = [];
  let text = value.replace(/`([^`]+)`/g, (_, content) => {
    code.push(`<code>${escapeHtml(content)}</code>`);
    return `\u0000${code.length - 1}\u0000`;
  });
  text = escapeHtml(text);
  text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (_, label, href) => {
    return `<a href="${escapeHtml(rewriteHref(href))}">${label}</a>`;
  });
  text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
  text = text.replace(/\*([^*]+)\*/g, '<em>$1</em>');
  text = text.replace(/\u0000(\d+)\u0000/g, (_, index) => code[Number(index)]);
  return text;
}

function renderMarkdown(markdown) {
  const lines = markdown
    .replace(/^\uFEFF/, '')
    .split(/\r?\n/)
    .filter((line) => !/^\[(Japanese|English)[^\]]*\]\(/i.test(line.trim()));
  const html = [];
  let index = 0;
  let listType = '';

  const closeList = () => {
    if (listType) html.push(`</${listType}>`);
    listType = '';
  };

  while (index < lines.length) {
    const line = lines[index];
    const trimmed = line.trim();

    if (trimmed.startsWith('```')) {
      closeList();
      const language = trimmed.slice(3).trim();
      const content = [];
      index += 1;
      while (index < lines.length && !lines[index].trim().startsWith('```')) {
        content.push(lines[index]);
        index += 1;
      }
      html.push(`<pre><code${language ? ` class="language-${escapeHtml(language)}"` : ''}>${escapeHtml(content.join('\n'))}</code></pre>`);
      index += 1;
      continue;
    }

    const heading = /^(#{1,6})\s+(.+)$/.exec(trimmed);
    if (heading) {
      closeList();
      const level = heading[1].length;
      const title = heading[2];
      const id = title.toLowerCase().replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-|-$/g, '');
      html.push(`<h${level} id="${escapeHtml(id)}">${renderInline(title)}</h${level}>`);
      index += 1;
      continue;
    }

    if (trimmed.includes('|') && index + 1 < lines.length && /^\s*\|?[\s:|-]+\|[\s:|-]+\|?\s*$/.test(lines[index + 1])) {
      closeList();
      const headers = trimmed.replace(/^\||\|$/g, '').split('|').map((cell) => cell.trim());
      index += 2;
      const rows = [];
      while (index < lines.length && lines[index].trim().includes('|')) {
        rows.push(lines[index].trim().replace(/^\||\|$/g, '').split('|').map((cell) => cell.trim()));
        index += 1;
      }
      html.push('<div class="table-scroll"><table><thead><tr>');
      headers.forEach((cell) => html.push(`<th>${renderInline(cell)}</th>`));
      html.push('</tr></thead><tbody>');
      rows.forEach((row) => {
        html.push('<tr>');
        row.forEach((cell) => html.push(`<td>${renderInline(cell)}</td>`));
        html.push('</tr>');
      });
      html.push('</tbody></table></div>');
      continue;
    }

    const unordered = /^[-*]\s+(.+)$/.exec(trimmed);
    const ordered = /^\d+\.\s+(.+)$/.exec(trimmed);
    if (unordered || ordered) {
      const wanted = ordered ? 'ol' : 'ul';
      if (listType !== wanted) {
        closeList();
        listType = wanted;
        html.push(`<${listType}>`);
      }
      html.push(`<li>${renderInline((unordered || ordered)[1])}</li>`);
      index += 1;
      continue;
    }

    if (trimmed.startsWith('>')) {
      closeList();
      html.push(`<blockquote>${renderInline(trimmed.replace(/^>\s?/, ''))}</blockquote>`);
      index += 1;
      continue;
    }

    if (trimmed === '---') {
      closeList();
      html.push('<hr>');
      index += 1;
      continue;
    }

    if (trimmed === '') {
      closeList();
      index += 1;
      continue;
    }

    closeList();
    const paragraph = [trimmed];
    index += 1;
    while (index < lines.length) {
      const next = lines[index].trim();
      if (
        next === ''
        || next.startsWith('#')
        || next.startsWith('```')
        || /^[-*]\s+/.test(next)
        || /^\d+\.\s+/.test(next)
        || next.startsWith('>')
        || next === '---'
      ) break;
      paragraph.push(next);
      index += 1;
    }
    html.push(`<p>${paragraph.map(renderInline).join('<br>')}</p>`);
  }

  closeList();
  return html.join('\n');
}

function relativeLink(fromOutput, toOutput) {
  const fromDir = path.posix.dirname(fromOutput.replaceAll('\\', '/'));
  let value = path.posix.relative(fromDir, toOutput.replaceAll('\\', '/'));
  if (!value.startsWith('.')) value = `./${value}`;
  return value;
}

function navigation(current, language) {
  const labelKey = language === 'ja' ? 'labelJa' : 'labelEn';
  const core = pages.filter((item) => item.group === 'core');
  const groups = frontends.map(([id, labelEn, labelJa]) => ({
    id,
    label: language === 'ja' ? labelJa : labelEn,
    pages: pages.filter((item) => item.group === id),
  }));
  const link = (item) => {
    const active = item.output === current ? ' aria-current="page"' : '';
    return `<li><a href="${relativeLink(current, item.output)}"${active}>${escapeHtml(item[labelKey])}</a></li>`;
  };
  return `
    <nav class="docs-nav" aria-label="${language === 'ja' ? 'ドキュメント目次' : 'Documentation navigation'}">
      <div class="docs-nav__brand"><span>ThreadForge</span><small>Documentation</small></div>
      <h2>${language === 'ja' ? '共通資料' : 'Core'}</h2>
      <ul>${core.map(link).join('')}</ul>
${groups.map((group) => `
        <h2>${escapeHtml(group.label)}</h2>
        <ul>${group.pages.map(link).join('')}</ul>
`).join('')}
    </nav>`;
}

function documentHtml(item, enMarkdown, jaMarkdown) {
  const depth = item.output.split('/').length - 1;
  const assetPrefix = depth === 0 ? 'assets/' : '../'.repeat(depth) + 'assets/';
  const title = `${item.labelEn} / ${item.labelJa}`;
  return `<!doctype html>
<html lang="ja" data-lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="ThreadForge bilingual documentation">
  <title>${escapeHtml(title)} | ThreadForge</title>
  <link rel="stylesheet" href="${assetPrefix}docs.css">
  <script src="${assetPrefix}docs.js" defer></script>
</head>
<body>
  <a class="skip-link" href="#content">Skip to content / 本文へ</a>
  <div class="docs-shell">
    <div data-language-panel="ja">${navigation(item.output, 'ja')}</div>
    <div data-language-panel="en">${navigation(item.output, 'en')}</div>
    <main class="docs-main" id="content">
      <header class="docs-toolbar">
        <div>
          <span class="docs-eyebrow">ThreadForge Docs</span>
          <strong>${escapeHtml(title)}</strong>
        </div>
        <div class="language-switch" role="group" aria-label="Language / 言語">
          <button type="button" data-language-button="ja">日本語</button>
          <button type="button" data-language-button="en">English</button>
        </div>
      </header>
      <article class="docs-article" data-language-panel="ja">${renderMarkdown(jaMarkdown)}</article>
      <article class="docs-article" data-language-panel="en">${renderMarkdown(enMarkdown)}</article>
      <footer>ThreadForge documentation · Generated from the repository Markdown sources.</footer>
    </main>
  </div>
</body>
</html>
`;
}

for (const item of pages) {
  const enPath = item.absoluteSources ? item.en : path.join(docsDir, item.en);
  const jaPath = item.absoluteSources ? item.ja : path.join(docsDir, item.ja);
  const [enMarkdown, jaMarkdown] = await Promise.all([
    readFile(enPath, 'utf8'),
    readFile(jaPath, 'utf8'),
  ]);
  const outputPath = path.join(docsDir, item.output);
  await mkdir(path.dirname(outputPath), { recursive: true });
  await writeFile(outputPath, documentHtml(item, enMarkdown, jaMarkdown), 'utf8');
}

console.log(`Generated ${pages.length} bilingual HTML documentation pages.`);
