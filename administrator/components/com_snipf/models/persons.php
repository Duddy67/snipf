<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class SnipfModelPersons extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array('id', 'p.id',
				       'lastname', 'p.lastname', 
				       'firstname', 'p.firstname', 
				       'alias', 'p.alias',
				       'created', 'p.created', 
				       'created_by', 'p.created_by',
				       'published', 'p.published', 
			               'access', 'p.access', 'access_level',
				       'user', 'user_id',
				       'ordering', 'p.ordering', 'tm.ordering', 'tm_ordering',
				       'language', 'p.language',
				       'hits', 'p.hits',
				       'status', 'p.status',
				       'certificate_status', 'p.certificate_status',
				       'subscription_status',
				       'catid', 'p.catid', 'category_id',
				       'tag'
				      );
    }

    parent::__construct($config);
  }


  protected function populateState($ordering = null, $direction = null)
  {
    // Initialise variables.
    $app = JFactory::getApplication();
    $session = JFactory::getSession();

    // Adjust the context to support modal layouts.
    if($layout = JFactory::getApplication()->input->get('layout')) {
      $this->context .= '.'.$layout;
    }

    //Get the state values set by the user.
    $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
    $this->setState('filter.search', $search);

    $access = $this->getUserStateFromRequest($this->context.'.filter.access', 'filter_access');
    $this->setState('filter.access', $access);

    $userId = $app->getUserStateFromRequest($this->context.'.filter.user_id', 'filter_user_id');
    $this->setState('filter.user_id', $userId);

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    $categoryId = $this->getUserStateFromRequest($this->context.'.filter.category_id', 'filter_category_id');
    $this->setState('filter.category_id', $categoryId);

    $personStatus = $this->getUserStateFromRequest($this->context.'.filter.status', 'filter_status');
    $this->setState('filter.status', $personStatus);

    $certificateStatus = $this->getUserStateFromRequest($this->context.'.filter.certificate_status', 'filter_certificate_status');
    $this->setState('filter.certificate_status', $certificateStatus);

    $subscriptionStatus = $this->getUserStateFromRequest($this->context.'.filter.subscription_status', 'filter_subscription_status');
    $this->setState('filter.subscription_status', $subscriptionStatus);

    $language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language');
    $this->setState('filter.language', $language);

    $tag = $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag');
    $this->setState('filter.tag', $tag);

    // List state information.
    parent::populateState('p.lastname', 'asc');

    // Force a language
    $forcedLanguage = $app->input->get('forcedLanguage');

    if(!empty($forcedLanguage)) {
      $this->setState('filter.language', $forcedLanguage);
      $this->setState('filter.forcedLanguage', $forcedLanguage);
    }
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.access');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.user_id');
    $id .= ':'.$this->getState('filter.category_id');
    $id .= ':'.$this->getState('filter.status');
    $id .= ':'.$this->getState('filter.certificate_status');
    $id .= ':'.$this->getState('filter.subscription_status');
    $id .= ':'.$this->getState('filter.language');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $user = JFactory::getUser();
    $currentYear = date("Y");

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'p.id,p.lastname,p.firstname,p.alias,p.created,p.published,p.catid,p.hits,'.
				   'p.status,p.certificate_status,p.access,p.ordering,p.created_by,p.checked_out,'.
				   'p.checked_out_time,p.language'))
	  ->from('#__snipf_person AS p');

    //Get the user name.
    $query->select('us.name AS user')
	  ->join('LEFT', '#__users AS us ON us.id = p.created_by');

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor')
	  ->join('LEFT', '#__users AS uc ON uc.id=p.checked_out');

    // Join over the categories.
    $query->select('ca.title AS category_title')
	  ->join('LEFT', '#__categories AS ca ON ca.id = p.catid');

    // Join over the language
    $query->select('lg.title AS language_title')
	  ->join('LEFT', $db->quoteName('#__languages').' AS lg ON lg.lang_code = p.language');

    // Join over the asset groups.
    $query->select('al.title AS access_level')
	  ->join('LEFT', '#__viewlevels AS al ON al.id = p.access');

    //Gets the subscription id of the person (if any).
    $query->select('IFNULL(sub.id, "0") AS subscription_id, sub.cqp1')
	  ->join('LEFT', '#__snipf_subscription AS sub ON sub.person_id=p.id AND sub.published=1');

    //Gets the cads payment of the subscription process matching the current year.
    $query->select('sp.item_id AS process_id, sp.cads_payment')
	  ->join('LEFT', '#__snipf_process AS sp ON sp.item_id=sub.id AND sp.item_type="subscription" AND sp.name='.$db->Quote($currentYear));

    //Filter by component category.
    $categoryId = $this->getState('filter.category_id');
    if(is_numeric($categoryId)) {
      $query->where('p.catid = '.(int)$categoryId);
    }
    elseif(is_array($categoryId)) {
      JArrayHelper::toInteger($categoryId);
      $categoryId = implode(',', $categoryId);
      $query->where('p.catid IN ('.$categoryId.')');
    }

    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('p.id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(p.lastname LIKE '.$search.')');
      }
    }

    // Filter by access level.
    if($access = $this->getState('filter.access')) {
      $query->where('p.access='.(int) $access);
    }

    // Filter by person status.
    if(!empty($personStatus = $this->getState('filter.status'))) {
      $query->where('p.status='.$db->Quote($personStatus));
    }

    // Filter by certificate status.
    if(!empty($certificateStatus = $this->getState('filter.certificate_status'))) {
      $query->where('p.certificate_status='.$db->Quote($certificateStatus));
    }

    // Filter by subscription status.
    if(!empty($subscriptionStatus = $this->getState('filter.subscription_status'))) {
      if($subscriptionStatus == 'membership') {
	$query->where('sub.id > 0 AND sp.cads_payment=1');
      }
      elseif($subscriptionStatus == 'unpaid') {
	$query->where('sub.id > 0 AND sp.item_id > 0 AND sp.cads_payment=0');
      }
      else { //no_membership
	$query->where('ISNULL(sub.id)');
      }
    }

    // Filter by access level on categories.
    if(!$user->authorise('core.admin')) {
      $groups = implode(',', $user->getAuthorisedViewLevels());
      $query->where('p.access IN ('.$groups.')');
      $query->where('ca.access IN ('.$groups.')');
    }

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('p.published='.(int)$published);
    }
    elseif($published === '') {
      $query->where('(p.published IN (0, 1))');
    }

    //Filter by user.
    $userId = $this->getState('filter.user_id');
    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('p.created_by'.$type.(int) $userId);
    }

    //Filter by language.
    if($language = $this->getState('filter.language')) {
      $query->where('p.language = '.$db->quote($language));
    }

    // Filter by a single tag.
    $tagId = $this->getState('filter.tag');

    if(is_numeric($tagId)) {
      $query->where($db->quoteName('tagmap.tag_id').' = '.(int)$tagId)
	    ->join('LEFT', $db->quoteName('#__contentitem_tag_map', 'tagmap').
		   ' ON '.$db->quoteName('tagmap.content_item_id').' = '.$db->quoteName('p.id').
		   ' AND '.$db->quoteName('tagmap.type_alias').' = '.$db->quote('com_snipf.person'));
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 'p.lastname');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }


  /**
   * Returns the data fetched by the current query.
   *
   *
   * @return array   The data as an array of items.
   */
  public function getDataFromCurrentQuery()
  {
    //Gets the POST array.
    $post = JFactory::getApplication()->input->post->getArray();

    //Sets filters and ordering states according to the current setting

    foreach($post['filter'] as $key => $value) {
      if(!empty($value)) {
	$this->setState('filter.'.$key, $value);
      }
    }

    if(!empty($post['list']['fullordering'])) {
      $this->setState('list.ordering', $post['list']['fullordering']);
    }

    //Gets the current query.
    $query = $this->getListQuery();

    $db = $this->getDbo();
    $db->setQuery($query);

    return $db->loadObjectList();
  }
}


