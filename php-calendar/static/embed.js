function _resize() {
    var height = $(document.documentElement).height();
    console.log("height: " + height);
    // Backwards . send message to parent
    window.parent.postMessage(['setHeight', height], '*');
}

console.log("loading page: " + document.URL);
var resize = _.debounce(_resize, 50);

$(document).ready(function() {
  resize();
  $('a').each(function() {
    if (this.href.indexOf("?") !== -1) {
      this.href += "&content=embed";
    } else {
      this.href += "?content=embed";
    }
  });
  $('form').each(function () {
    var input = $("<input>").attr("type", "hidden").attr("name", "content").val("embed");
    $(this).append($(input));
  });
});

