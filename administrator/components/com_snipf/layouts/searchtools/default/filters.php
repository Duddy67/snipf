<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
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

			<?php if ($fieldName === 'filter_from_date') : ?>
			  <div class="date-filters">
			<?php endif; ?>
			<div class="js-stools-field-filter"<?php echo $dataShowOn; ?>>
				<?php echo $field->input; ?>
			</div>
			<?php if ($fieldName === 'filter_to_date') : ?>
			   <button type="button" id="filter_dates" class="btn hasTooltip" title=""
				   data-original-title=""><?php echo JText::_('COM_SNIPF_FILTER_BUTTON'); ?></button>
			   <button type="button" id="clear_dates" class="btn hasTooltip" title=""
				   data-original-title=""><?php echo JText::_('JCLEAR'); ?></button>
			  </div>
			<?php endif; ?>
		<?php endif; ?>
	<?php endforeach; ?>
<?php endif; ?>
