(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    $('#clear_dates').click( function() { $.fn.clearDates(); });
    $('#filter_dates').click( function() { $.fn.filterDates(); });

    var fromDate = $('#filter_from_date').val();
    var toDate = $('#filter_to_date').val();

    //If only one of the date filter is filled-in after reloading the page, date filters
    //are cleared.
    if((fromDate != '' && toDate == '') || (fromDate == '' && toDate != '')) {
      $.fn.clearDates();
    }

    //Prevent the user to type into the filter date fields.
    $('#filter_from_date').keypress(function(e) {
	return false;
      });

    $('#filter_from_date').keydown(function(e) {
	return false;
      });

    $('#filter_to_date').keypress(function(e) {
	return false;
      });

    $('#filter_to_date').keydown(function(e) {
	return false;
      });
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

