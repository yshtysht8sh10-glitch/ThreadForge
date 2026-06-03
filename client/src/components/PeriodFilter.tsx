import { Post } from '../types';

export type Period = {
  year: string;
  month: string;
  count: number;
};

type PeriodFilterProps = {
  periods: Period[];
  selectedYears: string[];
  selectedMonths: string[];
  total: number;
  onChange: (years: string[], months: string[]) => void;
  expanded?: boolean;
  onExpandedChange?: (expanded: boolean) => void;
  className?: string;
};

const PeriodFilter = ({ periods, selectedYears, selectedMonths, total, onChange, expanded = true, onExpandedChange, className }: PeriodFilterProps) => {
  const years = groupPeriods(periods);
  const hasPeriodFilter = selectedYears.length > 0 || selectedMonths.length > 0;

  const updatePeriods = (yearsNext: string[], monthsNext: string[]) => {
    onChange(
      [...yearsNext].sort((a, b) => b.localeCompare(a)),
      [...monthsNext].sort((a, b) => b.localeCompare(a)),
    );
  };

  const toggleYear = (year: string) => {
    const yearSet = new Set(selectedYears);
    const monthSet = new Set(selectedMonths.filter((month) => !month.startsWith(`${year}-`)));
    if (yearSet.has(year)) {
      yearSet.delete(year);
    } else {
      yearSet.add(year);
    }
    updatePeriods([...yearSet], [...monthSet]);
  };

  const toggleMonth = (month: string) => {
    const year = month.slice(0, 4);
    const yearSet = new Set(selectedYears.filter((selectedYear) => selectedYear !== year));
    const monthSet = new Set(selectedMonths);
    if (monthSet.has(month)) {
      monthSet.delete(month);
    } else {
      monthSet.add(month);
    }
    updatePeriods([...yearSet], [...monthSet]);
  };

  return (
    <section className={['period-filter-card', className ?? ''].filter(Boolean).join(' ')} aria-label="表示期間">
      <div className="period-filter-header">
        <div>
          <h2>表示期間</h2>
          <p>総投稿数: {total.toLocaleString('ja-JP')}</p>
        </div>
        {onExpandedChange && (
          <button
            type="button"
            className="period-display-toggle"
            onClick={() => onExpandedChange(!expanded)}
            title={expanded ? '表示期間を隠す' : '表示期間を表示'}
            aria-label={expanded ? '表示期間を隠す' : '表示期間を表示'}
            aria-expanded={expanded}
          >
            <span className="period-toggle-triangle" aria-hidden="true">
              {expanded ? '▲' : '▼'}
            </span>
          </button>
        )}
      </div>
      <div className="period-filter-grid">
        {years.map((year) => {
          const fullYearSelected = selectedYears.includes(year.year);
          const hasSelectedMonth = selectedMonths.some((month) => month.startsWith(`${year.year}-`));
          const yearSelected = fullYearSelected || hasSelectedMonth;
          return (
            <section className={yearSelected ? 'period-year-block selected' : 'period-year-block'} key={year.year}>
              <button
                type="button"
                className={fullYearSelected ? 'period-chip selected' : 'period-chip'}
                onClick={() => toggleYear(year.year)}
                title={`${year.year}年: ${year.count}件`}
              >
                <span>{year.year}年</span>
                <b className="period-count-text">{year.count}件</b>
              </button>
              <div className="period-month-list" role="group" aria-label={`${year.year}年の月`}>
                {year.months.map((month) => {
                  const key = `${year.year}-${month.month}`;
                  const monthSelected = fullYearSelected || selectedMonths.includes(key);
                  const monthNumber = Number(month.month);
                  return (
                    <button
                      type="button"
                      className={monthSelected ? 'period-chip month selected' : 'period-chip month'}
                      key={key}
                      style={{ gridColumn: String(13 - monthNumber) }}
                      onClick={() => toggleMonth(key)}
                      title={`${year.year}年${monthNumber}月: ${month.count}件`}
                    >
                      <span>{monthNumber}月</span>
                      <b className="period-count-text">{month.count}件</b>
                    </button>
                  );
                })}
              </div>
            </section>
          );
        })}
      </div>
      {hasPeriodFilter && (
        <button type="button" className="secondary period-clear-button" onClick={() => updatePeriods([], [])}>
          期間指定を解除
        </button>
      )}
    </section>
  );
};

export default PeriodFilter;

export function queryList(value: string | null): string[] {
  return (value ?? '').split(',').map((item) => item.trim()).filter(Boolean);
}

export function periodsFromPosts(posts: Post[], includeReplies = false): Period[] {
  const periodMap = new Map<string, number>();
  posts.forEach((post) => {
    if (!post.created_at || (!includeReplies && post.parent_id !== 0)) return;
    const key = post.created_at.slice(0, 7);
    if (!/^\d{4}-\d{2}$/.test(key)) return;
    periodMap.set(key, (periodMap.get(key) ?? 0) + 1);
  });
  return [...periodMap.entries()]
    .map(([key, count]) => ({ year: key.slice(0, 4), month: key.slice(5, 7), count }))
    .sort((a, b) => `${b.year}-${b.month}`.localeCompare(`${a.year}-${a.month}`));
}

export function filterPostsByPeriods(posts: Post[], selectedYears: string[], selectedMonths: string[]): Post[] {
  if (selectedYears.length === 0 && selectedMonths.length === 0) {
    return posts;
  }
  const yearSet = new Set(selectedYears);
  const monthSet = new Set(selectedMonths);
  return posts.filter((post) => {
    if (!post.created_at) return false;
    const year = post.created_at.slice(0, 4);
    const month = post.created_at.slice(0, 7);
    return monthSet.has(month) || (yearSet.has(year) && !selectedMonths.some((selectedMonth) => selectedMonth.startsWith(`${year}-`)));
  }).sort((a, b) => {
    const created = b.created_at.localeCompare(a.created_at);
    return created !== 0 ? created : b.id - a.id;
  });
}

function groupPeriods(periods: Period[]): Array<{ year: string; count: number; months: Array<{ month: string; count: number }> }> {
  const yearMap = new Map<string, { year: string; count: number; months: Array<{ month: string; count: number }> }>();
  periods.forEach((period) => {
    const year = yearMap.get(period.year) ?? { year: period.year, count: 0, months: [] };
    year.count += period.count;
    year.months.push({ month: period.month, count: period.count });
    yearMap.set(period.year, year);
  });
  return [...yearMap.values()]
    .sort((a, b) => b.year.localeCompare(a.year))
    .map((year) => ({
      ...year,
      months: year.months.sort((a, b) => b.month.localeCompare(a.month)),
    }));
}
