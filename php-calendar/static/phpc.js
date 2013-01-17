var activeRequest = null;
var cache = new Object;
 
$(function() {
	$(".form-date").datepicker();
	$(".form-time").timepicker();
});

$(document).ready(function(){
  // Add space at top of page for messages
  padding = 0;
  $(".phpc-message").each(function() {
      padding += 2.25;
      $("body").css("padding-top", padding + "em");
  });

  // Summary init
  $("#phpc-summary-view").hide();
  $(".phpc-calendar li a").hoverIntent(
    function() { showSummary(this); },
    function() { hideSummary(this); });

  // Multi select stuff
  var select_id = 1;
  var options = new Array();
  var default_option = false;
  $(".phpc-multi-select").each(function() {
    var master_id = "phpc-multi-master"+select_id
    $(this).before("<select class=\"phpc-multi-master\" id=\""+master_id+"\"></select>");
    $(this).children().each(function() {
      if($(this).prop("tagName") == "OPTION") {
        var val = $(this).attr("value");
        $("#"+master_id).append("<option value=\""+val+"\">"+$(this).text()+"</option>");
        options[val] = [this];
        if($(this).attr("selected") == "selected")
          default_option = val;
      } else if($(this).prop("tagName") == "OPTGROUP") {
        var val = $(this).attr("label");
        var sub_options = new Array();
        $("#"+master_id).append("<option value=\""+val+"\">"+val+"</option>");
        $(this).children().each(function() {
          sub_options.push(this);
          if($(this).attr("selected") == "selected")
            default_option = val;
        });
        options[val] = sub_options;
      }
    });
    if(default_option !== false)
      $("#"+master_id).val(default_option);
    var select = this;
    $("#"+master_id).each(function() {
      var val = $("#"+master_id+" option:selected").attr("value");
      $(select).empty();
      for(var key in options[val]) {
        $(select).append(options[val][key]);
      }
    });
    $("#"+master_id).change(function() {
      var val = $("#"+master_id+" option:selected").attr("value");
      $(select).empty();
      for(var key in options[val]) {
        $(select).append(options[val][key]);
      }
    });
    select_id++;
  });

  // Generic form stuff
  $(".form-select").each(function(){
    formSelectUpdate($(this));
  });
  $(".form-select").change(function(){
    formSelectUpdate($(this));
  });

  // Calendar specific/hacky stuff
  $("#time-type").change(function(){
    if($(this).val() == "normal") {
      $("#start-time").show();
      $("#end-time").show();
    } else {
      $("#start-time").hide();
      $("#end-time").hide();
    }
  });

  $("#time-type").each(function(){
    if($(this).val() == "normal") {
      $("#start-time").show();
      $("#end-time").show();
    } else {
      $("#start-time").hide();
      $("#end-time").hide();
    }
  });

  var dateRelation = compareDates("start", "end");
  $("#start-date select").change(function(){
    if(dateRelation == 0) {
      copyDate("start", "end");
    } else {
      dateRelation = compareDates("start", "end");
      /*if(dateRelation > 0) {
        copyDate("start", "end");
	dateRelation = 0;
      }*/
    }
  });
  $("#end-date select").change(function(){
    dateRelation = compareDates("start", "end");
    /*if(dateRelation > 0) {
      copyDate("end", "start");
      dateRelation = 0;
    }*/
  });

  $(".form-color").click(function(){
    table = $(this).parents('.form-color-picker');
    table.find('.form-color-selected').removeClass('form-color-selected');
    selected_color = rgbToHex($(this).css("background-color"));
    table.find("input").val(selected_color);
    $(this).addClass('form-color-selected');
    tcolor = textcolor(selected_color);
    $('#text-color').val(tcolor);
    $(this).css("border-color", tcolor);
  });

  $(".form-color-selected").each(function(){
    var bordercolor = textcolor(rgbToHex($(this).css("background-color")));
    $(this).css("border-color", bordercolor);
  });
});

function formSelectUpdate(select) {
  var idPrefix = "#" + select.attr("name") + "-";
  select.children("option:not(:selected)").each(function(){
    $(idPrefix + $(this).val()).hide();
  });
  select.children("option:selected").each(function(){
    $(idPrefix + $(this).val()).show();
  });
}

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

//based on http://haacked.com/archive/2009/12/29/convert-rgb-to-hex.aspx
function rgbToHex(color) {
  if (color.substr(0, 1) == '#') {
    return color.toUpperCase();
  }
  var digits = /(.*?)RGB\((\d+), (\d+), (\d+)\)/.exec(color.toUpperCase());
  var red =  '0' + parseInt(digits[2]).toString(16);
  var green = '0' + parseInt(digits[3]).toString(16);
  var blue = '0' + parseInt(digits[4]).toString(16);
  return ('#' + red.substring(red.length-2) + green.substring(green.length-2) + blue.substring(blue.length-2)).toUpperCase();
}

function textcolor(bgcolor) {
  var red = parseInt(bgcolor.substr(1, 2),16);
  var green = parseInt(bgcolor.substr(3, 2),16);
  var blue = parseInt(bgcolor.substr(5, 2),16);
  var luminance= (red*0.3 + green*0.59 + blue*0.11);
  if (luminance <128) 
  {
    return "#FFFFFF"
  }
  else
  {
    return "#000000";
  }
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
		setSummaryText(data.title,data.author,data.time,data.body,
				data.category);
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
				setSummaryText(data.title,data.author,data.time,
					data.body,data.category);
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
	setSummaryText('','','','','');
}
