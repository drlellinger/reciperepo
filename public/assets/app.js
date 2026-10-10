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