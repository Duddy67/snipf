(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    $('#clear_year').click( function() { $.fn.clearYear(); });
    $('#filter_year').click( function() { $.fn.filterYear(); });

    if($('#filter_payment_status').val() != 'unpaid') {
      $('#filter_since_year').val('');
      $('#year-filter').css({'visibility':'hidden','display':'none'});
    }
  });

  $.fn.filterYear = function() {
    var sinceYear = $('#filter_since_year').val();

    if(sinceYear == '') {
      $('#filter_since_year').addClass('required invalid');
      return false;
    }

    $('#adminForm').submit();
  },

  $.fn.clearYear = function() {
    $('#filter_since_year').val('');
    $('#adminForm').submit();
  };
})(jQuery);

