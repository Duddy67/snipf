<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;
?>


<ol class="nav nav-tabs nav-stacked">
<?php foreach ($this->link_items as &$item) : ?>
	<li>
	  <a href="<?php echo JRoute::_(SnipfHelperRoute::getPersonRoute($item->slug, $item->catid, $item->language)); ?>">
		      <?php echo $item->lastname; ?></a>
	</li>
<?php endforeach; ?>
</ol>

