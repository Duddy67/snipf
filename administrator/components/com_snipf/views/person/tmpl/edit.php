<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('behavior.formvalidator');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');
JHtml::_('behavior.modal');

//Prevent params layout (layouts/joomla/edit/params.php) to display (or display twice) some fieldsets.
$this->ignore_fieldsets = array('details', 'permissions', 'jmetadata', 'ha', 'pa', 'work_situation',
				'snipf_positions', 'bfc', 'dbfc');
$canDo = SnipfHelper::getActions($this->state->get('filter.category_id'));

//Lang tag is needed in the Ajax file.
$lang = JFactory::getLanguage();
$langTag = $lang->getTag();

$user = JFactory::getUser();
$authorisedGroups = $user->getAuthorisedGroups();
$cadsGroupId = JComponentHelper::getParams('com_snipf')->get('cads_group_id');
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
  if(task == 'person.cancel' || document.formvalidator.isValid(document.getElementById('person-form'))) {
    Joomla.submitform(task, document.getElementById('person-form'));
  }
}
</script>

<div id="ajax-waiting-screen" style="visibility: hidden;display: none;">
  <img src="../media/com_snipf/images/ajax-loader.gif" width="31" height="31" />
</div>

<form action="<?php echo JRoute::_('index.php?option=com_snipf&view=person&layout=edit&id='.(int) $this->item->id); ?>" 
 method="post" name="adminForm" id="person-form" enctype="multipart/form-data" class="form-validate">

  <div class="form-inline form-inline-header">
    <?php
	  echo $this->form->getControlGroup('lastname');
	  echo $this->form->getControlGroup('alias');
    ?>
  </div>

  <div class="form-horizontal">

    <?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'details')); ?>

    <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_SNIPF_TAB_DETAILS')); ?>

      <div class="row-fluid">
	<div class="span4 form-vertical">
	    <?php
		  echo $this->form->getControlGroup('test_field');
		  echo $this->form->getControlGroup('firstname');
		  echo $this->form->getControlGroup('maiden_name');
		  echo $this->form->getControlGroup('person_title');
		  echo $this->form->getControlGroup('birthdate');
		  echo $this->form->getControlGroup('country_of_birth');
		  echo $this->form->getControlGroup('region_of_birth');
		  echo $this->form->getControlGroup('city_of_birth');
		  echo $this->form->getControlGroup('citizenship');
		  echo $this->form->getControlGroup('cqp1');

		  if($this->item->cqp1) {
		    $this->form->setValue('cqp1_extra_data', null, $this->item->extra_data_text);
		    echo $this->form->getControlGroup('cqp1_extra_data');
		  }
	    ?>
	</div>
	<div class="span4 form-vertical">
	    <?php
		  echo $this->form->getControlGroup('status');
		  echo $this->form->getControlGroup('active_retired');
		  echo $this->form->getControlGroup('retirement_date');
		  echo $this->form->getControlGroup('deceased_date');
		  echo $this->form->getControlGroup('email');
		  echo $this->form->getControlGroup('mail_address_type');
		  echo $this->form->getControlGroup('demand_origin');
		  echo $this->form->getControlGroup('publishing_auth');

		  if($this->item->old_id) {
		    $this->form->setValue('nextmedia_id', null, $this->item->old_id);
		    echo $this->form->getControlGroup('nextmedia_id');
		  }
	    ?>
	</div>
	<div class="span4 form-vertical">
	  <?php
		  echo JLayoutHelper::render('joomla.edit.global', $this); 
		  echo $this->form->getControlGroup('honor_member');
		  echo $this->form->getControlGroup('honor_member_date');
		  $this->form->setValue('certification_status', null, JText::_('COM_SNIPF_CERTIFICATION_STATUS_'.$this->item->certification_status));
		  echo $this->form->getControlGroup('certification_status');
		  $this->form->setValue('subscription_status', null, JText::_('COM_SNIPF_OPTION_'.$this->item->subscription_status));
		  echo $this->form->getControlGroup('subscription_status');
		  echo $this->form->getControlGroup('rgpd_sending_date');
		  echo $this->form->getControlGroup('rgpd_reception_date');
		  echo $this->form->getControlGroup('rfi_subscription_date');
		  echo $this->form->getControlGroup('rfi_speciality');
		  echo $this->form->getControlGroup('rfi_category');
	       ?>
	</div>
	<div class="span12 form-vertical" style="margin:0;">
	  <?php echo $this->form->getControlGroup('persontext'); ?>
	</div>
      </div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php echo $this->loadTemplate('work_situation'); ?>
      <?php echo $this->loadTemplate('addresses'); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'positions', JText::_('COM_SNIPF_TAB_POSITIONS')); ?>
	<div class="row-fluid">
	  <div class="span10" id="positions">
	    <div id="position">
	    </div>
	  </div>
	</div>
      <?php echo JHtml::_('bootstrap.endTab'); ?>

      <?php if(in_array($cadsGroupId, $authorisedGroups)) : ?>
	<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'cads', JText::_('COM_SNIPF_TAB_CADS')); ?>
	  <div class="row-fluid">
	    <?php echo $this->loadTemplate('beneficiaries'); ?>
	  </div>
	<?php echo JHtml::_('bootstrap.endTab'); ?>
      <?php endif; ?>

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

      <?php echo JLayoutHelper::render('joomla.edit.params', $this); ?>

      <?php echo JHtml::_('bootstrap.addTab', 'myTab', 'permissions', JText::_('COM_SNIPF_TAB_PERMISSIONS', true)); ?>
	      <?php echo $this->form->getInput('rules'); ?>
	      <?php echo $this->form->getInput('asset_id'); ?>
      <?php echo JHtml::_('bootstrap.endTab'); ?>
  </div>

  <input type="hidden" name="task" value="" />
  <?php //Required for the dynamical Javascript region setting. ?>
  <input type="hidden" name="hidden_region_code" id="hidden-region-code" value="<?php echo $this->form->getValue('region_of_birth'); ?>" />
  <input type="hidden" name="lang_tag" id="lang-tag" value="<?php echo $langTag; ?>" />
  <?php //Required for the checkCertificateClosure function. ?>
  <input type="hidden" name="old_status" value="<?php echo $this->item->status; ?>" />
  <input type="hidden" name="is_root" id="is-root" value="<?php echo (int)$user->get('isRoot'); ?>" />
  <input type="hidden" name="is_readonly" id="is-readonly" value="<?php echo (int)$this->readonly; ?>" />
  <?php echo JHtml::_('form.token', array('id' => 'token')); ?>
</form>

<?php
//Load the jQuery scripts.
$doc = JFactory::getDocument();
$doc->addScript(JURI::base().'components/com_snipf/js/common.js');
$doc->addScript(JURI::base().'components/com_snipf/js/setregions.js');
$doc->addScript(JURI::base().'components/com_snipf/js/person.js');

