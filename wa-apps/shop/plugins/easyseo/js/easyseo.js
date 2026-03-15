$(function() {
  // hide
  $(".js_hide_element").parent().parent().hide();
  // sync state to inputs
  if(!$( ".toggle_home" ).is(":checked")){
    $(".home_toggle").parent().parent().toggle();
  }
  if(!$( ".toggle_category" ).is(":checked")){
    $(".category_toggle").parent().parent().toggle();
  }
  if(!$( ".toggle_product" ).is(":checked")){
    $(".product_toggle").parent().parent().toggle();
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
  $( ".toggle_category" ).on( "click", function() {
    $(".category_toggle").parent().parent().toggle();
  } );
  $( ".toggle_product" ).on( "click", function() {
    $(".product_toggle").parent().parent().toggle();
  } );
  $( ".toggle_brand" ).on( "click", function() {
    $(".brand_toggle").parent().parent().toggle();
  } );
  $( ".toggle_hint" ).on( "click", function() {
    $(".hint_toggle").parent().parent().toggle();
  } );
});