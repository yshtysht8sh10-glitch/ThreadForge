const urlPattern = /(https?:\/\/[^\s<]+|www\.[^\s<]+)/g;

type LinkedTextProps = {
  text: string;
};

const LinkedText = ({ text }: LinkedTextProps) => {
  const parts = text.split(urlPattern);

  return (
    <>
      {parts.map((part, index) => {
        if (part.match(urlPattern)) {
          const href = part.startsWith('http') ? part : `https://${part}`;
          return (
            <a key={`${part}-${index}`} href={href} target="_blank" rel="noreferrer">
              {part}
            </a>
          );
        }

        return part.split(/\r?\n/).map((line, lineIndex, lines) => (
          <span key={`${index}-${lineIndex}`}>
            {line}
            {lineIndex < lines.length - 1 && <br />}
          </span>
        ));
      })}
    </>
  );
};

export default LinkedText;
