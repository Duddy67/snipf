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
  if(task != 'subscription.cancel' && !checkFields()) {
    return false;
  }

  if(task == 'subscription.cancel' || document.formvalidator.isValid(document.getElementById('subscription-form'))) {
    Joomla.submitform(task, document.getElementById('subscription-form'));
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_snipf&view=subscription&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="subscription-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_SNIPF_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span4">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('person_id');

		  if($this->item->id) { //Existing item.
		    echo $this->form->getControlGroup('person_status');
		    echo $this->form->getControlGroup('cqp1');

		    if($this->item->cqp1) {
		      $this->form->setValue('cqp1_extra_data', null, $this->item->extra_data_text);
		      echo $this->form->getControlGroup('cqp1_extra_data');
		    }

		    echo $this->form->getControlGroup('adhesion_date');
		    echo $this->form->getControlGroup('resignation_date');
		    echo $this->form->getControlGroup('deregistration_date');
		    echo $this->form->getControlGroup('reinstatement_date');
		  }

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
  <input type="hidden" name="nb_processes" id="nb-processes" value="<?php echo $this->item->nb_processes; ?>" />
  <input type="hidden" name="current_year" id="current-year" value="<?php echo date('Y'); ?>" />
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
$doc->addScript(JURI::base().'components/com_snipf/js/subscription.js');

