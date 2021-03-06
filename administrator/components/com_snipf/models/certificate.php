<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.

jimport('joomla.application.component.modeladmin');
require_once JPATH_COMPONENT.'/helpers/process.php';


class SnipfModelCertificate extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_SNIPF';

  //Returns a Table object, always creating it.
  //Table can be defined/overrided in the file: tables/mycomponent.php
  public function getTable($type = 'Certificate', $prefix = 'SnipfTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  public function getForm($data = array(), $loadData = true) 
  {
    //Loads the corresponding form according to the user's privileges.
    $formName = 'certificate';
    if(SnipfHelper::isReadOnly()) {
      $formName = 'certificate_ro';
    }

    $form = $this->loadForm('com_snipf.certificate', $formName, array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_snipf.edit.certificate.data', array());

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

    //Adds the processes to the certificate object;
    $item->processes = ProcessHelper::getProcesses($item->id, 'certificate');
    $item->nb_processes = 0;
    $item->last_process_outcome = '';

    if(count($item->processes)) {
      $item->nb_processes = count($item->processes);
    }

    if($item->nb_processes) {
      //Needed for the retired and deceased cases. 
      $item->last_process_outcome = $item->processes[$item->nb_processes - 1]->outcome;
    }

    //Gets and adds the status of the person the certificate is linked to.
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('status')
	  ->from('#__snipf_person')
	  ->where('id='.(int)$item->person_id);
    $db->setQuery($query);
    $item->person_status = $db->loadResult();

    return $item;
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
    $nullDate = $this->getDbo()->getNullDate();

    if($data['id']) {
      $form = $this->checkProcessFields($data['id'], $form);
    }

    //Checks that closure_date and closure_reason are properly set. If one of these fields
    //is filled in the other one must to be set as well.
    if(!empty($data['closure_date']) && $data['closure_date'] != $nullDate && empty($data['closure_reason'])) {
      $form->setFieldAttribute('closure_reason', 'required', 'true');
    }

    //Checks the other way around.
    if(!empty($data['closure_reason']) && (empty($data['closure_date']) || $data['closure_date'] == $nullDate)) {
      $form->setFieldAttribute('closure_date', 'required', 'true');
    }

    //In case of abandon the abandon_code field must be set.
    if(!empty($data['closure_date']) && $data['closure_date'] != $nullDate &&
       $data['closure_reason'] == 'abandon' && empty($data['abandon_code'])) {
      $form->setFieldAttribute('abandon_code', 'required', 'true');
    }

    return parent::validate($form, $data, $group);
  }


  /**
   * Updates the last process end date as well as the certificate end date according to the
   * state of the processes.
   *
   * @param   integer   $itemId  The certificate id.
   *
   * @return  void
   */
  public function updateEndDates($itemId)
  {
    $processes = ProcessHelper::getProcesses($itemId, 'certificate');
    $db = $this->getDbo();
    $query = $db->getQuery(true);

    if(!$nbProcesses = count($processes)) {
      //Reset the certificate end date in case the last process has been deleted.
      $query->update('#__snipf_certificate')
	    ->set('end_date='.$db->Quote($db->getNullDate()))
	    ->where('id='.(int)$itemId);
      $db->setQuery($query);
      $db->execute();

      return;
    }

    $lastProcess = $processes[$nbProcesses - 1];

    if($lastProcess->outcome == 'accepted') {
      //The end process date is already set.
      if($lastProcess->end_process > $db->getNullDate()) {
	//Keeps it as it is.
	$endDate = $endProcess = $lastProcess->end_process;
      }
      else {
	//Computes the end process date (ie: fin de validité du certificat). 
	$date = new DateTime($lastProcess->commission_date);
	//Adds 3 years from the commission date.
	$date->add(new DateInterval('P3Y'));
	//Sets to the last day of the month (ie: 'Y-m-t' and not 'Y-m-d').
	$date = new DateTime($date->format('Y-m-t H:i:s'));
	//As date is in UTC, because of the timezone we have to get the penultimate
	//day of the month to actualy get the last day displayed. 
	$date->sub(new DateInterval('P1D'));
	$endDate = $endProcess = $date->format('Y-m-d H:i:s');
      }
    }
    //The last process is not accepted, so we rely on the penultimate process to get the
    //end date.
    elseif($nbProcesses > 1) {
      $penultimateProcess = $processes[$nbProcesses - 2];
      $endDate = $penultimateProcess->end_process;
      //Sets the last process end date to nulldate just in case.
      $endProcess = $db->getNullDate();
    }
    //There's only one process and it's not accepted.
    else {
      $endDate = $endProcess = $db->getNullDate();
    }

    //Update the last process end date.
    $query->update('#__snipf_process')
	  ->set('end_process='.$db->Quote($endProcess))
	  ->where('item_id='.(int)$itemId)
	  ->where('item_type="certificate"')
	  ->where('number='.(int)$nbProcesses);
    $db->setQuery($query);
    $db->execute();

    //Update the certificate end date.
    $query->clear();
    $query->update('#__snipf_certificate')
	  ->set('end_date='.$db->Quote($endDate))
	  ->where('id='.(int)$itemId);
    $db->setQuery($query);
    $db->execute();
  }


  /**
   * In case of retirement or decease as a person's status, the bounded certificates are closed.
   *
   * @param   object $person   The person object.
   *
   * @return  void
   */
  public function checkCertificateClosure($person)
  {
    $oldStatus = JFactory::getApplication()->input->post->get('old_status', '', 'string');
    //Checks first that the status has changed.
    if($person->status == $oldStatus) {
      return;
    }

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $now = JFactory::getDate()->toSql();

    //Fetches all certificates bounded to the given person.
    $query->select('id, closure_date, closure_reason, end_date')
	  ->from('#__snipf_certificate')
	  ->where('person_id='.(int)$person->id);
    $db->setQuery($query);
    $certificates = $db->loadObjectList();

    if(empty($certificates)) {
      //No need to go further.
      return;
    }

    $user = JFactory::getUser();
    $certIds = array();
    //Do not treat the following cases:
    $doNotTreat = array('removal', 'rejected_file', 'abandon', 'other');
    $personStatus = $person->status;
    //The active status is taken in account to allow an admin to go back to this status in
    //case of error.
    if($personStatus == 'active') {
      //An active status is not a closure reason.
      $personStatus = '';
    }

    //Builds the CASE/WHEN clauses.
    $cases = array('closure_date' => array(), 'closure_reason' => array(),
		   'modified' => array(), 'modified_by' => array(), 'number' => array());
    foreach($certificates as $certificate) {

      if(!in_array($certificate->closure_reason, $doNotTreat)) {
	//Collects the certificate ids.
	$certIds[] = $certificate->id;

	if($certificate->closure_date == $db->getNullDate()) {
	  //Certificate has to be closed.
	  $cases['closure_date'][] = ' WHEN id='.(int)$certificate->id.' THEN '.$db->Quote($now);
	}
	//In case of way back to the active status.
	elseif(empty($personStatus)) {
	  $cases['closure_date'][] = ' WHEN id='.(int)$certificate->id.' THEN '.$db->Quote($db->getNullDate());

	  if($certificate->end_date == $db->getNullDate()) {
	    //In case of initial pending the title becomes Pending again.
	    $cases['number'][] = ' WHEN id='.(int)$certificate->id.' THEN '.$db->Quote(JText::_('COM_SNIPF_STATUS_PENDING'));
	  }
	}

	//Checks for initial pending (ie: the certificate doesn't exist yet).
	if(!empty($personStatus) && $certificate->end_date == $db->getNullDate()) {
	  $personStatus = 'obsolete';
	  $cases['number'][] = ' WHEN id='.(int)$certificate->id.' THEN '.$db->Quote(JText::_('COM_SNIPF_STATUS_OBSOLETE'));
	}

	//Updates the closure reason as well as the last modification variables.
	$cases['closure_reason'][] = ' WHEN id='.(int)$certificate->id.' THEN '.$db->Quote($personStatus);
	$cases['modified'][] = ' WHEN id='.(int)$certificate->id.' THEN '.$db->Quote($now);
	$cases['modified_by'][] = ' WHEN id='.(int)$certificate->id.' THEN '.$user->get('id');
      }
    }

    //Puts the SET clause together.
    $set = '';
    foreach($cases as $key => $values) {
      if(!empty($values)) {
	$set .= $key.' = CASE ';
	foreach($values as $value) {
	  $set .= $value;
	}

	$set .= ' ELSE '.$key.' END, ';
      }
    }

    //Remove the comma and the space from the end of the string.
    $set = substr($set, 0, -2);

    if(!empty($set)) {
      //Updates the certificate closures.
      $query->clear();
      $query->update('#__snipf_certificate')
	    ->set($set)
	    ->where('id IN('.implode(',', $certIds).')');
      $db->setQuery($query);
      $db->execute();
    }
  }


  /**
   * Runs through all the current processes and checks mandatory fields according to the
   * current state of the process.
   *
   * @param   integer $itemId The certificate id.
   * @param   object  $form   The certificate form to validate against.
   *
   * @return  object  The certificate form possibly modified.
   */
  private function checkProcessFields($itemId, $form)
  {
    //Fetches all the processes linked to this certificate.
    $processes = ProcessHelper::getProcesses($itemId, 'certificate');
    $post = JFactory::getApplication()->input->post->getArray();
    $nullDate = $this->getDbo()->getNullDate();

    //Loops through the processes.
    foreach($processes as $process) {
      $idNb = $process->number;

      //Checks that file_receiving_date and return_file_number are properly set. If one of these fields
      //is filled in the other one must to be set as well.
      if(!empty($post['file_receiving_date_'.$idNb]) && $post['file_receiving_date_'.$idNb] != $nullDate &&
	  empty($post['return_file_number_'.$idNb])) {
	//Inserts this process field as required into the certificate form. In doing so,
	//a warning message is sent to the admin. 
	$xmlstr = <<<XML
		    <field name="return_file_number" type="text"
			   label="COM_SNIPF_FIELD_RETURN_FILE_NUMBER_LABEL"
			   description="COM_SNIPF_FIELD_RETURN_FILE_NUMBER_DESC"
			   required="true" />
XML;
	$returnFileNumber = new SimpleXMLElement($xmlstr);
	$form->setField($returnFileNumber);

	return $form;
      }

      //Checks the other way around.
      if((empty($post['file_receiving_date_'.$idNb]) || $post['file_receiving_date_'.$idNb] == $nullDate) &&
	 !empty($post['return_file_number_'.$idNb])) {

	$xmlstr = <<<XML
		    <field name="file_receiving_date" type="calendar"
			   label="COM_SNIPF_FIELD_FILE_RECEIVING_DATE_LABEL" 
			   description="COM_SNIPF_FIELD_FILE_RECEIVING_DATE_DESC"
			   translateformat="true"
			   showtime="false"
			   size="22"
			   required="true" 
			   filter="user_utc" />
XML;
	$fileReceivingDate = new SimpleXMLElement($xmlstr);
	$form->setField($fileReceivingDate);

	return $form;
      }

      //None of those fields are filled in.
      if((empty($post['file_receiving_date_'.$idNb]) || $post['file_receiving_date_'.$idNb] == $nullDate) &&
	 empty($post['return_file_number_'.$idNb])) {
	//Reset the fields relating to the commission just in case.  
	$db = $this->getDbo();
	$query = $db->getQuery(true);

	$set = array('commission_date='.$db->Quote($nullDate), 
	             'outcome=""',
		     'commission_derogation=""',
		     'end_process='.$db->Quote($nullDate));

	$query->update('#__snipf_process')
	      ->set($set)
	      ->where('item_id='.(int)$itemId)
	      ->where('number='.(int)$idNb)
	      ->where('item_type="certificate"');
	$db->setQuery($query);
	$db->execute();
      }
    }

    return $form;
  }
}

