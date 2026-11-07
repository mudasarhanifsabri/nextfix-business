import './bootstrap';
import '../css/pwa.css';
import '../css/worker-media.css';
import '../css/brand.css';

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
}

let installPrompt;
const installButtons = [...document.querySelectorAll('[data-install-app]')];
const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
const splash = document.querySelector('[data-pwa-splash]');
if (isStandalone && splash && !sessionStorage.getItem('nextfix-splash-seen')) {
    document.documentElement.classList.add('pwa-launching');
    sessionStorage.setItem('nextfix-splash-seen', '1');
    window.setTimeout(() => {
        document.documentElement.classList.add('pwa-splash-leaving');
        window.setTimeout(() => document.documentElement.classList.remove('pwa-launching', 'pwa-splash-leaving'), 450);
    }, 1450);
}
window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault(); installPrompt = event;
    installButtons.forEach((button) => { button.hidden = false; });
});
window.addEventListener('appinstalled', () => installButtons.forEach((button) => { button.hidden = true; }));
if (isIos && !isStandalone) installButtons.forEach((button) => { button.hidden = false; });
installButtons.forEach((button) => button.addEventListener('click', async () => {
    if (installPrompt) {
        installPrompt.prompt(); await installPrompt.userChoice; installPrompt = null; button.hidden = true;
    } else if (isIos) {
        window.alert('To install: tap the Share button in Safari, then choose “Add to Home Screen”.');
    }
}));

document.querySelectorAll('[data-counter]').forEach((element) => {
    const target = Number(element.textContent);
    let value = 0;
    const step = Math.max(1, Math.ceil(target / 24));
    const timer = setInterval(() => {
        value = Math.min(target, value + step);
        element.textContent = value;
        if (value >= target) clearInterval(timer);
    }, 35);
});

document.querySelectorAll('[data-hero-slider]').forEach((slider) => {
    const slides = [...slider.querySelectorAll('.hero-slide')];
    const dots = [...slider.querySelectorAll('.hero-dots button')];
    let current = 0;
    const show = (index) => {
        current = index;
        slides.forEach((slide, i) => slide.classList.toggle('active', i === index));
        dots.forEach((dot, i) => dot.classList.toggle('active', i === index));
    };
    dots.forEach((dot, index) => dot.addEventListener('click', () => show(index)));
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        setInterval(() => show((current + 1) % slides.length), 5500);
    }
});

if (document.querySelector('[data-deployment-progress]')) {
    window.setTimeout(() => window.location.reload(), 2500);
} else if (document.querySelector('[data-github-update-check]')) {
    window.setTimeout(() => window.location.reload(), 60000);
}

const workerChecks = document.querySelector('.worker-check-items');
const inspectionPhotos = document.querySelector('[data-inspection-photos]');
if (inspectionPhotos) {
    const preview = document.querySelector('[data-inspection-preview]');
    inspectionPhotos.addEventListener('change', () => {
        preview.replaceChildren();
        [...inspectionPhotos.files].slice(0, 8).forEach((file, index) => {
            const card = document.createElement('span');
            const image = document.createElement('img');
            image.src = URL.createObjectURL(file); image.alt = `Evidence ${index + 1}`;
            const label = document.createElement('b'); label.textContent = `${index + 1}`;
            card.append(image, label); preview.appendChild(card);
        });
    });
}

if (workerChecks) {
    const updateWorkerProgress = () => {
        const boxes = [...workerChecks.querySelectorAll('input[type="checkbox"]')];
        const complete = boxes.filter((box) => box.checked).length;
        const percent = Math.round((complete / boxes.length) * 100);
        const label = document.querySelector('[data-job-percent]');
        const bar = document.querySelector('[data-job-bar]');
        if (label) label.textContent = percent;
        if (bar) bar.style.width = `${percent}%`;
    };
    workerChecks.addEventListener('change', updateWorkerProgress);
    updateWorkerProgress();
}

document.querySelectorAll('[data-swipe-form]').forEach((form) => {
    const slider = form.querySelector('input[type="range"]');
    slider.addEventListener('change', () => {
        if (Number(slider.value) < 95) {
            slider.value = 0;
            return;
        }
        const checked = document.querySelectorAll('.worker-check-items input:checked');
        form.querySelectorAll('input[name="completed_items[]"]').forEach((input) => input.remove());
        checked.forEach((box) => {
            const hidden = document.createElement('input');
            hidden.type = 'hidden'; hidden.name = 'completed_items[]'; hidden.value = box.value;
            form.appendChild(hidden);
        });
        form.submit();
    });
});

document.querySelectorAll('.quote-grid').forEach((quote) => {
    const inputs = [...quote.querySelectorAll('input[name="labour_cost"],input[name="parts_cost"]')];
    const total = quote.querySelector('[data-quote-total]');
    const update = () => total.textContent = `AED ${inputs.reduce((sum, input) => sum + (Number(input.value) || 0), 0).toFixed(2)}`;
    inputs.forEach((input) => input.addEventListener('input', update));
    update();
});

const notificationFeed = document.querySelector('[data-notification-feed]');
if (notificationFeed) {
    let lastNotification = notificationFeed.dataset.lastNotification || '';
    const countBadge = notificationFeed.querySelector('[data-notification-count]');
    const pollNotifications = async () => {
        try {
            const response = await fetch(notificationFeed.dataset.notificationFeed, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
            if (!response.ok) return;
            const data = await response.json();
            countBadge.textContent = data.unread_count || '';
            countBadge.hidden = !data.unread_count;
            if (data.latest?.id && lastNotification && data.latest.id !== lastNotification) {
                const toast = document.createElement('div');
                toast.className = 'toast show border-0 shadow';
                toast.setAttribute('role', 'alert');
                const header = document.createElement('div');
                header.className = 'toast-header';
                header.innerHTML = `<span class="note-dot ${data.latest.level || 'info'}"></span><strong class="me-auto"></strong><button type="button" class="btn-close"></button>`;
                header.querySelector('strong').textContent = data.latest.title;
                header.querySelector('button').addEventListener('click', () => toast.remove());
                const body = document.createElement('div');
                body.className = 'toast-body';
                body.textContent = data.latest.message;
                const link = document.createElement('a');
                link.href = data.latest.url; link.className = 'd-block mt-2'; link.textContent = 'View notification';
                body.appendChild(link); toast.append(header, body);
                document.querySelector('.toast-container')?.appendChild(toast);
                window.setTimeout(() => toast.remove(), 12000);
            }
            if (data.latest?.id) lastNotification = data.latest.id;
        } catch (_) {
            // Keep the app usable when the device is temporarily offline.
        }
    };
    window.setInterval(pollNotifications, 15000);
}
