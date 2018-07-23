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
//Check only against component permission as certificate items have no categories.
$canOrder = $user->authorise('core.edit.state', 'com_snipf');

$colorCodes = array('initial_pending' => '#bfbfbf', 'commission_pending' => '#ff9933', 'file_pending' => '#cc9900', 
                    'running' => '#6cd26b', 'outdated' => '#e60000', 'retired' => '#4da6ff', 'deceased' => '#ac00e6',
		    'removal' => '#404040', 'rejected_file' => '#404040', 'abandon' => '#404040', 'other' => '#404040');
?>

<script type="text/javascript">
//Global variable. It will be set as function in the js file.
var checkFilterDates;
</script>

<form action="<?php echo JRoute::_('index.php?option=com_snipf&view=certificates');?>" method="post" name="adminForm" id="adminForm">

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
//echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this));
echo JLayoutHelper::render('searchtools.default', array('view' => $this));
?>

  <div class="clr"> </div>
  <?php if (empty($this->items)) : ?>
	<div class="alert alert-no-items">
		<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
	</div>
  <?php else : ?>
    <table class="table table-striped" id="certificateList">
      <thead>
      <tr>
	<th width="1%" class="hidden-phone">
	  <?php echo JHtml::_('grid.checkall'); ?>
	</th>
	<th width="1%" style="min-width:55px" class="nowrap center">
	  <?php echo JHtml::_('searchtools.sort', 'JSTATUS', 'c.published', $listDirn, $listOrder); ?>
	</th>
	<th width="10%">
	  <?php echo JHtml::_('searchtools.sort', 'COM_SNIPF_HEADING_NUMBER', 'c.number', $listDirn, $listOrder); ?>
	</th>
	<th width="15%">
	  <?php echo JHtml::_('searchtools.sort', 'COM_SNIPF_HEADING_LASTNAME', 'p.lastname', $listDirn, $listOrder); ?>
	</th>
	<th width="15%">
	  <?php echo JHtml::_('searchtools.sort', 'COM_SNIPF_HEADING_FIRSTNAME', 'p.firstname', $listDirn, $listOrder); ?>
	</th>
	<th>
	  <?php echo JText::_('COM_SNIPF_HEADING_STATE'); ?>
	</th>
	<th width="10%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_CREATED_BY', 'user', $listDirn, $listOrder); ?>
	</th>
	<th width="5%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'JDATE', 'c.created', $listDirn, $listOrder); ?>
	</th>
	<th width="1%" class="nowrap hidden-phone">
	  <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'c.id', $listDirn, $listOrder); ?>
	</th>
      </tr>
      </thead>

      <tbody>
      <?php foreach ($this->items as $i => $item) :

      $canEdit = $user->authorise('core.edit','com_snipf.certificate.'.$item->id);
      $canEditOwn = $user->authorise('core.edit.own', 'com_snipf.certificate.'.$item->id) && $item->created_by == $userId;
      $canCheckin = $user->authorise('core.manage','com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
      $canChange = ($user->authorise('core.edit.state','com_snipf.certificate.'.$item->id) && $canCheckin) || $canEditOwn; 
      ?>

      <tr class="row<?php echo $i % 2; ?>">
	  <td class="center hidden-phone">
	    <?php echo JHtml::_('grid.id', $i, $item->id); ?>
	  </td>
	  <td class="center">
	    <div class="btn-group">
	      <?php echo JHtml::_('jgrid.published', $item->published, $i, 'certificates.', $canChange, 'cb'); ?>
	      <?php
	      // Create dropdown items
	      $action = $archived ? 'unarchive' : 'archive';
	      JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'certificates');

	      $action = $trashed ? 'untrash' : 'trash';
	      JHtml::_('actionsdropdown.' . $action, 'cb' . $i, 'certificates');

	      // Render dropdown list
	      echo JHtml::_('actionsdropdown.render', $this->escape($item->number));
	      ?>
	    </div>
	  </td>
	  <td class="has-context">
	    <div class="pull-left">
	      <?php if ($item->checked_out) : ?>
		  <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'certificates.', $canCheckin); ?>
	      <?php endif; ?>
	      <?php if($canEdit || $canEditOwn) : ?>
		<a href="<?php echo JRoute::_('index.php?option=com_snipf&task=certificate.edit&id='.$item->id);?>" title="<?php echo JText::_('JACTION_EDIT'); ?>">
			<?php echo $this->escape($item->number); ?></a>
	      <?php else : ?>
		<?php echo $this->escape($item->number); ?>
	      <?php endif; ?>
	    </div>
	  </td>
	  <td class="hidden-phone">
	    <?php echo $this->escape($item->lastname); ?>
	  </td>
	  <td class="hidden-phone">
	    <?php echo $this->escape($item->firstname); ?>
	  </td>
	  <td class="hidden-phone">
	  <?php
	        if($item->process_states[0] == 'no_process') {
		  echo '<div class="no-process">'.JText::_('COM_SNIPF_NO_PROCESS').'</div>';
		}
                else {
		  foreach($item->process_names as $key => $name) {
		    echo '<div class="process" style="background-color:'.$colorCodes[$item->process_states[$key]].';">'.$name.'</div>';
		  }
		}
	  ?>
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
	  <td colspan="9"><?php echo $this->pagination->getListFooter(); ?></td>
      </tr>
      </tbody>
    </table>
  <?php endif; ?>

<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="option" value="com_snipf" />
<input type="hidden" name="task" value="" />
<?php echo JHtml::_('form.token'); ?>
</form>

<?php
$doc = JFactory::getDocument();
//Load the jQuery script.
$doc->addScript(JURI::base().'components/com_snipf/js/certificates.js');

