//var hash = window.location.hash;
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    $.fn.hideSearchTools();
  });

  $.fn.hideSearchTools = function() {
    //Hides some fields if the user is not a super user.
    if($('#is-root').val() != 1) {
      $('#filter_published').parent().css({'visibility':'hidden','display':'none'});
      $('#filter_category_id').parent().css({'visibility':'hidden','display':'none'});
      $('#filter_access').parent().css({'visibility':'hidden','display':'none'});
      $('#filter_user_id').parent().css({'visibility':'hidden','display':'none'});
    }
  };

})(jQuery);

