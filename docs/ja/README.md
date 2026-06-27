# ThreadForge 繝峨く繝･繝｡繝ｳ繝・
莠ｺ髢薙′隱ｭ繧縺溘ａ縺ｮ繝峨く繝･繝｡繝ｳ繝医・縲∵律譛ｬ隱・闍ｱ隱槭ｒ蛻・ｊ譖ｿ縺医ｉ繧後ｋHTML縺ｨ縺励※逕滓・縺励∪縺吶・arkdown 縺ｯ菫晏ｮ育畑縺ｮ蜴溽ｨｿ縺ｧ縺吶・
HTML繧貞・逕滓・縺吶ｋ蝣ｴ蜷・

```powershell
npm run build:docs
```

## 蜈ｱ騾壹ラ繧ｭ繝･繝｡繝ｳ繝・
- `SPEC.md`: 莉墓ｧ倡ｴ｢蠑輔→蜈ｱ騾壻ｻ墓ｧ・- `DB.md`: DB/runtime 繝・・繧ｿ縺ｮ邏｢蠑輔→驕狗畑繝ｫ繝ｼ繝ｫ
- `API.md`: PHP API 縺ｮ繧｢繧ｯ繧ｷ繝ｧ繝ｳ縺ｨ繝ｪ繧ｯ繧ｨ繧ｹ繝・繝ｬ繧ｹ繝昴Φ繧ｹ
- `ARCHITECTURE.md`: 繝輔Ο繝ｳ繝医お繝ｳ繝・繝舌ャ繧ｯ繧ｨ繝ｳ繝画ｧ区・
- `FRONTEND_ARCHITECTURE.md`: 隍・焚繝輔Ο繝ｳ繝医お繝ｳ繝画ｧ区・
- `MIGRATION.md`: 繝ｭ繝ｼ繧ｫ繝ｫ繧｢繝ｼ繧ｫ繧､繝悶Ο繧ｰ蜿悶ｊ霎ｼ縺ｿ繝｡繝｢
- `TESTING.md`: 繝・せ繝医さ繝槭Φ繝峨→遒ｺ隱咲ｯ・峇
- `../../CHANGELOG.md`: 闍ｱ隱槫､画峩螻･豁ｴ
- `../../CHANGELOG.ja.md`: 譌･譛ｬ隱槫､画峩螻･豁ｴ

## 繝輔Ο繝ｳ繝医お繝ｳ繝牙挨繝峨く繝･繝｡繝ｳ繝・
- `frontends/image-board/README.md`
- `frontends/image-board/DB.md`
- `frontends/image-board/SPEC.md`
- `frontends/file-uploader/README.md`
- `frontends/file-uploader/DB.md`
- `frontends/file-uploader/SPEC.md`
- `frontends/document-holder/README.md`
- `frontends/document-holder/DB.md`
- `frontends/document-holder/SPEC.md`
- `frontends/proxy-release/README.md`
- `frontends/proxy-release/DB.md`
- `frontends/proxy-release/SPEC.md`
- `frontends/materials-library/README.md`
- `frontends/materials-library/DB.md`
- `frontends/materials-library/SPEC.md`

## 繝ｪ繝ｪ繝ｼ繧ｹZip

```powershell
npm run release:image-board
npm run release:file-uploader
npm run release:proxy-release
npm run release:materials-library
```

繝ｪ繝ｪ繝ｼ繧ｹZip縺ｯ `release/threadforge-<frontend-id>-<version>.zip` 縺ｫ菴懊ｉ繧後∪縺吶・
- image-board: `11_image_board/`
- file-uploader: `12_file_uploader/`
- materials-library: `15_materials_library/`
- proxy-release: `16_proxy_release/`
- 縺昴・莉悶・繝輔Ο繝ｳ繝・ frontend id 縺ｮ `-` 繧・`_` 縺ｫ鄂ｮ縺肴鋤縺医◆繝・ぅ繝ｬ繧ｯ繝医Μ

髢狗匱迺ｰ蠅・〒縺ｯ `server/runtime/<frontend-id>/...`縲・・鄂ｮ蠕後・蜷・・髢狗畑繝・ぅ繝ｬ繧ｯ繝医Μ蜀・・ `database.sqlite` 縺ｨ `storage/data/` 繧剃ｽｿ縺・∪縺吶ゅΜ繝ｪ繝ｼ繧ｹZip縺ｫ縺ｯ螳滄°逕ｨ縺ｮDB縺ｨ繧｢繝・・繝ｭ繝ｼ繝画ｸ医∩繝輔ぃ繧､繝ｫ縺ｯ蜷ｫ繧√∪縺帙ｓ縲・
