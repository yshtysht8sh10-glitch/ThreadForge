import { FormEvent, useMemo, useState } from 'react';

type UploadRow = {
  id: number;
  filename: string;
  comment: string;
  size: string;
  date: string;
  originalName: string;
};

const initialRows: UploadRow[] = [
  { id: 1134, filename: 'file1134.png', comment: '', size: '415bytes', date: '25/12/09-00:25', originalName: 'hiyoko.png' },
  { id: 1133, filename: 'file1133.png', comment: 'コンパス', size: '8KB', date: '24/10/12-00:43', originalName: '1.png' },
  { id: 1132, filename: 'file1132.png', comment: '顔', size: '23KB', date: '24/09/20-23:30', originalName: 'crn+.png' },
  { id: 1131, filename: 'file1131.png', comment: '製作希望', size: '1311KB', date: '24/09/19-00:01', originalName: 'ブルードルフィン220089.png' },
  { id: 1130, filename: 'file1130.png', comment: '', size: '0bytes', date: '24/08/11-09:47', originalName: 'IMG_2839.png' },
];

function App() {
  const [rows, setRows] = useState(initialRows);
  const [comment, setComment] = useState('');
  const [deleteKey, setDeleteKey] = useState('');
  const [fileName, setFileName] = useState('');

  const totalText = useMemo(() => `${rows.length.toLocaleString()}件（1/11）`, [rows.length]);

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!fileName.trim()) {
      return;
    }
    const now = new Date();
    const nextId = Math.max(...rows.map((row) => row.id)) + 1;
    setRows([
      {
        id: nextId,
        filename: `file${nextId}.${fileName.split('.').pop() || 'dat'}`,
        comment,
        size: 'preview',
        date: `${String(now.getFullYear()).slice(2)}/${String(now.getMonth() + 1).padStart(2, '0')}/${String(now.getDate()).padStart(2, '0')}`,
        originalName: fileName,
      },
      ...rows,
    ]);
    setComment('');
    setDeleteKey('');
    setFileName('');
  }

  return (
    <div className="uploader-page" id="top">
      <section className="uploader-shell">
        <div className="uploader-title">ドット絵板新アップローダー</div>
        <div className="uploader-content">
          <form className="upload-form" id="post" onSubmit={submit}>
            <label>
              <span>ファイル</span>
              <input type="file" onChange={(event) => setFileName(event.currentTarget.files?.[0]?.name ?? '')} />
            </label>
            <label>
              <span>コメント</span>
              <input value={comment} onChange={(event) => setComment(event.currentTarget.value)} />
            </label>
            <label className="upload-form__inline">
              <span>Delkey</span>
              <input type="password" value={deleteKey} onChange={(event) => setDeleteKey(event.currentTarget.value)} />
              <button type="submit">SUBMIT</button>
            </label>
            <p className="upload-note">
              サイズ：20000KBまで<br />
              up可：gif bmp png jpg jpeg zip txt avi swf
              <span>count:80747 <a href="#top">[reload]</a></span>
            </p>
          </form>

          <p className="file-count">ファイル数：{totalText}</p>
          <table className="upload-table" id="list">
            <thead>
              <tr>
                <th>DL</th>
                <th>コメント</th>
                <th>サイズ</th>
                <th>日付</th>
                <th>元ファイル名</th>
                <th>削除</th>
              </tr>
            </thead>
            <tbody>
              {rows.map((row) => (
                <tr key={row.id}>
                  <td><a href="#download">[{row.filename}]</a></td>
                  <td>{row.comment}</td>
                  <td>{row.size}</td>
                  <td>{row.date}</td>
                  <td>{row.originalName}</td>
                  <td><a href="#delete">[DEL]</a></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </div>
  );
}

export default App;
