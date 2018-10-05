<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modeladmin');
require_once JPATH_COMPONENT.'/helpers/process.php';


class SnipfModelSubscription extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_SNIPF';

  //Returns a Table object, always creating it.
  //Table can be defined/overrided in the file: tables/mycomponent.php
  public function getTable($type = 'Subscription', $prefix = 'SnipfTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  public function getForm($data = array(), $loadData = true) 
  {
    $form = $this->loadForm('com_snipf.subscription', 'subscription', array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_snipf.edit.subscription.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }


  /**
   * Method to get a single record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  \JObject|boolean  Object on success, false on failure.
   *
   * @since   1.6
   */
  public function getItem($pk = null)
  {
    $item = parent::getItem($pk = null);

    //Adds the processes to the subscription object;
    $item->processes = ProcessHelper::getProcesses($item->id, 'subscription');
    $item->nb_processes = 0;

    if(count($item->processes)) {
      $item->nb_processes = count($item->processes);
    }

    if($item->id) { //Existing item.
      $db = $this->getDbo();
      $query = $db->getQuery(true);
      $query->select('id, status, cqp1')
	    ->from('#__snipf_person')
	    ->where('id='.(int)$item->person_id);
      $db->setQuery($query);
      $person = $db->loadObject();

      if($person->status == 'retired' || $person->status == 'deceased') {
	$item->person_status = JText::_('COM_SNIPF_OPTION_'.strtoupper($person->status));
      }
      else { 
	$model = JModelLegacy::getInstance('Person', 'SnipfModel');
	$item->person_status = JText::_('COM_SNIPF_CERTIFICATION_STATUS_'.strtoupper($model->getCertificationStatus($person->id)));
      }

      $item->cqp1 = $person->cqp1;
    }

    return $item;
  }


  /**
   * Adds or remove the user from his corresponding SRIPF group according to the payment
   * state of the current year.
   *
   * @param   object  $subscription  The subscription object.
   *
   * @return  void
   */
  public function updateSripfGroup($subscription)
  {
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    //Fetches the person's user id and the corresponding SRIPF group id.
    $query->select('s.group_id, p.user_id')
	  ->from('#__snipf_address AS a')
	  ->join('INNER', '#__snipf_sripf AS s ON s.id=a.sripf_id')
	  ->join('INNER', '#__snipf_person AS p ON p.id=a.person_id')
	  ->where('a.person_id='.(int)$subscription->person_id)
	  ->where('a.type="ha" AND history=0');
    $db->setQuery($query);
    $ids = $db->loadObject();

    //Don't go further if no group id is defined.
    if(!$ids->group_id) {
      return;
    }

    $currentYear = date('Y');
    $query->clear();
    //Checks the payment state of the current year.
    $query->select('cads_payment')
	  ->from('#__snipf_process')
	  ->where('item_id='.(int)$subscription->id)
	  ->where('item_type="subscription" AND name='.$db->Quote($currentYear));
    $db->setQuery($query);
    $process = $db->loadObject();

    if($process && $process->cads_payment == 1) {
      //Note: 2 = Registered.
      JUserHelper::setUserGroups($ids->user_id, array(2, $ids->group_id));
    }
    else {
      JUserHelper::removeUserFromGroup($ids->user_id, $ids->group_id);
    }
  }


  /**
   * Loads ContentHelper for filters before validating data.
   *
   * @param   object  $form   The form to validate against.
   * @param   array   $data   The data to validate.
   * @param   string  $group  The name of the group(defaults to null).
   *
   * @return  mixed  Array of filtered data if valid, false otherwise.
   *
   * @since   1.1
   */
  public function validate($form, $data, $group = null)
  {
    return parent::validate($form, $data, $group);
  }
}

