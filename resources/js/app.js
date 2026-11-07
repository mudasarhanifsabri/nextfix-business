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
