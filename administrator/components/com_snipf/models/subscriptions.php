<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class SnipfModelSubscriptions extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 's.id',
	      'name', 's.name',
	      'lastname', 'p.lastname',
	      'firstname', 'p.firstname',
	      'status', 'p.status',
	      'sripf_id', 'a.sripf_id',
	      'created', 's.created',
	      'created_by', 's.created_by',
	      'published', 's.published',
	      'person_status', 'subscription_status', 'payment_status',
	      'user', 'user_id', 'person_id', 'since_year'
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

    $userId = $app->getUserStateFromRequest($this->context.'.filter.user_id', 'filter_user_id');
    $this->setState('filter.user_id', $userId);

    $published = $this->getUserStateFromRequest($this->context.'.filter.published', 'filter_published', '');
    $this->setState('filter.published', $published);

    $personStatus = $this->getUserStateFromRequest($this->context.'.filter.person_status', 'filter_person_status');
    $this->setState('filter.person_status', $personStatus);

    $sripfId = $this->getUserStateFromRequest($this->context.'.filter.sripf_id', 'filter_sripf_id');
    $this->setState('filter.sripf_id', $sripfId);

    $sinceYear = $this->getUserStateFromRequest($this->context.'.filter.since_year', 'filter_since_year');
    $this->setState('filter.since_year', $sinceYear);

    $paymentStatus = $this->getUserStateFromRequest($this->context.'.filter.payment_status', 'filter_payment_status');
    $this->setState('filter.payment_status', $paymentStatus);

    $subscriptionStatus = $this->getUserStateFromRequest($this->context.'.filter.subscription_status', 'filter_subscription_status');
    $this->setState('filter.subscription_status', $subscriptionStatus);

    // List state information.
    parent::populateState('s.name', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.user_id');
    $id .= ':'.$this->getState('filter.person_status');
    $id .= ':'.$this->getState('filter.sripf_id');
    $id .= ':'.$this->getState('filter.payment_status');
    $id .= ':'.$this->getState('filter.subscription_status');
    $id .= ':'.$this->getState('filter.since_year');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $currentYear = date('Y');
    $now = JFactory::getDate()->toSql();

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 's.id, s.name, s.created, s.published, s.deregistration_date, s.resignation_date,'.
				   's.reinstatement_date, s.created_by, s.checked_out, s.checked_out_time'));

    $query->from('#__snipf_subscription AS s');

    //Subscription must be linked to a person.
    $query->select('p.lastname, p.firstname, p.id AS person_id, p.status, p.cqp1');
    $query->join('INNER', '#__snipf_person AS p ON p.id = s.person_id');

    //Get the process containing the current year.
    $query->select('pr.number AS current_year_process, pr.cads_payment, pr.payment_date');
    $query->join('LEFT', '#__snipf_process AS pr ON pr.item_id=s.id AND pr.item_type="subscription" AND pr.year='.$db->Quote($currentYear));

    //Get the process containing the last known year.
    $query->select('lp.year AS last_registered_year');
    $query->join('LEFT', '#__snipf_process AS lp ON lp.item_id=s.id AND lp.item_type="subscription" AND lp.is_last=1');

    //Gets the sripf id from the current home address.
    $query->select('a.sripf_id, sr.name AS sripf_name')
	  ->join('LEFT', '#__snipf_address AS a ON a.person_id=p.id AND a.type="ha" AND a.history=0')
	  ->join('LEFT', '#__snipf_sripf AS sr ON sr.id=a.sripf_id');

    //Get the user name.
    $query->select('u.name AS user');
    $query->join('LEFT', '#__users AS u ON u.id = s.created_by');

    //Filter by title search.
    $search = $this->getState('filter.search');
    if(!empty($search)) {
      if(stripos($search, 'id:') === 0) {
	$query->where('p.person_id = '.(int) substr($search, 3));
      }
      else {
	$search = $db->Quote('%'.$db->escape($search, true).'%');
	$query->where('(p.lastname LIKE '.$search.')');
      }
    }

    //Filter by publication state.
    $published = $this->getState('filter.published');
    if(is_numeric($published)) {
      $query->where('s.published='.(int)$published);
    }
    elseif($published === '') {
      $query->where('(s.published IN (0, 1))');
    }

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor');
    $query->join('LEFT', '#__users AS uc ON uc.id=s.checked_out');

    //Filter by user.
    $userId = $this->getState('filter.user_id');
    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('s.created_by'.$type.(int) $userId);
    }

    // Filter by person status.
    if(!empty($personStatus = $this->getState('filter.person_status'))) {
      if($personStatus == 'certified') {
	$query->where('(SELECT COUNT(*) FROM #__snipf_certificate AS c
			WHERE c.person_id=p.id AND c.published=1 AND c.closure_reason="" AND c.end_date > '.$db->Quote($now).') > 0 ');
      }
      elseif($personStatus == 'no_longer_certified') {
	//The no_longer_certified status is trickier.
	$query->where('(SELECT COUNT(*) FROM #__snipf_certificate AS c
			WHERE c.person_id=p.id AND c.published=1 AND (c.closure_reason="removal" OR
			      c.closure_reason="rejected_file" OR c.closure_reason="abandon"  OR
			      c.closure_reason="other") AND c.end_date > '.$db->Quote($db->getNullDate()).') > 0 ');

	//Ensures also that no certified or formerly_certified certificate is found.
	$query->where('(SELECT COUNT(*) FROM #__snipf_certificate AS c
			WHERE c.person_id=p.id AND c.published=1 AND c.closure_reason="" AND c.end_date > '.$db->Quote($now).') = 0 ');

	$query->where('(SELECT COUNT(*) FROM #__snipf_certificate AS c
			WHERE c.person_id=p.id AND c.published=1 AND (c.closure_reason="retired" OR c.closure_reason="deceased")) = 0 ');
      }
      elseif($personStatus == 'retired' || $personStatus == 'deceased') {
	$query->where('p.status='.$db->Quote($personStatus));
      }
      elseif($personStatus == 'cqp1') {
	$query->where('p.cqp1=1');
      }
      elseif($personStatus == 'retired_cqp1') {
	$query->where('p.status="retired" AND p.cqp1=1');
      }
      elseif($personStatus == 'deceased_cqp1') {
	$query->where('p.status="deceased" AND p.cqp1=1');
      }
    }

    // Filter by payment status.
    if(!empty($paymentStatus = $this->getState('filter.payment_status'))) {
      if($paymentStatus == 'paid') {
	$query->where('pr.number > 0 AND pr.cads_payment=1');
      }
      elseif($paymentStatus == 'partially_paid') {
	$query->where('pr.number > 0 AND pr.cads_payment=0');
      }
      else { //unpaid
	$query->where('ISNULL(pr.number) AND lp.year!=""');
      }
    }

    // Filter by subscription status.
    if(!empty($subscriptionStatus = $this->getState('filter.subscription_status'))) {
      if($subscriptionStatus == 'membership') {
	$query->where('((s.deregistration_date='.$db->Quote($db->getNullDate()).' AND s.resignation_date='.$db->Quote($db->getNullDate()).') OR s.reinstatement_date > '.$db->Quote($db->getNullDate()).')');
      }
      else { //no_longer_membership
       $query->where('((s.deregistration_date > '.$db->Quote($db->getNullDate()).' OR s.resignation_date > '.$db->Quote($db->getNullDate()).') AND s.reinstatement_date='.$db->Quote($db->getNullDate()).')');
	//Rules out the deceased persons from the search.
	$query->where('p.status!="deceased"');
      }
    }

    //Filter by sripf.
    $sripfId = $this->getState('filter.sripf_id');
    if(is_numeric($sripfId)) {
      $query->where('a.sripf_id='.(int) $sripfId);
    }

    //Filter by year (only for outdated subscriptions).
    if(!empty($sinceYear = $this->getState('filter.since_year'))) {
      $query->where('lp.year='.$db->Quote($sinceYear));
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 's.name');
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


