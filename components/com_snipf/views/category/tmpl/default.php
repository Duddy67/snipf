<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

JHtml::_('formbehavior.chosen', 'select');
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');
?>
<script type="text/javascript">
var snipf = {
  clearSearch: function() {
    document.getElementById('filter-search').value = '';
    snipf.submitForm();
  },

  submitForm: function() {
    var action = document.getElementById('siteForm').action;
    //Set an anchor on the form.
    document.getElementById('siteForm').action = action+'#siteForm';
    document.getElementById('siteForm').submit();
  }
};
</script>

<div class="list<?php echo $this->pageclass_sfx;?>">
  <?php if ($this->params->get('show_page_heading')) : ?>
	  <h1>
		  <?php echo $this->escape($this->params->get('page_heading')); ?>
	  </h1>
  <?php endif; ?>
  <?php if($this->params->get('show_category_title')) : ?>
	  <h2 class="category-title">
	      <?php echo JHtml::_('content.prepare', $this->category->title, '', $this->category->extension.'.category.title'); ?>
	  </h2>
  <?php endif; ?>
  <?php if ($this->params->get('show_tags', 1)) : ?>
	  <?php echo JLayoutHelper::render('joomla.content.tags', $this->category->tags->itemTags); ?>
  <?php endif; ?>
  <?php if ($this->params->get('show_description') || $this->params->def('show_description_image')) : ?>
	  <div class="category-desc">
		  <?php if ($this->params->get('show_description_image') && $this->category->getParams()->get('image')) : ?>
			  <img src="<?php echo $this->category->getParams()->get('image'); ?>"/>
		  <?php endif; ?>
		  <?php if ($this->params->get('show_description') && $this->category->description) : ?>
			  <?php echo JHtml::_('content.prepare', $this->category->description, '', $this->category->extension.'.category'); ?>
		  <?php endif; ?>
		  <div class="clr"></div>
	  </div>
  <?php endif; ?>

  <form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="siteForm" id="siteForm">

    <?php if($this->params->get('filter_field') != 'hide' || $this->params->get('show_pagination_limit') || $this->params->get('filter_ordering')) : ?>
    <div class="snipf-toolbar clearfix">
      <?php if ($this->params->get('filter_field') != 'hide') :?>
	<div class="btn-group input-append span6">
	  <label class="filter-search-lbl element-invisible" for="filter-search">
	    <?php echo JText::_('COM_SNIPF_'.$this->params->get('filter_field').'_FILTER_LABEL').'&#160;'; ?>
	  </label>
	  <input type="text" name="filter-search" id="filter-search" value="<?php echo $this->escape($this->state->get('list.filter')); ?>"
		  class="inputbox" onchange="snipf.submitForm();" title="<?php echo JText::_('COM_SNIPF_FILTER_SEARCH_DESC'); ?>"
		  placeholder="<?php echo JText::_('COM_SNIPF_'.$this->params->get('filter_field').'_FILTER_LABEL'); ?>" />

	  <button type="submit" class="btn hasTooltip" title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>">
		  <i class="icon-search"></i>
	  </button>
	  <button type="button" class="btn hasTooltip js-stools-btn-clear" onclick="snipf.clearSearch();"
		  title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_CLEAR'); ?>">
		  <?php echo JText::_('JSEARCH_FILTER_CLEAR');?>
	  </button>
	</div>
      <?php endif; ?>
     
      <?php echo JLayoutHelper::render('filter_ordering', $this, JPATH_SITE.'/components/com_snipf/layouts/'); ?>

      <?php if($this->params->get('show_pagination_limit')) : ?>
	<div class="span1">
	  <label for="limit" class="element-invisible"><?php echo JText::_('JGLOBAL_DISPLAY_NUM'); ?></label>
	    <?php echo $this->pagination->getLimitBox(); ?>
	</div>
      <?php endif; ?>

    </div>
    <?php endif; ?>

    <?php if(empty($this->items)) : ?>
	    <?php if($this->params->get('show_no_persons', 1)) : ?>
	    <p><?php echo JText::_('COM_SNIPF_NO_PERSONS'); ?></p>
	    <?php endif; ?>
    <?php else : ?>
      <?php echo $this->loadTemplate('persons'); ?>
    <?php endif; ?>

    <?php if(($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
    <div class="pagination">

	    <?php if ($this->params->def('show_pagination_results', 1)) : ?>
		    <p class="counter pull-right">
			    <?php echo $this->pagination->getPagesCounter(); ?>
		    </p>
	    <?php endif; ?>

	    <?php //Load our own pagination layout. ?>
	    <?php echo JLayoutHelper::render('person_pagination', $this->pagination, JPATH_SITE.'/components/com_snipf/layouts/'); ?>
    </div>
    <?php endif; ?>

    <?php if($this->get('children') && $this->maxLevel != 0) : ?>
	    <div class="cat-children">
	      <h3><?php echo JTEXT::_('JGLOBAL_SUBCATEGORIES'); ?></h3>
	      <?php echo $this->loadTemplate('children'); ?>
	    </div>
    <?php endif; ?>

    <input type="hidden" name="limitstart" value="" />
    <input type="hidden" name="task" value="" />
  </form>
</div><!-- list -->
