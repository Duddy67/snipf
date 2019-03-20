<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

require_once JPATH_COMPONENT_SITE.'/helpers/query.php';

/**
 * SNIPF Component Model
 *
 * @package     Joomla.Site
 * @subpackage  com_snipf
 */
class SnipfModelCategory extends JModelList
{
  
  /**
   * Category items data
   *
   * @var array
   */
  protected $_item = null;

  protected $_persons = null;

  protected $_siblings = null;

  protected $_children = null;

  protected $_parent = null;

  /**
   * Model context string.
   *
   * @var		string
   */
  protected $_context = 'com_snipf.category';

  /**
   * The category that applies.
   *
   * @access    protected
   * @var        object
   */
  protected $_category = null;

  /**
   * The list of other person categories.
   *
   * @access    protected
   * @var        array
   */
  protected $_categories = null;


  /**
   * Method to get a list of items.
   *
   * @return  mixed  An array of objects on success, false on failure.
   */

  /**
   * Constructor.
   *
   * @param   array  An optional associative array of configuration settings.
   * @see     JController
   * @since   1.6
   */
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 'p.id',
	      'lastname', 'p.lastname',
	      'firstname', 'p.firstname',
	      'author', 'p.author',
	      'created', 'p.created',
	      'catid', 'p.catid', 'category_title',
	      'modified', 'p.modified',
	      'published', 'p.published',
	      'ordering', 'p.ordering',
	      'publish_up', 'p.publish_up',
	      'publish_down', 'p.publish_down'
      );
    }

    parent::__construct($config);
  }


  /**
   * Method to auto-populate the model state.
   *
   * Person. Calling getState in this method will result in recursion.
   *
   * @since   1.6
   */
  protected function populateState($ordering = null, $direction = null)
  {
    $app = JFactory::getApplication('site');

    //Gets and sets the current category id as well as the person type.
    $pk = $app->input->getInt('id');
    $this->setState('category.id', $pk);
    $this->setState('list.person_type', $app->input->get('person_type', '', 'string'));

    //getParams function return global parameters overrided by the menu parameters (if any).
    //Person: Some specific parameters of this menu are not returned.
    $params = $app->getParams();

    $menuParams = new JRegistry;

    //Get the menu with its specific parameters.
    if($menu = $app->getMenu()->getActive()) {
      $menuParams->loadString($menu->params);
    }

    //Merge Global and Menu Item params into a new object.
    $mergedParams = clone $menuParams;
    $mergedParams->merge($params);

    // Load the parameters in the session.
    $this->setState('params', $mergedParams);

    // process show_noauth parameter

    //The user is not allowed to see the registered persons unless he has the proper view permissions.
    if(!$params->get('show_noauth')) {
      //Set the access filter to true. This way the SQL query checks against the user
      //view permissions and fetchs only the persons this user is allowed to see.
      $this->setState('filter.access', true);
    }
    //The user is allowed to see any of the registred persons (ie: intro_text as a teaser). 
    else {
      //The user is allowed to see all the persons or some of them.
      //All of the persons are returned and it's up to thelayout to 
      //deal with the access (ie: redirect the user to login form when Read more
      //button is clicked).
      $this->setState('filter.access', false);
    }

    // Set limit for query. If list, use parameter. If blog, add blog parameters for limit.
    //Important: The pagination limit box must be hidden to use the limit value based upon the layout.
    if(!$params->get('show_pagination_limit') && (($app->input->get('layout') === 'blog') || $params->get('layout_type') === 'blog')) {
      $limit = $params->get('num_leading_persons') + $params->get('num_intro_persons') + $params->get('num_links');
    }
    else { // list layout or blog layout with the pagination limit box shown.
      //Get the number of songs to display per page.
      $limit = $params->get('display_num', 10);

      if($params->get('show_pagination_limit')) {
	//Gets the limit value from the pagination limit box.
	$limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $limit, 'uint');
      }
    }

    $this->setState('list.limit', $limit);

    //Get the limitstart variable (used for the pagination) from the form variable.
    $limitstart = $app->input->get('limitstart', 0, 'uint');
    $this->setState('list.start', $limitstart);

    // Optional filter text
    $search = $this->getUserStateFromRequest($this->context.'.list.filter_search', 'filter-search');
    $this->setState('list.filter_search', $search);
    //$this->setState('list.filter_search', $app->input->getString('filter-search'));
    //Get the value of the select list and load it in the session.
    $filterOrdering = $this->getUserStateFromRequest($this->context.'.list.filter_ordering', 'filter-ordering');
    $this->setState('list.filter_ordering', $filterOrdering);
    //$this->setState('list.filter_ordering', $app->input->getString('filter-ordering'));
    $sripfId = $this->getUserStateFromRequest($this->context.'.list.filter_sripf_id', 'filter_sripf_id');
    $this->setState('list.filter_sripf_id', $sripfId);
    //$this->setState('list.filter_sripf', $app->input->getString('sripf_id'));
    $specialityId = $this->getUserStateFromRequest($this->context.'.list.filter_speciality_id', 'filter_speciality_id');
    $this->setState('list.filter_speciality_id', $specialityId);
    //$this->setState('list.filter_speciality', $app->input->getString('speciality_id'));

    $user = JFactory::getUser();
    $asset = 'com_snipf';

    if($pk) {
      $asset .= '.category.'.$pk;
    }

    //Check against the category permissions.
    if((!$user->authorise('core.edit.state', $asset)) && (!$user->authorise('core.edit', $asset))) {
      // limit to published for people who can't edit or edit.state.
      $this->setState('filter.published', 1);

      // Filter by start and end dates.
      $this->setState('filter.publish_date', true);
    }
    else {
      //User can access published, unpublished and archived persons.
      $this->setState('filter.published', array(0, 1, 2));
    }

    $this->setState('filter.language', JLanguageMultilang::isEnabled());
  }


  /**
   * Method to get a list of items.
   *
   * @return  mixed  An array of objects on success, false on failure.
   */
  public function getItems()
  {
    // Invoke the parent getItems method (using the getListQuery method) to get the main list
    $items = parent::getItems();
    $input = JFactory::getApplication()->input;

    //Get some user data.
    $user = JFactory::getUser();
    $userId = $user->get('id');
    $guest = $user->get('guest');
    $groups = $user->getAuthorisedViewLevels();

    // Convert the params field into an object, saving original in _params
    foreach($items as $item) {
      //Get the person parameters only.
      $personParams = new JRegistry;
      $personParams->loadString($item->params);
      //Set the params attribute, eg: the merged global and menu parameters set
      //in the populateState function.
      $item->params = clone $this->getState('params');

      // For Blog layout, person params override menu item params only if menu param='use_person'.
      // Otherwise, menu item params control the layout.
      // If menu item is 'use_person' and there is no person param, use global.
      if($input->getString('layout') == 'blog' || $this->getState('params')->get('layout_type') == 'blog') {
	// Create an array of just the params set to 'use_person'
	$menuParamsArray = $this->getState('params')->toArray();
	$personArray = array();

	foreach($menuParamsArray as $key => $value) {
	  if($value === 'use_person') {
	    // If the person has a value, use it
	    if($personParams->get($key) != '') {
	      // Get the value from the person
	      $personArray[$key] = $personParams->get($key);
	    }
	    else {
	      // Otherwise, use the global value
	      $personArray[$key] = $globalParams->get($key);
	    }
	  }
	}

	// Merge the selected person params
	if(count($personArray) > 0) {
	  $personParams = new JRegistry;
	  $personParams->loadArray($personArray);
	  $item->params->merge($personParams);
	}
      }
      else { //Default layout (list).
	// Merge all of the person params.
	//Person: Person params (if they are defined) override global/menu params.
	$item->params->merge($personParams);
      }

      // Compute the asset access permissions.
      // Technically guest could edit a person, but lets not check that to improve performance a little.
      if(!$guest) {
	$asset = 'com_snipf.person.'.$item->id;

	// Check general edit permission first.
	if($user->authorise('core.edit', $asset)) {
	  $item->params->set('access-edit', true);
	}
	// Now check if edit.own is available.
	elseif(!empty($userId) && $user->authorise('core.edit.own', $asset)) {
	  // Check for a valid user and that they are the owner.
	  if($userId == $item->created_by) {
	    $item->params->set('access-edit', true);
	  }
	}
      }

      $access = $this->getState('filter.access');
      //Set the access view parameter.
      if($access) {
	// If the access filter has been set, we already have only the persons this user can view.
	$item->params->set('access-view', true);
      }
      else { // If no access filter is set, the layout takes some responsibility for display of limited information.
	if($item->catid == 0 || $item->category_access === null) {
	  //In case the person is not linked to a category, we just check permissions against the person access.
	  $item->params->set('access-view', in_array($item->access, $groups));
	}
	else { //Check the user permissions against the person access as well as the category access.
	  $item->params->set('access-view', in_array($item->access, $groups) && in_array($item->category_access, $groups));
	}
      }

      //Set the type of date to display, (default layout only).
      if($this->getState('params')->get('layout_type') != 'blog'
	  && $this->getState('params')->get('list_show_date')
	  && $this->getState('params')->get('order_date')) {
	switch($this->getState('params')->get('order_date')) {
	  case 'modified':
		  $item->displayDate = $item->modified;
		  break;

	  case 'published':
		  $item->displayDate = ($item->publish_up == 0) ? $item->created : $item->publish_up;
		  break;

	  default: //created
		  $item->displayDate = $item->created;
	}
      }

      // Get the tags
      $item->tags = new JHelperTags;
      $item->tags->getItemTags('com_snipf.person', $item->id);
    }

    return $items;
  }



  /**
   * Method to build an SQL query to load the list data (person items).
   *
   * @return  string    An SQL query
   * @since   1.6
   */
  protected function getListQuery()
  {
    $user = JFactory::getUser();
    $groups = implode(',', $user->getAuthorisedViewLevels());

    // Create a new query object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $nowDate = $db->quote(JFactory::getDate()->toSql());
    $currentYear = date('Y');

    // Select required fields from the categories.
    $query->select($this->getState('list.select', 'p.id,p.lastname,p.firstname,p.alias,p.intro_text,p.full_text,p.catid,p.published,'.
	                           'p.person_title,p.checked_out,p.checked_out_time,p.created,p.created_by,p.access,p.params,'.
				   'p.metadata,p.metakey,p.metadesc,p.hits,p.publish_up,p.publish_down,p.language,p.modified,'.
				   'p.birthdate,p.modified_by'))
	  ->from($db->quoteName('#__snipf_person').' AS p')
	  //Display persons of the current category.
	  ->where('p.catid='.(int)$this->getState('category.id'));

    // Join on category table.
    $query->select('ca.title AS category_title, ca.alias AS category_alias, ca.access AS category_access')
	  ->join('LEFT', '#__categories AS ca on ca.id = p.catid');

    // Join over the categories to get parent category titles
    $query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
	  ->join('LEFT', '#__categories as parent ON parent.id = ca.parent_id');

    // Join over the users.
    $query->select('us.name AS author')
	  ->join('LEFT', '#__users AS us ON us.id = p.created_by');

    // Join over the asset groups.
    $query->select('al.title AS access_level');
    $query->join('LEFT', '#__viewlevels AS al ON al.id = p.access');

    // Filter by access level.
    if($access = $this->getState('filter.access')) {
      $query->where('p.access IN ('.$groups.')')
	    ->where('ca.access IN ('.$groups.')');
    }

    // Join over the address and the sripf.
    $query->select('ha.sripf_id, sr.name AS sripf_name, ha.street AS street_ha, ha.additional_address AS additional_address_ha,'.
                   'ha.city AS city_ha, ha.postcode AS postcode_ha, ha.phone AS phone_ha, ha.mobile AS mobile_ha, ha.fax AS fax_ha,'.
		   'hac.lang_var AS country_lang_var_ha');
    $query->join('LEFT', '#__snipf_address AS ha ON ha.person_id=p.id AND ha.type="ha" AND ha.history=0')
	  ->join('LEFT', '#__snipf_sripf AS sr ON sr.id=ha.sripf_id')
	  ->join('LEFT', '#__snipf_country AS hac ON hac.alpha_2=ha.country_code');

    if($this->getState('list.person_type') == 'certified') {
      $query->where('(SELECT COUNT(*) FROM #__snipf_certificate AS c
		      WHERE c.person_id=p.id AND c.published=1 AND c.closure_reason="" AND c.end_date > '.$nowDate.') > 0 ');
    }
    elseif($this->getState('list.person_type') == 'membership') {
      $query->select('pa.street AS street_pa, pa.additional_address AS additional_address_pa,'.
	             'pa.city AS city_pa, pa.postcode AS postcode_pa, pa.phone AS phone_pa, pa.mobile AS mobile_pa,'.
		     'pa.fax AS fax_pa, pac.lang_var AS country_lang_var_pa')
	    ->join('INNER', '#__snipf_subscription AS sub ON sub.person_id=p.id AND sub.published=1')
	    ->join('LEFT', '#__snipf_process AS sp ON sp.item_id=sub.id AND sp.item_type="subscription" AND '.
		   'sp.year='.$db->Quote($currentYear).' AND sp.cads_payment=1')
	    ->join('LEFT', '#__snipf_address AS pa ON pa.person_id=p.id AND pa.type="pa" AND pa.history=0')
	    ->join('LEFT', '#__snipf_country AS pac ON pac.alpha_2=pa.country_code')
	    ->where('((sub.deregistration_date='.$db->Quote($db->getNullDate()).' OR sub.deregistration_date < sub.reinstatement_date) AND (sub.resignation_date='.$db->Quote($db->getNullDate()).' OR sub.resignation_date < sub.reinstatement_date))')
	    //Rules out the deceased persons.
	    ->where('p.status!="deceased"');
    }

    // Filter by state
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      //User is only allowed to see published persons.
      $query->where('p.published='.(int)$published);
    }
    elseif(is_array($published)) {
      //User is allowed to see persons with different states.
      JArrayHelper::toInteger($published);
      $published = implode(',', $published);
      $query->where('p.published IN ('.$published.')');
    }

    //Do not show expired persons to users who can't edit or edit.state.
    if($this->getState('filter.publish_date')) {
      // Filter by start and end dates.
      $nullDate = $db->quote($db->getNullDate());

      $query->where('(p.publish_up = '.$nullDate.' OR p.publish_up <= '.$nowDate.')')
	    ->where('(p.publish_down = '.$nullDate.' OR p.publish_down >= '.$nowDate.')');
    }

    // Filter by sripf
    if($sripfId = $this->getState('list.filter_sripf_id')) {
      $query->where('ha.sripf_id='.(int)$sripfId);
    }

    // Filter by speciality
    if($specialityId = $this->getState('list.filter_speciality_id')) {
      $query->where('(SELECT COUNT(*) FROM #__snipf_certificate AS c
		      WHERE c.person_id=p.id AND c.published=1 AND c.closure_reason="" 
		      AND c.end_date > '.$nowDate.' AND c.speciality_id='.(int)$specialityId.') > 0 ');
    }

    // Filter by language
    if($this->getState('filter.language')) {
      $query->where('p.language IN ('.$db->quote(JFactory::getLanguage()->getTag()).','.$db->quote('*').')');
    }

    // Filter by search in title
    $search = $this->getState('list.filter_search');
    //Get the field to search by.
    $field = $this->getState('params')->get('filter_field');
    if(!empty($search)) {

      if(is_numeric($search)) {
	$query->where('(SELECT COUNT(*) FROM #__snipf_certificate AS c
			WHERE c.person_id=p.id AND c.published=1 AND c.closure_reason="" 
			AND c.end_date > '.$nowDate.' AND c.bit_number_1988='.$db->Quote($search).') > 0 ');
      }
      else {
	$search = $db->quote('%'.$db->escape($search, true).'%');
	$query->where('(p.'.$field.' LIKE '.$search.')');
      }
    }

    //Get the persons ordering by default set in the menu options. (Person: sec stands for secondary). 
    $personOrderBy = $this->getState('params')->get('orderby_sec', 'rdate');
    //If persons are sorted by date (ie: date, rdate), order_date defines
    //which type of date should be used (ie: created, modified or publish_up).
    $personOrderDate = $this->getState('params')->get('order_date');
    //Get the field to use in the ORDER BY clause according to the orderby_sec option.
    $orderBy = SnipfHelperQuery::orderbySecondary($personOrderBy, $personOrderDate);

    //Filter by order (eg: the select list set by the end user).
    $filterOrdering = $this->getState('list.filter_ordering');
    //If the end user has define an order, we override the ordering by default.
    if(!empty($filterOrdering)) {
      $orderBy = SnipfHelperQuery::orderbySecondary($filterOrdering, $personOrderDate);
    }

    $query->order($orderBy);

    return $query;
  }


  /**
   * Method to get category data for the current category
   *
   * @param   integer  An optional ID
   *
   * @return  object
   * @since   1.5
   */
  public function getCategory()
  {
    if(!is_object($this->_item)) {
      $app = JFactory::getApplication();
      $menu = $app->getMenu();
      $active = $menu->getActive();
      $params = new JRegistry;

      if($active) {
	$params->loadString($active->params);
      }

      $options = array();
      $options['countItems'] = $params->get('show_cat_num_persons_cat', 1) || $params->get('show_empty_categories', 0);
      $categories = JCategories::getInstance('Snipf', $options);
      $this->_item = $categories->get($this->getState('category.id', 'root'));

      // Compute selected asset permissions.
      if(is_object($this->_item)) {
	$user = JFactory::getUser();
	$asset = 'com_snipf.category.'.$this->_item->id;

	// Check general create permission.
	if($user->authorise('core.create', $asset)) {
	  $this->_item->getParams()->set('access-create', true);
	}

	$this->_children = $this->_item->getChildren();
	$this->_parent = false;

	if($this->_item->getParent()) {
	  $this->_parent = $this->_item->getParent();
	}

	$this->_rightsibling = $this->_item->getSibling();
	$this->_leftsibling = $this->_item->getSibling(false);
      }
      else {
	$this->_children = false;
	$this->_parent = false;
      }
    }

    // Get the tags
    $this->_item->tags = new JHelperTags;
    $this->_item->tags->getItemTags('com_snipf.category', $this->_item->id);

    return $this->_item;
  }

  /**
   * Get the parent category
   *
   * @param   integer  An optional category id. If not supplied, the model state 'category.id' will be used.
   *
   * @return  mixed  An array of categories or false if an error occurs.
   */
  public function getParent()
  {
    if(!is_object($this->_item)) {
      $this->getCategory();
    }

    return $this->_parent;
  }

  /**
   * Get the sibling (adjacent) categories.
   *
   * @return  mixed  An array of categories or false if an error occurs.
   */
  function &getLeftSibling()
  {
    if(!is_object($this->_item)) {
      $this->getCategory();
    }

    return $this->_leftsibling;
  }

  function &getRightSibling()
  {
    if(!is_object($this->_item)) {
      $this->getCategory();
    }

    return $this->_rightsibling;
  }

  /**
   * Get the child categories.
   *
   * @param   integer  An optional category id. If not supplied, the model state 'category.id' will be used.
   *
   * @return  mixed  An array of categories or false if an error occurs.
   * @since   1.6
   */
  function &getChildren()
  {
    if(!is_object($this->_item)) {
      $this->getCategory();
    }

    // Order subcategories
    if(count($this->_children)) {
      $params = $this->getState()->get('params');

      if($params->get('orderby_pri') == 'alpha' || $params->get('orderby_pri') == 'ralpha') {
	jimport('joomla.utilities.arrayhelper');
	JArrayHelper::sortObjects($this->_children, 'title', ($params->get('orderby_pri') == 'alpha') ? 1 : -1);
      }
    }

    return $this->_children;
  }


  /**
   * Increment the hit counter for the category.
   *
   * @param   int  $pk  Optional primary key of the category to increment.
   *
   * @return  boolean True if successful; false otherwise and internal error set.
   *
   * @since   3.2
   */
  public function hit($pk = 0)
  {
    $input = JFactory::getApplication()->input;
    $hitcount = $input->getInt('hitcount', 1);

    if($hitcount) {
      $pk = (!empty($pk)) ? $pk : (int) $this->getState('category.id');

      $table = JTable::getInstance('Category', 'JTable');
      $table->load($pk);
      $table->hit($pk);
    }

    return true;
  }


  /**
   * Returns person's lastname suggestions for a given search request.
   *
   * @param   int  $pk  	Optional primary key of the current tag.
   * @param   string $search 	The request search to get the matching lastname suggestions.
   *
   * @return  mixed		An array of suggestion results.
   *
   */
  public function getAutocompleteSuggestions($pk = null, $search)
  {
    $pk = (!empty($pk)) ? $pk : (int) $this->getState('category.id');
    $results = array();
    $personType = $this->getState('list.person_type');

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('p.lastname AS value, p.id AS data')
	  ->from('#__snipf_person AS p')
	  ->where('p.catid='.(int)$pk)
	  ->where('p.published=1')
	  ->where('p.lastname LIKE '.$db->Quote($search.'%'));

    if($personType == 'certified') {
      $nowDate = $db->quote(JFactory::getDate()->toSql());
      $query->where('(SELECT COUNT(*) FROM #__snipf_certificate AS c
		      WHERE c.person_id=p.id AND c.closure_reason="" AND c.end_date > '.$nowDate.') > 0 ');
    }
    else { //membership
      $query->select('IFNULL(sub.id, "0") AS subscription_id, sub.resignation_date, sub.deregistration_date, sub.reinstatement_date')
	    ->join('LEFT', '#__snipf_subscription AS sub ON sub.person_id=p.id AND sub.published=1')
	    ->where('((sub.deregistration_date='.$db->Quote($db->getNullDate()).' OR sub.deregistration_date < sub.reinstatement_date) AND (sub.resignation_date='.$db->Quote($db->getNullDate()).' OR sub.resignation_date < sub.reinstatement_date))');
    }

    $query->order('p.lastname DESC');
    $db->setQuery($query);
    //Requested to get the JQuery autocomplete working properly.
    $results['suggestions'] = $db->loadAssocList();

    return $results;
  }
}



