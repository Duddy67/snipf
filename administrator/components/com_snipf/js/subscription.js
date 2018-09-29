
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    //Set as function the global variable previously declared edit.php file.
    checkFields = $.fn.checkFields;

    $.fn.setReadOnly();
    $.fn.setTabColors();

    //Hides status field if the user is not a super user.
    if($('#is-root').val() != 1) {
      $('#jform_published').parent().parent().css({'visibility':'hidden','display':'none'});
    }
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
    var currentYear = $('#current-year').val();

    if(nbProcesses == 0) {
      return;
    }

    //A process has just been created.
    if(processAction == 'create') {
      //grey
      $('[href="#process-'+nbProcesses+'"]').css({'background-color': '#bfbfbf', 'color': 'white'});
      return;
    }

    //The last year registered is lower than the current year.
    if($('#year_'+nbProcesses).val() < currentYear) {
      //grey
      $('[href="#process-'+nbProcesses+'"]').css({'background-color': '#bfbfbf', 'color': 'white'});
      return;
    }

    var processNb = 0;
    //The last year registered is greater than the current year.
    if($('#year_'+nbProcesses).val() > currentYear) {
      //Searches for the process number which contains the current year.
      for(var i = 1; i < nbProcesses; i++) {
	if($('#year_'+i).val() == currentYear) {
	  processNb = i;
	  break;
	}
      }
    }
    else {
      //the current year is the last process.
      processNb = nbProcesses;
    }

    //No current year has been found.
    if(!processNb) {
      return;
    }

    for(var i = 0; i < processNb; i++) {
      var processId = i + 1;
      var headQuarters = $('input[name=headquarters_payment_'+processId+']:checked').val();
      var communication = $('input[name=communication_payment_'+processId+']:checked').val();
      var cadsPayment = $('input[name=cads_payment_'+processId+']:checked').val();

      if(headQuarters == 1 && communication == 1 && cadsPayment == 1) {
	//green
	$('[href="#process-'+processId+'"]').css({'background-color': '#6cd26b', 'color': 'white'});
      }
      else if(headQuarters == 1 || communication == 1 || cadsPayment == 1) {
	//orange
	$('[href="#process-'+processId+'"]').css({'background-color': '#ff9933', 'color': 'white'});
      }
      else {
	//red
	$('[href="#process-'+processId+'"]').css({'background-color': '#e60000', 'color': 'white'});
      }
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

