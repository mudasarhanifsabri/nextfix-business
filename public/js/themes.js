(() => {
    const root = document.documentElement;
    const media = window.matchMedia("(prefers-color-scheme: dark)");
    const resolveMode = (appearance) =>
        appearance === "system"
            ? media.matches
                ? "dark"
                : "light"
            : appearance;
    const sync = () => {
        root.dataset.colorMode = resolveMode(
            root.dataset.appearance || "light",
        );
        const meta = document.querySelector('meta[name="theme-color"]');
        if (meta && root.classList.contains("nf-themed"))
            meta.content = getComputedStyle(root)
                .getPropertyValue("--nf-primary")
                .trim();
    };
    sync();
    const form = document.querySelector("[data-theme-form]");
    const preview = document.querySelector("[data-theme-preview]");
    const foreground = (hex) => {
        const c = hex
            .slice(1)
            .match(/../g)
            .map((v) => {
                const n = parseInt(v, 16) / 255;
                return n <= 0.04045 ? n / 12.92 : ((n + 0.055) / 1.055) ** 2.4;
            });
        const l = 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
        return (l + 0.05) / 0.05 > 1.05 / (l + 0.05) ? "#000000" : "#ffffff";
    };
    const update = () => {
        if (!form || !preview) return;
        const values = new FormData(form);
        preview.dataset.theme = values.get("preset");
        preview.dataset.colorMode = resolveMode(values.get("appearance"));
        const primary = values.get("primary");
        preview.style.setProperty("--nf-primary", primary);
        preview.style.setProperty("--nf-secondary", values.get("secondary"));
        preview.style.setProperty("--nf-on-primary", foreground(primary));
    };
    form?.addEventListener("input", update);
    form?.querySelectorAll('[name="preset"]').forEach((radio) =>
        radio.addEventListener("change", () => {
            form.elements.primary.value = radio.dataset.primary;
            form.elements.secondary.value = radio.dataset.secondary;
            update();
        }),
    );
    document
        .querySelector("[data-theme-preview-button]")
        ?.addEventListener("click", () => {
            update();
            preview?.scrollIntoView({
                behavior:
                    media.matches ||
                    window.matchMedia("(prefers-reduced-motion: reduce)")
                        .matches
                        ? "auto"
                        : "smooth",
                block: "center",
            });
        });
    media.addEventListener("change", () => {
        sync();
        update();
    });
    update();
})();

// Manual carousel only: no auto motion, no automatic booking or payment actions.
document.querySelectorAll("[data-theme-carousel]").forEach((carousel) => {
    const slides = Array.from(carousel.querySelectorAll("[data-theme-slide]"));
    const dots = Array.from(carousel.querySelectorAll("[data-theme-slide-to]"));
    let current = 0;
    const show = (index) => {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => {
            slide.hidden = i !== current;
            slide.classList.toggle("is-current", i === current);
        });
        dots.forEach((dot, i) =>
            dot.setAttribute("aria-pressed", String(i === current)),
        );
    };
    dots.forEach((dot, i) => dot.addEventListener("click", () => show(i)));
    carousel
        .querySelector("[data-theme-slide-prev]")
        ?.addEventListener("click", () => show(current - 1));
    carousel
        .querySelector("[data-theme-slide-next]")
        ?.addEventListener("click", () => show(current + 1));
    carousel.addEventListener("keydown", (event) => {
        if (event.key === "ArrowLeft" || event.key === "ArrowRight") {
            event.preventDefault();
            show(current + (event.key === "ArrowRight" ? 1 : -1));
        }
    });
    let start = null;
    carousel.addEventListener(
        "touchstart",
        (event) => {
            start = {
                x: event.touches[0].clientX,
                y: event.touches[0].clientY,
            };
        },
        { passive: true },
    );
    carousel.addEventListener(
        "touchend",
        (event) => {
            if (!start) return;
            const dx = event.changedTouches[0].clientX - start.x;
            const dy = event.changedTouches[0].clientY - start.y;
            if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy))
                show(current + (dx < 0 ? 1 : -1));
            start = null;
        },
        { passive: true },
    );
});
(() => {
    const form = document.querySelector("[data-theme-form]");
    const banner = document.querySelector("[data-preview-banner]");
    if (!form || !banner) return;
    let active = 0;
    const objectUrls = new Map();
    const update = () => {
        const fieldset = form.querySelector(
            '[data-slider-editor="' + active + '"]',
        );
        if (!fieldset) return;
        banner.querySelector("strong").textContent =
            fieldset.querySelector('[name$="[title]"]').value;
        const caption = fieldset.querySelector('[name$="[caption]"]').value;
        let p = banner.querySelector("p");
        if (!p) {
            p = document.createElement("p");
            banner.querySelector("strong").after(p);
        }
        p.textContent = caption;
        banner.querySelector("span").textContent =
            fieldset.querySelector('[name$="[action]"] option:checked')
                .textContent + " →";
        const input = fieldset.querySelector("input[type=file]");
        let url = fieldset.dataset.currentImage;
        if (input.files[0]) {
            if (!objectUrls.has(input.files[0]))
                objectUrls.set(
                    input.files[0],
                    URL.createObjectURL(input.files[0]),
                );
            url = objectUrls.get(input.files[0]);
        } else if (fieldset.querySelector('[name$="[remove_image]"]')?.checked)
            url = "";
        banner.style.setProperty(
            "--preview-photo",
            url ? 'url("' + url.replaceAll('"', "%22") + '")' : "none",
        );
    };
    form.addEventListener("input", update);
    form.addEventListener("change", update);
    document.querySelectorAll("[data-preview-slide]").forEach((button) =>
        button.addEventListener("click", () => {
            active = Number(button.dataset.previewSlide);
            update();
        }),
    );
    window.addEventListener("pagehide", () =>
        objectUrls.forEach((url) => URL.revokeObjectURL(url)),
    );
    update();
})();
