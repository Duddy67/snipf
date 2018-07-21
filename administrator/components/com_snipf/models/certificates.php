<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modellist');



class SnipfModelCertificates extends JModelList
{
  public function __construct($config = array())
  {
    if(empty($config['filter_fields'])) {
      $config['filter_fields'] = array(
	      'id', 'c.id',
	      'number', 'c.number',
	      'pr.number', 'process_nb',
	      'lastname', 'p.lastname',
	      'firstname', 'p.firstname',
	      'end_date', 'c.end_date',
	      'created', 'c.created',
	      'created_by', 'c.created_by',
	      'published', 'c.published',
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

    $certificateState = $this->getUserStateFromRequest($this->context.'.filter.certificate_state', 'filter_certificate_state', '');
    $this->setState('filter.certificate_state', $published);

    // List state information.
    parent::populateState('p.lastname', 'asc');
  }


  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':'.$this->getState('filter.search');
    $id .= ':'.$this->getState('filter.published');
    $id .= ':'.$this->getState('filter.user_id');
    $id .= ':'.$this->getState('filter.certificate_state');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'c.id, c.number, c.end_date, c.closure_reason, c.created, c.published,'.
				   'c.created_by, c.checked_out, c.checked_out_time'));

    $query->from('#__snipf_certificate AS c');

    //Certificate must be linked to a person.
    $query->select('p.lastname, p.firstname, p.id AS person_id');
    $query->join('INNER', '#__snipf_person AS p ON p.id = c.person_id');

    //Get the user name.
    $query->select('u.name AS user');
    $query->join('LEFT', '#__users AS u ON u.id = c.created_by');

    //Gets the last process (if any) linked to the certificate.
    $query->select('pr.number AS process_nb, pr.name AS process_name, pr.outcome, pr.return_file_number, pr.file_receiving_date');
    $query->join('LEFT', '#__snipf_process AS pr ON pr.item_id=c.id AND pr.item_type="certificate" AND pr.is_last=1');

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
      $query->where('c.published='.(int)$published);
    }
    elseif($published === '') {
      $query->where('(c.published IN (0, 1))');
    }

    // Join over the users for the checked out user.
    $query->select('uc.name AS editor');
    $query->join('LEFT', '#__users AS uc ON uc.id=c.checked_out');

    //Filter by user.
    $userId = $this->getState('filter.user_id');
    if(is_numeric($userId)) {
      $type = $this->getState('filter.user_id.include', true) ? '= ' : '<>';
      $query->where('c.created_by'.$type.(int) $userId);
    }

    //Filter by publication state.
    $certificateState = $this->getState('filter.certificate_state');
    if(!empty($certificateState)) {
      //
    }

    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 'c.number');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }
}


