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

<div class="blog<?php echo $this->pageclass_sfx;?>">
  <?php if($this->params->get('show_page_heading')) : ?>
	  <h1>
	    <?php echo $this->escape($this->params->get('page_heading')); ?>
	  </h1>
  <?php endif; ?>
  <?php if($this->params->get('show_category_title', 1)) : ?>
	  <h2 class="category-title">
	      <?php echo JHtml::_('content.prepare', $this->category->title, '', $this->category->extension.'.category.title'); ?>
	  </h2>
  <?php endif; ?>
  <?php if($this->params->get('show_cat_tags', 1)) : ?>
	  <?php echo JLayoutHelper::render('joomla.content.tags', $this->category->tags->itemTags); ?>
  <?php endif; ?>
  <?php if($this->params->get('show_description') || $this->params->def('show_description_image')) : ?>
	  <div class="category-desc">
		  <?php if($this->params->get('show_description_image') && $this->category->getParams()->get('image')) : ?>
			  <img src="<?php echo $this->category->getParams()->get('image'); ?>"/>
		  <?php endif; ?>
		  <?php if($this->params->get('show_description') && $this->category->description) : ?>
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
	  <input type="text" name="filter-search" id="filter-search" value="<?php echo $this->escape($this->state->get('list.filter')); ?>"
		  class="inputbox" onchange="snipf.submitForm();" title="<?php echo JText::_('COM_SNIPF_FILTER_SEARCH_DESC'); ?>"
		  placeholder="<?php echo JText::_('COM_SNIPF_'.$this->params->get('filter_field').'_FILTER_LABEL'); ?>" />

	    <button type="submit" class="btn hasTooltip" title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_SUBMIT'); ?>">
		    <i class="icon-search"></i>
	    </button>

	    <button type="button" onclick="snipf.clearSearch()" class="btn hasTooltip js-stools-btn-clear"
		    title="<?php echo JHtml::tooltipText('JSEARCH_FILTER_CLEAR'); ?>">
		    <?php echo JText::_('JSEARCH_FILTER_CLEAR');?>
	    </button>
	</div>
      <?php endif; ?>
     
      <?php echo JLayoutHelper::render('filter_ordering', $this, JPATH_SITE.'/components/com_snipf/layouts/'); ?>

      <?php if($this->params->get('show_pagination_limit')) : ?>
	<div class="span1">
	    <?php echo $this->pagination->getLimitBox(); ?>
	</div>
      <?php endif; ?>

    </div>
    <?php endif; ?>

    <?php if(empty($this->lead_items) && empty($this->link_items) && empty($this->intro_items)) : ?>
      <?php if($this->params->get('show_no_persons')) : ?>
	      <p><?php echo JText::_('COM_SNIPF_NO_PERSONS'); ?></p>
      <?php endif; ?>
    <?php endif; ?>

    <?php $leadingcount = 0; ?>
    <?php if(!empty($this->lead_items)) : ?>
	    <div class="items-leading clearfix">
	  <?php foreach($this->lead_items as &$item) : ?>
		  <div class="leading-<?php echo $leadingcount; ?><?php echo $item->state == 0 ? ' system-unpublished' : null; ?>"
			  itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
			  <?php
			  $this->item = & $item;
			  echo $this->loadTemplate('item');
			  ?>
		  </div>
		  <?php $leadingcount++; ?>
	  <?php endforeach; ?>
	    </div><!-- end items-leading -->
    <?php endif; ?>

    <?php
    $introcount = (count($this->intro_items));
    $counter = 0;
    ?>

    <?php if(!empty($this->intro_items)) : ?>
      <?php foreach($this->intro_items as $key => &$item) : ?>
	  <?php $rowcount = ((int) $key % (int) $this->columns) + 1; ?>
	  <?php if($rowcount == 1) : ?>
		  <?php $row = $counter / $this->columns; ?>
		  <div class="items-row cols-<?php echo (int) $this->columns; ?> <?php echo 'row-'.$row; ?> row-fluid clearfix">
	  <?php endif; ?>
	  <div class="span<?php echo round((12 / $this->columns)); ?>">
		  <div class="item column-<?php echo $rowcount; ?><?php echo $item->state == 0 ? ' system-unpublished' : null; ?>"
		      itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
		      <?php
		      $this->item = & $item;
		      echo $this->loadTemplate('item');
		      ?>
		  </div>
		  <!-- end item -->
		  <?php $counter++; ?>
	  </div><!-- end span -->
	  <?php if(($rowcount == $this->columns) or ($counter == $introcount)) : ?>
		  </div><!-- end row -->
	  <?php endif; ?>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if(!empty($this->link_items)) : ?>
	    <div class="items-more">
	      <?php echo $this->loadTemplate('links'); ?>
	    </div>
    <?php endif; ?>

    <?php if(($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
    <div class="nb-results">
       <?php echo JText::sprintf('COM_SNIPF_PAGINATION_NB_RESULTS', $this->pagination->get('total')); ?>
    </div>
    <div class="pagination">

	    <?php if ($this->params->def('show_pagination_results', 1)) : ?>
		    <p class="counter pull-right">
			    <?php echo $this->pagination->getPagesCounter(); ?>
		    </p>
	    <?php endif; ?>

	    <?php echo $this->pagination->getListFooter(); ?>
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
</div><!-- blog -->

<?php

if($this->params->get('filter_field') == 'lastname') {
  //Loads the JQuery autocomplete file.
  JHtml::_('script', 'media/jui/js/jquery.autocomplete.min.js');
  //Loads our js script.
  $doc = JFactory::getDocument();
  $doc->addScript(JURI::base().'components/com_snipf/js/autocomplete.js');
}

