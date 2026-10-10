document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('button[data-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
        const status = document.querySelector('.copy-status');
        try {
            await navigator.clipboard.writeText(button.dataset.copy);
            if (status) status.textContent = 'Bild-URL kopiert.';
        } catch {
            const input = button.parentElement.querySelector('input');
            input?.select();
            if (status) status.textContent = 'URL markiert. Zum Kopieren Ctrl+C oder Cmd+C drücken.';
        }
    });
});

document.querySelectorAll('button[data-share]').forEach((button) => {
    button.addEventListener('click', async () => {
        const status = document.querySelector('.share-status');
        const shareData = { title: button.dataset.shareTitle, url: window.location.href };

        if (navigator.share) {
            try {
                await navigator.share(shareData);
                if (status) status.textContent = 'Rezept wurde geteilt.';
                return;
            } catch (error) {
                if (error.name === 'AbortError') return;
            }
        }

        try {
            await navigator.clipboard.writeText(shareData.url);
            if (status) status.textContent = 'Rezeptlink kopiert.';
        } catch {
            window.prompt('Rezeptlink kopieren:', shareData.url);
        }
    });
});