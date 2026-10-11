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

    // Render markdown to sanitized HTML. The source is the element's text, which Twig escaped.
    const renderMarkdown = (source) => DOMPurify.sanitize(marked.parse(source));

    document.querySelectorAll('.markdown').forEach((element) => {
        element.innerHTML = renderMarkdown(element.textContent);
    });

    // Event popovers show the rendered description
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach((element) => {
        const content = element.getAttribute('data-bs-content');
        if (content !== null) {
            element.setAttribute('data-bs-content', renderMarkdown(content));
        }
        new bootstrap.Popover(element);
    });

    // Make days out of month gray
    document.querySelectorAll('.phpc-shadow a').forEach((a) => a.classList.add('text-secondary'));

    // Highlight today
    document.querySelectorAll('.phpc-today').forEach((day) => {
        day.classList.add('bg-light');
        day.querySelectorAll('.phpc-day-number').forEach((n) => n.classList.add('bg-primary', 'rounded', 'text-white'));
    });
});

// Inside an embedded calendar (see embed.js): keep calendar links embedded, open everything else in a
// new tab, and tell the host page our height
if (document.body && document.body.dataset.embed === '1') {
    // Must match EmbedSubscriber::EMBEDDABLE_ROUTES
    const embeddable = /^\/[^/]+\/(calendar\/[a-z0-9-]+\/((month|week|day)\/[\d\/]+|search)?|event\/\d+)$/;
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
