document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach((alert) => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
        }, 5000);
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const ensureCsrf = (form) => {
        if (!csrfToken || form.method.toLowerCase() !== 'post') {
            return;
        }
        if (!form.querySelector('input[name="_csrf"]')) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = '_csrf';
            input.value = csrfToken;
            form.appendChild(input);
        }
    };

    document.querySelectorAll('form').forEach((form) => ensureCsrf(form));
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (form && form.tagName === 'FORM') {
            ensureCsrf(form);
        }
    });

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js').catch(() => {});
    }
});
