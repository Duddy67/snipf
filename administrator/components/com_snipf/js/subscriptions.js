(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    $('#clear_year').click( function() { $.fn.clearYear(); });
    $('#filter_year').click( function() { $.fn.filterYear(); });

    if($('#filter_payment_status').val() != 'paid' && $('#filter_payment_status').val() != 'unpaid') {
      $('#filter_by_year').val('');
      $('#year-filter').css({'visibility':'hidden','display':'none'});
    }
  });

  $.fn.filterYear = function() {
    var sinceYear = $('#filter_by_year').val();

    if(sinceYear == '') {
      $('#filter_by_year').addClass('required invalid');
      return false;
    }

    $('#adminForm').submit();
  },

  $.fn.clearYear = function() {
    $('#filter_by_year').val('');
    $('#adminForm').submit();
  };
})(jQuery);

