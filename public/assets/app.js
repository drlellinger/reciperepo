const toast = document.querySelector('[data-toast]');
let toastTimer;

function showToast(message) {
    if (!toast) return;

    toast.textContent = message;
    toast.classList.add('toast-visible');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toast.classList.remove('toast-visible'), 5000);
}

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('button[data-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(button.dataset.copy);
            showToast('Bild-URL kopiert.');
        } catch {
            const input = button.parentElement.querySelector('input');
            input?.focus();
            input?.select();
            showToast('URL markiert. Zum Kopieren Ctrl+C oder Cmd+C drücken.');
        }
    });
});

document.querySelectorAll('button[data-share]').forEach((button) => {
    button.addEventListener('click', async () => {
        const shareData = { title: button.dataset.shareTitle, url: window.location.href };

        if (navigator.share) {
            try {
                await navigator.share(shareData);
                showToast('Rezept wurde geteilt.');
                return;
            } catch (error) {
                if (error.name === 'AbortError') return;
            }
        }

        try {
            await navigator.clipboard.writeText(shareData.url);
            showToast('Rezeptlink kopiert.');
        } catch {
            const fallback = document.querySelector('[data-share-fallback]');
            const input = fallback?.querySelector('[data-share-url]');
            if (fallback && input) {
                input.value = shareData.url;
                fallback.hidden = false;
                input.focus();
                input.select();
                showToast('Link markiert. Zum Kopieren Ctrl+C oder Cmd+C drücken.');
            }
        }
    });
});