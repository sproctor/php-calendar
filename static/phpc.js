var activeRequest = null;
var cache = new Object;
 
$(document).ready(function(){

  // Tabs - Persistence reference: http://stackoverflow.com/questions/19539547/maintaining-jquery-ui-previous-active-tab-before-reload-on-page-reload
  /*
  var currentTabId = "0";
  $tab = $(".phpc-tabs").tabs({
      activate: function (e, ui) {
          currentTabId = ui.newPanel.attr("id");
          sessionStorage.setItem("phpc-tab-index", currentTabId);
      }
  });
  var haveTabs = false;
  $(".phpc-tabs").each (function () {
    haveTabs = true;
    if (sessionStorage.getItem("phpc-tab-index") != null) {
      currentTabId = sessionStorage.getItem("phpc-tab-index");
      var index = $(this).find('a[href="#' + currentTabId + '"]').parent().index();
      if (index > 0)
        $tab.tabs('option', 'active', index);
    }
  });
  if (!haveTabs) {
    sessionStorage.removeItem("phpc-tab-index");
  }
  */

  // Summary init
  $('[data-toggle="popover"]').popover();

  // Enable confirmation dialogues
  $('[data-toggle=confirmation]').click(function(e) {
    var title = $(this).attr('data-title');
    var href = $(this).attr('href');
    var content = $(this).attr('data-content');
    var okButtonTxt = $(this).attr('data-button-text');
    var confirmModal = 
      $('<div class="modal fade">' +    
          '<div class="modal-dialog" role="document">' +
            '<div class="modal-content">' +
              '<div class="modal-header">' +
                '<h5 class="modal-title">' + title + '</h5>' +
                '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
                  '<span aria-hidden="true">&times;</span>' +
                '</button>' +
              '</div>' +
              '<div class="modal-body">' +
                '<p>' + content + '</p>' +
              '</div>' +
              '<div class="modal-footer">' +
                '<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>' + 
                '<a href="' + href + '" class="btn btn-primary">' + okButtonTxt + '</a>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</div>');
    confirmModal.modal();
    e.preventDefault();
  });

  $('input[type=datetime]').datetimepicker({step: 15});

  // Markdown descriptions
  var md = new Remarkable('commonmark');
  $('.markdown').html(function() { return md.render($(this).html()) });
  $('.phpc-occurrence').attr('data-content', function () { return md.render($(this).attr('data-content')) });

  // Make days out of month gray
  $('.phpc-shadow a').addClass('text-secondary');

  // Highlight today
  $('.phpc-today').addClass('bg-light');
  $('.phpc-today .phpc-day-number').addClass('bg-primary rounded text-white');
});