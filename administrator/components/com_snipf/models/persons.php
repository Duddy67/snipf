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
				       'cqp1', 'p.cqp1',
				       'sripf_id', 'a.sripf_id',
				       'status', 'p.status',
				       'certification_status',
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

    $certificationStatus = $this->getUserStateFromRequest($this->context.'.filter.certification_status', 'filter_certification_status');
    $this->setState('filter.certification_status', $certificationStatus);

    $subscriptionStatus = $this->getUserStateFromRequest($this->context.'.filter.subscription_status', 'filter_subscription_status');
    $this->setState('filter.subscription_status', $subscriptionStatus);

    $cqp1 = $this->getUserStateFromRequest($this->context.'.filter.cqp1', 'filter_cqp1');
    $this->setState('filter.cqp1', $cqp1);

    $sripfId = $this->getUserStateFromRequest($this->context.'.filter.sripf_id', 'filter_sripf_id');
    $this->setState('filter.sripf_id', $sripfId);

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
    $id .= ':'.$this->getState('filter.certification_status');
    $id .= ':'.$this->getState('filter.subscription_status');
    $id .= ':'.$this->getState('filter.cqp1');
    $id .= ':'.$this->getState('filter.sripf_id');
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
    $now = JFactory::getDate()->toSql();

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'p.id,p.lastname,p.firstname,p.alias,p.created,p.published,p.catid,p.hits,'.
				   'p.status,p.access,p.ordering,p.created_by,p.checked_out,'.
				   'p.cqp1,p.old_id,p.checked_out_time,p.language'))
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
    $query->select('IFNULL(sub.id, "0") AS subscription_id, sub.resignation_date, sub.deregistration_date, sub.reinstatement_date')
	  ->join('LEFT', '#__snipf_subscription AS sub ON sub.person_id=p.id AND sub.published=1');

    //Gets the sripf id from the current home address.
    $query->select('a.sripf_id, sr.name AS sripf_name')
	  ->join('LEFT', '#__snipf_address AS a ON a.person_id=p.id AND a.type="ha" AND a.history=0')
	  ->join('LEFT', '#__snipf_sripf AS sr ON sr.id=a.sripf_id');

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

    // Filter by certification status.
    if(!empty($certificationStatus = $this->getState('filter.certification_status'))) {
      //Sets up an array containing the end of query for each status.
      $certStatus = array();
      $certStatus['certified'] = 'c.closure_reason="" AND c.end_date > '.$db->Quote($now).') > 0 ';
      $certStatus['outdated'] = 'c.closure_reason="" AND c.end_date < '.$db->Quote($now).' AND c.end_date != '.$db->Quote($db->getNullDate()).') > 0 ';
      $certStatus['formerly_certified'] = '(c.closure_reason="retired" OR c.closure_reason="deceased")) > 0 ';
      $certStatus['no_certificate'] = 'c.end_date > '.$db->Quote($db->getNullDate()).') = 0 ';
      $certStatus['no_longer_certified'] = '(c.closure_reason="removal" OR c.closure_reason="rejected_file" OR c.closure_reason="abandon"  OR c.closure_reason="other") AND c.end_date > '.$db->Quote($db->getNullDate()).') > 0 ';

      //Sets the where clause with the corresponding query.
      $query->where('(SELECT COUNT(*) FROM #__snipf_certificate AS c
		      WHERE c.person_id=p.id AND c.published=1 AND '.$certStatus[$certificationStatus]);

      //The no_longer_certified and outdated statuses are trickier.
      if($certificationStatus == 'no_longer_certified') {
        //Removes the " > 0" from the end of the queries.
	$certified = substr($certStatus['certified'], 0, -5);
	$formerlyCertified = substr($certStatus['formerly_certified'], 0, -5);

	//Ensures also that no certified or formerly_certified certificate is found.
	$query->where('(SELECT COUNT(*) FROM #__snipf_certificate AS c
			WHERE c.person_id=p.id AND c.published=1 AND '.$certified.' = 0 ');

	$query->where('(SELECT COUNT(*) FROM #__snipf_certificate AS c
			WHERE c.person_id=p.id AND c.published=1 AND '.$formerlyCertified.' = 0 ');
      }
      elseif($certificationStatus == 'outdated') {
	$certified = substr($certStatus['certified'], 0, -5);
	//Ensures also that no certified certificate is found.
	$query->where('(SELECT COUNT(*) FROM #__snipf_certificate AS c
			WHERE c.person_id=p.id AND c.published=1 AND '.$certified.' = 0 ');
      }
    }

    // Filter by subscription status.
    if(!empty($subscriptionStatus = $this->getState('filter.subscription_status'))) {
      if($subscriptionStatus == 'membership') {
	$query->where('((sub.deregistration_date='.$db->Quote($db->getNullDate()).' AND sub.resignation_date='.$db->Quote($db->getNullDate()).') OR sub.reinstatement_date > '.$db->Quote($db->getNullDate()).')');
      }
      else { //no_membership
	$query->where('(ISNULL(sub.id) OR ((sub.deregistration_date > '.$db->Quote($db->getNullDate()).' OR sub.resignation_date > '.$db->Quote($db->getNullDate()).') AND sub.reinstatement_date='.$db->Quote($db->getNullDate()).'))');
      }
    }

    // Filter by cqp1.
    if(!empty($cqp1 = $this->getState('filter.cqp1'))) {
      if($cqp1) {
	$query->where('p.cqp1=1');
      }
      else {
	$query->where('p.cqp1=0');
      }
    }

    //Filter by sripf.
    $sripfId = $this->getState('filter.sripf_id');
    if(is_numeric($sripfId)) {
      $query->where('a.sripf_id='.(int)$sripfId);
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


