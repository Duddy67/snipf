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

      AddressHelper::saveAddresses($data->id, $addresses, $data->mail_address_type);

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
	    $position->office_id = $filteredData['office_id_'.$positionNb];
	    $position->start_date = $filteredData['start_date_'.$positionNb];
	    $position->end_date = $filteredData['end_date_'.$positionNb];
	    $position->comments = $filteredData['position_comments_'.$positionNb];

	    $positions[] = $position;
	  }
	}
      }

      //Set fields.
      $columns = array('person_id', 'position_id','office_id','start_date','end_date','comments');
      //Update positions.
      SnipfHelper::updateMappingTable('#__snipf_person_position_map', $columns, $positions, array($data->id));
    }
    elseif($context == 'com_snipf.certificate' && !$isNew) { 
      $filteredData = $this->filterDateFields('certificate_process', 'process');
      ProcessHelper::saveProcess($filteredData);
    }
    elseif($context == 'com_snipf.subscription' && !$isNew) { 
      $filteredData = $this->filterDateFields('subscription_process', 'process');
      ProcessHelper::saveProcess($filteredData);
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

      SnipfHelper::deleteItems($certificateIds, 'Certificate');

      //Deletes all the processes linked to the person's certificates.
      $query->clear();
      $query->delete('#__snipf_process')
	    ->where('item_id IN('.implode(',', $certificateIds).')')
	    ->where('item_type="certificate"');
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      $query->delete('#__snipf_address')
	    ->where('person_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      $query->delete('#__snipf_beneficiary')
	    ->where('person_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

      $query->clear();
      $query->delete('#__snipf_person_position_map')
	    ->where('person_id='.(int)$data->id);
      $db->setQuery($query);
      $db->execute();

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
   * This method is designed for dynamic items. Forms are used to get the relevant
   * information (such as showTime, translateFormat etc...) about the fields.
   *
   * As the dynamic item forms are externals they don't take advantage of the filterField
   * native Joomla method. So they have to be treated separately.
   *
   * @param   string  $formName		The name of the form to parse.
   *
   * @param   string  $fieldsetName	The name of the form fieldset.
   *
   * @return  mixed   The filtered values.
   *
   * @note    Based on the filterField function: libraries/src/Form/Form.php 
   */
  private function filterDateFields($formName, $fieldsetName)
  {
    // Creates a new JForm object
    $form = new JForm('Form');
    //Loads the form.
    $form->loadFile('components/com_snipf/models/forms/'.$formName.'.xml');

    //Gets the POST array.
    $data = JFactory::getApplication()->input->post->getArray();
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

	//Searches for the corresponding values for each process.
	foreach($data as $key => $value) {
	  if(preg_match('#^'.$name.'_[0-9]+$#', $key) && !empty($value) && !preg_match('#^0000-00-00#', $value)) {
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

