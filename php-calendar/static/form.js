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
};

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
};

$(document).ready(function(){
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


