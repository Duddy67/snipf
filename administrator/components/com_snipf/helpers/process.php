<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.



class ProcessHelper
{

  //List of the process attributes which must not be quoted in MySQL queries.
  public static $unquoted = array('item_id', 'number', 'created_by', 'modified_by');

  public static function getProcesses($itemId, $itemType, $processNb = null) 
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('*')
	  ->from('#__snipf_process')
	  ->where('item_id='.(int)$itemId);

    if($processNb !== null) {
      $query->where('number='.(int)$processNb);
    }

    $query->where('item_type='.$db->Quote($itemType))
	  ->order('number');

    $db->setQuery($query);

    if($processNb !== null) {
      //Returns a single object.
      return $db->loadObject();
    }

    return $db->loadObjectList();
  }


  public static function getNbProcesses($itemId, $itemType) 
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('COUNT(*)')
	  ->from('#__snipf_process')
	  ->where('item_id='.(int)$itemId)
	  ->where('item_type='.$db->Quote($itemType));
    $db->setQuery($query);

    return $db->loadResult();
  }


  public static function createProcess($itemId, $itemType)
  {
    //Gets the number of processes linked to the given item.
    $nbProcesses = self::getNbProcesses($itemId, $itemType);

    $processNb = 1;

    if($nbProcesses) {
      $processNb = $nbProcesses + 1;
    }

    //Gets the current date and time (UTC).
    $now = JFactory::getDate()->toSql();
    $user = JFactory::getUser();
    $name = 'Process: '.$processNb;

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    $columns = array('item_id', 'item_type', 'number', 'name', 'created', 'created_by', 'start_process');
    $values = $itemId.','.$db->Quote($itemType).','.$processNb.','.$db->Quote($name).','.$db->Quote($now).','.$user->get('id').','.$db->Quote($now);

    //Creates the new process.
    $query->insert('#__snipf_process')
	  ->columns($columns)
	  ->values($values);
    $db->setQuery($query);

    try {
      $db->execute();
    }
    catch(RuntimeException $e) {
      JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_SNIPF_CANNOT_CREATE_PROCESS', $e->getMessage()), 'error');
      return false;
    }

    JFactory::getApplication()->enqueueMessage(JText::_('COM_SNIPF_PROCESS_CREATED_SUCCESSFULLY'), 'message');

    return true;
  }


  public static function saveProcess($data)
  {
    //Gets the id and type of the item to process.
    $itemId = $data['jform']['id'];
    $itemType = $data['jform']['item_type'];
    $nbProcesses = self::getNbProcesses($itemId, $itemType);

    //If no processes are linked to the item there is no need to go further.
    if(!$nbProcesses) {
      return;
    }

    $processes = array();

    //The initial process number starts from one.
    for($i = 1; $i <= $nbProcesses; $i++) {
      //Synchronizes the array indexes with the process numbers.
      $processes[$i] = array();
    }

    //Binds the new values to the corresponding processes.
    foreach($data as $key => $value) {
      if(preg_match('#^([a-z_]+)_([0-9]+)$#', $key, $matches)) {
	$attribute = $matches[1];
	$number = $matches[2];
	$processes[$number][$attribute] = trim($value);
      }
    }

    $processes = self::setModifyingParams($itemId, $itemType, $processes);

    //Rearranges data by attributes to make it easier to use when building the query.
    $attributes = array();
    foreach($processes as $key => $process) {
      foreach($process as $attribute => $value) {
	if(!array_key_exists($attribute, $attributes)) {
	  $attributes[$attribute] = array($key => $value);
	}
	else {
	  $attributes[$attribute][$key] = $value;
	}
      }
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Builds the CASE/WHEN clauses.
    $set = '';
    foreach($attributes as $key => $attribute) {
      $set .= $key.' = CASE ';
      foreach($attribute as $number => $value) {
	//Checks for values to be quoted or unquoted.
	if(!in_array($key, self::$unquoted)) {
	  $value = $db->Quote($value);
	}

	$set .= ' WHEN number='.$number.' THEN '.$value;
      }

      $set .= ' ELSE '.$key.' END, ';
    }

    //Remove the comma and the space from the end of the string.
    $set = substr($set, 0,-2);

    //Updates the processes.
    $query->update('#__snipf_process')
          ->set($set)
	  ->where('item_id='.(int)$itemId)
	  ->where('item_type='.$db->Quote($itemType));
    $db->setQuery($query);

    try {
      $db->execute();
    }
    catch(RuntimeException $e) {
      JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_SNIPF_CANNOT_UPDATE_PROCESS', $e->getMessage()), 'error');
    }
  }


  public static function deleteProcess($itemId, $itemType, $processNb)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->delete('#__snipf_process')
	  ->where('item_id='.(int)$itemId.' AND number='.(int)$processNb)
	  ->where('item_type='.$db->Quote($itemType));
    $db->setQuery($query);

    try {
      $db->execute();
    }
    catch(RuntimeException $e) {
      JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_SNIPF_CANNOT_DELETE_PROCESS', $e->getMessage()), 'error');
      return false;
    }

    JFactory::getApplication()->enqueueMessage(JText::_('COM_SNIPF_PROCESS_DELETED_SUCCESSFULLY'), 'message');

    return true;
  }


  /**
   * Compares the old processes's values against the new ones. As soon as a difference between
   * those values is noticed, the modifiyng parameters are set with the current user's data. 
   *
   * @param   integer   $itemId	The id of the item to which the processes are linked to.
   * @param   string	$itemType	The item type.
   * @param   mixed	$newProcesses	The values of the new processes.
   *
   * @return  mixed  Array of the new processes's values.
   *
   */
  public static function setModifyingParams($itemId, $itemType, $newProcesses) 
  {
    //Gets the corresponding process form.
    $processForm = new JForm('ProcessForm');
    $processForm->loadFile('components/com_snipf/models/forms/'.$itemType.'_process.xml');

    $fieldset = $processForm->getFieldset('process');
    $fieldNames = array('number');

    //Collects the field names of the process.
    foreach($fieldset as $field) {
      $fieldNames[] = $field->getAttribute('name');
    }

    //Fetches the old values of the processes.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select(implode(',', $fieldNames))
	  ->from('#__snipf_process')
	  ->where('item_id='.(int)$itemId)
	  ->where('item_type='.$db->Quote($itemType))
	  ->order('number');
    $db->setQuery($query);
    $oldProcesses = $db->loadObjectList('number');

    //Gets the current date and time (UTC).
    $now = JFactory::getDate()->toSql();
    $user = JFactory::getUser();
    //These fields are not to be compared.
    $ignoredFields = array('modified', 'modified_by', 'number', 'end_process');
    $dateFields = array('reminder_date', 'file_receiving_date');

    //Starts comparison between old and new process values.
    foreach($oldProcesses as $key => $oldProcesse) {
      foreach($oldProcesse as $fieldName => $value) {
	//In case of empty date field, set the value to null date or comparison with the
	//old value will be distorted.
	if(in_array($fieldName, $dateFields) && empty($newProcesses[$key][$fieldName])) {
	  $newProcesses[$key][$fieldName] = $db->getNullDate();
	}

	//Checks if the field value has changed since the last saving.
	if(!in_array($fieldName, $ignoredFields) && strcmp($newProcesses[$key][$fieldName], $value) !== 0) {
	  //Sets the modifying parameters.
	  $newProcesses[$key]['modified'] = $now;
	  $newProcesses[$key]['modified_by'] = $user->get('id');
	  //Move on to the next process.
	  break;
	}
      }
    }

    return $newProcesses;
  }


  public static function setProcessName($itemId, $itemType) 
  {
  }


  public static function checkRequiredFields($itemId, $itemType) 
  {
  }
}


