import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import LinkedText from './LinkedText';

describe('LinkedText', () => {
  it('renders URLs as safe external links and keeps line breaks', () => {
    render(<LinkedText text={'hello\nhttps://example.com <b>raw</b>'} />);

    const link = screen.getByRole('link', { name: 'https://example.com' });
    expect(link).toHaveAttribute('href', 'https://example.com');
    expect(link).toHaveAttribute('target', '_blank');
    expect(screen.getByText('<b>raw</b>')).toBeInTheDocument();
  });
});
