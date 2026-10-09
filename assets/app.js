const themeToggle = document.querySelector('[data-theme-toggle]');
const themeStorageKey = 'simple-blog-theme';

function applyTheme(theme) {
    if (theme === 'dark') {
        document.documentElement.dataset.theme = 'dark';
    } else {
        delete document.documentElement.dataset.theme;
    }

    if (themeToggle) {
        themeToggle.textContent = theme === 'dark' ? 'Light mode' : 'Dark mode';
        themeToggle.setAttribute('aria-pressed', String(theme === 'dark'));
    }
}

let savedTheme = 'light';
try {
    savedTheme = localStorage.getItem(themeStorageKey) === 'dark' ? 'dark' : 'light';
} catch (error) {
    console.warn('Theme preference could not be read.', error);
}
applyTheme(savedTheme);

themeToggle?.addEventListener('click', () => {
    const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
    applyTheme(theme);
    try {
        localStorage.setItem(themeStorageKey, theme);
    } catch (error) {
        console.warn('Theme preference could not be saved.', error);
    }
});

document.querySelectorAll('[data-character-count]').forEach((textarea) => {
    const counter = textarea.parentElement.querySelector('[data-character-count-label]');
    const updateCount = () => {
        counter.textContent = `${textarea.value.length} characters`;
    };

    textarea.addEventListener('input', updateCount);
    updateCount();
});

document.querySelectorAll('[data-like-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        const label = button.querySelector('span:nth-child(2)');
        const count = form.querySelector('[data-like-count]');
        const errorMessage = form.querySelector('[data-like-error]');
        button.disabled = true;
        errorMessage.textContent = '';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.error || 'Unable to update your like.');
            }

            button.setAttribute('aria-pressed', String(result.liked));
            button.classList.toggle('is-liked', result.liked);
            label.textContent = result.liked ? 'Liked' : 'Like';
            count.textContent = result.like_count;
        } catch (error) {
            errorMessage.textContent = error instanceof Error
                ? error.message
                : 'Unable to update your like. Please try again.';
        } finally {
            button.disabled = false;
        }
    });
});
