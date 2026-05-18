import { ReactNode, useEffect, useState } from 'react';
import { api, DEFAULT_PUBLIC_SETTINGS, PublicSettings } from '../api';

const ManualPage = () => {
  const [settings, setSettings] = useState<PublicSettings>(DEFAULT_PUBLIC_SETTINGS);

  useEffect(() => {
    let ignore = false;
    api.publicSettings()
      .then((response) => {
        if (!ignore && response.success) {
          setSettings(response.settings);
        }
      })
      .catch(() => {
        setSettings(DEFAULT_PUBLIC_SETTINGS);
      });

    return () => {
      ignore = true;
    };
  }, []);

  return (
    <article className="manual-page">
      <h1>{settings.config.manualTitle || DEFAULT_PUBLIC_SETTINGS.config.manualTitle}</h1>
      <div className="manual-body">
        {renderManualBody(manualBodyText(settings))}
      </div>
    </article>
  );
};

function manualBodyText(settings: PublicSettings): string {
  const label = settings.config.gdgdLabel || DEFAULT_PUBLIC_SETTINGS.config.gdgdLabel;
  return (settings.config.manualBody || DEFAULT_PUBLIC_SETTINGS.config.manualBody).replace(/\{\{gdgdLabel\}\}/g, label);
}

function renderManualBody(text: string): ReactNode[] {
  const nodes: ReactNode[] = [];
  let listItems: string[] = [];

  const flushList = (key: string) => {
    if (listItems.length === 0) return;
    nodes.push(
      <ul key={key}>
        {listItems.map((item, index) => <li key={`${key}-${index}`}>{item}</li>)}
      </ul>,
    );
    listItems = [];
  };

  text.split(/\r?\n/).forEach((rawLine, index) => {
    const line = rawLine.trim();
    if (line === '') {
      flushList(`list-${index}`);
      nodes.push(<br key={`break-${index}`} />);
      return;
    }
    if (line.startsWith('- ')) {
      listItems.push(line.slice(2));
      return;
    }
    flushList(`list-${index}`);
    if (line.startsWith('### ')) {
      nodes.push(<h4 key={`h4-${index}`}>{line.slice(4)}</h4>);
    } else if (line.startsWith('## ')) {
      nodes.push(<h3 key={`h3-${index}`}>{line.slice(3)}</h3>);
    } else if (line.startsWith('# ')) {
      nodes.push(<h2 key={`h2-${index}`}>{line.slice(2)}</h2>);
    } else {
      nodes.push(<p key={`line-${index}`}>{rawLine}</p>);
    }
  });
  flushList('list-final');

  return nodes;
}

export default ManualPage;
