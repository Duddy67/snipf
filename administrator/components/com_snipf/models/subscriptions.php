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
	      'certificate_status', 'p.certificate_status',
	      'created', 's.created',
	      'created_by', 's.created_by',
	      'published', 's.published',
	      'person_status', 'subscription_status',
	      'user', 'user_id', 'person_id',
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
    $id .= ':'.$this->getState('filter.subscription_status');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $currentYear = date('Y');

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 's.id, s.name, s.created, s.published,s.cqp1,'.
				   's.created_by, s.checked_out, s.checked_out_time'));

    $query->from('#__snipf_subscription AS s');

    //Subscription must be linked to a person.
    $query->select('p.lastname, p.firstname, p.id AS person_id, p.status, p.certificate_status');
    $query->join('INNER', '#__snipf_person AS p ON p.id = s.person_id');

    //Get the process containing the current year.
    $query->select('pr.number AS current_year_process, pr.cads_payment');
    $query->join('LEFT', '#__snipf_process AS pr ON pr.item_id=s.id AND pr.item_type="subscription" AND pr.name='.$db->Quote($currentYear));

    //Get the process containing the current year.
    $query->select('lp.name AS last_registered_year');
    $query->join('LEFT', '#__snipf_process AS lp ON lp.item_id=s.id AND lp.item_type="subscription" AND lp.is_last=1');

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
      if($personStatus == 'certified' || $personStatus == 'no_longer_certified') {
	$query->where('p.certificate_status='.$db->Quote($personStatus));
      }
      elseif($personStatus == 'retired' || $personStatus == 'deceased') {
	$query->where('p.status='.$db->Quote($personStatus));
      }
      elseif($personStatus == 'cqp1') {
	$query->where('s.cqp1=1');
      }
      elseif($personStatus == 'retired_cqp1') {
	$query->where('p.status="retired" AND s.cqp1=1');
      }
      elseif($personStatus == 'deceased_cqp1') {
	$query->where('p.status="deceased" AND s.cqp1=1');
      }
    }

    // Filter by subscription status.
    if(!empty($subscriptionStatus = $this->getState('filter.subscription_status'))) {
      if($subscriptionStatus == 'paid') {
	$query->where('pr.number > 0 AND pr.cads_payment=1');
      }
      elseif($subscriptionStatus == 'unpaid') {
	$query->where('pr.number > 0 AND pr.cads_payment=0');
      }
      else { //outdated
	$query->where('ISNULL(pr.number) AND lp.name!=""');
      }
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 's.name');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }
}


