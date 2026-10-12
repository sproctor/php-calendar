// In auto color mode, follow the browser's light or dark setting, also when it changes
const darkScheme = matchMedia('(prefers-color-scheme: dark)');
const applyColorMode = () => {
    const mode = document.documentElement.dataset.phpcColorMode;
    document.documentElement.dataset.bsTheme = mode === 'dark' || (mode === 'auto' && darkScheme.matches) ? 'dark' : 'light';
};
darkScheme.addEventListener('change', applyColorMode);

addEventListener("DOMContentLoaded", () => {

    // Open the tab named in the URL hash, e.g. /admin#users
    // See: https://stackoverflow.com/questions/69573435/twitter-bootstrap-5-tabs-go-to-specific-tab-on-page-reload-or-hyperlink
    if (window.location.hash) {
        const trigger = document.querySelector(
            `.nav [data-bs-toggle="tab"][href="${CSS.escape(window.location.hash)}"],`
            + ` .nav [data-bs-toggle="tab"][data-bs-target="${CSS.escape(window.location.hash)}"]`
        );
        if (trigger) {
            bootstrap.Tab.getOrCreateInstance(trigger).show();
        }
    }

    // Event previews in the month view; their content is HTML rendered on the server
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach((element) => new bootstrap.Popover(element));

    // Offer the browser's timezone when the page shows another one; see components/timezone_note.html.twig
    document.querySelectorAll('.phpc-timezone-note').forEach((note) => {
        const setTimezone = (timezone) => {
            const secure = location.protocol === 'https:' ? '; Secure' : '';
            const value = timezone === null ? '; max-age=0' : `${encodeURIComponent(timezone)}; max-age=31536000`;
            document.cookie = `phpc_timezone=${value}; path=${note.dataset.cookiePath}; SameSite=Lax${secure}`;
            location.reload();
        };
        note.querySelector('.phpc-timezone-reset')?.addEventListener('click', () => setTimezone(null));

        const local = note.querySelector('.phpc-timezone-local');
        const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        if (!local || !browserTimezone || browserTimezone === note.dataset.timezone) {
            return;
        }
        // E.g. "Central European Time (Paris)", like the server's names
        const name = new Intl.DateTimeFormat(document.documentElement.lang || undefined, {
            timeZone: browserTimezone,
            timeZoneName: 'longGeneric',
        }).formatToParts(new Date()).find((part) => part.type === 'timeZoneName')?.value;
        const city = browserTimezone.split('/').pop().replaceAll('_', ' ');
        const button = local.querySelector('button');
        button.textContent = button.dataset.label.replace('%timezone%', name ? `${name} (${city})` : browserTimezone);
        button.addEventListener('click', () => setTimezone(browserTimezone));
        local.hidden = false;
    });

    // Forms of checkboxes, e.g. to delete the selected events: the button named by data-phpc-selection
    // only works once something is selected, and a data-phpc-select-all box checks them all
    document.querySelectorAll('form[data-phpc-selection]').forEach((form) => {
        const button = document.getElementById(form.dataset.phpcSelection);
        const all = form.querySelector('[data-phpc-select-all]');
        const boxes = [...form.querySelectorAll('input[type="checkbox"]:not([data-phpc-select-all])')];
        const update = () => {
            const checked = boxes.filter((box) => box.checked).length;
            if (button) {
                button.disabled = checked === 0;
            }
            if (all) {
                all.checked = checked === boxes.length;
                all.indeterminate = checked > 0 && checked < boxes.length;
            }
        };
        boxes.forEach((box) => box.addEventListener('change', update));
        all?.addEventListener('change', () => {
            boxes.forEach((box) => box.checked = all.checked);
            update();
        });
        update();
    });

    // Light, dark or auto color mode, remembered in a cookie; base.html.twig applies it on the next page
    document.querySelectorAll('.phpc-color-mode').forEach((menu) => {
        menu.querySelectorAll('[data-mode]').forEach((item) => item.addEventListener('click', () => {
            const mode = item.dataset.mode;
            const secure = location.protocol === 'https:' ? '; Secure' : '';
            const value = mode === 'auto' ? '; max-age=0' : `${mode}; max-age=31536000`;
            document.cookie = `phpc_color_mode=${value}; path=${menu.dataset.cookiePath}; SameSite=Lax${secure}`;
            document.documentElement.dataset.phpcColorMode = mode;
            applyColorMode();
            menu.querySelectorAll('.dropdown-item').forEach((other) => {
                other.classList.toggle('active', other === item);
                other.setAttribute('aria-pressed', other === item ? 'true' : 'false');
            });
        }));
    });

    // Copy buttons: data-phpc-copy names the input whose text to copy
    document.querySelectorAll('[data-phpc-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const input = document.getElementById(button.dataset.phpcCopy);
            input.select();
            try {
                await navigator.clipboard.writeText(input.value);
            } catch {
                document.execCommand('copy');
            }
            const label = button.innerHTML;
            button.textContent = button.dataset.copiedLabel;
            setTimeout(() => button.innerHTML = label, 2000);
        });
    });

    // Make days out of month gray
    document.querySelectorAll('.phpc-shadow a').forEach((a) => a.classList.add('text-secondary'));

    // Highlight today
    document.querySelectorAll('.phpc-today').forEach((day) => {
        day.classList.add('bg-body-tertiary');
        day.querySelectorAll('.phpc-day-number').forEach((n) => n.classList.add('bg-primary', 'rounded', 'text-white'));
    });
});

// Inside an embedded calendar (see embed.js): keep calendar links embedded, open everything else in a
// new tab, and tell the host page our height
if (document.body && document.body.dataset.embed === '1') {
    // Must match EmbedSubscriber::EMBEDDABLE_ROUTES
    const embeddable = /^\/[^/]+\/(calendar\/[a-z0-9-]+\/((month|day)\/[\d\/]+|search)?|event\/\d+)$/;
    document.querySelectorAll('a[href]').forEach((a) => {
        const url = new URL(a.href, location.href);
        if (url.origin !== location.origin || a.target) {
            return;
        }
        if (embeddable.test(url.pathname)) {
            url.searchParams.set('embed', '1');
            a.href = url.toString();
        } else {
            a.target = '_blank';
            a.rel = 'noopener';
        }
    });
    document.querySelectorAll('form[method="get" i]').forEach((form) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'embed';
        input.value = '1';
        form.append(input);
    });
    const postHeight = () => window.parent.postMessage(
        {type: 'phpc-height', height: document.documentElement.scrollHeight}, '*');
    new ResizeObserver(postHeight).observe(document.documentElement);
    postHeight();
}
