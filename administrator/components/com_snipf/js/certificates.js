//var hash = window.location.hash;
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    //Set as function the global variable previously declared edit.php file.
    checkFilterDates = $.fn.checkFilterDates;

    $('#filter_dates').click( function() { $.fn.filterDates(); });
    $('#clear_dates').click( function() { $.fn.clearDates(); });
  });


  $.fn.filterDates = function() {
    var fromDate = $('#filter_from_date').val();
    var toDate = $('#filter_to_date').val();

    if(fromDate != '' && toDate == '') {
      $('#filter_to_date').addClass('required invalid');
    }

    if(toDate != '' && fromDate == '') {
      $('#filter_from_date').addClass('required invalid');
    }

    if(fromDate != '' && toDate != '') {
      $('#adminForm').submit();
    }
  },

  $.fn.clearDates = function() {
    $('#filter_from_date').val('');
    $('#filter_to_date').val('');
    $('#adminForm').submit();
  },

  $.fn.checkFilterDates = function() {
    var fromDate = $('#filter_from_date').val();
    var toDate = $('#filter_to_date').val();

    if(fromDate != '' && toDate == '') {
      $('#filter_to_date').addClass('required invalid');
    }

    if(toDate != '' && fromDate == '') {
      $('#filter_from_date').addClass('required invalid');
    }
  };
})(jQuery);

