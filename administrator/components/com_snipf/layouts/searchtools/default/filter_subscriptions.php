<?php
/**
 * @package SNIPF
 * @subpackage  Layout
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('JPATH_BASE') or die;

$data = $displayData;

// Load the form filters
$filters = $data['view']->filterForm->getGroup('filter');
?>
<?php if ($filters) : ?>
	<?php foreach ($filters as $fieldName => $field) : ?>
		<?php if ($fieldName !== 'filter_search') : ?>
			<?php $dataShowOn = ''; ?>
			<?php if ($field->showon) : ?>
				<?php JHtml::_('jquery.framework'); ?>
				<?php JHtml::_('script', 'jui/cms.js', array('version' => 'auto', 'relative' => true)); ?>
				<?php $dataShowOn = " data-showon='" . json_encode(JFormHelper::parseShowOnConditions($field->showon, $field->formControl, $field->group)) . "'"; ?>
			<?php endif; ?>

			<?php if($fieldName === 'filter_by_year') : //Wraps the date filters into a div. ?>
			    <div class="year-filter" id="year-filter">
			    <h3><?php echo JText::_('COM_SNIPF_YEAR_FILTER'); ?></h3>
			<?php endif; ?>

			<div class="js-stools-field-filter"<?php echo $dataShowOn; ?>>
				<?php echo $field->input; ?>
			</div>

			<?php if($fieldName === 'filter_by_year') : //Adds the needed buttons to manage the date filters. ?>
			   <button type="button" id="filter_year" class="btn hasTooltip" title=""
				   data-original-title=""><?php echo JText::_('COM_SNIPF_FILTER_BUTTON'); ?></button>
			   <button type="button" id="clear_year" class="btn hasTooltip" title=""
				   data-original-title=""><?php echo JText::_('JCLEAR'); ?></button>
			  </div>
			<?php endif; ?>
		<?php endif; ?>
	<?php endforeach; ?>
<?php endif; ?>
