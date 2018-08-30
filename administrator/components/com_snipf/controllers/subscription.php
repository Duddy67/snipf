<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
require_once JPATH_COMPONENT.'/helpers/process.php';
 


class SnipfControllerSubscription extends JControllerForm
{

  public function save($key = null, $urlVar = null)
  {
    $post = $this->input->post->getArray();
    $id = $this->input->get('id', 0, 'int');
    //Gets the last process number (if any).
    $nbProcesses = $post['nb_processes'];

    $previousYear = 0;
    //Gets the previous year value (if any).
    if($nbProcesses > 1) {
      $penultimateProcessNb = $nbProcesses - 1;
      $previousYear = $post['year_'.$penultimateProcessNb];
    }

    //Checks that the current year value is correct and that is not lower or equal to the
    //previous year value (if any).
    if((int)$nbProcesses && (!preg_match('#^[1-9][0-9]{3}$#', $post['year_'.$nbProcesses]) ||
       $post['year_'.$nbProcesses] <= $previousYear)) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_SNIPF_WARNING_INVALID_YEAR'), 'warning');
      //Sets the process variable to 'create' so that the current process will be deleted
      //in case of cancelation
      $this->setRedirect('index.php?option='.$this->option.'&view='.$this->context.'&layout=edit&id='.(int)$id.'&process=create');
      return false;
    }

    $this->setProcessDates();

    //Hand over to the parent function.
    return parent::save($key = null, $urlVar = null);
  }


  //Overrided function.
  protected function allowEdit($data = array(), $key = 'id')
  {
    $itemId = $data['id'];
    $user = JFactory::getUser();

    //Get the item owner id.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('created_by')
	  ->from('#__snipf_subscription')
	  ->where('id='.(int)$itemId);
    $db->setQuery($query);
    $createdBy = $db->loadResult();

    $canEdit = $user->authorise('core.edit', 'com_snipf');
    $canEditOwn = $user->authorise('core.edit.own', 'com_snipf') && $createdBy == $user->id;

    //Allow edition. 
    if($canEdit || $canEditOwn) {
      return 1;
    }

    //Hand over to the parent function.
    return parent::allowEdit($data = array(), $key = 'id');
  }


  /**
   * Method to cancel an edit.
   *
   * @param   string  $key  The name of the primary key of the URL variable.
   *
   * @return  boolean  True if access level checks pass, false otherwise.
   *
   * @since   1.6
   */
  public function cancel($key = null)
  {
    if(parent::cancel($key)) {
      //Ensures first that the subscription item exists.
      $recordId = $this->input->get('id', 0, 'int');
      //A new process has been created but has not been saved.
      if($recordId && $this->input->post->get('process_action', '', 'string') == 'create') {
	//Deletes the last created but unsaved process from the table.
	$lastProcessNb = ProcessHelper::getNbProcesses($recordId, $this->context);
	ProcessHelper::deleteProcess($recordId, $this->context, $lastProcessNb);
      }
    }
  }


  public function process()
  {
    // Check for request forgeries
    JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

    //Gets the task string.
    $task = $this->input->post->get('task', '', 'str');
    $validActions = array('create', 'delete');
    // Get item id to update from the request.
    $id = $this->input->get('id', 0, 'int');

    //Gets both the action and the possible process number from the task string.
    preg_match('#^subscription\.process\.([a-z]+)\.*([0-9]+)*$#', $task, $matches);
    $action = $matches[1];

    //Checks for valid data.
    if(empty($id) || !in_array($action, $validActions)) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_SNIPF_INVALID_ITEM_ID'), 'warning');
    }
    //The createProcess method aside, all the other methods need the process number parameter.
    elseif($action != 'create') {
      //Gets the process number from the task string.
      $processNb = $matches[2];
      //Concatenates the name of the method to call.
      $methodName = $action.'Process';
      //Calls the method.
      ProcessHelper::$methodName($id, $this->context, $processNb);
    }
    //A process has already been created but has not been saved yet. A new process cannot
    //be currently created.
    elseif($this->input->post->get('process_action', '', 'string') == 'create') {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_SNIPF_PROCESS_CURRENTLY_UNSAVED'), 'warning');
    }
    //Creates a new process.
    else {
      ProcessHelper::createProcess($id, $this->context);
    }

    $this->setRedirect('index.php?option='.$this->option.'&view='.$this->context.'&layout=edit&id='.(int)$id.'&process='.$action);

    return true;
  }


  protected function setProcessDates()
  {
    $post = $this->input->post->getArray();

    foreach($post as $key => $value) {
      //Gets the year value of each process.
      if(preg_match('#^year_([0-9]+)$#', $key, $matches)) {
	$processNb = $matches[1];
	$year = trim($value);

	//Updates some attributes from the year value.
	$this->input->post->set('start_process_'.$processNb, $year.'-01-01 00:01:00');
	$this->input->post->set('end_process_'.$processNb, $year.'-12-31 23:59:00');
	$this->input->post->set('name_'.$processNb, $year);
      }
    }
  }
}

