<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
$currentProcess = $this->item->last_process_nb;
?>

<script type="text/javascript">

Joomla.submitbutton = function(task)
{
  //Allows the last process's deletion without taking in account the required fields.
  if(task == 'certificate.cancel' || task == 'certificate.process.delete.<?php echo $this->item->last_process_nb; ?>' 
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
	<div class="span4">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('person_id');
		  echo $this->form->getControlGroup('description');
	      ?>
	  </div>
	</div>
	<div class="span3">
	  <?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
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
  <input type="hidden" name="current_process" id="current-process" value="<?php echo $currentProcess; ?>" />

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

