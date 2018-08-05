
<?php foreach($this->item->processes as $key => $process) : 
        echo JHtml::_('bootstrap.addTab', 'myTab', 'process-'.$process->number, $process->name); 
	$fieldset = $this->processForm->getFieldset('process');
?>

      <div class="span6">
	<div class="form-vertical"> 
<?php

	foreach($fieldset as $field) {
	  $name = $field->getAttribute('name');

	  if($name == 'created_by') { //Displays fields in 2 columns.
	    echo '</div></div><div class="span4"><div class="form-vertical">'; 
	  }

	  //Number the name and the id of the field for each process.
	  $field->__set('name', $name.'_'.$process->number);
	  $field->__set('id', $name.'_'.$process->number);

	  $value = $process->$name;
	  //Empties the possible zero SQL date or datetime.
	  if($field->getAttribute('type') == 'calendar' && preg_match('#^0000-00-00#', $process->$name)) {
	    $value = '';
	  }

	  //Sets the process field value.
	  $field->setValue($value);

	  echo $field->getControlGroup();
	}

        if($this->item->nb_processes == $process->number) : //Only the last process can be deleted. ?>
	  <div id="btn-delete">
	    <a class="btn btn-warning" id="btn-delete-subscription-<?php echo $process->number; ?>" href="#">
	    <?php echo JText::_('COM_SNIPF_DELETE_BUTTON'); ?></a>
	  </div>
     <?php endif; ?>

	</div>
      </div>

<?php   echo JHtml::_('bootstrap.endTab'); 
      endforeach; ?>


