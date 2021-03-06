<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die('Restricted access');
 
// import Joomla table library
jimport('joomla.database.table');
require_once JPATH_ROOT.'/administrator/components/com_snipf/helpers/process.php';
 
/**
 * Certificate table class
 */
class SnipfTableCertificate extends JTable
{
  /**
   * Constructor
   *
   * @param object Database connector object
   */
  function __construct(&$db) 
  {
    parent::__construct('#__snipf_certificate', 'id', $db);
  }


  /**
   * Overrides JTable::store to set modified data and user id.
   *
   * @param   boolean  $updateNulls  True to update fields even if they are null.
   *
   * @return  boolean  True on success.
   *
   * @since   11.1
   */
  public function store($updateNulls = false)
  {
    //Gets the current date and time (UTC).
    $now = JFactory::getDate()->toSql();
    $user = JFactory::getUser();

    if($this->id) { // Existing item
      $this->modified = $now;
      $this->modified_by = $user->get('id');

      //In case of abandonment or rejection during the very first commission pending 
      if(($this->closure_reason == 'abandon' || $this->closure_reason == 'rejected_file') &&
	  ProcessHelper::getNbProcesses($this->id, 'certificate') == 1) {
	$process = ProcessHelper::getProcesses($this->id, 'certificate', 1);
	//Ensures that no certificate has been created yet.
	if($process->commission_date > '0000-00-00 00:00:00' && $process->end_process == '0000-00-00 00:00:00') {
	  //Sets the number field to the corresponding label.
	  $this->number = JText::_('COM_SNIPF_STATUS_ABANDON');

	  if($this->closure_reason == 'rejected_file') {
	    $this->number = JText::_('COM_SNIPF_OPTION_REJECTED_FILE');
	  }
	}
      }
    }
    else {
      // New item. An item created and created_by field can be set by the user,
      // so we don't touch either of these if they are set.
      if(!(int)$this->created) {
	$this->created = $now;
      }

      if(empty($this->created_by)) {
	$this->created_by = $user->get('id');
      }
    }


    //Removes possible spaces from the certificate number.
    $this->number = trim($this->number);

    //Checks that a valid number is set.  
    if(preg_match('#^[0-9]+$#', $this->number)) {
      $table = JTable::getInstance('Certificate', 'SnipfTable', array('dbo', $this->getDbo()));
      // Verify that the certificate number is unique.
      if($table->load(array('number' => $this->number)) && ($table->id != $this->id || $this->id == 0)) {
	$this->setError(JText::_('COM_SNIPF_DATABASE_ERROR_CERTIFICATE_UNIQUE_NUMBER'));
	return false;
      }
    }

    return parent::store($updateNulls);
  }
}


