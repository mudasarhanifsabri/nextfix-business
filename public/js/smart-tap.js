(() => {
    const dialog = document.querySelector("#smart-tap-dialog");
    const search = dialog?.querySelector("[data-smart-tap-search]");
    const links = dialog?.querySelector("[data-smart-tap-links]");
    if (!dialog || !search || !links || typeof dialog.showModal !== "function")
        return;
    let returnFocus = null;
    const commands = new Map();
    // Only existing navigation links are surfaced. No actions or permissions are invented.
    document
        .querySelectorAll(".sidebar nav a,.mobile-nav a,.worker-nav a")
        .forEach((anchor) => {
            const url = new URL(anchor.href, location.href);
            if (
                url.origin !== location.origin ||
                !["http:", "https:"].includes(url.protocol)
            )
                return;
            const label = anchor.textContent
                .replace(/[⌂▣◇◫◉▤●⚙⌄]/g, "")
                .trim()
                .replace(/\s+/g, " ");
            if (label && !commands.has(url.href)) commands.set(url.href, label);
        });
    const render = () => {
        links.replaceChildren();
        let count = 0;
        for (const [href, label] of commands) {
            if (
                !label.toLowerCase().includes(search.value.toLowerCase().trim())
            )
                continue;
            const a = document.createElement("a");
            a.href = href;
            a.textContent = label;
            links.append(a);
            count++;
        }
        dialog.querySelector("[data-smart-tap-empty]").hidden = count !== 0;
    };
    const open = (trigger) => {
        returnFocus = trigger;
        search.value = "";
        render();
        if (!dialog.open) dialog.showModal();
        search.focus();
    };
    document
        .querySelectorAll("[data-smart-tap-open]")
        .forEach((button) =>
            button.addEventListener("click", () => open(button)),
        );
    dialog
        .querySelector("[data-smart-tap-close]")
        .addEventListener("click", () => dialog.close());
    dialog.addEventListener("close", () => returnFocus?.focus());
    search.addEventListener("input", render);
    document.addEventListener("keydown", (event) => {
        if (
            (event.ctrlKey || event.metaKey) &&
            event.key.toLowerCase() === "k"
        ) {
            event.preventDefault();
            if (dialog.open) dialog.close();
            else open(document.activeElement);
        }
    });
    // This filter searches only the currently loaded rows, never implying global search.
    document
        .querySelectorAll(".content table, .worker-app main table")
        .forEach((table, index) => {
            const bodies = Array.from(table.tBodies);
            const rows = bodies.flatMap((body) => Array.from(body.rows));
            if (rows.length < 6) return;
            const wrapper = table.closest(".table-wrap") || table;
            const label = document.createElement("label");
            label.className = "smart-table-filter";
            label.textContent = "Find in loaded rows";
            const input = document.createElement("input");
            input.type = "search";
            input.placeholder = "Filter this table…";
            input.setAttribute(
                "aria-label",
                "Find in loaded rows, table " + (index + 1),
            );
            const status = document.createElement("span");
            status.setAttribute("aria-live", "polite");
            label.append(input, status);
            wrapper.before(label);
            const originalVisibility = new Map(
                rows.map((row) => [row, row.hidden]),
            );
            input.addEventListener("input", () => {
                const term = input.value.trim().toLowerCase();
                let count = 0;
                rows.forEach((row) => {
                    const match =
                        !originalVisibility.get(row) &&
                        row.textContent.toLowerCase().includes(term);
                    row.hidden = !match;
                    if (match) count++;
                });
                status.textContent = count + " matching loaded rows";
            });
        });
    // Keep native validation and focus the relevant field without submitting anything.
    document.addEventListener(
        "invalid",
        (event) => {
            const field = event.target;
            if (!(field instanceof HTMLElement)) return;
            const form = field.closest("form");
            if (form?.querySelector(":invalid") === field) {
                let parent = field.parentElement;
                while (parent) {
                    if (parent instanceof HTMLDetailsElement)
                        parent.open = true;
                    parent = parent.parentElement;
                }
                field.scrollIntoView({ block: "center", behavior: "auto" });
            }
        },
        true,
    );
})();

// Card lists retain their existing job actions and pagination.
document.querySelectorAll(".native-job-list").forEach((list) => {
    const cards = Array.from(list.children).filter((card) =>
        card.classList.contains("native-job-card"),
    );
    if (cards.length < 3) return;
    const label = document.createElement("label");
    label.className = "smart-table-filter";
    label.textContent = "Find in loaded jobs";
    const input = document.createElement("input");
    input.type = "search";
    input.placeholder = "Unit, building or service";
    label.append(input);
    list.before(label);
    const status = document.createElement("span");
    status.setAttribute("aria-live", "polite");
    label.append(status);
    input.addEventListener("input", () => {
        let n = 0;
        cards.forEach((card) => {
            card.hidden = !card.textContent
                .toLowerCase()
                .includes(input.value.trim().toLowerCase());
            if (!card.hidden) n++;
        });
        status.textContent = n + " matching loaded jobs";
    });
});
