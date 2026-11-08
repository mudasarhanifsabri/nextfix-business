import './bootstrap';
import '../css/pwa.css';
import '../css/worker-media.css';
import '../css/brand.css';

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' }).then((registration) => registration.update()));
}

// Browsers and installed PWAs can restore a page from their back-forward cache
// with an old CSRF token after logout or account switching. Force a fresh page.
window.addEventListener('pageshow', (event) => {
    if (event.persisted) window.location.reload();
});

if (['/bookings', '/admin/operations'].includes(window.location.pathname)) {
    document.querySelectorAll('.table-wrap tbody tr').forEach((row) => {
        const cell = row.cells?.[0];
        const strong = cell?.querySelector('strong');
        const reference = strong?.textContent.trim();
        if (!reference?.startsWith('NFB-') || cell.querySelector('.job-report-eye')) return;
        const link = document.createElement('a');
        link.className = 'job-report-eye';
        link.href = `/jobs/reference/${encodeURIComponent(reference)}/report`;
        link.title = 'View job report';
        link.setAttribute('aria-label', `View report ${reference}`);
        link.innerHTML = '<i class="bi bi-eye"></i>';
        strong.after(link);
    });
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
            const hasStartPhoto = swipeForm.dataset.startPhoto !== '0';
            const hasAfterPhoto = swipeForm.dataset.afterPhoto === '1';
            const ready = complete === boxes.length && hasStartPhoto && hasAfterPhoto;
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
                : [boxes.length - complete ? `${boxes.length - complete} tasks` : '', !hasStartPhoto ? 'before photos' : '', !hasAfterPhoto ? 'after photos' : ''].filter(Boolean).join(' · ') + ' remaining';
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
            overlay.innerHTML = `<header><div><strong>${title}</strong><small class="nf-camera-prompt"></small></div><button type="button" aria-label="Close">×</button></header><div class="nf-camera-stage"><video autoplay playsinline muted></video><div class="nf-camera-guide"></div></div><footer><div class="nf-camera-shots"></div><div class="nf-camera-upload" hidden><div><i></i></div><span>Preparing photos…</span></div><div class="nf-camera-error" hidden></div><div class="nf-camera-controls"><button type="button" class="nf-camera-fallback"><i class="bi bi-images"></i><span>Gallery</span></button><button type="button" class="nf-camera-capture" aria-label="Take photo"></button><button type="button" class="nf-camera-done" disabled><span>Done</span><i class="bi bi-check2"></i></button></div></footer>`;
            document.body.appendChild(overlay); document.body.style.overflow = 'hidden';
            const video = overlay.querySelector('video'); const shots = overlay.querySelector('.nf-camera-shots');
            const done = overlay.querySelector('.nf-camera-done'); const capture = overlay.querySelector('.nf-camera-capture'); const prompt = overlay.querySelector('.nf-camera-prompt'); const upload = overlay.querySelector('.nf-camera-upload'); const uploadBar = upload.querySelector('i'); const uploadText = upload.querySelector('span'); const cameraError = overlay.querySelector('.nf-camera-error'); const files = []; const maxPhotos = 30; const batchSize = 5; let uploadedCount = 0; let uploading = false; let stream;
            const close = () => { stream?.getTracks().forEach((track) => track.stop()); overlay.remove(); document.body.style.overflow = ''; };
            const render = () => { shots.replaceChildren(); files.forEach((file,index) => { const card=document.createElement('span'); card.className=`nf-camera-shot${index<uploadedCount?' saved':''}`; const img=document.createElement('img'); img.src=URL.createObjectURL(file); const remove=document.createElement('button'); remove.type='button'; remove.textContent=index<uploadedCount?'✓':'×'; remove.disabled=uploading||index<uploadedCount; remove.addEventListener('click',()=>{files.splice(index,1);cameraError.hidden=true;render();}); card.append(img,remove); shots.appendChild(card); }); done.disabled=uploading||!files.length||uploadedCount>=files.length; capture.disabled=uploading||files.length>=maxPhotos; const next=photoPlan[files.length]; prompt.textContent=files.length>=maxPhotos ? `Maximum ${maxPhotos} photos · tap Done` : next ? `${files.length + 1} · ${next}` : `${files.length} photos ready · add more or tap Done`; shots.scrollLeft=shots.scrollWidth; };
            overlay.querySelector('header button').addEventListener('click', close);
            overlay.querySelector('.nf-camera-fallback').addEventListener('click', () => { close(); input.click(); });
            try { stream = await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}},audio:false}); video.srcObject = stream; render(); }
            catch (_) { close(); input.click(); return; }
            capture.addEventListener('click', () => { if(uploading||files.length>=maxPhotos)return; const sourceWidth=video.videoWidth||1280; const sourceHeight=video.videoHeight||960; const scale=Math.min(1,1600/Math.max(sourceWidth,sourceHeight)); const canvas=document.createElement('canvas'); canvas.width=Math.round(sourceWidth*scale); canvas.height=Math.round(sourceHeight*scale); canvas.getContext('2d').drawImage(video,0,0,canvas.width,canvas.height); canvas.toBlob((blob)=>{ if(!blob)return; files.push(new File([blob],`nextfix-${Date.now()}-${files.length+1}.jpg`,{type:'image/jpeg'})); render(); },'image/jpeg',.74); });
            done.addEventListener('click', () => { uploading=true; cameraError.hidden=true; upload.hidden=false; uploadBar.style.width=`${Math.round(uploadedCount/files.length*100)}%`; done.innerHTML='<span>Uploading</span><i class="bi bi-cloud-arrow-up"></i>'; render(); const fail=(xhr,message='Upload failed. Your remaining photos are still here.')=>{ if(xhr?.status===419)message='Session expired. Saved photos are safe; reopen the job to continue.'; else if(xhr?.status===413)message='This photo batch is too large. Please try again.'; else if(xhr){try{const data=JSON.parse(xhr.responseText);message=data.message||Object.values(data.errors||{}).flat()[0]||message;}catch(_){}} uploading=false; upload.hidden=true; cameraError.textContent=uploadedCount?`${uploadedCount} saved. ${message}`:message; cameraError.hidden=false; done.innerHTML='<span>Retry remaining</span><i class="bi bi-arrow-clockwise"></i>'; render();}; const uploadNext=()=>{if(uploadedCount>=files.length){uploadBar.style.width='100%';uploadText.textContent=`All ${files.length} photos saved ✓`;window.setTimeout(()=>{close();window.location.reload();},450);return;} const batch=files.slice(uploadedCount,uploadedCount+batchSize); const payload=new FormData(form); payload.delete(input.name); batch.forEach((file)=>payload.append(input.name,file)); const xhr=new XMLHttpRequest(); const uploadUrl=form.getAttribute('action')||window.location.href; xhr.open('POST',uploadUrl); xhr.withCredentials=true; xhr.setRequestHeader('X-Requested-With','XMLHttpRequest'); xhr.setRequestHeader('Accept','application/json'); xhr.upload.addEventListener('progress',(event)=>{if(!event.lengthComputable)return;const overall=((uploadedCount+(event.loaded/event.total)*batch.length)/files.length)*100;const percent=Math.max(2,Math.round(overall));uploadBar.style.width=`${percent}%`;uploadText.textContent=`Saving ${uploadedCount + 1}–${uploadedCount + batch.length} of ${files.length} · ${percent}%`;}); xhr.addEventListener('load',()=>{if(xhr.status>=200&&xhr.status<400){uploadedCount+=batch.length;render();uploadNext();return;}fail(xhr);}); xhr.addEventListener('error',()=>fail(null,'Connection lost. Check internet and retry the remaining photos.')); xhr.send(payload);}; uploadNext(); });
        });
    };
    installCamera(beforeStep?.querySelector('form'), 'Take before photos');
    installCamera(afterStep?.querySelector('form'), 'Take after photos');
}

const installMaintenanceCamera = (form) => {
    if (!form || form.querySelector('.camera-session-button')) return;
    const input = form.querySelector('input[type="file"]');
    if (!input) return;
    const title = form.dataset.cameraTitle || 'Take job photos';
    let photoPlan = [];
    try { photoPlan = JSON.parse(form.dataset.cameraPlan || '[]'); } catch (_) {}
    input.removeAttribute('capture'); input.multiple = true; form.classList.add('cleaning-camera-active');
    const launch = document.createElement('button'); launch.type = 'button'; launch.className = 'camera-session-button';
    launch.innerHTML = `<i class="bi bi-camera-fill"></i><span>${title}</span>`; form.prepend(launch);
    input.addEventListener('change', () => {
        if (!input.files.length) return;
        if (input.files.length > 8) {
            input.setCustomValidity('Choose up to 8 gallery photos at a time, or use the guided camera for up to 30.');
            input.reportValidity(); return;
        }
        input.setCustomValidity(''); form.requestSubmit();
    });
    launch.addEventListener('click', async () => {
        if (!navigator.mediaDevices?.getUserMedia) { input.click(); return; }
        const overlay = document.createElement('section'); overlay.className = 'nf-camera';
        overlay.innerHTML = `<header><div><strong>${title}</strong><small class="nf-camera-prompt"></small></div><button type="button" aria-label="Close">×</button></header><div class="nf-camera-stage"><video autoplay playsinline muted></video><div class="nf-camera-guide"></div></div><footer><div class="nf-camera-shots"></div><div class="nf-camera-upload" hidden><div><i></i></div><span>Preparing photos…</span></div><div class="nf-camera-error" hidden></div><div class="nf-camera-controls"><button type="button" class="nf-camera-fallback"><i class="bi bi-images"></i><span>Gallery</span></button><button type="button" class="nf-camera-capture" aria-label="Take photo"></button><button type="button" class="nf-camera-done" disabled><span>Done</span><i class="bi bi-check2"></i></button></div></footer>`;
        document.body.appendChild(overlay); document.body.style.overflow = 'hidden';
        const video = overlay.querySelector('video'); const shots = overlay.querySelector('.nf-camera-shots');
        const done = overlay.querySelector('.nf-camera-done'); const capture = overlay.querySelector('.nf-camera-capture'); const prompt = overlay.querySelector('.nf-camera-prompt');
        const upload = overlay.querySelector('.nf-camera-upload'); const uploadBar = upload.querySelector('i'); const uploadText = upload.querySelector('span'); const cameraError = overlay.querySelector('.nf-camera-error');
        const files = []; const maxPhotos = 30; const batchSize = 5; let uploadedCount = 0; let uploading = false; let stream;
        const close = () => { stream?.getTracks().forEach((track) => track.stop()); overlay.remove(); document.body.style.overflow = ''; };
        const render = () => {
            shots.replaceChildren();
            files.forEach((file, index) => {
                const card = document.createElement('span'); card.className = `nf-camera-shot${index < uploadedCount ? ' saved' : ''}`;
                const image = document.createElement('img'); image.src = URL.createObjectURL(file); image.alt = `Photo ${index + 1}`;
                const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = index < uploadedCount ? '✓' : '×'; remove.disabled = uploading || index < uploadedCount;
                remove.addEventListener('click', () => { files.splice(index, 1); cameraError.hidden = true; render(); });
                card.append(image, remove); shots.appendChild(card);
            });
            done.disabled = uploading || !files.length || uploadedCount >= files.length; capture.disabled = uploading || files.length >= maxPhotos;
            const next = photoPlan[files.length];
            prompt.textContent = files.length >= maxPhotos ? `Maximum ${maxPhotos} photos · tap Done` : next ? `${files.length + 1} · ${next}` : `${files.length} photos ready · add more or tap Done`;
            shots.scrollLeft = shots.scrollWidth;
        };
        overlay.querySelector('header button').addEventListener('click', close);
        overlay.querySelector('.nf-camera-fallback').addEventListener('click', () => { close(); input.click(); });
        try { stream = await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}},audio:false}); video.srcObject = stream; render(); }
        catch (_) { close(); input.click(); return; }
        capture.addEventListener('click', () => {
            if (uploading || files.length >= maxPhotos) return;
            const sourceWidth = video.videoWidth || 1280; const sourceHeight = video.videoHeight || 960; const scale = Math.min(1, 1600 / Math.max(sourceWidth, sourceHeight));
            const canvas = document.createElement('canvas'); canvas.width = Math.round(sourceWidth * scale); canvas.height = Math.round(sourceHeight * scale); canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => { if (!blob) return; files.push(new File([blob], `nextfix-${Date.now()}-${files.length + 1}.jpg`, {type:'image/jpeg'})); render(); }, 'image/jpeg', .74);
        });
        done.addEventListener('click', () => {
            uploading = true; cameraError.hidden = true; upload.hidden = false; done.innerHTML = '<span>Uploading</span><i class="bi bi-cloud-arrow-up"></i>'; render();
            const fail = (xhr, message = 'Upload failed. Your remaining photos are still here.') => {
                if (xhr?.status === 419) message = 'Session expired. Saved photos are safe; reopen the job to continue.';
                else if (xhr?.status === 413) message = 'This photo batch is too large. Please try again.';
                else if (xhr) { try { const data = JSON.parse(xhr.responseText); message = data.message || Object.values(data.errors || {}).flat()[0] || message; } catch (_) {} }
                uploading = false; upload.hidden = true; cameraError.textContent = uploadedCount ? `${uploadedCount} saved. ${message}` : message; cameraError.hidden = false; done.innerHTML = '<span>Retry remaining</span><i class="bi bi-arrow-clockwise"></i>'; render();
            };
            const uploadNext = () => {
                if (uploadedCount >= files.length) { uploadBar.style.width = '100%'; uploadText.textContent = `All ${files.length} photos saved ✓`; window.setTimeout(() => { close(); window.location.reload(); }, 450); return; }
                const batch = files.slice(uploadedCount, uploadedCount + batchSize); const payload = new FormData(form); payload.delete(input.name); batch.forEach((file) => payload.append(input.name, file));
                const xhr = new XMLHttpRequest(); xhr.open('POST', form.getAttribute('action') || window.location.href); xhr.withCredentials = true; xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); xhr.setRequestHeader('Accept', 'application/json');
                xhr.upload.addEventListener('progress', (event) => { if (!event.lengthComputable) return; const percent = Math.max(2, Math.round(((uploadedCount + (event.loaded / event.total) * batch.length) / files.length) * 100)); uploadBar.style.width = `${percent}%`; uploadText.textContent = `Saving ${uploadedCount + 1}–${uploadedCount + batch.length} of ${files.length} · ${percent}%`; });
                xhr.addEventListener('load', () => { if (xhr.status >= 200 && xhr.status < 400) { uploadedCount += batch.length; render(); uploadNext(); return; } fail(xhr); });
                xhr.addEventListener('error', () => fail(null, 'Connection lost. Check internet and retry the remaining photos.')); xhr.send(payload);
            };
            uploadNext();
        });
    });
};
document.querySelectorAll('[data-maintenance-camera-session]').forEach(installMaintenanceCamera);

const maintenanceChecklist = document.querySelector('.maintenance-checklist');
if (maintenanceChecklist) {
    const startStep = document.querySelector('.maintenance-start-step'); const progressCard = document.querySelector('.worker-progress-card');
    const afterStep = document.querySelector('.work-evidence'); const holdStep = document.querySelector('.worker-hold'); const finishStep = document.querySelector('[data-swipe-form]');
    const oldJourney = document.querySelector('.job-journey'); const allChecks = [...maintenanceChecklist.querySelectorAll('input[type="checkbox"]')];
    const hasStart = finishStep?.dataset.startPhoto === '1'; const hasAfter = finishStep?.dataset.afterPhoto === '1';
    const panels = [[startStep], [progressCard, maintenanceChecklist], [afterStep], [holdStep, finishStep]];
    const labels = [['bi-camera','Before'],['bi-tools','Repair'],['bi-camera-fill','After'],['bi-check2-circle','Finish']];
    const allDone = () => allChecks.length > 0 && allChecks.every((box) => box.checked);
    let activeStep = !hasStart ? 0 : !allDone() ? 1 : !hasAfter ? 2 : 3;
    const nav = document.createElement('nav'); nav.className = 'maintenance-wizard-nav'; nav.setAttribute('aria-label', 'Maintenance work steps');
    const requirements = () => [hasStart, allDone(), hasAfter, true];
    const showStep = (index) => {
        activeStep = index;
        panels.forEach((group, groupIndex) => group.forEach((node) => node?.classList.toggle('maintenance-wizard-hidden', groupIndex !== index)));
        [...nav.children].forEach((button, buttonIndex) => { button.classList.toggle('active', buttonIndex === index); button.classList.toggle('done', requirements()[buttonIndex] && buttonIndex < index); });
        document.querySelector('.maintenance-wizard-actions')?.remove();
        const visible = panels[index].filter(Boolean).at(-1); if (!visible || index === 3) return;
        const actions = document.createElement('div'); actions.className = 'maintenance-wizard-actions';
        const back = document.createElement('button'); back.type = 'button'; back.textContent = index ? '← Back' : 'My jobs'; back.addEventListener('click', () => index ? showStep(index - 1) : (window.location.href = '/work/jobs'));
        const next = document.createElement('button'); next.type = 'button'; next.className = 'primary-next'; next.textContent = index === 2 ? 'Continue to finish →' : 'Next →'; next.disabled = !requirements()[index]; next.addEventListener('click', () => showStep(index + 1));
        actions.append(back, next); visible.after(actions);
    };
    labels.forEach(([icon, label], index) => { const button = document.createElement('button'); button.type = 'button'; button.innerHTML = `<i class="bi ${icon}"></i><span>${label}</span>`; button.addEventListener('click', () => { if (index <= activeStep || requirements().slice(0, index).every(Boolean)) showStep(index); }); nav.appendChild(button); });
    oldJourney?.classList.add('maintenance-wizard-hidden'); oldJourney?.after(nav); showStep(activeStep);
    maintenanceChecklist.addEventListener('change', () => window.setTimeout(() => showStep(1), 20));
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

document.querySelectorAll('[data-rate-browser]').forEach((browser) => {
    const buttons = [...browser.querySelectorAll('[data-rate-unit]')];
    const panels = [...browser.querySelectorAll('[data-rate-panel]')];
    const activate = (unit) => {
        buttons.forEach((button) => {
            const active = button.dataset.rateUnit === unit;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach((panel) => { panel.hidden = panel.dataset.ratePanel !== unit; });
    };
    buttons.forEach((button) => button.addEventListener('click', () => activate(button.dataset.rateUnit)));
    activate(browser.dataset.activeUnit || buttons[0]?.dataset.rateUnit);
});

document.querySelectorAll('[data-cleaning-booking]').forEach((form) => {
    let rates = {};
    try { rates = JSON.parse(form.dataset.bookingRates || '{}'); } catch (_) {}
    const unit = form.querySelector('select[name="unit_id"]');
    const cards = [...form.querySelectorAll('[data-rate-code]')];
    const empty = form.querySelector('[data-no-contracted-service]');
    const unitLabel = form.querySelector('[data-booking-unit-label]');
    const summaryTitle = form.querySelector('[data-booking-summary-title]');
    const summaryCopy = form.querySelector('[data-booking-summary-copy]');
    const submit = form.querySelector('button[type="submit"]');
    const activeRates = form.dataset.ratesActive === '1';
    const format = (minor) => `AED ${(Number(minor) / 100).toLocaleString('en-AE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    const updateSummary = () => {
        const selected = cards.find((card) => card.querySelector('input').checked && !card.hidden);
        if (!selected) {
            summaryTitle.textContent = 'No service selected';
            summaryCopy.textContent = 'Choose one of the approved cleaning services above.';
            if (submit) submit.disabled = true;
            return;
        }
        const input = selected.querySelector('input');
        const price = selected.querySelector('[data-rate-price]').textContent;
        summaryTitle.textContent = `${input.value} · ${price}`;
        summaryCopy.textContent = 'This is the approved contract price for the selected property.';
        if (submit) submit.disabled = !activeRates;
    };
    const updateServices = () => {
        const selectedUnit = rates[String(unit?.value || '')] || {};
        const services = selectedUnit.services || {};
        let available = 0;
        cards.forEach((card) => {
            const amount = services[card.dataset.rateCode];
            const isAvailable = amount !== null && amount !== '' && amount !== undefined && Number.isFinite(Number(amount));
            card.hidden = !isAvailable;
            const input = card.querySelector('input');
            input.disabled = !isAvailable;
            if (!isAvailable) input.checked = false;
            else {
                available += 1;
                card.querySelector('[data-rate-price]').textContent = format(amount);
            }
        });
        if (!cards.some((card) => !card.hidden && card.querySelector('input').checked)) {
            const first = cards.find((card) => !card.hidden);
            if (first) first.querySelector('input').checked = true;
        }
        if (unitLabel) unitLabel.textContent = selectedUnit.unit_label ? `· ${selectedUnit.unit_label}` : '';
        if (empty) empty.hidden = available > 0;
        updateSummary();
    };
    unit?.addEventListener('change', updateServices);
    cards.forEach((card) => card.querySelector('input').addEventListener('change', updateSummary));
    updateServices();
});

const notificationFeed = document.querySelector('[data-notification-feed]');
if (notificationFeed) {
    let lastNotification = notificationFeed.dataset.lastNotification || '';
    const countBadges = [...document.querySelectorAll('[data-notification-count]')];
    const soundButtons = [...document.querySelectorAll('[data-alert-sound]')];
    let soundEnabled = localStorage.getItem('nextfix-alert-sound') !== 'off';
    let audioContext;
    const updateSoundButtons = () => soundButtons.forEach((button) => {
        button.classList.toggle('enabled', soundEnabled);
        button.classList.toggle('muted', !soundEnabled);
        button.setAttribute('aria-pressed', soundEnabled ? 'true' : 'false');
        button.setAttribute('aria-label', soundEnabled ? 'Mute alert sounds' : 'Enable alert sounds');
        const icon = button.querySelector('i');
        if (icon) icon.className = soundEnabled ? 'bi bi-volume-up' : 'bi bi-volume-mute';
    });
    const primeSound = async () => {
        if (!soundEnabled) return null;
        audioContext ||= new (window.AudioContext || window.webkitAudioContext)();
        if (audioContext.state === 'suspended') await audioContext.resume();
        return audioContext;
    };
    const playTone = async (kind = 'default') => {
        const context = await primeSound().catch(() => null);
        if (!context) return;
        const isJob = kind === 'job';
        const notes = isJob ? [[0,880,.22],[.28,1046,.22],[.56,1318,.28],[.92,1046,.2],[1.18,1318,.38]] : (kind === 'message' ? [[0,740,.12],[.16,988,.16]] : [[0,660,.12],[.18,880,.18]]);
        const now = context.currentTime;
        notes.forEach(([delay, frequency, duration]) => {
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            oscillator.type = isJob ? 'square' : 'sine';
            oscillator.frequency.value = frequency;
            gain.gain.setValueAtTime(0.0001, now + delay);
            gain.gain.exponentialRampToValueAtTime(isJob ? .15 : .08, now + delay + .015);
            gain.gain.exponentialRampToValueAtTime(.0001, now + delay + duration);
            oscillator.connect(gain).connect(context.destination);
            oscillator.start(now + delay); oscillator.stop(now + delay + duration + .03);
        });
        navigator.vibrate?.(isJob ? [280,120,280,120,520] : [100,70,140]);
    };
    const showSystemNotification = (latest) => {
        if (!('Notification' in window) || Notification.permission !== 'granted' || document.visibilityState === 'visible') return;
        const notice = new Notification(latest.title, {body: latest.message, icon: '/images/pwa/icon-192.png', tag: `nextfix-${latest.id}`, renotify: latest.sound === 'job'});
        notice.addEventListener('click', () => { window.focus(); window.location.href = latest.url; });
    };
    soundButtons.forEach((button) => button.addEventListener('click', async () => {
        soundEnabled = !soundEnabled;
        localStorage.setItem('nextfix-alert-sound', soundEnabled ? 'on' : 'off');
        updateSoundButtons();
        if (soundEnabled) {
            await primeSound().catch(() => null);
            if ('Notification' in window && Notification.permission === 'default') await Notification.requestPermission().catch(() => null);
            playTone('message');
        }
    }));
    const unlock = () => { primeSound().catch(() => null); document.removeEventListener('pointerdown', unlock); document.removeEventListener('keydown', unlock); };
    document.addEventListener('pointerdown', unlock, {passive:true});
    document.addEventListener('keydown', unlock);
    updateSoundButtons();
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
                if (soundEnabled) playTone(data.latest.sound || 'default');
                showSystemNotification(data.latest);
            }
            if (data.latest?.id) lastNotification = data.latest.id;
        } catch (_) {
            // Keep the app usable when the device is temporarily offline.
        }
    };
    window.setInterval(pollNotifications, document.body.classList.contains('worker-app') ? 5000 : 10000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) pollNotifications(); });
}

const bookingChat = document.querySelector('[data-chat-feed]');
if (bookingChat) {
    document.body.classList.add('chat-open');
    const stream = bookingChat.querySelector('[data-chat-stream]');
    const form = bookingChat.querySelector('[data-chat-form]');
    const body = form.querySelector('textarea[name="body"]');
    const attachment = form.querySelector('input[name="attachment"]');
    const attachmentKind = form.querySelector('input[name="attachment_kind"]');
    const preview = form.querySelector('[data-chat-preview]');
    const upload = form.querySelector('[data-chat-upload]');
    const uploadBar = upload.querySelector('i');
    const error = form.querySelector('[data-chat-error]');
    const record = form.querySelector('[data-chat-record]');
    let lastId = Number(stream.querySelector('[data-message-id]:last-of-type')?.dataset.messageId || 0);
    let recorder; let recordStarted; let recordTimer; let recordedChunks = [];
    let previewUrl;
    const csrf = form.querySelector('input[name="_token"]').value;
    const syncChatViewport = () => bookingChat.style.setProperty('--chat-viewport-height', `${window.visualViewport?.height || window.innerHeight}px`);
    syncChatViewport();
    window.addEventListener('resize', syncChatViewport);
    window.visualViewport?.addEventListener('resize', syncChatViewport);
    const scrollDown = (smooth = false) => stream.scrollTo({top: stream.scrollHeight, behavior: smooth ? 'smooth' : 'auto'});
    const escapeHtml = (value = '') => { const div = document.createElement('div'); div.textContent = value; return div.innerHTML; };
    const formatDuration = (seconds) => `${Math.floor((seconds || 0) / 60)}:${String(Math.floor((seconds || 0) % 60)).padStart(2, '0')}`;
    const initAudioCard = (card) => {
        if (card.dataset.ready) return;
        card.dataset.ready = '1';
        const audio = card.querySelector('audio'); const play = card.querySelector('[data-audio-play]'); const icon = play.querySelector('i');
        const track = card.querySelector('[data-audio-track]'); const progress = card.querySelector('[data-audio-progress]'); const time = card.querySelector('[data-audio-time]');
        const resetIcon = () => { icon.className = audio.paused ? 'bi bi-play-fill' : 'bi bi-pause-fill'; };
        const showDuration = () => { time.textContent = formatDuration(audio.duration); };
        audio.addEventListener('loadedmetadata', showDuration);
        if (audio.readyState >= 1) showDuration();
        audio.addEventListener('timeupdate', () => { progress.style.width = `${audio.duration ? (audio.currentTime / audio.duration) * 100 : 0}%`; time.textContent = formatDuration(audio.currentTime); });
        audio.addEventListener('play', () => { document.querySelectorAll('.chat-audio audio').forEach((other) => { if (other !== audio) other.pause(); }); resetIcon(); });
        audio.addEventListener('pause', resetIcon); audio.addEventListener('ended', () => { audio.currentTime = 0; resetIcon(); });
        play.addEventListener('click', () => audio.paused ? audio.play().catch(() => {}) : audio.pause());
        track.addEventListener('click', (event) => { if (audio.duration) audio.currentTime = ((event.clientX - track.getBoundingClientRect().left) / track.clientWidth) * audio.duration; });
    };
    const audioMarkup = (url) => `<div class="chat-audio" data-audio-card><button type="button" data-audio-play aria-label="Play voice message"><i class="bi bi-play-fill"></i></button><div><div class="chat-wave" data-audio-track>${'<i></i>'.repeat(18)}<span data-audio-progress></span></div><small><b data-audio-time>0:00</b><em>Voice message</em></small></div><audio preload="metadata" src="${url}"></audio></div>`;
    const messageNode = (item) => {
        const article = document.createElement('article'); article.className = `chat-message${item.mine ? ' mine' : ''}`; article.dataset.messageId = item.id;
        const sender = `<div class="chat-sender"><strong>${escapeHtml(item.sender)}</strong><span>${escapeHtml(item.sender_role)}</span></div>`;
        let media = '';
        if (item.type === 'image') media = `<a class="chat-image" href="${item.file_url}" target="_blank"><img src="${item.file_url}" alt="Shared photo"><span><i class="bi bi-arrows-fullscreen"></i></span></a>`;
        if (item.type === 'audio') media = audioMarkup(item.file_url);
        if (item.type === 'file') media = `<a class="chat-file" href="${item.file_url}" target="_blank"><i class="bi bi-file-earmark-text"></i><span><strong>${escapeHtml(item.file_name || 'Attachment')}</strong><small>${item.file_size ? `${Math.ceil(item.file_size / 1024)} KB` : 'Open attachment'}</small></span><b><i class="bi bi-download"></i></b></a>`;
        article.innerHTML = `${sender}<div class="chat-bubble">${media}${item.body ? `<p>${escapeHtml(item.body)}</p>` : ''}<time>${escapeHtml(item.time)}</time></div>`;
        article.querySelectorAll('[data-audio-card]').forEach(initAudioCard);
        return article;
    };
    const append = (item, smooth = true) => {
        if (stream.querySelector(`[data-message-id="${item.id}"]`)) return;
        stream.querySelector('[data-chat-empty]')?.remove(); stream.appendChild(messageNode(item)); lastId = Math.max(lastId, Number(item.id)); scrollDown(smooth);
    };
    const poll = async () => {
        if (document.hidden) return;
        try { const response = await fetch(`${bookingChat.dataset.chatFeed}?after=${lastId}`, {headers:{Accept:'application/json'}, credentials:'same-origin'}); if (!response.ok) return; const data = await response.json(); data.messages.forEach((item) => append(item)); } catch (_) {}
    };
    const send = (payload) => new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest(); xhr.open('POST', bookingChat.dataset.chatStore); xhr.withCredentials = true; xhr.setRequestHeader('Accept','application/json'); xhr.setRequestHeader('X-CSRF-TOKEN', csrf); upload.hidden = false; error.hidden = true;
        xhr.upload.addEventListener('progress', (event) => { if (event.lengthComputable) uploadBar.style.width = `${Math.max(4, Math.round(event.loaded/event.total*100))}%`; });
        xhr.addEventListener('load', () => { upload.hidden = true; uploadBar.style.width = '0'; if (xhr.status >= 200 && xhr.status < 300) { resolve(JSON.parse(xhr.responseText)); return; } try { reject(JSON.parse(xhr.responseText).message); } catch (_) { reject('Message could not be sent.'); } });
        xhr.addEventListener('error', () => { upload.hidden = true; reject('Connection lost. Tap send to retry.'); }); xhr.send(payload);
    });
    form.addEventListener('submit', async (event) => {
        event.preventDefault(); if (!body.value.trim() && !attachment.files.length) return; const payload = new FormData(form); form.querySelector('.chat-send').disabled = true;
        try { const data = await send(payload); append(data.message); body.value = ''; attachment.value = ''; attachmentKind.value = ''; preview.hidden = true; preview.replaceChildren(); if (previewUrl) URL.revokeObjectURL(previewUrl); previewUrl = null; }
        catch (message) { error.textContent = message; error.hidden = false; }
        finally { form.querySelector('.chat-send').disabled = false; }
    });
    const renderAttachmentPreview = (file) => {
        preview.replaceChildren();
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        previewUrl = file ? URL.createObjectURL(file) : null;
        preview.hidden = !file;
        if (!file) return;
        const card = document.createElement('div'); card.className = 'chat-preview-card';
        const visual = file.type.startsWith('image/') ? document.createElement('img') : document.createElement('i');
        if (visual.tagName === 'IMG') visual.src = previewUrl; else visual.className = file.type.startsWith('audio/') ? 'bi bi-mic-fill' : 'bi bi-file-earmark-text';
        const meta = document.createElement('span'); const title = document.createElement('strong'); const size = document.createElement('small');
        title.textContent = file.type.startsWith('audio/') ? 'Voice message ready' : file.name; size.textContent = `${Math.max(1, Math.ceil(file.size / 1024))} KB · ready to send`; meta.append(title, size);
        const remove = document.createElement('button'); remove.type = 'button'; remove.setAttribute('aria-label', 'Remove attachment'); remove.innerHTML = '<i class="bi bi-x-lg"></i>';
        remove.addEventListener('click', () => { attachment.value = ''; attachmentKind.value = ''; renderAttachmentPreview(null); });
        card.append(visual, meta, remove);
        if (file.type.startsWith('audio/')) { const player = document.createElement('audio'); player.controls = true; player.src = previewUrl; card.appendChild(player); }
        preview.appendChild(card);
    };
    attachment.addEventListener('change', () => { const file = attachment.files[0] || null; attachmentKind.value = file?.type.startsWith('audio/') ? 'audio' : ''; renderAttachmentPreview(file); });
    bookingChat.querySelectorAll('[data-quick-reply]').forEach((button) => button.addEventListener('click', () => { body.value = button.dataset.quickReply; body.focus(); }));
    body.addEventListener('input', () => { body.style.height = 'auto'; body.style.height = `${Math.min(body.scrollHeight,120)}px`; });
    record.addEventListener('click', async () => {
        if (recorder?.state === 'recording') { recorder.stop(); return; }
        try {
            const media = await navigator.mediaDevices.getUserMedia({audio:true}); recordedChunks = []; recorder = new MediaRecorder(media); recorder.addEventListener('dataavailable', (event) => { if (event.data.size) recordedChunks.push(event.data); });
            recorder.addEventListener('stop', () => { media.getTracks().forEach((track) => track.stop()); clearInterval(recordTimer); record.classList.remove('recording'); record.querySelector('i').className='bi bi-mic'; record.querySelector('span').hidden=true; const blob=new Blob(recordedChunks,{type:recorder.mimeType || 'audio/webm'}); const file=new File([blob],`voice-${Date.now()}.webm`,{type:blob.type}); const transfer=new DataTransfer(); transfer.items.add(file); attachment.files=transfer.files; attachmentKind.value='audio'; renderAttachmentPreview(file); });
            recorder.start(); recordStarted=Date.now(); record.classList.add('recording'); record.querySelector('i').className='bi bi-stop-fill'; const timer=record.querySelector('span'); timer.hidden=false; recordTimer=setInterval(()=>{const seconds=Math.floor((Date.now()-recordStarted)/1000);timer.textContent=`${Math.floor(seconds/60)}:${String(seconds%60).padStart(2,'0')}`; if(seconds>=120)recorder.stop();},250);
        } catch (_) { error.textContent='Microphone permission is required for voice messages.'; error.hidden=false; }
    });
    bookingChat.querySelectorAll('[data-audio-card]').forEach(initAudioCard);
    scrollDown(); window.setInterval(poll, 3000); document.addEventListener('visibilitychange', poll);
}

document.querySelectorAll('.table-wrap tbody tr').forEach((row) => {
    const reference = row.querySelector('td:first-child strong')?.textContent.trim();
    const actions = row.querySelector('.document-actions') || row.querySelector('td:last-child');
    if (!reference?.startsWith('NFB-') || !actions) return;
    const chat = document.createElement('a'); chat.className = 'icon-document'; chat.href = `/booking/${encodeURIComponent(reference)}/chat`; chat.title = 'Job chat'; chat.setAttribute('aria-label','Job chat'); chat.innerHTML = '<i class="bi bi-chat-dots"></i>';
    actions.prepend(chat);
});

document.querySelectorAll('[data-gallery]').forEach((gallery) => {
    const items = [...gallery.querySelectorAll('[data-gallery-item]')]; let current = 0; let touchX = 0;
    const lightbox = document.createElement('section'); lightbox.className = 'gallery-lightbox'; lightbox.hidden = true;
    lightbox.innerHTML = '<button class="gallery-close" aria-label="Close">×</button><button class="gallery-prev" aria-label="Previous"><i class="bi bi-chevron-left"></i></button><img alt="Booking evidence"><button class="gallery-next" aria-label="Next"><i class="bi bi-chevron-right"></i></button><footer><strong></strong><span></span></footer>';
    document.body.appendChild(lightbox); const image = lightbox.querySelector('img'); const caption = lightbox.querySelector('footer strong'); const count = lightbox.querySelector('footer span');
    const show = (index) => { current = (index + items.length) % items.length; image.src = items[current].dataset.src; caption.textContent = items[current].dataset.caption || ''; count.textContent = `${current + 1} of ${items.length}`; lightbox.hidden = false; document.body.style.overflow = 'hidden'; };
    const close = () => { lightbox.hidden = true; image.src = ''; document.body.style.overflow = ''; };
    items.forEach((item,index) => item.addEventListener('click', (event) => { event.preventDefault(); show(index); })); lightbox.querySelector('.gallery-close').addEventListener('click', close); lightbox.querySelector('.gallery-prev').addEventListener('click', () => show(current-1)); lightbox.querySelector('.gallery-next').addEventListener('click', () => show(current+1));
    lightbox.addEventListener('touchstart', (event) => touchX = event.touches[0].clientX, {passive:true}); lightbox.addEventListener('touchend', (event) => { const delta=event.changedTouches[0].clientX-touchX; if(Math.abs(delta)>45) show(current+(delta<0?1:-1)); }, {passive:true});
    document.addEventListener('keydown', (event) => { if(lightbox.hidden)return; if(event.key==='Escape')close(); if(event.key==='ArrowLeft')show(current-1); if(event.key==='ArrowRight')show(current+1); });
});
