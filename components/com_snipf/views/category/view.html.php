<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * HTML View class for the SNIPF component.
 */
class SnipfViewCategory extends JViewCategory
{
  /**
   * Execute and display a template script.
   *
   * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
   *
   * @return  mixed  A string if successful, otherwise a Error object.
   */

  /**
   * @var    array  Array of leading items for blog display
   * @since  3.2
   */
  protected $lead_items = array();

  /**
   * @var    array  Array of intro (multicolumn display) items for blog display
   * @since  3.2
   */
  protected $intro_items = array();

  /**
   * @var    array  Array of links in blog display
   * @since  3.2
   */
  protected $link_items = array();

  /**
   * @var    integer  Number of columns in a multi column display
   * @since  3.2
   */
  protected $columns = 1;

  /**
   * @var    string  The name of the extension for the category
   * @since  3.2
   */
  protected $extension = 'com_snipf';

  protected $nowDate;
  protected $user;
  protected $uri;
  protected $person_type;

  public function display($tpl = null)
  {
    //Call parent method with common display elements (state, items etc...) used in
    //category list displays.
    parent::commonCategoryDisplay();

    // Prepare the data
    // Get the metrics for the structural page layout.
    $params     = $this->params;
    $numLeading = $params->def('num_leading_persons', 1);
    $numIntro   = $params->def('num_intro_persons', 4);
    $numLinks   = $params->def('num_links', 4);

    //Get the user object and the current url, (needed in the person edit layout).
    $this->user = JFactory::getUser();
    $this->uri = JUri::getInstance();
    $this->state = $this->get('State');
    $this->pagination = $this->get('Pagination');

    $model = JModelLegacy::getInstance('Person', 'SnipfModel');

    // Prepare the data.
    // Compute the person slugs.
    foreach($this->items as $item) {
      $item->slug = $item->alias ? ($item->id.':'.$item->alias) : $item->id;
      $item->catslug = $item->category_alias ? ($item->catid.':'.$item->category_alias) : $item->catid;
      $item->parent_slug = ($item->parent_alias) ? ($item->parent_id.':'.$item->parent_alias) : $item->parent_id;
      // No link for ROOT category
      if($item->parent_alias == 'root') {
	$item->parent_slug = null;
      }

      $item->certificates = $model->getCertificates($item->id);
    }

    // Check for layout override only if this is not the active menu item
    // If it is the active menu item, then the view and category id will match
    $app = JFactory::getApplication();
    $active = $app->getMenu()->getActive();

    //The category has no itemId and thus is not linked to any menu item. 
    if((!$active) || ((strpos($active->link, 'view=category') === false) ||
		      (strpos($active->link, '&id='.(string)$this->category->id) === false))) {
      // Get the layout from the merged category params
      if($layout = $this->category->params->get('category_layout')) {
	$this->setLayout($layout);
      }
    }
    // At this point, we are in a menu item, so we don't override the layout
    elseif(isset($active->query['layout'])) {
      // We need to set the layout from the query in case this is an alternative menu item (with an alternative layout)
      $this->setLayout($active->query['layout']);
    }
    //Person: In case the layout parameter is not found within the query, the default layout
    //will be set.

    //Gets the current person type.
    $this->person_type = $app->getMenu()->getActive()->query['person_type'];

    // For blog layouts, preprocess the breakdown of leading, intro and linked articles.
    // This makes it much easier for the designer to just interrogate the arrays.
    if(($params->get('layout_type') == 'blog') || ($this->getLayout() == 'blog')) {

      //Computes the number of extra items (if any).
      $numExtra = 0;
      if(count($this->items) > ($numLeading + $numIntro + $numLinks)) {
	$numExtra = count($this->items) - ($numLeading + $numIntro + $numLinks);
      }

      foreach($this->items as $i => $item) {
	if($i < $numLeading) {
	  $this->lead_items[] = $item;
	}
	//Adds the possible extra items to the intro items.
	elseif($i >= $numLeading && $i < $numLeading + $numIntro + $numExtra) {
	  $this->intro_items[] = $item;
	}
	elseif($i < $numLeading + $numIntro + $numLinks + $numExtra) {
	  $this->link_items[] = $item;
	}
	else {
	  continue;
	}
      }

      $this->columns = max(1, $params->def('num_columns', 1));

      $order = $params->def('multi_column_order', 1);

      if($order == 0 && $this->columns > 1) {
	// Call order down helper
	$this->intro_items = SnipfHelperQuery::orderDownColumns($this->intro_items, $this->columns);
      }
    }

    //Set the name of the active layout in params, (needed for the filter ordering layout).
    $this->params->set('active_layout', $this->getLayout());
    //Set the filter_ordering parameter for the layout.
    $this->filter_ordering = $this->state->get('list.filter_ordering');

    $this->nowDate = JFactory::getDate()->toSql();

    // Creates a new JForm object
    $this->filterForm = new JForm('FilterForm');
    $this->filterForm->loadFile(JPATH_SITE.'/components/com_snipf/models/forms/filter_category.xml');

    $this->prepareDocument();

    $this->setDocument();

    return parent::display($tpl);
  }


  /**
   * Method to prepares the document
   *
   * @return  void
   *
   * @since   3.2
   */
  protected function prepareDocument()
  {
    $app = JFactory::getApplication();
    // Because the application sets a default page title,
    // we need to get it from the menu item itself
    $menus = $app->getMenu();
    $menu = $menus->getActive();

    if($menu) {
      $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
    }

    $title = $this->params->get('page_title', '');

    // Check for empty title and add site name if param is set
    if(empty($title)) {
      $title = $app->get('sitename');
    }
    elseif($app->get('sitename_pagetitles', 0) == 1) {
      $title = JText::sprintf('JPAGETITLE', $app->get('sitename'), $title);
    }
    elseif($app->get('sitename_pagetitles', 0) == 2) {
      $title = JText::sprintf('JPAGETITLE', $title, $app->get('sitename'));
    }

    //If no title is find, set it to the category title. 
    if(empty($title)) {
      $title = $this->category->title;
    }

    $this->document->setTitle($title);

    if($this->category->metadesc) {
      $this->document->setDescription($this->category->metadesc);
    }
    elseif($this->params->get('menu-meta_description')) {
      $this->document->setDescription($this->params->get('menu-meta_description'));
    }

    if($this->category->metakey) {
      $this->document->setMetadata('keywords', $this->category->metakey);
    }
    elseif($this->params->get('menu-meta_keywords')) {
      $this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
    }

    if($this->params->get('robots')) {
      $this->document->setMetadata('robots', $this->params->get('robots'));
    }

    if(!is_object($this->category->metadata)) {
      $this->category->metadata = new Registry($this->category->metadata);
    }

    if(($app->get('MetaAuthor') == '1') && $this->category->get('author', '')) {
      $this->document->setMetaData('author', $this->category->get('author', ''));
    }

    $mdata = $this->category->metadata->toArray();

    foreach($mdata as $k => $v) {
      if($v) {
	$this->document->setMetadata($k, $v);
      }
    }

    return;
  }


  protected function setDocument() 
  {
    //Include css file (if needed).
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_snipf/css/snipf.css');
  }
}
