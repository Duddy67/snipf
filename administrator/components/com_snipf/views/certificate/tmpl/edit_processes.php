
<?php   $index = 'a';
	foreach($this->item->processes as $key => $process) : 

	$processName = $process->name;
	//Starts the indexing after the first process.
	if($process->number > 1) {
	  $processName = $processName.' '.$index;
	  $index++;
	}

        echo JHtml::_('bootstrap.addTab', 'myTab', 'process-'.$process->number, $processName); 
	$fieldset = $this->processForm->getFieldset('process');
?>
	<div class="span4">
	  <div class="form-vertical"> 
<?php
        //Fields relating to the commission are not shown as long as both 
	//file_receiving_date and return_file_number fields are not properly set.
	$commission = false;
        $hiddenFields = array('commission_date', 'outcome', 'commission_derogation', 'end_process', 'appeal_date', 'appeal_result');

	if($process->file_receiving_date != $this->nullDate && !empty($process->return_file_number)) {
	  $commission = true;
	}

	foreach($fieldset as $field) {
	  $name = $field->getAttribute('name');

	  if($name == 'commission_date' || $name == 'created_by') { //Displays fields in 3 columns.
	    echo '</div></div><div class="span4"><div class="form-vertical">'; 
	  }

	  if(!$commission && in_array($name, $hiddenFields)) {
	    //Turns commission fields into hidden fields.
	    echo '<input type="hidden" name="'.$name.'_'.$process->number.'" id="'.$name.'_'.$process->number.'" value="" />';
	  }
	  //Hides end_process and suspension_date as long as the outcome is not accepted.
	  elseif($commission && $process->outcome != 'accepted' && ($name == 'end_process' || $name == 'suspension_date')) {
	    echo '<input type="hidden" name="'.$name.'_'.$process->number.'" id="'.$name.'_'.$process->number.'" value="" />';
	  }
	  else {
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

	    echo $field->renderField();
	  }
	}

        if($this->item->nb_processes == $process->number && $this->certificateState != 'done') : //Only the last process can be deleted. ?>
	  <div>
	    <a class="btn btn-warning" id="btn-delete-certificate-<?php echo $process->number; ?>" href="#">
	    <?php echo JText::_('COM_SNIPF_DELETE_BUTTON'); ?></a>
	  </div>
     <?php endif; ?>

	  </div>
	</div>

<?php   echo JHtml::_('bootstrap.endTab'); 
      endforeach; ?>


