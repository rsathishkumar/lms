jQuery(document).ready(function() {
  if(jQuery('.user-info').is(':visible')) {
    var name = jQuery('.user-info-name').text();
    var email = jQuery('.user-info-email').text();
    jQuery('.user-info').prepend('<p class="user-info-name">'+name+'</p><p class="user-info-email">'+email+'</p>');
  }
});