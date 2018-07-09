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

    return $item;
  }
}

