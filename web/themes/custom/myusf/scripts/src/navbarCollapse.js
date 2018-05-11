$( document ).ready(function() {
  if ($(window).width() >= 575){  
    $( "#submenuButton" ).remove();
    $("#CollapsingNavbar").addClass("show");
  } 
  if ($(window).width() < 575){  
    $("#CollapsingNavbar").remove("show");
  } 
  $(window).resize(function(){
    if ($(window).width() >= 575){  
      $( "#submenuButton" ).remove();
      $("#CollapsingNavbar").addClass("show");
    } 
    if ($(window).width() < 575){  
      $("#CollapsingNavbar").remove("show");
    } 
  });
});