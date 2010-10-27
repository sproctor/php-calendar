$(document).ready(function() {
  padding = 0;
  $(".phpc-message").each(function() {
      padding += 2.25;
      $("body").css("padding-top", padding + "em");
  });
});
