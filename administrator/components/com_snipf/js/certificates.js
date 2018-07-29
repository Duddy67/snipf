(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    $('#clear_dates').click( function() { $.fn.clearDates(); });
    $('#filter_dates').click( function() { $.fn.filterDates(); });
    $('#clear_dates').click( function() { $.fn.clearDates(); });

    var fromDate = $('#filter_from_date').val();
    var toDate = $('#filter_to_date').val();

    //If only one of the date filter is filled-in after reloading the page, date filters
    //are cleared.
    if((fromDate != '' && toDate == '') || (fromDate == '' && toDate != '')) {
      $.fn.clearDates();
    }
  });

  $.fn.filterDates = function() {
    var fromDate = $('#filter_from_date').val();
    var toDate = $('#filter_to_date').val();

    if(fromDate != '' && toDate == '') {
      $('#filter_to_date').addClass('required invalid');
      return false;
    }

    if(toDate != '' && fromDate == '') {
      $('#filter_from_date').addClass('required invalid');
      return false;
    }

    if(fromDate != '' && toDate != '') {
      $('#adminForm').submit();
    }
  },

  $.fn.clearDates = function() {
    $('#filter_from_date').val('');
    $('#filter_to_date').val('');
    $('#adminForm').submit();
  };
})(jQuery);

