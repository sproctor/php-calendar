var activeRequest = null;
var cache = new Object;
 
$(document).ready(function(){
  // Add theme to appropriate items
  // All widgets
  $(".phpc-event-list a, .phpc-message, .phpc-date, .phpc-bar, .php-calendar h1, #phpc-summary-view, .phpc-logged, .php-calendar td, .php-calendar th, .phpc-message, .phpc-callist, .phpc-dropdown-list ul").addClass("ui-widget");
  // Buttons
  $(".phpc-add").button({
      text: false,
      icons: { primary: "ui-icon-plus" }
    });
  $(".php-calendar input[type=submit], .php-calendar tfoot a, .phpc-button").button();
  // The buttons are too hard to read waiting on:
  //    http://wiki.jqueryui.com/w/page/12137730/Checkbox
  // $(".php-calendar input[type=checkbox] + label").prev().button();
  $(".phpc-date, .phpc-event-list a, .phpc-calendar th.ui-state-default").on('mouseover mouseout',
      function (event) {
        $(this).toggleClass("ui-state-hover");
      });
  $(".phpc-date .phpc-add").on('mouseover mouseout',
      function (event) {
        $(this).parent(".phpc-date").toggleClass("ui-state-hover");
      });
  // fancy corners
  $(".phpc-event-list a, .phpc-message, .phpc-bar, .php-calendar h1, #phpc-summary-view, .phpc-logged, .phpc-dropdown-list ul").addClass("ui-corner-all");
  // add jquery ui style classes
  $(".php-calendar th, .phpc-callist").addClass("ui-widget-header");
  $(".php-calendar td, #phpc-summary-view, .phpc-dropdown-list ul").addClass("ui-widget-content");
  $(".phpc-event-list a, .phpc-message").addClass("ui-state-default");
  // Tabs
  $(".phpc-tabs").tabs();

  // Summary init
  $("#phpc-summary-view").hide();
  $(".phpc-event-list a").hoverIntent(
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
  $(".form-color-input").jPicker({
      window: {
        position: {
          x: 'screenCenter',
          y: 0 
        }
      },
      images: {
        clientPath: imagePath
      }
    });

  // Dropdown list stuff
  $(".phpc-dropdown-list").each(function(index, elem) {
    var titleElement = $(elem).children(".phpc-dropdown-list-header");
    var listElement = $(elem).children("ul");
    $(document).mouseup(function(e) {
      var container = $(elem);

      if (!container.is(e.target) // if the target of the click isn't the container...
        && container.has(e.target).length === 0) // ... nor a descendant of the container
      {
        listElement.hide();
      }
    });
    var positionList = function() {
        listElement.css("left", titleElement.offset().left);
        listElement.css("top", titleElement.offset().top +
		titleElement.outerHeight());
        listElement.css("min-width", titleElement.outerWidth());
    }
    var button = $("<a>")
      .appendTo(titleElement)
      .addClass("phpc-dropdown-list-button ui-icon ui-icon-circle-triangle-s")
      .click(function() {
        $(window).resize(positionList);
        positionList();
        listElement.toggle();
      });

    listElement.hide();
  });

  // Calendar specific/hacky stuff
  if($("#phpc-modify").length > 0 && !$("#phpc-modify").prop("checked"))
    toggle_when(false);

  $("#phpc-modify").click(function() {
      toggle_when($(this).prop("checked"));
    });

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

});

function toggle_when(on) {
  $('.phpc-when input[name!="phpc-modify"], .phpc-when select').prop('disabled', !on);
}

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
