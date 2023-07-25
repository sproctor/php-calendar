addEventListener("DOMContentLoaded", (event) => {

    // Open tab by hash
    // See: https://stackoverflow.com/questions/69573435/twitter-bootstrap-5-tabs-go-to-specific-tab-on-page-reload-or-hyperlink
    const trigger = document.querySelector(`ul.nav button[data-bs-target="${window.location.hash}"]`)
    const tab = new bootstrap.Tab(trigger)
    tab.show()

    // Enable Bootstrap 5.x popovers
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl))

    // Silence marked warnings
    marked.use({
        mangle: false,
        headerIds: false
    });
    // Markdown descriptions
    $('.markdown').html(function () {
        return marked.parse($(this).html())
    });
    $('.phpc-occurrence').attr('data-bs-content', function () {
        return marked.parse($(this).attr('data-bs-content'))
    });

    // Make days out of month gray
    $('.phpc-shadow a').addClass('text-secondary');

    // Highlight today
    $('.phpc-today').addClass('bg-light');
    $('.phpc-today .phpc-day-number').addClass('bg-primary rounded text-white');
});