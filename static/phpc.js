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
  $("#phpc-summary-view").hide();
  $(".phpc-event-list li").mouseenter(function() {
    showSummary(this, $(this).find("a").attr("href"));
  }).mouseleave(function() {
    hideSummary();
  });

  $('input[type=datetime]').datetimepicker({step: 15});

});


// return 0 on equal 1 on prefix1 > prefix, -1 on prefix1 < prefix2
function compareDates(prefix1, prefix2) {
  var year1 = parseInt($("#" + prefix1 + "-year").val());
  var year2 = parseInt($("#" + prefix2 + "-year").val());
  if(year1 > year2)
    return 1;
  if(year1 < year2)
    return -1;

  var month1 = parseInt($("#" + prefix1 + "-month").val());
  var month2 = parseInt($("#" + prefix2 + "-month").val());
  if(month1 > month2)
    return 1;
  if(month1 < month2)
    return -1;

  var day1 = parseInt($("#" + prefix1 + "-day").val());
  var day2 = parseInt($("#" + prefix2 + "-day").val());
  if(day1 > day2)
    return 1;
  if(day1 < day2)
    return -1;

  return 0;
}

function copyDate(date1, date2) {
  $("#" + date2 + "-year").val($("#" + date1 + "-year").val());
  $("#" + date2 + "-month").val($("#" + date1 + "-month").val());
  $("#" + date2 + "-day").val($("#" + date1 + "-day").val());
}

// sets he specified text in the floating div
function setSummaryText(title,author,time,description,category) {
	$("#phpc-summary-title").html(title);
	$("#phpc-summary-author").html(author);
	$("#phpc-summary-time").html(time);
	$("#phpc-summary-body").html(description);
	$("#phpc-summary-category").html(category);
}
 
// set the location of the div relative to the current link and display it
function showSummaryDiv(elem) {
	
	var div = $("#phpc-summary-view");
	var newTop = $(elem).offset().top + $(elem).innerHeight();

	$(elem).append(div);

	if(newTop + div.outerHeight()
			> $(window).height() + $(window).scrollTop())
		newTop -= $(elem).outerHeight() + div.outerHeight();
	
	var newLeft = $(elem).offset().left - ((div.outerWidth()
			- $(elem).outerWidth()) / 2)
	if(newLeft < 1)
		newLeft = 1;
	else if(newLeft + div.outerWidth() > $(window).width()) 
		newLeft -= (newLeft + div.outerWidth()) - $(window).width();

	div.css("top", newTop + "px");
	div.css("left", newLeft + "px");
	div.show();
}
 
// shows the summary for a particular anchor's url. This will display cached data after the first request
function showSummary(elem, href) {
	if( cache[href] != null ) {
		var data = cache[href];
		setSummaryText(data.title,data.author,data.time,data.body,
				data.category);
		showSummaryDiv(elem);
	}
	else {
		// abort any pending requests
		if( activeRequest != null )
			activeRequest.abort();
		
		// get the calendar data
		activeRequest = $.getJSON(href + "&content=json",
			function(data) {
				cache[href] = data;
				setSummaryText(data.title,data.author,data.time,
					data.body,data.category);
				showSummaryDiv(elem);
				activeRequest = null;
			});
	}	
}
 
// hides the event summary information
function hideSummary() {
	// abort any pending requests
	if( activeRequest != null )
		activeRequest.abort();

	$("#phpc-summary-view").hide();
	setSummaryText('','','','','');
}
