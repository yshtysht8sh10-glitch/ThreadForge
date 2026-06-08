import type { ReactNode } from 'react';

export type LegacyLayoutProps = {
  title: string;
  subtitle?: string;
  className?: string;
  children: ReactNode;
};

export function LegacyLayout({ title, subtitle, className = '', children }: LegacyLayoutProps) {
  return (
    <div className={`legacy-layout ${className}`.trim()}>
      <header className="legacy-layout__header">
        <div>
          <h1>{title}</h1>
          {subtitle ? <p>{subtitle}</p> : null}
        </div>
        <nav aria-label="site navigation">
          <a href="#top">Top</a>
          <a href="#post">Post</a>
          <a href="#list">List</a>
        </nav>
      </header>
      <main>{children}</main>
    </div>
  );
}

export type RuleMark = '○' | '×' | '-' | '△';

export type RuleTableProps = {
  rules: Array<{ label: string; value: RuleMark }>;
};

export function RuleTable({ rules }: RuleTableProps) {
  return (
    <table className="rule-table">
      <tbody>
        {rules.map((rule) => (
          <tr key={rule.label}>
            <th>{rule.label}</th>
            <td>{rule.value}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
}

export type CatalogCardProps = {
  title: string;
  author: string;
  image?: string;
  downloadLabel?: string;
  description: ReactNode;
  rules?: RuleTableProps['rules'];
};

export function CatalogCard({ title, author, image, downloadLabel = 'Download', description, rules }: CatalogCardProps) {
  return (
    <article className="catalog-card">
      <div className="catalog-card__image">
        {image ? <img src={image} alt="" loading="lazy" /> : <span className="catalog-card__no-image">No image</span>}
      </div>
      <a className="catalog-card__download" href="#download">{downloadLabel}</a>
      {rules ? <RuleTable rules={rules} /> : null}
      <div className="catalog-card__description">{description}</div>
      <div className="catalog-card__author">作者：{author}</div>
    </article>
  );
}

export type ThreadForgePostDraft = {
  name: string;
  title: string;
  body: string;
  file?: File | null;
};

export function createInitialDraft(): ThreadForgePostDraft {
  return {
    name: '',
    title: '',
    body: '',
    file: null,
  };
}
