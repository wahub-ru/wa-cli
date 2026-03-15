$(function() {
  // hide
  $(".js_hide_element").parent().parent().hide();
  // sync state to inputs
  if(!$( ".toggle_home" ).is(":checked")){
    $(".home_toggle").parent().parent().toggle();
  }
  if(!$( ".toggle_album" ).is(":checked")){
    $(".album_toggle").parent().parent().toggle();
  }
  if(!$( ".toggle_image" ).is(":checked")){
    $(".image_toggle").parent().parent().toggle();
  }
  if(!$( ".toggle_brand" ).is(":checked")){
    $(".brand_toggle").parent().parent().toggle();
  }
  if(!$( ".toggle_hint" ).is(":checked")){
    $(".hint_toggle").parent().parent().toggle();
  }
  // events
  $( ".toggle_home" ).on( "click", function() {
    $(".home_toggle").parent().parent().toggle();
  } );
  $( ".toggle_album" ).on( "click", function() {
    $(".album_toggle").parent().parent().toggle();
  } );
  $( ".toggle_image" ).on( "click", function() {
    $(".image_toggle").parent().parent().toggle();
  } );
  $( ".toggle_brand" ).on( "click", function() {
    $(".brand_toggle").parent().parent().toggle();
  } );
  $( ".toggle_hint" ).on( "click", function() {
    $(".hint_toggle").parent().parent().toggle();
  } );
});