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
	      'certificate_state', 'from_date',
	      'to_date', 'speciality'
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

    $certificateState = $this->getUserStateFromRequest($this->context.'.filter.certificate_state', 'filter_certificate_state');
    $this->setState('filter.certificate_state', $certificateState);

    $fromDate = $this->getUserStateFromRequest($this->context.'.filter.from_date', 'filter_from_date');
    $this->setState('filter.from_date', $fromDate);

    $toDate = $this->getUserStateFromRequest($this->context.'.filter.to_date', 'filter_to_date');
    $this->setState('filter.to_date', $toDate);

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
    $id .= ':'.$this->getState('filter.from_date');
    $id .= ':'.$this->getState('filter.to_date');

    return parent::getStoreId($id);
  }


  protected function getListQuery()
  {
    //Create a new JDatabaseQuery object.
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select($this->getState('list.select', 'c.id, c.number, c.end_date, c.closure_reason, c.created, c.published,'.
				   'c.closure_date, c.created_by, c.checked_out, c.checked_out_time'));

    $query->from('#__snipf_certificate AS c');

    //Certificate must be linked to a person.
    $query->select('p.lastname, p.firstname, p.id AS person_id');
    $query->join('INNER', '#__snipf_person AS p ON p.id = c.person_id');

    //Get the user name.
    $query->select('u.name AS user');
    $query->join('LEFT', '#__users AS u ON u.id = c.created_by');

    $query->select('s.name AS speciality');
    $query->join('LEFT', '#__snipf_speciality AS s ON s.id = c.speciality_id');

    //Gets the last process (if any) linked to the certificate.
    $query->select('pr.number AS process_nb, pr.name AS process_name, pr.outcome, pr.return_file_number,'.
	           'pr.end_process, pr.commission_date, pr.file_receiving_date');
    $query->join('LEFT', '#__snipf_process AS pr ON pr.item_id=c.id AND pr.item_type="certificate" AND pr.is_last=1');

    //Gets the first process (if any) linked to the certificate (used for date filters).
    $query->select('IFNULL(fpr.commission_date, "'.$db->getNullDate().'") AS first_commission_date');
    $query->join('LEFT', '#__snipf_process AS fpr ON fpr.item_id=c.id AND fpr.item_type="certificate" AND fpr.number=1');

    //Gets the subscription id of the person (if any).
    $query->select('IFNULL(sub.id, "0") AS subscription_id')
	  ->join('LEFT', '#__snipf_subscription AS sub ON sub.person_id=p.id AND sub.published=1');

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

    //Filter by certificate state.
    $certificateState = $this->getState('filter.certificate_state');
    if(!empty($certificateState)) {
      $this->filterByCertificateState($query, $certificateState);
    }

    //Filter by dates.
    $this->filterByDates($query);


    //Add the list to the sort.
    $orderCol = $this->state->get('list.ordering', 'c.number');
    $orderDirn = $this->state->get('list.direction'); //asc or desc

    $query->order($db->escape($orderCol.' '.$orderDirn));

    return $query;
  }


  /**
   * Filters the main query according to the given certificate state.
   *
   * @param   object   $query  A valid query object.
   * @param   string   $state  The certificate state to filter.
   *
   * @return  void
   *
   */
  protected function filterByCertificateState(&$query, $state)
  {
    $db = $this->getDbo();
    $nullDate = $db->getNullDate();
    $now = JFactory::getDate()->toSql();

    switch($state) {
      case 'all_running':
	  $query->where('c.end_date > '.$db->Quote($now).' AND c.closure_date='.$db->Quote($nullDate));
	break;

      case 'running_commission_pending':
	  $query->where('c.end_date > '.$db->Quote($now).' AND c.closure_date='.$db->Quote($nullDate))
	        ->where('pr.commission_date > '.$db->Quote($nullDate).' AND pr.outcome="pending"');
	break;

      case 'running_file_pending':
	  $query->where('c.end_date > '.$db->Quote($now).' AND c.closure_date='.$db->Quote($nullDate))
	        ->where('pr.file_receiving_date='.$db->Quote($nullDate));
	break;

      case 'running':
	  $query->where('c.end_date > '.$db->Quote($now).' AND c.closure_date='.$db->Quote($nullDate))
	        ->where('pr.outcome="accepted"');
	break;

      case 'initial_running':
	  $query->where('c.end_date > '.$db->Quote($now).' AND c.closure_date='.$db->Quote($nullDate))
	        //Gets only persons who have no subscription yet.
	        ->where('pr.number = 1 AND pr.outcome="accepted" AND ISNULL(sub.id)');
	break;

      case 'all_outdated':
	  $query->where('c.end_date < '.$db->Quote($now).' AND c.end_date > '.$db->Quote($nullDate))
	        ->where('c.closure_date='.$db->Quote($nullDate));
	break;

      case 'outdated_commission_pending':
	  $query->where('c.end_date < '.$db->Quote($now).' AND c.end_date > '.$db->Quote($nullDate))
	        ->where('c.closure_date='.$db->Quote($nullDate).' AND pr.commission_date > '.$db->Quote($nullDate))
		->where('(pr.outcome="pending" OR pr.outcome="adjourned")');
	break;

      case 'outdated_file_pending':
	  $query->where('c.end_date < '.$db->Quote($now).' AND c.end_date > '.$db->Quote($nullDate))
	        ->where('c.closure_date='.$db->Quote($nullDate).' AND pr.file_receiving_date='.$db->Quote($nullDate));
	break;

      case 'outdated':
	  $query->where('c.end_date < '.$db->Quote($now).' AND c.end_date > '.$db->Quote($nullDate))
	        ->where('c.closure_date='.$db->Quote($nullDate).' AND pr.outcome="accepted"');
	break;

      case 'all_pending':
	  $query->where('pr.end_process='.$db->Quote($nullDate).' AND c.closure_date='.$db->Quote($nullDate));
	break;

      case 'initial_pending':
	  $query->where('pr.number=1 AND pr.file_receiving_date='.$db->Quote($nullDate));
	break;

      case 'initial_commission_pending':
	  $query->where('pr.number=1 AND pr.commission_date > '.$db->Quote($nullDate))
		->where('(pr.outcome="pending" OR pr.outcome="adjourned")');
	break;

      case 'all_invalidated':
	  $query->where('(c.closure_reason="removal" OR c.closure_reason="rejected_file" OR '.
		        ' c.closure_reason="abandon" OR c.closure_reason="other")');
	break;

      case 'rejected':
	  $query->where('c.closure_reason="rejected_file"');
	break;

      case 'removal':
	  $query->where('c.closure_reason="removal"');
	break;

      case 'abandon':
	  $query->where('c.closure_reason="abandon"');
	break;

      case 'other':
	  $query->where('c.closure_reason="other"');
	break;

      case 'retired':
	  $query->where('c.closure_reason="retired"');
	break;

      case 'deceased':
	  $query->where('c.closure_reason="deceased"');
	break;

      case 'no_process':
	  $query->where('pr.number IS NULL');
	break;
    }
  }


  /**
   * Filters the main query according to the given date filters.
   *
   * @param   object   $query  A valid query object.
   *
   * @return  void
   *
   */
  protected function filterByDates(&$query)
  {
    $fromDate = $this->getState('filter.from_date');
    $toDate = $this->getState('filter.to_date');

    //Don't do anything if one of the date is empty.
    if(empty($fromDate) || empty($toDate)) {
      return;
    }

    $filterDates = array('from_date' => $fromDate, 'to_date' => $toDate);

    //Date filters don't show time.
    $format = JText::_('DATE_FORMAT_FILTER_DATE');
    // Get the server timezone setting (server_utc).
    $offset = JFactory::getConfig()->get('offset');

    //Converts date as an SQL formatted datetime string in UTC.
    foreach($filterDates as $key => $filterDate) {
      $date = date_parse_from_format($format, $filterDate);
      $filterDate = (int)$date['year'].'-'.(int)$date['month'].'-'.(int)$date['day'];
      $filterDates[$key] = JFactory::getDate($filterDate, $offset)->toSql();
    }

    if($filterDates['from_date'] > $filterDates['to_date']) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_SNIPF_MISMATCH_FILTER_DATES'), 'warning');
      return;
    }

    $db = $this->getDbo();
    $nullDate = $db->getNullDate();

    if($filterDates['from_date'] == $filterDates['to_date']) {
      //Strict mode. Fetches only certificates where the commission date of the initial
      //process matches the given filter date.
      $query->where('fpr.commission_date = '.$db->Quote($filterDates['from_date']));
    }
    else {
      //Fetches the certificates which matche the date filters gap.
      $query->where('c.end_date > '.$db->Quote($nullDate))
	    ->where('(fpr.commission_date >= '.$db->Quote($filterDates['from_date']).' AND '.$db->Quote($filterDates['to_date']).' <= c.end_date)');
    }
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


