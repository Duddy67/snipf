<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');


$user = JFactory::getUser();
$userId = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$archived = $this->state->get('filter.published') == 2 ? true : false;
$trashed = $this->state->get('filter.published') == -2 ? true : false;
//Check only against component permission as subscription items have no categories.
$canOrder = $user->authorise('core.edit.state', 'com_snipf');
?>


<form action="<?php echo JRoute::_('index.php?option=com_snipf&view=subscriptions');?>" method="post" name="adminForm" id="adminForm">

<?php if (!empty( $this->sidebar)) : ?>
  <div id="j-sidebar-container" class="span2">
	  <?php echo $this->sidebar; ?>
  </div>
  <div id="j-main-container" class="span10">
<?php else : ?>
  <div id="j-main-container">
<?php endif;?>

<?php
// Search tools bar (uses the component layout). 
echo JLayoutHelper::render('searchtools.default', array('view' => $this, 'view_name' => 'subscriptions'));
// Search tools bar 
//echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
?>

  <div class="clr"> </div>
  <?php if (empty($this->items)) : ?>
	<div class="alert alert-no-items">
		<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
	</div>
  <?php else : ?>
    <table class="table table-striped" id="subscriptionList">
      <thead>
      <tr>
	<th width="1%" class="hidden-phone">
	  <?php echo JHtml::_('grid.checkall'); ?>
	</th>
	<th width="1%" style="min-width:55px" class="nowrap center">
	  <?php echo JHtml::_('searchtools.sort', 'JSTATUS', 's.published', $listDirn, $listOrder); ?>
	</th>
	<th width="20%">
	  <?php echo JHtml::_('searchtools.sort', 'COM_SNIPF_HEADING_LASTNAME', 'p.lastname', $listDirn, $listOrder); ?>
	</th>
	<th>
	  <?php echo JHtml::_('searchtools.sort', 'COM_SNIPF_HEADING_FIRSTNAME', 'p.firstname', $listDirn, $listOrder); ?>
	</th>
	<th width="15%">
	  <?php echo JText::_('COM_SNIPF_HEADING_PERSON_STATUS'); ?>
	</th>
	<th width="15%">
	  <?php echo JText::_('COM_SNIPF_HEADING_SUBSCRIPTION_STATUS'); ?>
	</th>
	<th width="15%">
	  <?php echo JText::_('COM_SNIPF_HEADING_SRIPF'); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_CREATED_BY', 'user', $listDirn, $listOrder); ?>
	</th>
	<th width="5%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'JDATE', 's.created', $listDirn, $listOrder); ?>
	</th>
	<th width="1%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 's.id', $listDirn, $listOrder); ?>
	</th>
      </tr>
      </thead>

      <tbody>
      <?php foreach ($this->items as $i => $item) :

      $canEdit = $user->authorise('core.edit','com_snipf.subscription.'.$item->id);
      $canEditOwn = $user->authorise('core.edit.own', 'com_snipf.subscription.'.$item->id) && $item->created_by == $userId;
      $canCheckin = $user->authorise('core.manage','com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
      $canChange = ($user->authorise('core.edit.state','com_snipf.subscription.'.$item->id) && $canCheckin) || $canEditOwn; 
      ?>

      <tr class="row<?php echo $i % 2; ?>">
	  <td class="center hidden-phone">
	    <?php echo JHtml::_('grid.id', $i, $item->id); ?>
	  </td>
	  <td class="center">
	    <div class="btn-group">
	      <?php echo JHtml::_('jgrid.published', $item->published, $i, 'subscriptions.', $canChange, 'cb'); ?>
	      <?php
	      // Create dropdown items
	      $action = $archived ? 'unarchive' : 'archive';
	      JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'subscriptions');

	      $action = $trashed ? 'untrash' : 'trash';
	      JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'subscriptions');

	      // Render dropdown list
	      echo JHtml::_('actionsdropdown.render', $this->escape($item->name));
	      ?>
	    </div>
	  </td>
	  <td class="has-context">
	    <div class="pull-left">
	      <?php if ($item->checked_out) : ?>
		  <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'subscriptions.', $canCheckin); ?>
	      <?php endif; ?>
	      <?php if($canEdit || $canEditOwn) : ?>
		<a href="<?php echo JRoute::_('index.php?option=com_snipf&task=subscription.edit&id='.$item->id);?>" title="<?php echo JText::_('JACTION_EDIT'); ?>">
			<?php echo $this->escape($item->lastname); ?></a>
	      <?php else : ?>
		<?php echo $this->escape($item->lastname); ?>
	      <?php endif; ?>
		<span class="small">
		  <?php echo '<br />(id: '.$item->person_id.')'; ?>
		</span>
	    </div>
	  </td>
	  <td class="hidden-phone">
	    <?php echo $this->escape($item->firstname); ?>
	  </td>
	  <td class="hidden-phone center">
	    <?php echo JText::_('COM_SNIPF_OPTION_'.strtoupper($item->person_status)); ?>
	      <?php if(($item->person_status == 'retired' || $item->person_status == 'deceased') && $item->cqp1) : ?>
		<span class="small">
		  <?php echo '<br />CQP1'; ?>
		</span>
	       <?php endif; ?>
	  </td>
	  <td class="hidden-phone center">
	    <?php echo JText::_('COM_SNIPF_OPTION_'.strtoupper($item->subscription_status)); ?>
		  <?php if($item->subscription_status == 'paid' && $item->payment_date) : ?>
		    <span class="small">
		      <?php echo '<br />'.JHtml::_('date', $item->payment_date, JText::_('DATE_FORMAT_LC4')); ?>
		    </span>
		  <?php elseif($item->subscription_status == 'outdated') : ?>
		    <span class="small">
		      <br />
		      <?php echo JText::sprintf('COM_SNIPF_SINCE_YEAR_LABEL', $item->last_registered_year); ?>
		    </span>
		  <?php endif; ?>
	  </td>
	  <td class="hidden-phone">
	    <?php echo $this->escape($item->sripf_name); ?>
	  </td>
	  <td class="small hidden-phone">
	    <?php echo $this->escape($item->user); ?>
	  </td>
	  <td class="nowrap small hidden-phone">
	    <?php echo JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC4')); ?>
	  </td>
	  <td>
	    <?php echo $item->id; ?>
	  </td></tr>

      <?php endforeach; ?>
      <tr>
	  <td colspan="10"><?php echo $this->pagination->getListFooter(); ?></td>
      </tr>
      </tbody>
    </table>
  <?php endif; ?>

<input type="hidden" name="is_root" id="is-root" value="<?php echo (int)$user->get('isRoot'); ?>" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="option" value="com_snipf" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

<?php
$doc = JFactory::getDocument();
//Load the jQuery script.
$doc->addScript(JURI::base().'components/com_snipf/js/hidesearchtools.js');
$doc->addScript(JURI::base().'components/com_snipf/js/subscriptions.js');

