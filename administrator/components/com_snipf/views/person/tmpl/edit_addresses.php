<?php 
      $fieldsets = $this->form->getFieldsets();
      //var_dump($this->item->addresses);
      foreach($fieldsets as $fieldsetName => $fieldset) {
	if($fieldsetName == 'ha' || $fieldsetName == 'pa') {
	  $fieldset = $this->form->getFieldset($fieldsetName);
	  echo JHtml::_('bootstrap.addTab', 'myTab', $fieldsetName, JText::_('COM_SNIPF_TAB_'.strtoupper($fieldsetName))); 

	  //Removes the "_address" string from the fieldset name in order to get the address type.
	  //$addressType = substr($fieldsetName, 0, -8);
	  $addressType = $fieldsetName;

	  foreach($fieldset as $field) {
	    $name = $field->getAttribute('name');

	    if($this->item->addresses[$addressType]) {
	      if($name == 'operation_'.$addressType) {
		//If no new address is set, the current address values will be updated when
		//saving.
		$field->setValue('update');
	      }
	      else {
		//Sets the address field value.
		$field->setValue($this->item->addresses[$addressType]->$name);
	      }
	    }

	    echo $field->getControlGroup();
	  }

	  if($this->item->addresses[$addressType]) { //Adds a "New address" button if the address type exists. ?>
	  <div class="address-btn" id="btn-new-address-<?php echo $addressType; ?>">
	    <a class="btn btn-warning" href="#"><?php echo JText::_('COM_SNIPF_NEW_ADDRESS_BUTTON'); ?></a>
	  </div>

	  <?php //Adds a delete button if the professional address is optional.
		if($addressType == 'pa' && $this->item->mail_address_type != 'pa') {  ?>
	    <div class="address-btn" id="btn-delete-address-<?php echo $addressType; ?>">
	      <a class="btn btn-danger" href="#"><?php echo JText::_('COM_SNIPF_BUTTON_REMOVE_LABEL'); ?></a>
	    </div>
   <?php    }
	  } 

	  echo '<hr><div class="tab-description alert alert-warning"><h4>'.JText::_('COM_SNIPF_HISTORY_TITLE').'</h2></div>'.
	       '<div id="address-history-'.$addressType.'">';
	       
	  echo AddressHelper::renderAddressHistory($this->item->id, $addressType);
	  echo '</div>';

	  echo JHtml::_('bootstrap.endTab'); 
	}
      }
?>


