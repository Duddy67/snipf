
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    //Set as function the global variable previously declared edit.php file.
    checkFields = $.fn.checkFields;

    $.fn.setReadOnly();
    $.fn.setTabColors();
  });


  $.fn.setReadOnly = function() {
    //Gets needed variables.
    var nbProcesses = $('#nb-processes').val();
    var processAction = $('#process-action').val();
    var subscriptionId = $('#jform_id').val();

    if(subscriptionId != 0) {
      //Once a person is picked and the item is saved, it's no longer possible to change
      //the person through the picker. 
      var selectBtn = $('#jform_person_id_name').next();
      selectBtn.css({'visibility':'hidden','display':'none'});
      $('#jform_person_id_clear').css({'visibility':'hidden','display':'none'});
      $('#jform_person_id_name').css({'border-radius':'2px', 'width':'206px'});
    }

    if(nbProcesses == 0) {
      return;
    }

    for(var i = 0; i < nbProcesses; i++) {
      var idNb = i + 1;
      //The year field is editable only when a new process has been created.
      if(idNb < nbProcesses || processAction === undefined) {
	$('#year_'+idNb).prop('readonly', true);
	$('#year_'+idNb).addClass('readonly');
      }
    }
  },

  $.fn.setTabColors = function() {
    //Gets needed variables.
    var nbProcesses = $('#nb-processes').val();
    var processAction = $('#process-action').val();

    if(nbProcesses == 0) {
      return;
    }

    //A process has just been created.
    if(processAction == 'create') {
      //grey
      $('[href="#process-'+nbProcesses+'"]').css({'background-color': '#bfbfbf', 'color': 'white'});
      return;
    }

    //Checks the payments.
    var headquartersPayment = $('input[name=headquarters_payment_'+nbProcesses+']:checked').val();
    var communicationPayment = $('input[name=communication_payment_'+nbProcesses+']:checked').val();
    var cadsPayment = $('input[name=cads_payment_'+nbProcesses+']:checked').val();

    if(headquartersPayment == 1 && communicationPayment == 1 && cadsPayment == 1) {
      //green
      $('[href="#process-'+nbProcesses+'"]').css({'background-color': '#6cd26b', 'color': 'white'});
    }
    else {
      //red
      $('[href="#process-'+nbProcesses+'"]').css({'background-color': '#e60000', 'color': 'white'});
    }
  },

  $.fn.checkFields = function() {
    var nbProcesses = $('#nb-processes').val();

    if(nbProcesses == 0) {
      return true;
    }

    var valid = true;
    var tab = 'details';
    var previousYear = 0;
    //Gets fields to check.
    var year = $('#year_'+nbProcesses).val();
    var id = 'year_'+nbProcesses;
    var regex = /^[1-9][0-9]{3}$/;

    //Checks that the year value is correct.
    if(!regex.test(year)) {
      tab = 'process-'+nbProcesses;
      valid = false;
    }
    else {
      if(nbProcesses > 1) {
	var penultimateProcessNb = nbProcesses - 1;
	previousYear = $('#year_'+penultimateProcessNb).val();
        //The current year cannot be lower or equal to the previous year.
	if(year <= previousYear) {
	  tab = 'process-'+nbProcesses;
	  valid = false;
	}
      }
    }

    if(!valid) {
      alert(Joomla.JText._('COM_SNIPF_WARNING_INVALID_YEAR'));
      var $itemTab = $('[data-toggle="tab"][href="#'+tab+'"]');
      $itemTab.show();
      $itemTab.tab('show');
      $('#'+id).addClass('invalid');

      return false
    }

    return true;
  };
})(jQuery);

