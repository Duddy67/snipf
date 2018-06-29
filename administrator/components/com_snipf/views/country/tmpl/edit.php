<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2017 - 2017 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'country.cancel' || document.formvalidator.isValid(document.getElementById('country-form'))) {
    Joomla.submitform(task, document.getElementById('country-form'));
  }
}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_snipf&view=country&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="country-form" enctype="multipart/form-data" class="form-validate">

  <?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_SNIPF_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span4">
	  <div class="form-vertical">
	    <?php
		  echo $this->form->getControlGroup('numerical');
		  echo $this->form->getControlGroup('alpha_2');
		  echo $this->form->getControlGroup('alpha_3');
		  echo $this->form->getControlGroup('continent_code');
		  echo $this->form->getControlGroup('lang_var');
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

  </div>

  <input type="hidden" name="task" value="" />
  <?php echo JHtml::_('form.token'); ?>
</form>

