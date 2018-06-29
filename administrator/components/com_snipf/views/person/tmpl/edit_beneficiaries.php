<?php 
      echo JHtml::_('bootstrap.startTabSet', 'beneficiaries', array('active' => 'beneficiary')); 

      $fieldsets = $this->form->getFieldsets();
      //var_dump($this->item->beneficiaries);
      foreach($fieldsets as $fieldsetName => $fieldset) {
	if($fieldsetName == 'bfc' || $fieldsetName == 'dbfc') {
	  $fieldset = $this->form->getFieldset($fieldsetName);

	  $beneficiaryType = $fieldsetName;
	  echo JHtml::_('bootstrap.addTab', 'beneficiaries', $fieldsetName, JText::_('COM_SNIPF_TAB_'.strtoupper($fieldsetName))); 

	  foreach($fieldset as $field) {
	    $name = $field->getAttribute('name');

	    if($this->item->beneficiaries[$beneficiaryType]) {//The beneficiary type exists.
	      //Checks for the operation field.
	      if($name == 'operation_'.$beneficiaryType) {
		//If no new beneficiary is set, the current beneficiary values will be updated when
		//saving.
		$field->setValue('update');
	      }
	      else {
		//Sets the beneficiary field value.
		$field->setValue($this->item->beneficiaries[$beneficiaryType]->$name);
	      }
	    }

	    echo $field->getControlGroup();
	  }

	  if($this->item->beneficiaries[$beneficiaryType]) { //Adds a "New" and "Delete" button if the beneficiary type exists. ?>
	    <div class="beneficiary-btn" id="btn-new-beneficiary-<?php echo $beneficiaryType; ?>">
	      <a class="btn btn-warning" href="#"><?php echo JText::_('COM_SNIPF_CHANGE_BUTTON'); ?></a>
	    </div>

	    <div class="beneficiary-btn" id="btn-delete-beneficiary-<?php echo $beneficiaryType; ?>">
	      <a class="btn btn-danger" href="#"><?php echo JText::_('COM_SNIPF_BUTTON_REMOVE_LABEL'); ?></a>
	    </div>
   <?php  } 

	  echo '<hr><div class="tab-description alert alert-warning"><h4>'.JText::_('COM_SNIPF_HISTORY_TITLE').'</h2></div>'.
	       '<div id="beneficiary-history-'.$beneficiaryType.'">';
	       
	  echo BeneficiaryHelper::renderBeneficiaryHistory($this->item->id, $beneficiaryType);
	  echo '</div>';

	  echo JHtml::_('bootstrap.endTab'); 
	}
      }

      echo JHtml::_('bootstrap.endTab'); 
?>


