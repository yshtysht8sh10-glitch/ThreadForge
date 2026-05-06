import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import App, { homeHref } from './App';

describe('App navigation', () => {
  it('links the right square to admin mode', () => {
    render(
      <BrowserRouter>
        <App />
      </BrowserRouter>,
    );

    expect(screen.getByRole('link', { name: '管理者モード' })).toHaveAttribute('href', '/admin');
  });

  it('links the post list item to the board top', () => {
    render(
      <BrowserRouter>
        <App />
      </BrowserRouter>,
    );

    expect(screen.getByRole('link', { name: '一覧' })).toHaveAttribute('href', '/');
  });

  it('links manual to the SPA manual page', () => {
    render(
      <BrowserRouter>
        <App />
      </BrowserRouter>,
    );

    expect(screen.getByRole('link', { name: '取説' })).toHaveAttribute('href', '/manual');
  });

  it('normalizes configured HOME URLs', () => {
    expect(homeHref('https://example.com/home')).toBe('https://example.com/home');
    expect(homeHref('example.com/home')).toBe('https://example.com/home');
    expect(homeHref('/local-home')).toBe('/local-home');
  });

  it('shows the application version', () => {
    render(
      <BrowserRouter>
        <App />
      </BrowserRouter>,
    );

    expect(screen.getByText(/ThreadForge v0\.1\.0/)).toBeInTheDocument();
  });
});
