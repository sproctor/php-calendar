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
    marked.use({
        mangle: false,
        headerIds: false
    });
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
