import './bootstrap';

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
}

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
