<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controllerform');
require_once JPATH_ROOT.'/administrator/components/com_snipf/helpers/process.php';
 


class SnipfControllerCertificate extends JControllerForm
{

  public function save($key = null, $urlVar = null)
  {
    //Get the jform data.
    //$data = $this->input->post->get('jform', array(), 'array');

    //Saves the modified jform data array 
    //$this->input->post->set('jform', $data);

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
	  ->from('#__snipf_certificate')
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
      //Ensures first that the certificate item exists.
      $recordId = $this->input->get('id', 0, 'int');
      //A new process has been created but has not been saved.
      if($recordId) { 
	if($this->input->post->get('process_action', '', 'string') == 'create') {
	  //Deletes the last created but unsaved process from the table.
	  $lastProcessNb = ProcessHelper::getNbProcesses($recordId, $this->context);
	  ProcessHelper::deleteProcess($recordId, $this->context, $lastProcessNb);
	}
	else {
	  $nbProcesses = ProcessHelper::getNbProcesses($recordId, $this->context);

	  if($nbProcesses) {
	    $post = $this->input->post->getArray();
            $nullDate = JFactory::getDbo()->getNullDate();

	    //If the file_receiving_date and return_file_number fields are filled in the
	    //commission_date field becomes mandatory.
	    if(!empty($post['file_receiving_date_'.$nbProcesses]) &&
	       !empty($post['return_file_number_'.$nbProcesses]) &&
	       (empty($post['commission_date_'.$nbProcesses]) || $post['commission_date_'.$nbProcesses] == $nullDate)) {
	      //Forces the admin to set the commission_date field properly.
	      JFactory::getApplication()->enqueueMessage(JText::_('COM_SNIPF_COMMISSION_DATE_FIELD_MUST_BE_SET'), 'warning');
	      $this->setRedirect('index.php?option='.$this->option.'&view='.$this->context.'&layout=edit&id='.(int)$recordId);
	      return false;
	    }
	  }
	}
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
    preg_match('#^certificate\.process\.([a-z]+)\.*([0-9]+)*$#', $task, $matches);
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
      //TODO: setEndDates() in case of deleting process.
    }
    //A process has already been created but has not been saved yet. A new process cannot
    //be currently created.
    elseif($this->input->post->get('process_action', '', 'string') == 'create') {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_SNIPF_PROCESS_CURRENTLY_UNSAVED'), 'warning');
    }
    //Creates a new process.
    else {
      ProcessHelper::createProcess($id, $this->context);
      $this->setProcessName($id);
    }

    $this->setRedirect('index.php?option='.$this->option.'&view='.$this->context.'&layout=edit&id='.(int)$id.'&process='.$action);

    return true;
  }


  protected function setProcessName($itemId)
  {
    $nbProcesses = ProcessHelper::getNbProcesses($itemId, $this->context);
    $name = JText::_('COM_SNIPF_INITIAL_CERTIFICATE');

    if($nbProcesses > 1) {
      $renewalNb = $nbProcesses - 1;
      $name = JText::sprintf('COM_SNIPF_RENEWAL_NB_X', $renewalNb);
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->update('#__snipf_process')
	  ->set('name='.$db->Quote($name))
	  ->where('item_id='.(int)$itemId)
	  ->where('item_type='.$db->Quote($this->context))
	  ->where('number='.(int)$nbProcesses);
    $db->setQuery($query);
    $db->execute();
  }
}

