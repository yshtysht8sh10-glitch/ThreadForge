import { useEffect, useState } from 'react';
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
        {(settings.config.manualBody || DEFAULT_PUBLIC_SETTINGS.config.manualBody).split(/\r?\n/).map((line, index) => (
          line.trim() === ''
            ? <br key={`break-${index}`} />
            : <p key={`line-${index}`}>{line}</p>
        ))}
      </div>
    </article>
  );
};

export default ManualPage;
