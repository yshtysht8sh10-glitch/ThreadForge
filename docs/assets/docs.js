(() => {
  const storageKey = 'threadforge-doc-language';
  const preferred = window.localStorage.getItem(storageKey)
    || (navigator.language.toLowerCase().startsWith('ja') ? 'ja' : 'en');

  function setLanguage(language) {
    const next = language === 'en' ? 'en' : 'ja';
    document.documentElement.dataset.lang = next;
    document.documentElement.lang = next;
    window.localStorage.setItem(storageKey, next);
    document.querySelectorAll('[data-language-button]').forEach((button) => {
      button.setAttribute('aria-pressed', String(button.dataset.languageButton === next));
    });
  }

  document.querySelectorAll('[data-language-button]').forEach((button) => {
    button.addEventListener('click', () => setLanguage(button.dataset.languageButton));
  });

  setLanguage(preferred);
})();
