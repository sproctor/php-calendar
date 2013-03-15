/*
From an answer http://stackoverflow.com/a/9975260 to a StackOverflow topic
http://stackoverflow.com/questions/2613632/jquery-ui-themes-and-html-tables
*/

(function ($) {
    $.fn.styleTable = function (options) {
        var defaults = {
            css: 'ui-styled-table'
        };
        options = $.extend(defaults, options);

        return this.each(function () {
            $this = $(this);
            $this.addClass(options.css);

            $this.on('mouseover mouseout', 'tbody tr td div', function (event) { /*.children()*/
                $(this).toggleClass("ui-state-hover",
                                               event.type == 'mouseover');
            });

            $this.find("th").addClass("ui-state-default");
            $this.find("td").addClass("ui-widget-content");
            $this.find("tr:last-child").addClass("last-child");
        });
    };
})(jQuery);


    $(document).ready(function () {
        $("table.phpc-main").styleTable();
    });