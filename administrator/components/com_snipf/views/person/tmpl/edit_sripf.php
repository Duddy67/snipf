<?php 
      echo JHtml::_('bootstrap.addTab', 'myTab', 'sripf', JText::_('COM_SNIPF_TAB_SRIPF')); 

      $fieldsets = $this->form->getFieldsets();
      foreach($fieldsets as $fieldsetName => $fieldset) {
	if($fieldsetName == 'sripf') {
	  $fieldset = $this->form->getFieldset($fieldsetName);

	  foreach($fieldset as $field) {
	    if($this->item->sripf) {
	      //Gets the field name without the _sripf suffix.
	      preg_match('#^([a-z0-9_]+)_sripf$#', $field->getAttribute('name'), $matches);
	      $name = $matches[1];
	      $field->setValue($this->item->sripf->$name);
	    }

	    echo $field->getControlGroup();
	  }
	}
      }

      echo JHtml::_('bootstrap.endTab'); 
?>


