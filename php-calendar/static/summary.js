var activeRequest = null;
var cache = new Object;
 
// adds the 'onhover' behavior to all anchors inside list items inside the calendar table
$(document).ready(function() {
	$("#phpc-summary-view").hide();
	$(".phpc-calendar li a").hoverIntent(
		function() {
			showSummary(this);
		},
		function() {
			hideSummary(this);
		});
});
 
// sets he specified text in the floating div
function setSummaryText(title,time,description) {
	$("#phpc-summary-title").html(title);
	$("#phpc-summary-time").html(time);
	$("#phpc-summary-body").html(description);
}
 
// set the location of the div relative to the current link and display it
function showSummaryDiv(link) {
	
	var div = $("#phpc-summary-view");
	var jLink = $(link);
	var win = $(window);
	var newTop = jLink.offset().top + jLink.outerHeight();
	if( newTop + div.outerHeight()  > win.height() + win.scrollTop() )
		newTop -= (jLink.outerHeight() + div.outerHeight());
	
	var newLeft = jLink.offset().left - ((div.outerWidth() - jLink.outerWidth())/2)
	if( newLeft < 1 )
		newLeft = 1;
	else if( newLeft + div.outerWidth() > win.width() ) 
		newLeft -=  (newLeft + div.outerWidth()) - win.width();

	div.css("top", newTop + "px");
	div.css("left", newLeft + "px");
	div.show();
}
 
// shows the summary for a particular anchor's url. This will display cached data after the first request
function showSummary(link) {
	if( cache[link.href] != null ) {
		var data = cache[link.href];
		setSummaryText(data.title,data.time,data.body);
		showSummaryDiv(link);
	}
	else {
		// abort any pending requests
		if( activeRequest != null )
			activeRequest.abort();
		
		// get the calendar data
		activeRequest = $.getJSON(link.href + "&contentType=json",
			function(data) {
				cache[link.href] = data;
				setSummaryText(data.title,data.time,data.body);
				showSummaryDiv(link);
				activeRequest = null;
			});
	}	
}
 
// hides the event summary information
function hideSummary(link) {
	// abort any pending requests
	if( activeRequest != null )
		activeRequest.abort();

	$("#phpc-summary-view").hide();
	setSummaryText('','','');
}
