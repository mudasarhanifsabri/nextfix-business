import './bootstrap';
import '../css/pwa.css';
import '../css/worker-media.css';
import '../css/brand.css';

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
}

// Browsers and installed PWAs can restore a page from their back-forward cache
// with an old CSRF token after logout or account switching. Force a fresh page.
window.addEventListener('pageshow', (event) => {
    if (event.persisted) window.location.reload();
});

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
document.querySelectorAll('[data-inspection-photos],[data-photo-input]').forEach((inspectionPhotos) => {
    const preview = inspectionPhotos.dataset.previewTarget
        ? document.getElementById(inspectionPhotos.dataset.previewTarget)
        : document.querySelector('[data-inspection-preview]');
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
});

if (workerChecks) {
    const updateWorkerProgress = () => {
        const boxes = [...workerChecks.querySelectorAll('input[type="checkbox"]')];
        const complete = boxes.filter((box) => box.checked).length;
        const percent = Math.round((complete / boxes.length) * 100);
        const label = document.querySelector('[data-job-percent]');
        const bar = document.querySelector('[data-job-bar]');
        const done = document.querySelector('[data-job-done]');
        const remaining = document.querySelector('[data-job-remaining]');
        if (label) label.textContent = percent;
        if (bar) bar.style.width = `${percent}%`;
        if (done) done.textContent = complete;
        if (remaining) remaining.textContent = `${boxes.length - complete} remaining`;
        boxes.forEach((box) => {
            const state = box.closest('label')?.querySelector('em');
            if (state) state.textContent = box.checked ? 'Done' : 'Tap';
        });

        const swipeForm = document.querySelector('[data-swipe-form]');
        if (swipeForm) {
            const hasAfterPhoto = swipeForm.dataset.afterPhoto === '1';
            const ready = complete === boxes.length && hasAfterPhoto;
            const slider = swipeForm.querySelector('input[type="range"]');
            const help = swipeForm.querySelector('[data-swipe-help]');
            const title = swipeForm.querySelector('.swipe-ready-title strong');
            const icon = swipeForm.querySelector('.swipe-ready-title>i');
            slider.disabled = !ready;
            swipeForm.classList.toggle('is-ready', ready);
            swipeForm.classList.toggle('is-locked', !ready);
            if (title) title.textContent = ready ? 'Ready to close' : 'Finish requirements first';
            if (help) help.textContent = ready
                ? 'Swipe the arrow to confirm handover'
                : `${boxes.length - complete} tasks${hasAfterPhoto ? '' : ' and after photos'} remaining`;
            if (icon) icon.className = `bi ${ready ? 'bi-check2-circle' : 'bi-lock'}`;
        }
    };
    workerChecks.addEventListener('change', updateWorkerProgress);
    const autoSaveForm = workerChecks.closest('form[data-auto-save="1"]');
    if (autoSaveForm) {
        let autoSaveTimer;
        const state = autoSaveForm.querySelector('[data-auto-save-state]');
        workerChecks.addEventListener('change', () => {
            window.clearTimeout(autoSaveTimer);
            if (state) state.textContent = 'Saving…';
            autoSaveTimer = window.setTimeout(async () => {
                try {
                    const response = await fetch(autoSaveForm.action, {
                        method: 'POST', body: new FormData(autoSaveForm), credentials: 'same-origin',
                        headers: {'X-Requested-With': 'XMLHttpRequest'},
                    });
                    if (!response.ok) throw new Error('save failed');
                    if (state) state.textContent = 'Saved ✓';
                } catch (_) {
                    if (state) state.textContent = 'Not saved — tap Save now';
                }
            }, 450);
        });
    }
    updateWorkerProgress();
}

const cleaningChecklist = document.querySelector('.cleaning-checklist');
if (cleaningChecklist) {
    const beforeStep = document.querySelector('.cleaning-photo-step');
    const progressCard = document.querySelector('.worker-progress-card');
    const afterStep = document.querySelector('.work-evidence');
    const holdStep = document.querySelector('.worker-hold');
    const finishStep = document.querySelector('[data-swipe-form]');
    const oldJourney = document.querySelector('.cleaning-journey');
    const allChecks = [...cleaningChecklist.querySelectorAll('input[type="checkbox"]')];
    const hasBefore = beforeStep?.querySelector('header>b')?.classList.contains('done') || false;
    const hasAfter = Number(afterStep?.querySelector('.worker-section-head>span')?.textContent.match(/\d+/)?.[0] || 0) > 0;
    const allAreasDone = allChecks.length > 0 && allChecks.every((box) => box.checked);
    const panels = [[beforeStep], [progressCard, cleaningChecklist], [afterStep], [holdStep, finishStep]];
    const labels = [['bi-camera','Before'],['bi-grid','Clean'],['bi-camera-fill','After'],['bi-check2-circle','Finish']];
    let activeStep = !hasBefore ? 0 : !allAreasDone ? 1 : !hasAfter ? 2 : 3;
    const nav = document.createElement('nav'); nav.className = 'cleaning-wizard-nav'; nav.setAttribute('aria-label','Cleaning steps');
    labels.forEach(([icon,label], index) => {
        const button = document.createElement('button'); button.type = 'button';
        button.innerHTML = `<i class="bi ${icon}"></i><span>${label}</span>`;
        button.addEventListener('click', () => { if (index <= activeStep || (index === 1 && hasBefore)) showStep(index); });
        nav.appendChild(button);
    });
    oldJourney?.classList.add('cleaning-wizard-hidden');
    oldJourney?.after(nav);
    const requirements = () => [hasBefore, allChecks.every((box) => box.checked), hasAfter, true];
    const showStep = (index) => {
        activeStep = index;
        panels.forEach((group, groupIndex) => group.forEach((node) => node?.classList.toggle('cleaning-wizard-hidden', groupIndex !== index)));
        [...nav.children].forEach((button, buttonIndex) => {
            button.classList.toggle('active', buttonIndex === index);
            button.classList.toggle('done', requirements()[buttonIndex] && buttonIndex < index);
        });
        document.querySelector('.cleaning-wizard-actions')?.remove();
        const visible = panels[index].filter(Boolean).at(-1);
        if (!visible || index === 3) return;
        const actions = document.createElement('div'); actions.className = 'cleaning-wizard-actions';
        const back = document.createElement('button'); back.type = 'button'; back.textContent = index ? '← Back' : 'My jobs';
        back.addEventListener('click', () => index ? showStep(index - 1) : (window.location.href = document.querySelector('.worker-back')?.href || '/work/jobs'));
        const next = document.createElement('button'); next.type = 'button'; next.className = 'primary-next';
        next.textContent = index === 2 ? 'Continue to finish →' : 'Next →'; next.disabled = !requirements()[index];
        next.addEventListener('click', () => showStep(index + 1)); actions.append(back, next); visible.after(actions);
    };
    showStep(activeStep);
    cleaningChecklist.addEventListener('change', () => window.setTimeout(() => showStep(1), 20));
    const photoPlan = ['Entrance', ...allChecks
        .filter((box) => /^(studio_room|bedroom_|maid_room|bathrooms|kitchen|living)/.test(box.value))
        .map((box) => box.closest('label')?.querySelector('strong')?.textContent.trim())
        .filter(Boolean)];

    const installCamera = (form, title) => {
        if (!form) return;
        const input = form.querySelector('input[type="file"]');
        if (!input) return;
        input.removeAttribute('capture'); input.multiple = true; form.classList.add('cleaning-camera-active');
        const launch = document.createElement('button'); launch.type = 'button'; launch.className = 'camera-session-button';
        launch.innerHTML = `<i class="bi bi-camera-fill"></i><span>${title}</span>`; form.prepend(launch);
        input.addEventListener('change', () => { if (input.files.length) form.requestSubmit(); });
        launch.addEventListener('click', async () => {
            if (!navigator.mediaDevices?.getUserMedia) { input.click(); return; }
            const overlay = document.createElement('section'); overlay.className = 'nf-camera';
            overlay.innerHTML = `<header><div><strong>${title}</strong><small class="nf-camera-prompt"></small></div><button type="button" aria-label="Close">×</button></header><div class="nf-camera-stage"><video autoplay playsinline muted></video><div class="nf-camera-guide"></div></div><footer><div class="nf-camera-shots"></div><div class="nf-camera-controls"><button type="button" class="nf-camera-fallback"><i class="bi bi-images"></i><span>Gallery</span></button><button type="button" class="nf-camera-capture" aria-label="Take photo"></button><button type="button" class="nf-camera-done" disabled><span>Done</span><i class="bi bi-check2"></i></button></div></footer>`;
            document.body.appendChild(overlay); document.body.style.overflow = 'hidden';
            const video = overlay.querySelector('video'); const shots = overlay.querySelector('.nf-camera-shots');
            const done = overlay.querySelector('.nf-camera-done'); const prompt = overlay.querySelector('.nf-camera-prompt'); const files = []; let stream;
            const close = () => { stream?.getTracks().forEach((track) => track.stop()); overlay.remove(); document.body.style.overflow = ''; };
            const render = () => { shots.replaceChildren(); files.forEach((file,index) => { const card=document.createElement('span'); card.className='nf-camera-shot'; const img=document.createElement('img'); img.src=URL.createObjectURL(file); const remove=document.createElement('button'); remove.type='button'; remove.textContent='×'; remove.addEventListener('click',()=>{files.splice(index,1);render();}); card.append(img,remove); shots.appendChild(card); }); done.disabled=!files.length; const next=photoPlan[files.length]; prompt.textContent=next ? `${files.length + 1} of ${photoPlan.length} · ${next}` : `Suggested areas covered · ${files.length} photos`; shots.scrollLeft=shots.scrollWidth; };
            overlay.querySelector('header button').addEventListener('click', close);
            overlay.querySelector('.nf-camera-fallback').addEventListener('click', () => { close(); input.click(); });
            try { stream = await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}},audio:false}); video.srcObject = stream; render(); }
            catch (_) { close(); input.click(); return; }
            overlay.querySelector('.nf-camera-capture').addEventListener('click', () => { const canvas=document.createElement('canvas'); canvas.width=video.videoWidth||1280; canvas.height=video.videoHeight||960; canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height); canvas.toBlob((blob)=>{ if(!blob)return; files.push(new File([blob],`nextfix-${Date.now()}-${files.length+1}.jpg`,{type:'image/jpeg'})); render(); },'image/jpeg',.86); });
            done.addEventListener('click', async () => { done.disabled=true; done.textContent='Uploading…'; const payload=new FormData(form); payload.delete(input.name); files.forEach((file)=>payload.append(input.name,file)); try { const response=await fetch(form.action,{method:'POST',body:payload,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}}); if(!response.ok)throw new Error(); close(); window.location.reload(); } catch(_){ done.disabled=false; done.textContent='Try again'; } });
        });
    };
    installCamera(beforeStep?.querySelector('form'), 'Take before photos');
    installCamera(afterStep?.querySelector('form'), 'Take after photos');
}

document.querySelectorAll('[data-swipe-form]').forEach((form) => {
    const slider = form.querySelector('input[type="range"]');
    slider.addEventListener('input', () => form.style.setProperty('--swipe', `${slider.value}%`));
    slider.addEventListener('change', () => {
        if (Number(slider.value) < 95) {
            slider.value = 0;
            form.style.setProperty('--swipe', '0%');
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
    const countBadges = [...document.querySelectorAll('[data-notification-count]')];
    const pollNotifications = async () => {
        try {
            const response = await fetch(notificationFeed.dataset.notificationFeed, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
            if (!response.ok) return;
            const data = await response.json();
            countBadges.forEach((badge) => {
                badge.textContent = data.unread_count > 9 ? '9+' : (data.unread_count || '');
                badge.hidden = !data.unread_count;
            });
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
                link.href = data.latest.url; link.className = 'd-block mt-2'; link.textContent = 'Open';
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
