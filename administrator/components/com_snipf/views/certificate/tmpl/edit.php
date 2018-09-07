<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

$user = JFactory::getUser();
?>

<script type="text/javascript">

//Global variable. It will be set as function in the js file.
var checkFields;

Joomla.submitbutton = function(task)
{
  //Checks the process's fields are properly set.
  if(task != 'certificate.cancel' && !checkFields()) {
    return false;
  }

  //Allows the last process's deletion without taking in account the required fields.
  if(task == 'certificate.cancel' || task == 'certificate.process.delete.<?php echo $this->item->nb_processes; ?>' 
     || document.formvalidator.isValid(document.getElementById('certificate-form'))) {
    Joomla.submitform(task, document.getElementById('certificate-form'));
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_snipf&view=certificate&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="certificate-form" enctype="multipart/form-data" class="form-validate">

  <div class="form-inline form-inline-header">
    <?php
          if($this->form->getValue('id') == 0) {
	    $this->form->setValue('number', null, JText::_('COM_SNIPF_STATUS_PENDING'));
	  }

	  echo $this->form->getControlGroup('number');
      ?>
  </div>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_SNIPF_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span6">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('person_id');
		  $states = array('done', 'outdated', 'running', 'current_outdated', 'file_pending',
				  'commission_pending', 'overlap', 'rejected_file', 'rejected_overlap');
		  //Shows or hides the fields relating to the certificate closure according
		  //to the process state.
		  if($this->item->id && in_array($this->certificateState, $states)) {
		    echo $this->form->getControlGroup('closure_date');
		    echo $this->form->getControlGroup('closure_reason');
		    echo $this->form->getControlGroup('abandon_code');
		    echo $this->form->getControlGroup('file_destruction_date');
		  }
	      ?>
	  </div>
	</div>
	<div class="span6">
	  <div class="form-vertical">
	  <?php echo JLayoutHelper::render('joomla.edit.global', $this); 

	      //Those fields are only available once the file is accepted.
	      if(isset($this->item->processes[0]) && ($this->item->nb_processes > 1 || $this->item->processes[0]->outcome == 'accepted')) {
		echo $this->form->getControlGroup('bit_number_1988');
		echo $this->form->getControlGroup('bit_number_2008');
		echo $this->form->getControlGroup('speciality_id');
		echo $this->form->getControlGroup('complement_1');
		echo $this->form->getControlGroup('complement_2');
		echo $this->form->getControlGroup('comments');
	      }
	  ?>
	</div>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>


      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
      <div class="row-fluid form-horizontal-desktop">
	<div class="span6">
	  <?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
	</div>
	<div class="span6">
	  <?php echo JLayoutHelper::render('joomla.edit.metadata', $this); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo $this->loadTemplate('processes'); ?>

  </div>

  <input type="hidden" name="task" value="" />
  <input type="hidden" name="nb_processes" id="nb-processes" value="<?php echo $this->item->nb_processes; ?>" />
  <input type="hidden" name="certificate_state" id="certificate-state" value="<?php echo $this->certificateState; ?>" />
  <input type="hidden" name="person_status" id="person-status" value="<?php echo $this->item->person_status; ?>" />
  <input type="hidden" name="is_root" id="is-root" value="<?php echo (int)$user->get('isRoot'); ?>" />

  <?php //Sets the last action name (if any) regarding processes (ie: create or delete). 
	if($action = JFactory::getApplication()->input->get('process', '', 'string')) : ?>
	  <input type="hidden" name="process_action" id="process-action" value="<?php echo $action; ?>" />
  <?php endif; ?>

      <?php echo $this->form->getInput('item_type'); ?>
  <?php echo JHtml::_('form.token'); ?>
</form>

<?php
$doc = JFactory::getDocument();
//Load the jQuery script.
$doc->addScript(JURI::base().'components/com_snipf/js/process.js');
$doc->addScript(JURI::base().'components/com_snipf/js/certificate.js');

