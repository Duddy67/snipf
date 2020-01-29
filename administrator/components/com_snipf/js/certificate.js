//var hash = window.location.hash;
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    //Set as function the global variable previously declared edit.php file.
    checkFields = $.fn.checkFields;

    //Into the first outcome the accepted value has to be labeled "Certifié".
    $('#outcome_1 option[value="accepted"]').text(Joomla.JText._('COM_SNIPF_OPTION_CERTIFIED'));
    $('#outcome_1').trigger('liszt:updated');

    var nbProcesses = $('#nb-processes').val();
    $.fn.setReadOnly();
    $.fn.setTabColors();

    //Hides status field if the user is not a super user.
    if($('#is-root').val() != 1) {
      $('#jform_published').parent().parent().css({'visibility':'hidden','display':'none'});
    }
  });


  $.fn.setReadOnly = function() {
    //Gets needed variables.
    var certificateState = $('#certificate-state').val();
    var nbProcesses = $('#nb-processes').val();
    var certificateId = $('#jform_id').val();

    if(certificateId != 0) {
      //Once a person is picked and the item is saved, it's no longer possible to change
      //the person through the picker. 
      var selectBtn = $('#jform_person_id_name').next();
      selectBtn.css({'visibility':'hidden','display':'none'});
      $('#jform_person_id_clear').css({'visibility':'hidden','display':'none'});
      $('#jform_person_id_name').css({'border-radius':'2px', 'width':'206px'});
    }

    if(nbProcesses == 0 || (nbProcesses == 1 && $('#end_process_1').val() == '')) {
      //As long as a certificate is not still valid (ie: has a number) the number field
      //cannot be edited. 
      $('#jform_number').prop('readonly', true);
      $('#jform_number').addClass('readonly');
    }

    //A single process (ie: CI) can be in readonly mode only if the certificate state is done.  
    if(nbProcesses == 0) {
      return;
    }

    // Disables fields. 

    //process fields that have to be disabled.
    var fields = ['starting_file_number', 'start_process', 'end_process', 'return_file_number', 
                  'file_receiving_date', 'reminder_date', 'amount', 'commission_date', 'outcome',
		  'commission_derogation', 'appeal_date', 'appeal_result', 'comments', 'created_by'];

    if(certificateState != 'done') {
      //These options are only available through the person status. 
      $('#jform_closure_reason option[value="retired"]').attr('disabled', 'disabled');
      $('#jform_closure_reason option[value="deceased"]').attr('disabled', 'disabled');
      $('#jform_closure_reason option[value="obsolete"]').attr('disabled', 'disabled');
      //Rejected is only available in a rejection case.
      $('#jform_closure_reason option[value="rejected_file"]').attr('disabled', 'disabled');
      $('#jform_closure_reason').trigger('liszt:updated');

      if(certificateState == 'rejected_file' || certificateState == 'rejected_overlap') {
	//Rejected is the only option available.
	$('#jform_closure_reason option[value="rejected_file"]').prop('disabled', false);
	$('#jform_closure_reason option[value="abandon"]').attr('disabled', 'disabled');
	$('#jform_closure_reason option[value="other"]').attr('disabled', 'disabled');
	$('#jform_closure_reason').trigger('liszt:updated');
      }

      if(certificateState == 'commission_pending' || certificateState == 'overlap' || 
	 certificateState == 'rejected_file' || certificateState == 'rejected_overlap') {
	//Removal option is not available during the commission pending or a rejection case.
	$('#jform_closure_reason option[value="removal"]').attr('disabled', 'disabled');
	$('#jform_closure_reason').trigger('liszt:updated');
      }

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

	  //Specific case.
	  if(fields[j] == 'created_by') {
	    //Hides the <a> button link just after the input element.   
	    $('#'+fields[j]+'_'+idNb).next().css({'visibility':'hidden','display':'none'});
	  }
	}
      }
    }

    if(certificateState == 'done') {
      //certificate fields that have to be disabled.
      fields = ['jform_number', 'jform_closure_date', 'jform_closure_reason', 'jform_abandon_code', 
		'jform_bit_number_1988', 'jform_bit_number_2008',
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
    var certificateState = $('#certificate-state').val();
    var nbProcesses = $('#nb-processes').val();
    var lastProcessOutcome = $('#last-process-outcome').val();
    var closureReason = $('#jform_closure_reason').val();

    if(nbProcesses == 0) {
      return;
    }

    penultimateProcessNb = nbProcesses - 1;

    if(certificateState == 'done') {
      //Sets the color according to the closure reason.

      //Treats the retired case by default.
      var color = '#4da6ff'; //blue

      if($('#jform_closure_reason').val() == 'deceased') {
	color = '#ac00e6'; //purple
      }
      //removal, rejected_file, abandon, other.
      else if($('#jform_closure_reason').val() != 'retired') {
	color = '#404040'; //black
      }

      //Colors the last tab.
      if(lastProcessOutcome == 'accepted' || (nbProcesses == 1 && (closureReason == 'abandon' || closureReason == 'rejected_file'))) {
	$('[href="#process-'+nbProcesses+'"]').css({'background-color': color, 'color': 'white'});
      }
      //Colors the penultimate tab. The last tab is colored in grey.
      else {
	$('[href="#process-'+penultimateProcessNb+'"]').css({'background-color': color, 'color': 'white'});
	$('[href="#process-'+nbProcesses+'"]').css({'background-color': '#bfbfbf', 'color': 'white'});
      }

      return;
    }

    switch(certificateState) {
      case 'commission_pending': //green and orange
	$('[href="#process-'+penultimateProcessNb+'"]').css({'background-color': '#6cd26b', 'color': 'white'});
	$('[href="#process-'+nbProcesses+'"]').css({'background-color': '#ff9933', 'color': 'white'});
	break;

      case 'file_pending': //green and brown
	$('[href="#process-'+penultimateProcessNb+'"]').css({'background-color': '#6cd26b', 'color': 'white'});
	$('[href="#process-'+nbProcesses+'"]').css({'background-color': '#cc9900', 'color': 'white'});
	break;

      case 'rejected_file': //green and dark brown
	$('[href="#process-'+penultimateProcessNb+'"]').css({'background-color': '#6cd26b', 'color': 'white'});
	$('[href="#process-'+nbProcesses+'"]').css({'background-color': '#805500', 'color': 'white'});
	break;

      case 'overlap': //red and orange
	$('[href="#process-'+penultimateProcessNb+'"]').css({'background-color': '#e60000', 'color': 'white'});
	$('[href="#process-'+nbProcesses+'"]').css({'background-color': '#ff9933', 'color': 'white'});
	break;

      case 'rejected_overlap': //red and dark brown
	$('[href="#process-'+penultimateProcessNb+'"]').css({'background-color': '#e60000', 'color': 'white'});
	$('[href="#process-'+nbProcesses+'"]').css({'background-color': '#805500', 'color': 'white'});
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

    if(nbProcesses == 0) {
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

    return true;
  };
})(jQuery);

