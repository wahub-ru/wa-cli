$(function() {
  // hide
  $(".js_hide_element").parent().parent().hide();
  // sync state to inputs
  if(!$( ".can_process_get_param" ).is(":checked")){
    $(".js_can_process_get_param").parent().parent().toggle();
  }
  if(!$( ".can_process_get_pages" ).is(":checked")){
    $(".js_can_process_get_pages").parent().parent().toggle();
  }
  if(!$( ".can_process_static_pages" ).is(":checked")){
    $(".js_can_process_static_pages").parent().parent().toggle();
  }
  // events
  $( ".can_process_get_param" ).on( "click", function() {
    $(".js_can_process_get_param").parent().parent().toggle();
  } );
  $( ".can_process_get_pages" ).on( "click", function() {
    $(".js_can_process_get_pages").parent().parent().toggle();
  } );
  $( ".can_process_static_pages" ).on( "click", function() {
    $(".js_can_process_static_pages").parent().parent().toggle();
  } );
});
