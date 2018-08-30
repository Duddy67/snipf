//var hash = window.location.hash;
(function($) {

  //Run a function when the page is fully loaded including graphics.
  $(window).load(function() {

    var personId = $('#jform_id').val();
    //Create a container for each item type.
    $('#position').getContainer();

    //If the item exists we need to get the data of the dynamical items.
    if(personId != 0) {
      //Gets the token's name as value.
      var token = $('#token').attr('name');
      var langTag = $('#lang-tag').val();
      //Calls the ajax function of the component global controller.
      var urlQuery = {[token]:1, 'task':'ajax', 'format':'json', 'context':'get_persons', 'person_id':personId, 'show_time':0};

      //Ajax call which get item data previously set.
      $.ajax({
	  type: 'GET', 
	  //url: '', 
	  dataType: 'json',
	  data: urlQuery,
	  //Get results as a json array.
	  success: function(results, textStatus, jqXHR) {
	    //Display warning messages sent through JResponseJson.
	    if(results.message) {
	      alert(results.message);
	    }

	    if(results.messages) {
	      Joomla.renderMessages(results.messages);
	    }

	    if(!results.success) {
	      return;
	    }

	    $.each(results.data.position, function(i, result) { $.fn.createItem('position', result); });
	  },
	  error: function(jqXHR, textStatus, errorThrown) {
	    //Display the error.
	    alert(textStatus+': '+errorThrown);
	  }
      });
    }

    //Bind some select tags to functions.
    $('#jform_mail_address_type').change( function() { $.fn.setMandatoryFields(); });
    $('[id^="btn-new-address-"]').children().click( function() { $.fn.initItemFields($(this)); });
    $('[id^="btn-new-beneficiary-"]').children().click( function() { $.fn.initItemFields($(this)); });
    $('[id^="btn-delete-address-"]').children().click( function() { $.fn.deleteItem($(this)); });
    $('[id^="btn-delete-beneficiary-"]').children().click( function() { $.fn.deleteItem($(this)); });
    $.fn.setMandatoryFields();
    $.fn.hideFields();
  });


  $.fn.createPositionItem = function(idNb, data) {
    //Creates both the position and sripf select tags.
    var selectTypes = ['position', 'sripf'];
    //Creates a tag container.
    var properties = {'id':'item-row-1-'+idNb};
    $('#position-item-'+idNb).createHTMLTag('<div>', properties, 'item-row');

    for(var i = 0; i < selectTypes.length; i++) {
      var selectType = selectTypes[i];
      var id = selectType+'_id';
      //Concatenates the name of the function to call for this select type.
      var functionName = 'get'+selectType.charAt(0).toUpperCase() + selectType.slice(1)+'s';

      //Create the "type name" label.
      properties = {'title':Joomla.JText._('COM_SNIPF_'+selectType.toUpperCase()+'_TITLE')};
      $('#item-row-1-'+idNb).createHTMLTag('<span>', properties, selectType+'-label');
      $('#item-row-1-'+idNb+' .'+selectType+'-label').text(Joomla.JText._('COM_SNIPF_'+selectType.toUpperCase()+'_LABEL'));

      //Create the select tag.
      var properties = {'name':selectType+'_id_'+idNb, 'id':selectType+'-id-'+idNb};
      $('#item-row-1-'+idNb).createHTMLTag('<select>', properties, selectType+'-select');

      //Get the items.
      var items = snipf[functionName]();
      var length = items.length;
      var options = '<option value="">'+Joomla.JText._('COM_SNIPF_OPTION_SELECT')+'</option>';

      //Create an option tag for each item.
      for(var j = 0; j < length; j++) {
	options += '<option value="'+items[j].id+'">'+items[j].text+'</option>';
      }

      //Add the item options to the select tag.
      $('#'+selectType+'-id-'+idNb).html(options);

      if(data[id] !== '') {
	//Set the selected option.
	$('#'+selectType+'-id-'+idNb+' option[value="'+data[id]+'"]').attr('selected', true);
      }

      //Use Chosen jQuery plugin.
      $('#'+selectType+'-id-'+idNb).trigger('liszt:updated');
      $('#'+selectType+'-id-'+idNb).chosen();
    }

    //Creates both the start date and end date fields.
    var dateTypes = ['start', 'end'];
    //Creates a tag container.
    properties = {'id':'item-row-2-'+idNb};
    $('#position-item-'+idNb).createHTMLTag('<div>', properties, 'item-row');

    for(var i = 0; i < dateTypes.length; i++) {
      var dateType = dateTypes[i];
      var date_data = dateType+'_date';

      //Create the "type name" label.
      properties = {'title':Joomla.JText._('COM_SNIPF_'+dateType.toUpperCase()+'_DATE_TITLE')};
      $('#item-row-2-'+idNb).createHTMLTag('<span>', properties, dateType+'-date-label');
      $('#item-row-2-'+idNb+' .'+dateType+'-date-label').text(Joomla.JText._('COM_SNIPF_'+dateType.toUpperCase()+'_DATE_LABEL'));

      //A parent wraping div with a "field-calendar" class is required to get the calendar working.
      properties = {'id':'field-calendar-'+dateType+'-'+idNb};
      $('#item-row-2-'+idNb).createHTMLTag('<div>', properties, 'field-calendar');

      //Another wraping div with a "input-append" class allows to get the proper css for
      //the input and button calendar.
      properties = {'id':'input-append-'+dateType+'-'+idNb};
      $('#field-calendar-'+dateType+'-'+idNb).createHTMLTag('<div>', properties, 'input-append');

      properties = {'type':'text', 'name':dateType+'_date_'+idNb, 'id':dateType+'-date-'+idNb,
		    'value':data[date_data], 'data-alt-value':data[date_data], 'autocomplete':'off'};

      $('#input-append-'+dateType+'-'+idNb).createHTMLTag('<input>', properties, 'input-append date-item');

      //Create the calendar button.
      properties = {'type':'button', 'id':'button-'+dateType+'-date-'+idNb, 'data-weekend':'0,6'};
      $('#input-append-'+dateType+'-'+idNb).createHTMLTag('<button>', properties, 'btn btn-secondary');
      properties = {};
      $('#button-'+dateType+'-date-'+idNb).createHTMLTag('<span>', properties, 'icon-calendar');

      //Set the Joomla calendar to the new date item.
      Calendar.setup({
	  // Id of the input field
	  inputField: dateType+'-date-'+idNb,
	  // Format of the input field
	  ifFormat: snipf.getDateFormat(),
	  // Trigger for the calendar (button ID)
	  button: 'button-'+dateType+'-date-'+idNb,
	  // Alignment (defaults to "Bl")
	  align: 'Tl',
	  showsTime: false,
	  singleClick: true,
	  firstDay: 0
      });
    }

    //Creates a tag container.
    properties = {'id':'item-row-3-'+idNb};
    $('#position-item-'+idNb).createHTMLTag('<div>', properties, 'item-row');

    //Create the "comments" label.
    properties = {'title':Joomla.JText._('COM_SNIPF_COMMENTS_TITLE')};
    $('#item-row-3-'+idNb).createHTMLTag('<span>', properties, 'comments-label');
    $('#item-row-3-'+idNb+' .comments-label').text(Joomla.JText._('COM_SNIPF_COMMENTS_LABEL'));

    //Note: textarea tag is specific, so it is created manually.
    var textArea = $('<textarea name="position_comments_'+idNb+'" id="position-comments-'+idNb+'" class="comments-item">'); 
    textArea.text(data.position_comments); 
    $('#item-row-3-'+idNb).append(textArea);

    //Create the item removal button.
    $('#position-item-'+idNb).createButton('remove');
  };


  $.fn.setMandatoryFields = function() {
    var fields = ['street', 'city', 'postcode', 'country_code', 'sripf_id', 'employer_name'];
    //Gets the selected address type for sending mail to.
    var mailAddressType = $('#jform_mail_address_type').val();

    //Reset all the mandatory attributes.
    for(var i = 0; i < fields.length; i++) {
      $('#jform_'+fields[i]+'_ha').removeAttr('required');
      $('#jform_'+fields[i]+'_pa').removeAttr('required');
      $('#jform_'+fields[i]+'_ha').removeClass('required');
      $('#jform_'+fields[i]+'_pa').removeClass('required');
      $('#jform_'+fields[i]+'_ha-lbl').children().remove('.star');
      $('#jform_'+fields[i]+'_pa-lbl').children().remove('.star');
    }

    //If no address type is selected no field must be set.
    if(mailAddressType == '') {
      return;
    }

    //Sets the fields as mandatory.
    for(var i = 0; i < fields.length; i++) {
      $('#jform_'+fields[i]+'_'+mailAddressType).attr('required', 'true');
      $('#jform_'+fields[i]+'_'+mailAddressType).addClass('required');
      $('#jform_'+fields[i]+'_'+mailAddressType+'-lbl').append('<span class="star">&nbsp;*</span>');
    }
  };


  $.fn.initItemFields = function(obj) {
    var tagId = obj.parent().attr('id');
    var itemName = 'address';

    if(/address/.test(tagId)) {
      //Gets the address type from the last 2 characters of the parent tag id name.
      var itemType = tagId.slice(-2);
    } 
    else { //beneficiary 
      var itemType = 'bfc';
      if(/dbfc/.test(tagId)) {
	itemType = 'dbfc';
      }

      itemName = 'beneficiary';
    }


    //Empties or unselects all the address fields for the given type. 
    $('[id$="_'+itemType+'"]').each(function() {
	if($(this).prop('tagName') == 'SELECT') {
	  $('select#'+$(this).attr('id')+' option').removeAttr('selected');
	  $($(this)).trigger('liszt:updated');
	}
	else { //INPUT
	  $(this).val('');
	}
    });

    //The new address data must be inserted in database as a new row. 
    $('#jform_operation_'+itemType).val('insert');
    //Hides the new address button.
    $('#'+tagId).css({'visibility':'hidden','display':'none'});

    //Hides the delete button of the no mandatory address.
    $('#btn-delete-'+itemName+'-'+itemType).css({'visibility':'hidden','display':'none'});
  };


  $.fn.deleteItem = function(obj) {
    //Gets the required variables.
    var tagId = obj.parent().attr('id');
    var token = $('#token').attr('name');
    var personId = $('#jform_id').val();

    //Sets the name of the item to delete.
    var itemName = 'address';
    if(/beneficiary/.test(tagId)) {
      itemName = 'beneficiary';
    }

    //Calls the ajax function of the component global controller.
    var urlQuery = {[token]:1, 'task':'ajax', 'format':'json', 'context':'delete_item', 'person_id':personId, 'item_name':itemName};

    var itemType = '';

    if(tagId.match(/history/)) {
      //Extracts both the item type and the id number from the id string.
      var matches = tagId.match(/history\-([a-z]{2,})\-([0-9]+)/)
      itemType = matches[1];
      //Adds specific variables to the query.
      urlQuery.item_type = itemType;
      urlQuery.id_nb = matches[2];
      urlQuery.history = 1;
    }
    else { //Deletes the current item.
      //Extracts the item type from the id string.
      var regex = new RegExp(itemName+'\-([a-z]{2,})$');
      var matches = tagId.match(regex);
      itemType = matches[1];
      //Adds specific variables to the query.
      urlQuery.item_type = itemType;
      urlQuery.history = 0;
    }

    //Runs the ajax file.
    if(confirm(Joomla.JText._('COM_SNIPF_WARNING_DELETE_'+itemName.toUpperCase()))) {
      $.ajax({
	  type: 'GET', 
	  dataType: 'json',
	  data: urlQuery,
	  beforeSend: function(jqXHR, settings) {
	    //Displays the waiting screen all over the page.
	    $('#ajax-waiting-screen').css({'visibility':'visible','display':'block'});
	  },
	  complete: function(jqXHR, textStatus) {
	    //Removes the waiting screen after the job is done.
	    $('#ajax-waiting-screen').css({'visibility':'hidden','display':'none'});
	  },
	  //Get results as a json array.
	  success: function(results, textStatus, jqXHR) {
	    //Display warning messages sent through JResponseJson.
	    if(results.message) {
	      alert(results.message);
	    }

	    if(results.messages) {
	      Joomla.renderMessages(results.messages);
	    }

	    if(!results.success) {
	      return;
	    }

	    if(!tagId.match(/history/)) {
	      //Empties or unselects all the item fields for the given type. 
	      $('[id$="_'+itemType+'"]').each(function() {
		  if($(this).prop('tagName') == 'SELECT') {
		    $('select#'+$(this).attr('id')+' option').removeAttr('selected');
		    $($(this)).trigger('liszt:updated');
		  }
		  else { //INPUT
		    $(this).val('');
		  }
	      });

	      //Hides the New and Delete item buttons as they are irrelevant after a
	      //current item is deleted. 
	      $('#btn-new-'+itemName+'-'+itemType).css({'visibility':'hidden','display':'none'});
	      $('#btn-delete-'+itemName+'-'+itemType).css({'visibility':'hidden','display':'none'});
	    }

	    //Updates the item history.
	    $('#'+itemName+'-history-'+itemType).empty();
	    $('#'+itemName+'-history-'+itemType).html(results.data.render);
	    //Since history is refreshed the delete buttons have to be bound to the
	    //function again.
	    $('[id^="btn-delete-'+itemName+'-"]').children().click( function() { $.fn.deleteItem($(this)); });
	  },
	  error: function(jqXHR, textStatus, errorThrown) {
	    //Display the error.
	    alert(textStatus+': '+errorThrown);
	    $('#ajax-waiting-screen').css({'visibility':'hidden','display':'none'});
	  }
      });
    }
  };


  $.fn.hideFields = function() {
    //Hides some fields if the user is not a super user.
    if($('#is-root').val() != 1) {
      $('#attrib-basic').css({'visibility':'hidden','display':'none'});
      $('a[href="#attrib-basic"]').css({'visibility':'hidden','display':'none'});
      $('#permissions').css({'visibility':'hidden','display':'none'});
      $('a[href="#permissions"]').css({'visibility':'hidden','display':'none'});

      $('#jform_publish_up').parent().parent().parent().parent().css({'visibility':'hidden','display':'none'});
      $('#jform_publish_down').parent().parent().parent().parent().css({'visibility':'hidden','display':'none'});
      $('#jform_hits').parent().parent().css({'visibility':'hidden','display':'none'});
      $('#jform_metadesc').parent().parent().parent().css({'visibility':'hidden','display':'none'});
      $('#jform_published').parent().parent().css({'visibility':'hidden','display':'none'});
      $('#jform_catid').parent().parent().css({'visibility':'hidden','display':'none'});
      $('#jform_access').parent().parent().css({'visibility':'hidden','display':'none'});
      $('#jform_language').parent().parent().css({'visibility':'hidden','display':'none'});
      $('#jform_tags').parent().parent().css({'visibility':'hidden','display':'none'});
    }
  };
})(jQuery);

