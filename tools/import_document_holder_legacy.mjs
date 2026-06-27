import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const positional = process.argv.slice(2).filter((arg) => !arg.startsWith('--'));
const legacyRoot = path.resolve(positional[0] ?? path.join(root, 'frontends/document-holder/legacy/04_DMF'));
const apiBase = process.env.THREADFORGE_API_BASE ?? 'http://127.0.0.1:8014/api.php';
const adminPassword = process.env.THREADFORGE_ADMIN_PASSWORD ?? 'admin';
const postPassword = process.env.THREADFORGE_LEGACY_POST_PASSWORD ?? 'legacy';
const dryRun = process.argv.includes('--dry-run');
const htmlPrefix = '<!-- threadforge-document-html -->';
let CRC_TABLE;

const menuHtml = fs.readFileSync(path.join(legacyRoot, 'menu.html'), 'utf8');
const records = collectMenuRecords(menuHtml);

if (dryRun) {
  console.log(`records: ${records.length}`);
  for (const record of records) console.log(`${record.category}\t${record.author}\t${record.title}\t${record.href}`);
  process.exit(0);
}

const settingsResponse = await apiGet('materialsSettings');
const syncedTags = await ensureLegacyTags(settingsResponse.tags, settingsResponse.terms, records);
const tagMap = new Map(syncedTags.map((tag) => [tag.name, tag.id]));
const existingResponse = await apiGet('listMaterialItems');
const existingKeys = new Set(existingResponse.items.map((item) => `${item.name}\n${item.authorName}`));

let imported = 0;
let skipped = 0;
for (const record of records) {
  const key = `${record.title}\n${record.author}`;
  if (existingKeys.has(key)) {
    skipped += 1;
    continue;
  }
  const sourcePath = path.join(legacyRoot, record.href.replace(/\//g, path.sep));
  if (!fs.existsSync(sourcePath)) {
    console.warn(`missing: ${record.href}`);
    skipped += 1;
    continue;
  }
  const sourceDir = path.dirname(sourcePath);
  const html = rewriteArticleHtml(fs.readFileSync(sourcePath, 'utf8'), sourceDir);
  const archiveBytes = makeZip([{ path: 'index.html', data: fs.readFileSync(sourcePath) }]);
  const form = new FormData();
  form.set('frontend_id', 'document-holder');
  form.set('action', 'createMaterialItem');
  form.set('name', record.title);
  form.set('author_name', record.author);
  form.set('tag_id', String(tagMap.get(record.category) ?? settingsResponse.tags[0].id));
  form.set('password', postPassword);
  form.set('terms', JSON.stringify({}));
  form.set('notes', htmlPrefix + html);
  form.set('archive', new File([archiveBytes], `${safeName(path.basename(sourceDir))}-${safeName(path.basename(sourcePath, path.extname(sourcePath)))}.zip`, { type: 'application/zip' }));
  const response = await fetch(apiBase, { method: 'POST', body: form });
  const text = await response.text();
  let json;
  try {
    json = JSON.parse(text);
  } catch {
    throw new Error(text);
  }
  if (!response.ok || json.success === false) throw new Error(json.message ?? text);
  imported += 1;
}

await applyLegacyDesign();
console.log(`imported: ${imported}`);
console.log(`skipped: ${skipped}`);

function collectMenuRecords(html) {
  const records = [];
  let category = 'その他';
  const categoryOnlyHtml = html.split('<!--ここから作者順-->')[0] ?? html;
  const tokenPattern = /<li class="title">([\s\S]*?)<\/li>|<li><a\s+href="([^"]+)"[^>]*>([\s\S]*?)<\/a><\/li>/gi;
  let match;
  while ((match = tokenPattern.exec(categoryOnlyHtml))) {
    if (match[1]) {
      category = cleanText(match[1]);
      continue;
    }
    const href = match[2];
    const title = cleanText(match[3]);
    if (!href || !title || !/^toukou\//i.test(href)) continue;
    const sourcePath = path.join(legacyRoot, href.replace(/\//g, path.sep));
    const articleHtml = fs.existsSync(sourcePath) ? fs.readFileSync(sourcePath, 'utf8') : '';
    records.push({
      href,
      title,
      category,
      author: authorFromHtml(articleHtml) || authorFromPath(href),
    });
  }
  return records;
}

function authorFromHtml(html) {
  const title = html.match(/<title>投稿者[：:]\s*([^<]+)<\/title>/i)?.[1]
    ?? html.match(/投稿者[：:]\s*([^<\s]+)/i)?.[1];
  return title ? cleanText(title) : '';
}

function authorFromPath(href) {
  const folder = href.split('/')[1] ?? 'unknown';
  return folder.replace(/^\d+/, '') || folder;
}

function rewriteArticleHtml(html, sourceDir) {
  let body = html.match(/<body[^>]*>([\s\S]*?)<\/body>/i)?.[1] ?? html;
  body = body
    .replace(/<div id="PAGEHEADMENU"[\s\S]*?<\/div>\s*<\/div>\s*<\/div>/i, '')
    .replace(/<link[^>]+>/gi, '')
    .replace(/<script[\s\S]*?<\/script>/gi, '');
  body = body.replace(/\s(src|href)=["']([^"']+)["']/gi, (whole, attr, rawUrl) => {
    if (/^(https?:|data:|mailto:|#)/i.test(rawUrl)) return whole;
    const filePath = path.resolve(sourceDir, rawUrl.replace(/\//g, path.sep));
    if (!fs.existsSync(filePath) || fs.statSync(filePath).isDirectory()) return whole;
    if (attr.toLowerCase() === 'src') {
      return ` ${attr}="${dataUrl(filePath)}"`;
    }
    return whole;
  });
  return `<div class="legacy-document">${body}</div>`;
}

function collectDirectoryFiles(dir) {
  const files = [];
  const walk = (current) => {
    for (const entry of fs.readdirSync(current, { withFileTypes: true })) {
      const full = path.join(current, entry.name);
      if (entry.isDirectory()) walk(full);
      else files.push({
        path: path.relative(dir, full).replace(/\\/g, '/'),
        data: fs.readFileSync(full),
      });
    }
  };
  walk(dir);
  return files;
}

async function applyLegacyDesign() {
  const settings = await apiGet('getSettings', { admin_password: adminPassword });
  const bgPath = path.join(legacyRoot, 'style/img/bg.gif');
  const design = {
    materialsPageBackgroundColor: '#ffffff',
    materialsPageTextColor: '#1f2a30',
    materialsHeaderBackgroundColor: '#d8eeee',
    materialsHeaderTextColor: '#123e46',
    materialsHeaderBorderColor: '#91b9bd',
    materialsPanelBackgroundColor: '#ffffff',
    materialsPanelBorderColor: '#9ab5b8',
    materialsHeadingBackgroundColor: '#3d6a72',
    materialsHeadingBackgroundImageUrl: fs.existsSync(bgPath) ? dataUrl(bgPath) : '',
    materialsHeadingTextColor: '#ffffff',
    materialsAccentColor: '#1e6b78',
    materialsButtonBackgroundColor: '#e9f2f2',
    materialsButtonTextColor: '#123e46',
    materialsSecondaryButtonBackgroundColor: '#eef3f3',
    materialsSecondaryButtonTextColor: '#263238',
    materialsDangerButtonBackgroundColor: '#ba3d3d',
    materialsDangerButtonTextColor: '#ffffff',
    materialsInputBackgroundColor: '#ffffff',
    materialsInputTextColor: '#111111',
    materialsFormLabelColor: '#005f73',
    materialsEditorBackgroundColor: '#ffffff',
    materialsEditorTextColor: '#1f2a30',
    materialsEditorBorderColor: '#7f989d',
    materialsToolbarBackgroundColor: '#eef7f7',
    materialsSelectionBackgroundColor: '#ffffff',
    materialsSelectionHoverBackgroundColor: '#e5f3f3',
    materialsSelectionTextColor: '#1f2a30',
    materialsSelectionMetaColor: '#52666d',
    materialsImageBackgroundColor: '#f7fafa',
    materialsMutedTextColor: '#6f7d80',
    materialsListRowTextColor: '#1f2a30',
    materialsListRowMetaColor: '#52666d',
    materialsListRowBorderColor: '#7f989d',
    materialsTocGroupTitleColor: '#1f2a30',
    materialsPositiveColor: '#3c8b4a',
    materialsNegativeColor: '#b64040',
    materialsUnknownColor: '#8b7b30',
  };
  const next = {
    ...settings.settings,
    config: {
      ...settings.settings.config,
      materialsTitle: 'Do||Mu,File',
      materialsDescription: 'MUGENやドット絵制作に関する投稿ページを、タグと作者名で整理して保管します。',
      materialsHomePageUrl: '../',
      materialsGroupParent: 'tag',
    },
    skin: { ...settings.settings.skin, ...design },
  };
  const form = new FormData();
  form.set('frontend_id', 'document-holder');
  form.set('action', 'updateSettings');
  form.set('admin_password', adminPassword);
  form.set('settings_b64', Buffer.from(JSON.stringify(next), 'utf8').toString('base64'));
  const response = await fetch(apiBase, { method: 'POST', body: form });
  const text = await response.text();
  const json = JSON.parse(text);
  if (!response.ok || json.success === false) throw new Error(json.message ?? text);
}

async function ensureLegacyTags(tags, terms, records) {
  const needed = [...new Set(records.map((record) => record.category))];
  if (needed.every((name) => tags.some((tag) => tag.name === name))) return tags;
  const merged = [
    ...needed.map((name, index) => ({ id: tags.find((tag) => tag.name === name)?.id ?? 0, name, sortOrder: index })),
    ...tags.filter((tag) => !needed.includes(tag.name)).map((tag, index) => ({ ...tag, sortOrder: needed.length + index })),
  ];
  const form = new FormData();
  form.set('frontend_id', 'document-holder');
  form.set('action', 'saveMaterialCatalog');
  form.set('admin_password', adminPassword);
  form.set('tags', JSON.stringify(merged));
  form.set('terms', JSON.stringify(terms));
  const response = await fetch(apiBase, { method: 'POST', body: form });
  const text = await response.text();
  const json = JSON.parse(text);
  if (!response.ok || json.success === false) throw new Error(json.message ?? text);
  return (await apiGet('materialsSettings')).tags;
}

async function apiGet(action, params = {}) {
  const url = new URL(apiBase);
  url.searchParams.set('frontend_id', 'document-holder');
  url.searchParams.set('action', action);
  for (const [key, value] of Object.entries(params)) url.searchParams.set(key, String(value));
  const response = await fetch(url);
  const text = await response.text();
  const json = JSON.parse(text);
  if (!response.ok || json.success === false) throw new Error(json.message ?? text);
  return json;
}

function cleanText(value) {
  return decodeEntities(value.replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim());
}

function decodeEntities(value) {
  return value
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'");
}

function dataUrl(filePath) {
  const ext = path.extname(filePath).toLowerCase();
  const type = {
    '.gif': 'image/gif',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.webp': 'image/webp',
    '.css': 'text/css',
  }[ext] ?? 'application/octet-stream';
  return `data:${type};base64,${fs.readFileSync(filePath).toString('base64')}`;
}

function safeName(value) {
  return value.replace(/[\\/:*?"<>|\s]+/g, '_').replace(/^_+|_+$/g, '') || 'document';
}

function makeZip(entries) {
  const localParts = [];
  const centralParts = [];
  let offset = 0;
  for (const entry of entries) {
    const name = Buffer.from(entry.path);
    const data = Buffer.from(entry.data);
    const crc = crc32(data);
    const local = Buffer.concat([
      u32(0x04034b50), u16(20), u16(0), u16(0), u16(0), u16(0), u32(crc),
      u32(data.length), u32(data.length), u16(name.length), u16(0), name, data,
    ]);
    localParts.push(local);
    centralParts.push(Buffer.concat([
      u32(0x02014b50), u16(20), u16(20), u16(0), u16(0), u16(0), u16(0), u32(crc),
      u32(data.length), u32(data.length), u16(name.length), u16(0), u16(0),
      u16(0), u16(0), u32(0), u32(offset), name,
    ]));
    offset += local.length;
  }
  const central = Buffer.concat(centralParts);
  return Buffer.concat([
    ...localParts,
    central,
    u32(0x06054b50), u16(0), u16(0), u16(entries.length), u16(entries.length),
    u32(central.length), u32(offset), u16(0),
  ]);
}

function u16(value) {
  const buffer = Buffer.alloc(2);
  buffer.writeUInt16LE(value);
  return buffer;
}

function u32(value) {
  const buffer = Buffer.alloc(4);
  buffer.writeUInt32LE(value >>> 0);
  return buffer;
}

function crc32(data) {
  CRC_TABLE ??= Array.from({ length: 256 }, (_, index) => {
    let value = index;
    for (let bit = 0; bit < 8; bit += 1) value = value & 1 ? 0xedb88320 ^ (value >>> 1) : value >>> 1;
    return value >>> 0;
  });
  let crc = 0xffffffff;
  for (const byte of data) crc = CRC_TABLE[(crc ^ byte) & 255] ^ (crc >>> 8);
  return (crc ^ 0xffffffff) >>> 0;
}
