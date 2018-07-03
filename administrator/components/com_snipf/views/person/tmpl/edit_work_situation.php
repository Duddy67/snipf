<?php 
      echo JHtml::_('bootstrap.addTab', 'myTab', 'work_situation', JText::_('COM_SNIPF_TAB_WORK_SITUATION')); 

      $fieldsets = $this->form->getFieldsets();
      foreach($fieldsets as $fieldsetName => $fieldset) {
	if($fieldsetName == 'work_situation') {
	  $fieldset = $this->form->getFieldset($fieldsetName);

	  foreach($fieldset as $field) {
	    if($this->item->work_situation) {
	      //Gets the field name without the _ws suffix.
	      preg_match('#^([a-z0-9_]+)_ws$#', $field->getAttribute('name'), $matches);
	      $name = $matches[1];
	      $field->setValue($this->item->work_situation->$name);
	    }

	    echo $field->getControlGroup();
	  }
	}
      }

      echo JHtml::_('bootstrap.endTab'); 
?>


