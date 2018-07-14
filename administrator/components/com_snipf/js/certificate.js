//var hash = window.location.hash;
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    //Set as function the global variable previously declared edit.php file.
    checkFields = $.fn.checkFields;

    //var processAction = $('#process-action').val();
    var nbProcesses = $('#nb-processes').val();
    $.fn.setReadOnly();
    $.fn.setTabColors();
  });


  $.fn.setReadOnly = function() {
    //Gets needed variables.
    var processState = $('#process-state').val();
    var nbProcesses = $('#nb-processes').val();

    //A single process (ie: CI) can be in readonly mode only if the process state is done.  
    if(!nbProcesses) {
      return;
    }

    // Disables fields. 

    //process fields that have to be disabled.
    var fields = ['starting_file_number', 'start_process', 'end_process', 'return_file_number', 
                  'file_receiving_date', 'reminder_date', 'amount', 'commission_date', 'outcome',
		  'commission_derogation', 'suspension_date', 'comments', 'created_by'];

    if(processState != 'done') {
      //These options are only available through the person status. 
      $('#jform_closure_reason option[value="retired"]').attr('disabled', 'disabled');
      $('#jform_closure_reason option[value="deceased"]').attr('disabled', 'disabled');
      $('#jform_closure_reason').trigger('liszt:updated');
      $('#outcome_'+nbProcesses+' option[value="canceled"]').attr('disabled', 'disabled');
      $('#outcome_'+nbProcesses).trigger('liszt:updated');

      //Only the last process is editable.
      nbProcesses = nbProcesses - 1;
    }

    //Note: In case of "done" state, all process's fields have to be disabled. 

    //Disables process fields.
    for(var i = 0; i < nbProcesses; i++) {
      var idNb = i + 1;
      for(var j = 0; j < fields.length; j++) {
	if($('#'+fields[j]+'_'+idNb).prop('tagName') == 'SELECT') {
	  //Note: The value of a disabled drop down list is not sent by the form.
	  $('#'+fields[j]+'_'+idNb).prop('disabled', true);
	  $('#'+fields[j]+'_'+idNb).trigger('liszt:updated');

	  //The field value is needed later on for comparison. 
	  $('#'+fields[j]+'_'+idNb).after('<input type="hidden" name="'+fields[j]+'_'+idNb+'" value="'+$('#'+fields[j]+'_'+idNb).val()+'">');
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

    if(processState == 'done') {
      //certificate fields that have to be disabled.
      fields = ['jform_number', 'jform_closure_date', 'jform_closure_reason', 'jform_abandon_code', 
		'jform_file_destruction_date', 'jform_bit_number_1988', 'jform_bit_number_2008',
		'jform_speciality_id', 'jform_complement_1', 'jform_complement_2', 'jform_comments'];

      for(var i = 0; i < fields.length; i++) {
	if($('#'+fields[i]).prop('tagName') == 'SELECT') {
	  //Note: The value of a disabled drop down list is not sent by the form.
	  $('#'+fields[i]).prop('disabled', true);
	  //Another approach is to disable all the options but the selected one.
	  //$('#'+fields[i]+' option:not(:selected)').prop('disabled', true);
	  $('#'+fields[i]).trigger('liszt:updated');
	}
	else { //INPUT
	  $('#'+fields[i]).prop('readonly', true);
	  $('#'+fields[i]).addClass('readonly');
	  //Removes the calendar button and reshapes its input border.
	  $('button[id="'+fields[i]+'_btn"]').css({'visibility':'hidden','display':'none'});
	  $('#'+fields[i]).css({'border-radius':'2px'});
	}
      }

      //Important: A hidden field is needed to send the closure_reason value or a warning
      //will be sent from the validate model method.  
      $('#jform_closure_reason').after('<input type="hidden" name="jform[closure_reason]" value="'+$('#jform_closure_reason').val()+'">');  
    }
  },

  $.fn.setTabColors = function() {
    //Gets needed variables.
    var processState = $('#process-state').val();
    var nbProcesses = $('#nb-processes').val();

    if(!nbProcesses) {
      return;
    }

    if(processState == 'done') {
      //Sets the color according to the closure reason.
      switch($('#jform_closure_reason').val()) {
	case 'retired': //blue
	  $('[href="#process-'+nbProcesses+'"]').css({'background-color': '#4da6ff', 'color': 'white'});
	  break;

	case 'deceased': //purple
	  $('[href="#process-'+nbProcesses+'"]').css({'background-color': '#ac00e6', 'color': 'white'});
	  break;

	default: //black - file_not_returned, file_rejected, abandon, other.
	  $('[href="#process-'+nbProcesses+'"]').css({'background-color': '#404040', 'color': 'white'});
      }

      return;
    }

    penultimateProcessNb = nbProcesses - 1;

    switch(processState) {
      case 'commission_pending': //green and orange
	$('[href="#process-'+penultimateProcessNb+'"]').css({'background-color': '#6cd26b', 'color': 'white'});
	$('[href="#process-'+nbProcesses+'"]').css({'background-color': '#ff9933', 'color': 'white'});
	break;

      case 'file_pending': //green and brown
	$('[href="#process-'+penultimateProcessNb+'"]').css({'background-color': '#6cd26b', 'color': 'white'});
	$('[href="#process-'+nbProcesses+'"]').css({'background-color': '#cc9900', 'color': 'white'});
	break;

      case 'overlap': //red and orange
	$('[href="#process-'+penultimateProcessNb+'"]').css({'background-color': '#e60000', 'color': 'white'});
	$('[href="#process-'+nbProcesses+'"]').css({'background-color': '#ff9933', 'color': 'white'});
	break;

      case 'outdated': //red and brown
	$('[href="#process-'+penultimateProcessNb+'"]').css({'background-color': '#e60000', 'color': 'white'});
	$('[href="#process-'+nbProcesses+'"]').css({'background-color': '#cc9900', 'color': 'white'});
	break;

      case 'current_outdated': //red
	$('[href="#process-'+nbProcesses+'"]').css({'background-color': '#e60000', 'color': 'white'});
	break;

      case 'running': //green
	$('[href="#process-'+nbProcesses+'"]').css({'background-color': '#6cd26b', 'color': 'white'});
	break;

      default: //grey - initial_pending, transitory_pending
	$('[href="#process-'+nbProcesses+'"]').css({'background-color': '#bfbfbf', 'color': 'white'});
    }
  },

  $.fn.checkFields = function() {
    var nbProcesses = $('#nb-processes').val();

    if(!nbProcesses) {
      return true;
    }

    var valid = true;
    var tab = 'details';
    var nullDate = '0000-00-00 00:00:00';
    //Gets fields to check.
    var closureDate = $('#jform_closure_date').val();
    var closureReason = $('#jform_closure_reason').val();
    var abandonCode = $('#jform_abandon_code').val();
    var fileReceivingDate = $('#file_receiving_date_'+nbProcesses).val();
    var returnFileNumber = $('#return_file_number_'+nbProcesses).val();
    var outcome = $('#outcome_'+nbProcesses).val();

    //Checks that closure_date and closure_reason are properly set. If one of these fields
    //is filled in the other one must to be set as well.
    if((closureDate == '' || closureDate == nullDate) && closureReason != '') {
      var id = 'jform_closure_date';
      valid = false;
    }

    //Checks the other way around.
    if(closureDate != '' && closureDate != nullDate && closureReason == '') {
      var id = 'jform_closure_reason_chzn';
      valid = false;
    }

    //In case of abandon the abandon_code field must be set.
    if(closureDate != '' && closureDate != nullDate && closureReason == 'abandon' && abandonCode == '') {
      var id = 'jform_abandon_code';
      valid = false;
    }

    //Checks that file_receiving_date and return_file_number are properly set. If one of these fields
    //is filled in the other one must to be set as well.
    if((fileReceivingDate == '' || fileReceivingDate == nullDate) && returnFileNumber != '') {
      tab = 'process-'+nbProcesses;
      var id = 'file_receiving_date_'+nbProcesses;
      valid = false;
    }

    //Checks the other way around.
    if(fileReceivingDate != '' && fileReceivingDate != nullDate && returnFileNumber == '') {
      tab = 'process-'+nbProcesses;
      var id = 'return_file_number_'+nbProcesses;
      valid = false;
    }

    if(!valid) {
      alert(Joomla.JText._('COM_SNIPF_WARNING_FIELD_EMPTY'));
      var $itemTab = $('[data-toggle="tab"][href="#'+tab+'"]');
      $itemTab.show();
      $itemTab.tab('show');
      $('#'+id).addClass('invalid');

      return false
    }

    //Handles the rejected file case.
    if(outcome == 'rejected' && (closureDate == '' || closureDate == nullDate)) {
      if(confirm(Joomla.JText._('COM_SNIPF_WARNING_REJECTED_FILE_CASE'))) {
	return true;
      }
      else {
	return false
      }
    }

    return true;
  };
})(jQuery);

