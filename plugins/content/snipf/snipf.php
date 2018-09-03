<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access
defined('_JEXEC') or die('Restricted access');
// Import the JPlugin class
jimport('joomla.plugin.plugin');
require_once JPATH_ROOT.'/administrator/components/com_snipf/helpers/process.php';
require_once JPATH_ROOT.'/administrator/components/com_snipf/helpers/address.php';
require_once JPATH_ROOT.'/administrator/components/com_snipf/helpers/beneficiary.php';



class plgContentSnipf extends JPlugin
{

  public function onContentPrepare($context, &$data, &$params, $page)
  {
  }


  public function onContentAfterTitle($context, &$data, &$params, $limitstart)
  {
  }


  public function onContentBeforeDisplay($context, &$data, &$params, $limitstart)
  {
  }


  public function onContentAfterDisplay($context, &$data, &$params, $limitstart)
  {
  }


  public function onContentBeforeSave($context, $data, $isNew)
  {
    if($context == 'com_snipf.person') { 
      if($data->status == 'retired' || $data->status == 'deceased') {
	$db = JFactory::getDbo();
	$query = $db->getQuery(true);

	//Checks whether the person's status has been changed since the last session.
	$query->select('status')
	      ->from('#__snipf_person')
	      ->where('id='.(int)$data->id);
	$db->setQuery($query);

	//Does nothing if the status hasn't changed.
	if($db->loadResult() == $data->status) {
	  return true;
	}

	//Gets the linked certificates then checks whether some are currently edited.
	$query->clear();
	$query->select('c.number, c.closure_date, c.checked_out, c.checked_out_time, u.name AS user_name')
	      ->from('#__snipf_certificate AS c')
	      ->join('LEFT', '#__users AS u ON u.id=c.checked_out')
	      ->where('c.person_id='.(int)$data->id);
	$db->setQuery($query);
	$certificates = $db->loadObjectList();

	foreach($certificates as $certificate) {
	  //A certificate is still opened and currently edited.
	  if((int)$certificate->checked_out && $certificate->closure_date == $db->getNullDate()) {
	    $data->setError(JText::sprintf('COM_SNIPF_CERTIFICATE_CURRENTLY_EDITED', $certificate->number,$certificate->user_name));
	    return false;
	  }
	}
      }
    }
    elseif($context == 'com_users.user') { 
      /*$table = JTable::getInstance('Person', 'SnipfTable', array('dbo', $this->getDbo()));
      if($table->load(array('email' => $data->email)) && ($table->user_id != $data->id || $data->id == 0)) {
      }*/
    }

    return true;
  }


  public function onContentAfterSave($context, $data, $isNew)
  {
    if($context == 'com_snipf.person') { 
      //$post = JFactory::getApplication()->input->post->getArray();
      $jform = JFactory::getApplication()->input->post->get('jform', array(), 'array');
      //Prepares the array to store the address values.
      $addresses = array('ha' => array(), 'pa' => array());

      foreach($jform as $key => $value) {
	//Gets and sets the address fields.
	if(preg_match('#^([a-z_]+)_(ha|pa)$#', $key, $matches)) {
	  $addressAttr = $matches[1];
	  $addressType = $matches[2];

	  $addresses[$addressType][$addressAttr] = $value;
	}
      }

      AddressHelper::saveAddresses($data->id, $addresses);

      //Moves to the beneficiaries.

      //Prepares the array to store the beneficiary values.
      $beneficiaries = array('bfc' => array(), 'dbfc' => array());

      foreach($jform as $key => $value) {
	//Gets and sets the beneficiary fields.
	if(preg_match('#^([a-z_]+)_(bfc|dbfc)$#', $key, $matches)) {
	  $beneficiaryAttr = $matches[1];
	  $beneficiaryType = $matches[2];

	  $beneficiaries[$beneficiaryType][$beneficiaryAttr] = $value;
	}
      }

      BeneficiaryHelper::saveBeneficiaries($data->id, $beneficiaries);

      //Moves to the position dynamic items
      $filteredData = $this->filterDateFields('person', 'snipf_positions');

      $positions = array();
      foreach($filteredData as $key => $val) {
	if(preg_match('#^position_id_([0-9]+)$#', $key, $matches)) {
	  $positionNb = $matches[1];

	  if(!empty($filteredData['position_id_'.$positionNb])) { //Check for empty field.
	    $position = new JObject;
	    $position->position_id = $filteredData['position_id_'.$positionNb];
	    $position->sripf_id = $filteredData['sripf_id_'.$positionNb];
	    $position->start_date = $filteredData['start_date_'.$positionNb];
	    $position->end_date = $filteredData['end_date_'.$positionNb];
	    $position->comments = $filteredData['position_comments_'.$positionNb];

	    $positions[] = $position;
	  }
	}
      }

      //Set fields.
      $columns = array('person_id', 'position_id','sripf_id','start_date','end_date','comments');
      //Update positions.
      SnipfHelper::updateMappingTable('#__snipf_person_position_map', $columns, $positions, array($data->id));

      //Moves to the work situation part.
      $columns = array('person_id', 'employer_name','employer_activity','ape_code','position','comments','law_company');
      $values = array();
      $workSituation = new JObject;

      //Note: Skips the very first index (ie: person_id).
      for($i = 1; $i < count($columns); $i++) {
	$name = $columns[$i];
	$workSituation->$name = $jform[$name.'_ws'];
      }

      $values[] = $workSituation;

      SnipfHelper::updateMappingTable('#__snipf_work_situation', $columns, $values, array($data->id));

      $model = JModelLegacy::getInstance('Certificate', 'SnipfModel');
      $model->checkCertificateClosure($data);
    }
    elseif($context == 'com_snipf.certificate' && !$isNew) { //CERTIFICATE
      $filteredData = $this->filterDateFields('certificate_process', 'process');
      ProcessHelper::saveProcess($filteredData);

      $model = JModelLegacy::getInstance('Certificate', 'SnipfModel');
      $model->updateEndDates($data->id);

      //Checks for a initial and accepted process.
      $outcomes = array();
      foreach($filteredData as $key => $value) {
	if(preg_match('#^outcome_[0-9]+$#', $key)) {
	  $outcomes[] = $value;
	}
      }

      //There is one process and the outcome is accepted. 
      if(count($outcomes) == 1 && $outcomes[0] == 'accepted') {
	//A Joomla user linked to this person may have to be created.
	SnipfHelper::createUser($data->person_id);
      }
    }
    elseif($context == 'com_snipf.subscription' && !$isNew) { //SUBSCRIPTION
      $filteredData = $this->filterDateFields('subscription_process', 'process');
      ProcessHelper::saveProcess($filteredData);

      $model = JModelLegacy::getInstance('Subscription', 'SnipfModel');
      $model->updateSripfGroup($data);
    }
  }


  public function onContentBeforeDelete($context, $data)
  {
    return true;
  }


  public function onContentAfterDelete($context, $data)
  {
    if($context == 'com_snipf.person') { 
      //First gets the certificate ids which are linked to this person.
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->select('id')
	    ->from('#__snipf_certificate')
	    ->where('person_id='.(int)$data->id);
      $db->setQuery($query);
      $certificateIds = $db->loadColumn();

      if(!empty($certificateIds)) {
	SnipfHelper::deleteItems($certificateIds, 'Certificate');

	//Deletes all the processes linked to the person's certificates.
	$query->clear();
	$query->delete('#__snipf_process')
	      ->where('item_id IN('.implode(',', $certificateIds).')')
	      ->where('item_type="certificate"');
	$db->setQuery($query);
	$db->execute();
      }

      $tables = array('address', 'beneficiary', 'person_position_map', 'work_situation');

      foreach($tables as $table) {
	$query->clear();
	$query->delete('#__snipf_'.$table)
	      ->where('person_id='.(int)$data->id);
	$db->setQuery($query);
	$db->execute();
      }

      //TODO: Removes the possible Joomla user linked to this person.
    }
    elseif($context == 'com_snipf.certificate') { 
      //Removes all the processes linked to the deleted certificate.
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->delete('#__snipf_process')
	    ->where('item_id='.(int)$data->id)
	    ->where('item_type="certificate"');
      $db->setQuery($query);
      $db->execute();
    }
    elseif($context == 'com_snipf.subscription') { 
      //Removes all the processes linked to the deleted subscription.
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->delete('#__snipf_process')
	    ->where('item_id='.(int)$data->id)
	    ->where('item_type="subscription"');
      $db->setQuery($query);
      $db->execute();
    }
  }


  public function onContentPrepareForm($form, $data)
  {
    return true;
  }


  public function onContentPrepareData($context, $data)
  {
    return true;
  }


  public function onContentChangeState($context, $pks, $value)
  {
    return true;
  }


  /**
   * Searches for calendar fields into a given form then (if necessary) turn their value into an 
   * SQL formatted date or datetime string in UTC.
   * This method is designed for dynamic items or fields treated separately. Forms are used to get the relevant
   * information (such as showTime, translateFormat etc...) about the fields.
   *
   * As the dynamic item forms are externals they don't take advantage of the filterField
   * native Joomla method. So they have to be treated separately.
   *
   * @param   string  $formName		The name of the form to parse.
   * @param   string  $fieldsetName	The name of the form fieldset.
   * @param   boolean $multiple		Field names have suffix (ie: a numerical id)
   * @param   boolean $jform		Gets data from the jform array over the global POST array.
   *
   * @return  mixed   The filtered values.
   *
   * @note    Based on the filterField function: libraries/src/Form/Form.php 
   */
  private function filterDateFields($formName, $fieldsetName, $multiple = true, $jform = false)
  {
    // Creates a new JForm object
    $form = new JForm('Form');
    //Loads the form.
    $form->loadFile('components/com_snipf/models/forms/'.$formName.'.xml');

    //Gets data from the POST array.
    $data = JFactory::getApplication()->input->post->getArray();
    //Gets data from the jform array.
    if($jform) {
      $data = JFactory::getApplication()->input->post->get('jform', array(), 'array');
    }

    //Gets the fieldset in order to parse the given form.
    $fieldset = $form->getFieldset($fieldsetName);

    //Searches for date fields using the filter attribute.
    foreach($fieldset as $field) {
      $type = $field->getAttribute('type');
      $translateFormat = $field->getAttribute('translateformat');

      //Checks for calendar fields which need a format translation.
      if($type == 'calendar' && $translateFormat && $translateFormat != 'false') {
	$name = $field->getAttribute('name');
	$filter = $field->getAttribute('filter');
	$showTime = (string)$field->getAttribute('showtime');
	$showTime = ($showTime && $showTime != 'false');
	$format = JText::_('DATE_FORMAT_FILTER_DATE');

	if($showTime) {
	  $format = JText::_('DATE_FORMAT_FILTER_DATETIME');
	}

	// Get the user timezone setting defaulting to the server timezone setting.
	$offset = JFactory::getUser()->getTimezone();

	if($filter == 'server_utc') {
	  // Get the server timezone setting.
	  $offset = JFactory::getConfig()->get('offset');
	}

	//Multiple fields have numerical id as suffix.
	$suffix = '_[0-9]+';
	if(!$multiple) {
	  //Searches for the name as it is (ie: without suffix).
	  $suffix = '';
	}

	//Searches for the corresponding values for each field.
	foreach($data as $key => $value) {
	  if(preg_match('#^'.$name.$suffix.'$#', $key) && !empty($value) && !preg_match('#^0000-00-00#', $value)) {
	    $date = date_parse_from_format($format, $value);
            $value = (int)$date['year'].'-'.(int)$date['month'].'-'.(int)$date['day'];

	    if($showTime) {
	      $value .= ' '.(int)$date['hour'].':'.(int)$date['minute'].':'.(int)$date['second'];
	    }

	    // Stores date as an SQL formatted datetime string in UTC.
	    $data[$key] = JFactory::getDate($value, $offset)->toSql();
	  }
	}
      }
    }

    return $data;
  }
}

