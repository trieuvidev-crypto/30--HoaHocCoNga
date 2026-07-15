/**
 * Theme engine: light/dark/system, persisted, no page reload.
 * Per DESIGN_SYSTEM.md §Dark Mode: "Theme switching should not reload the page."
 */

const STORAGE_KEY = 'hhcn_theme';

function applyTheme(theme) {
    const resolved = theme === 'system'
        ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
        : theme;

    document.documentElement.setAttribute('data-theme', resolved);
}

function initTheme() {
    const saved = localStorage.getItem(STORAGE_KEY) || 'system';
    applyTheme(saved);

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if ((localStorage.getItem(STORAGE_KEY) || 'system') === 'system') {
            applyTheme('system');
        }
    });
}

export function setTheme(theme) {
    localStorage.setItem(STORAGE_KEY, theme);
    applyTheme(theme);
}

initTheme();
