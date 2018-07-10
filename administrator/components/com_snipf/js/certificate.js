//var hash = window.location.hash;
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    //var processAction = $('#process-action').val();
    var nbProcesses = $('#nb-processes').val();
    $.fn.setReadOnly();
  });


  $.fn.setReadOnly = function() {
    //Gets needed variables.
    var processState = $('#process-state').val();
    var nbProcesses = $('#nb-processes').val();

    if(processState != 'done' && nbProcesses < 2) {
      return;
    }

    // Disables fields according to the process state.

    //Fields that have to be disabled.
    var fields = ['starting_file_number', 'start_process', 'end_process', 'return_file_number', 
                  'file_receiving_date', 'reminder_date', 'amount', 'commission_date', 'outcome',
		  'commission_derogation', 'suspension_date', 'comments', 'created_by'];

    if(processState == 'running') {
      //Only the last process is editable.
      nbProcesses = nbProcesses - 1;
    }

    //Note: In case of "done" state, all process's fields have to be disabled. 

    //Disables fields.
    for(var i = 0; i < nbProcesses; i++) {
      var idNb = i + 1;
      for(var j = 0; j < fields.length; j++) {
	if($('#'+fields[j]+'_'+idNb).prop('tagName') == 'SELECT') {
	  $('#'+fields[j]+'_'+idNb).prop('disabled', true);
	  $('#'+fields[j]+'_'+idNb).trigger('liszt:updated');
	}
	else { //INPUT
	  $('#'+fields[j]+'_'+idNb).prop('readonly', true);
	  $('#'+fields[j]+'_'+idNb).addClass('readonly');
	  //Removes the calendar button and reshapes its input border.
	  $('button[id="'+fields[j]+'_'+idNb+'_btn"]').css({'visibility':'hidden','display':'none'});
	  $('#'+fields[j]+'_'+idNb).css({'border-radius':'2px'});
	}
      }
    }
  },

  $.fn.setTabColors = function() {
    //Gets needed variables.
    var processState = $('#process-state').val();
    var nbProcesses = $('#nb-processes').val();

    if(!nbProcesses) {
      return;
    }

  },

  $.fn.checkFields = function() {
    //Gets needed variables.
    var processState = $('#process-state').val();
    var nbProcesses = $('#nb-processes').val();

    if(!nbProcesses) {
      return;
    }

    var closureDate = $('#jform_closure_date').val();
    var closureReason = $('#jform_closure_reason').val();
  };
})(jQuery);

