<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
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
$canOrder = $user->authorise('core.edit.state', 'com_snipf.category');
$saveOrder = $listOrder == 'p.ordering';

if($saveOrder) {
  $saveOrderingUrl = 'index.php?option=com_snipf&task=persons.saveOrderAjax&tmpl=component';
  JHtml::_('sortablelist.sortable', 'personList', 'adminForm', strtolower($listDirn), $saveOrderingUrl);
}

?>

<script type="text/javascript">
Joomla.orderTable = function()
{
  table = document.getElementById("sortTable");
  direction = document.getElementById("directionTable");
  order = table.options[table.selectedIndex].value;

  if(order != '<?php echo $listOrder; ?>') {
    dirn = 'asc';
  }
  else {
    dirn = direction.options[direction.selectedIndex].value;
  }

  Joomla.tableOrdering(order, dirn, '');
}

/**
 * Default function. Can be overriden by the component to add custom logic
 *
 * @param  {bool}  task  The given task
 *
 * @returns {void}
 */
Joomla.submitbutton = function(task)
{
  var form = document.getElementById("adminForm");

  if(task == 'persons.generateDocument.pdf') {
    //Displays the pdf output in a new tab.
    form.setAttribute('target', '_blank');
    Joomla.submitform(task);
    //Cleans out the values previously set to prevent the other tasks
    //to be also opened in a new tab.
    document.getElementById('task').value = '';
    form.removeAttribute('target');
  }
  else {
    Joomla.submitform(task);
  }
}
</script>


<form action="<?php echo JRoute::_('index.php?option=com_snipf&view=persons');?>" method="post" name="adminForm" id="adminForm">

<?php if (!empty( $this->sidebar)) : ?>
  <div id="j-sidebar-container" class="span2">
	  <?php echo $this->sidebar; ?>
  </div>
  <div id="j-main-container" class="span10">
<?php else : ?>
  <div id="j-main-container">
<?php endif;?>

<?php
// Search tools bar 
echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
?>

  <div class="clr"> </div>
  <?php if (empty($this->items)) : ?>
	<div class="alert alert-no-items">
		<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
	</div>
  <?php elseif($this->readonly && empty(SnipfHelper::getUserSripfs())) : //If a user in readonly mode has no sripf, he cannot see the item list. ?>
	<div class="alert alert-no-items">
		<?php echo JText::_('JERROR_ALERTNOAUTHOR'); ?>
	</div>
  <?php else : ?>
    <table class="table table-striped" id="personList">
      <thead>
	<tr>
	<th width="1%" class="nowrap center hidden-phone">
	<?php echo JHtml::_('searchtools.sort', '', 'p.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
	</th>
	<th width="1%" class="hidden-phone">
	  <?php echo JHtml::_('grid.checkall'); ?>
	</th>
	<th width="1%" style="min-width:55px" class="nowrap center">
	  <?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'p.published', $listDirn, $listOrder); ?>
	</th>
	<th width="25%">
	  <?php echo JHtml::_('searchtools.sort', 'COM_SNIPF_HEADING_LASTNAME', 'p.lastname', $listDirn, $listOrder); ?>
	</th>
	<th width="15%">
	  <?php echo JHtml::_('searchtools.sort', 'COM_SNIPF_HEADING_FIRSTNAME', 'p.firstname', $listDirn, $listOrder); ?>
	</th>
	<th width="10%">
	  <?php echo JText::_('JSTATUS'); ?>
	</th>
	<th width="10%">
	  <?php echo JText::_('COM_SNIPF_HEADING_CERTIFICATION_STATUS'); ?>
	</th>
	<th width="10%">
	  <?php echo JText::_('COM_SNIPF_HEADING_SUBSCRIPTION_STATUS'); ?>
	</th>
	<th width="10%">
	  <?php echo JText::_('COM_SNIPF_HEADING_SRIPF'); ?>
	</th>
	<th width="8%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort',  'JGRID_HEADING_ACCESS', 'p.access', $listDirn, $listOrder); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_CREATED_BY', 'user', $listDirn, $listOrder); ?>
	</th>
	<!--<th width="5%" class="nowrap hidden-phone">
	  <?php //echo JHtml::_('searchtools.sort', 'JGRID_HEADING_LANGUAGE', 'language', $listDirn, $listOrder); ?>
	</th>-->
	<th width="10%" class="nowrap hidden-phone">
	<?php echo JHtml::_('searchtools.sort', 'JDATE', 'p.created', $listDirn, $listOrder); ?>
	</th>
	<!--<th width="1%" class="nowrap hidden-phone">
	  <?php //echo JHtml::_('searchtools.sort', 'JGLOBAL_HITS', 'p.hits', $listDirn, $listOrder); ?>
	</th>-->
	<th width="1%" class="nowrap hidden-phone">
	<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'p.id', $listDirn, $listOrder); ?>
	</th>
      </tr>
      </thead>

      <tbody>
      <?php foreach ($this->items as $i => $item) :

      $ordering = ($listOrder == 'p.ordering');
      $canCreate = $user->authorise('core.create', 'com_snipf.category.'.$item->catid);
      $canEdit = $user->authorise('core.edit','com_snipf.person.'.$item->id);
      $canEditOwn = $user->authorise('core.edit.own', 'com_snipf.person.'.$item->id) && $item->created_by == $userId;
      $canCheckin = $user->authorise('core.manage','com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
      $canChange = ($user->authorise('core.edit.state','com_snipf.person.'.$item->id) && $canCheckin); 
      ?>

      <tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->catid; ?>">
	<td class="order nowrap center hidden-phone">
	  <?php
	  $iconClass = '';
	  if(!$canChange)
	  {
	    $iconClass = ' inactive';
	  }
	  elseif(!$saveOrder)
	  {
	    $iconClass = ' inactive tip-top hasTooltip" title="' . JHtml::tooltipText('JORDERINGDISABLED');
	  }
	  ?>
	  <span class="sortable-handler<?php echo $iconClass ?>">
		  <i class="icon-menu"></i>
	  </span>
	  <?php if($canChange && $saveOrder) : ?>
	      <input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering;?>" class="width-20 text-area-order " />
	  <?php endif; ?>
	  </td>
	  <td class="center hidden-phone">
		  <?php echo JHtml::_('grid.id', $i, $item->id); ?>
	  </td>
	  <td class="center">
	    <div class="btn-group">
	      <?php echo JHtml::_('jgrid.published', $item->published, $i, 'persons.', $canChange, 'cb'); ?>
	      <?php
	      if($canChange) {
		// Create dropdown items
		$action = $archived ? 'unarchive' : 'archive';
		JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'persons');

		$action = $trashed ? 'untrash' : 'trash';
		JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'persons');

		// Render dropdown list
		echo JHtml::_('actionsdropdown.render', $this->escape($item->lastname));
	      }
	      ?>
	    </div>
	  </td>
	  <td class="has-context">
	    <div class="pull-left">
	      <?php if ($item->checked_out) : ?>
		  <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'persons.', $canCheckin); ?>
	      <?php endif; ?>
	      <?php if($canEdit || $canEditOwn || $this->readonly) : //Users in readonly mode must also have access to the edit form. ?>
		<a href="<?php echo JRoute::_('index.php?option=com_snipf&task=person.edit&id='.$item->id);?>" title="<?php echo JText::_('JACTION_EDIT'); ?>"><?php echo $this->escape($item->lastname); ?></a>
	      <?php else : ?>
		<?php echo $this->escape($item->lastname); ?>
	      <?php endif; ?>
		<span class="small break-word">
		  <?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias)); 
		   if($item->cqp1) {
		    echo ' <strong>CQP1</strong>'; 
		   }
		   ?>
		</span>
		<?php if ($item->old_id):?>
		  <div class="small">
		    <?php echo 'ID Nextmedia: '.$item->old_id; ?>
		    <?php //echo JText::_('JCATEGORY') . ": ".$this->escape($item->category_title); ?>
		  </div>
		<?php endif;?>
	    </div>
	  </td>
	  <td class="hidden-phone">
	    <?php echo $this->escape($item->firstname); ?>
	  </td>
	  <td class="small hidden-phone">
	    <?php echo JText::_('COM_SNIPF_OPTION_'.strtoupper($item->status)); ?>
	  </td>
	  <td class="small hidden-phone">
	    <?php echo JText::_('COM_SNIPF_CERTIFICATION_STATUS_'.strtoupper($item->certification_status)); ?>
	  </td>
	  <td class="small hidden-phone">
	    <?php echo JText::_('COM_SNIPF_OPTION_'.strtoupper($item->subscription_status)); ?>
	  </td>
	  <td class="hidden-phone">
	    <?php echo $this->escape($item->sripf_name); ?>
	  </td>
	  <td class="small hidden-phone">
	    <?php echo $this->escape($item->access_level); ?>
	  </td>
	  <td class="small hidden-phone">
	    <?php echo $this->escape($item->user); ?>
	  </td>
	  <!--<td class="small hidden-phone">
	    <?php //if ($item->language == '*'):?>
	      <?php //echo JText::alt('JALL', 'language'); ?>
	    <?php //else:?>
	      <?php //echo $item->language_title ? $this->escape($item->language_title) : JText::_('JUNDEFINED'); ?>
	    <?php //endif;?>
	  </td>-->
	  <td class="nowrap small hidden-phone">
	    <?php echo JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC4')); ?>
	  </td>
	  <!--<td class="hidden-phone">
	    <?php //echo (int) $item->hits; ?>
	  </td>-->
	  <td>
	    <?php echo $item->id; ?>
	  </td></tr>

      <?php endforeach; ?>
      <tr>
	  <td colspan="13">
	    <div class="nb-results">
	       <?php echo JText::sprintf('COM_SNIPF_PAGINATION_NB_RESULTS', $this->pagination->get('total')); ?>
	    </div>
	    <?php echo $this->pagination->getListFooter(); ?>
	  </td>
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
//Load the jQuery scripts.
$doc = JFactory::getDocument();
$doc->addScript(JURI::base().'components/com_snipf/js/hidesearchtools.js');

