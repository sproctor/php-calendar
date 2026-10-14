// In auto color mode, follow the browser's light or dark setting, also when it changes
const darkScheme = matchMedia('(prefers-color-scheme: dark)');
const applyColorMode = () => {
    const mode = document.documentElement.dataset.phpcColorMode;
    document.documentElement.dataset.bsTheme = mode === 'dark' || (mode === 'auto' && darkScheme.matches) ? 'dark' : 'light';
};
darkScheme.addEventListener('change', applyColorMode);

// Query parameters for an embedded calendar's look, besides embed=1; see EmbedOptions and embed.js
const EMBED_OPTIONS = ['header', 'bg', 'accent', 'font', 'mode', 'theme'];

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

    // Theme menus show the chosen theme on the page right away; see ThemeChoiceType
    document.querySelectorAll('[data-phpc-theme-preview]').forEach((select) => {
        const link = document.getElementById('phpc-theme');
        const root = document.documentElement;
        // The visitor's own light/dark choice, which themes designed dark override
        const chosenMode = document.cookie.match(/(?:^|; )phpc_color_mode=(light|dark)/)?.[1] ?? 'auto';
        select.addEventListener('change', () => {
            const option = select.selectedOptions[0];
            // The empty option is the site's default theme
            const data = option.value === '' ? {
                stylesheet: select.dataset.defaultStylesheet,
                stylesheetRtl: select.dataset.defaultStylesheetRtl,
                dark: select.dataset.defaultDark,
            } : option.dataset;
            link.href = (root.dir === 'rtl' && data.stylesheetRtl) || data.stylesheet;
            root.dataset.phpcColorMode = data.dark ? 'dark' : chosenMode;
            applyColorMode();
        });
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

    // The embed code builder in calendar admin: the snippet and a preview follow the chosen options
    document.querySelectorAll('.phpc-embed-builder').forEach((builder) => {
        const field = (name) => builder.querySelector(`[name="${name}"]`);
        const snippet = builder.querySelector('.phpc-embed-snippet');
        const preview = builder.querySelector('.phpc-embed-preview');
        const escape = (value) => value.replaceAll('&', '&amp;').replaceAll('"', '&quot;').replaceAll('<', '&lt;');
        let reload;
        const update = () => {
            field('bg_color').disabled = field('bg').value !== 'color';
            field('accent_color').disabled = field('accent').value !== 'color';
            // data-* attribute for embed.js, and the query parameter it becomes
            const options = [];
            if (!field('header').checked) {
                options.push(['header', 'header', '0']);
            }
            if (field('bg').value === 'transparent') {
                options.push(['background', 'bg', 'transparent']);
            } else if (field('bg').value === 'color') {
                options.push(['background', 'bg', field('bg_color').value]);
            }
            if (field('accent').value === 'color') {
                options.push(['accent', 'accent', field('accent_color').value]);
            }
            if (field('font').value.trim() !== '') {
                options.push(['font', 'font', field('font').value.trim()]);
            }
            if (field('mode').value !== 'light') {
                options.push(['mode', 'mode', field('mode').value]);
            }
            if (field('theme').value !== '') {
                options.push(['theme', 'theme', field('theme').value]);
            }
            const minHeight = parseInt(field('min_height').value, 10);
            const attributes = [`src="${escape(builder.dataset.script)}"`, `data-src="${escape(builder.dataset.calendar)}"`,
                ...options.map(([attribute, , value]) => `data-${attribute}="${escape(value)}"`)];
            if (minHeight > 0 && minHeight !== 400) {
                attributes.push(`data-min-height="${minHeight}"`);
            }
            snippet.value = `<script ${attributes.join(' ')}></script>`;

            const src = new URL(builder.dataset.calendar);
            src.searchParams.set('embed', '1');
            options.forEach(([, param, value]) => src.searchParams.set(param, value));
            preview.style.minHeight = `${minHeight > 0 ? minHeight : 400}px`;
            preview.style.colorScheme = {dark: 'dark', auto: 'light dark'}[field('mode').value] ?? 'light';
            clearTimeout(reload);
            reload = setTimeout(() => preview.src = src.toString(), 300);
        };
        builder.querySelectorAll('input, select').forEach((input) => input.addEventListener('input', update));
        addEventListener('message', (event) => {
            if (event.source === preview.contentWindow && event.data?.type === 'phpc-height'
                && Number.isFinite(event.data.height)) {
                preview.style.height = `${Math.ceil(event.data.height)}px`;
            }
        });
        update();
    });

    // Leave unchosen filters out of the address, rather than sending ?category=&author=
    document.querySelectorAll('.phpc-filter-menu').forEach((form) => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('select').forEach((select) => select.disabled = select.value === '');
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
    const embeddable = /^\/[^/]+\/((calendar|view)\/[a-z0-9-]+\/((month|day)\/[\d\/]+|search)?|event\/\d+)$/;
    // embed=1 and the look the embed code chose (see EmbedOptions), kept on every page in the frame
    const current = new URLSearchParams(location.search);
    const embedParams = ['embed', ...EMBED_OPTIONS].filter((name) => current.has(name))
        .map((name) => [name, current.get(name)]);
    document.querySelectorAll('a[href]').forEach((a) => {
        const url = new URL(a.href, location.href);
        if (url.origin !== location.origin || a.target) {
            return;
        }
        if (embeddable.test(url.pathname)) {
            embedParams.forEach(([name, value]) => url.searchParams.set(name, value));
            a.href = url.toString();
        } else {
            a.target = '_blank';
            a.rel = 'noopener';
        }
    });
    document.querySelectorAll('form[method="get" i]').forEach((form) => {
        embedParams.forEach(([name, value]) => {
            form.querySelector(`input[type="hidden"][name="${name}"]`)?.remove();
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.append(input);
        });
    });
    const postHeight = () => window.parent.postMessage(
        {type: 'phpc-height', height: document.documentElement.scrollHeight}, '*');
    new ResizeObserver(postHeight).observe(document.documentElement);
    postHeight();
}
