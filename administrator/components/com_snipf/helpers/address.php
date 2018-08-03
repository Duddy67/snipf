<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.



class AddressHelper
{

  /**
   * Gets the home and professional addresses for a given person.
   *
   * @param   integer  $personId  The id of the person to which the addresses are linked to.
   *
   * @return  mixed  Array of adress objects.
   *
   */
  public static function getAddresses($personId)
  {
    $addresses = array();

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('street AS street_ha, additional_address AS additional_address_ha, city AS city_ha,'.
		   'postcode AS postcode_ha, phone AS phone_ha, mobile AS mobile_ha, fax AS fax_ha,'.
		   'country_code AS country_code_ha, sripf_id AS sripf_id_ha, created AS created_ha,'.
		   'created_by AS created_by_ha, modified AS modified_ha, modified_by AS modified_by_ha')
	  ->from('#__snipf_address')
	  ->where('person_id='.(int)$personId)
	  ->where('type="ha"')
	  //Fetches the current address.
	  ->where('history=0');
    $db->setQuery($query);
    $addresses['ha'] = $db->loadObject();

    $query->clear();
    $query->select('employer_name AS employer_name_pa, street AS street_pa, additional_address AS additional_address_pa,'.
		   'city AS city_pa, postcode AS postcode_pa, phone AS phone_pa, mobile AS mobile_pa, fax AS fax_pa,'.
		   'country_code AS country_code_pa, created AS created_pa, created_by AS created_by_pa,'.
		   'modified AS modified_pa, modified_by AS modified_by_pa')
	  ->from('#__snipf_address')
	  ->where('person_id='.(int)$personId)
	  ->where('type="pa"')
	  //Fetches the current address.
	  ->where('history=0');
    $db->setQuery($query);
    $addresses['pa'] = $db->loadObject();

    return $addresses;
  }


  /**
   * Gets the home or professional address history for a given person.
   *
   * @param   integer  $personId  	The id of the person to which the address is linked to.

   * @param   integer  $addressType	The type of the given address.
   *
   * @return  mixed  Array of adress objects.
   *
   */
  public static function getAddressHistory($personId, $addressType)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('street AS street_'.$addressType.', additional_address AS additional_address_'.$addressType.','.
	           'city AS city_'.$addressType.', postcode AS postcode_'.$addressType.', phone AS phone_'.$addressType.','.
		   'mobile AS mobile_'.$addressType.', fax AS fax_'.$addressType.','.
		   'country_code AS country_code_'.$addressType.', created AS created_'.$addressType.','.
		   'created_by AS created_by_'.$addressType.', modified AS modified_'.$addressType.','.
		   'modified_by AS modified_by_'.$addressType);

    //Gets some extra columns according to the address type.
    if($addressType == 'pa') {
      $query->select('employer_name AS employer_name_pa');
    }
    else { //ha
      $query->select('sripf_id AS sripf_id_ha');
    }

    $query->from('#__snipf_address')
	  ->where('person_id='.(int)$personId)
	  ->where('type='.$db->Quote($addressType))
	  //Fetches the addresses in the history.
	  ->where('history=1')
	  ->order('created DESC');
    $db->setQuery($query);

    return $db->loadObjectList();
  }


  /**
   * Determines, according to some variable values, if the addresses have to be updated,
   * inserted or ignored when saving.
   *
   * @param   integer   $personId  		The id of the person to which the addresses have to be linked to.
   * @param   mixed	$addresses 		The address data.
   *
   * @return  void
   */
  public static function saveAddresses($personId, $addresses)
  {
    //If the operation variable value is empty, it means that the person item is new so
    //the home address must be created as it is mandatory. 
    //The "insert" value means that a new home address has been created.
    if(empty($addresses['ha']['operation']) || $addresses['ha']['operation'] == 'insert') {
      self::insertAddress($personId, $addresses['ha'], 'ha');
    }
    else { //Otherwise updates the home address data.
      self::updateAddress($personId, $addresses['ha'], 'ha');
    }

    //Moves to the professional address.

    //If the street mandatory field is not empty it means that the professional address has
    //been correctly set so it can be created.
    //The "insert" value means that a new professional address has been created.
    if((empty($addresses['pa']['operation']) && !empty($addresses['pa']['street'])) ||
	$addresses['pa']['operation'] == 'insert') {
      self::insertAddress($personId, $addresses['pa'], 'pa');
    }
    elseif($addresses['pa']['operation'] == 'update') {
      self::updateAddress($personId, $addresses['pa'], 'pa');
    }
  }


  /**
   * Inserts an address in database.
   *
   * @param   integer   $personId	The id of the person to whom the address is linked to.
   * @param   mixed	$address	The values of the address.
   * @param   string	$addressType	The address type.
   *
   * @return  void
   *
   */
  public static function insertAddress($personId, $address, $addressType)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Gets the current date and time (UTC).
    $now = JFactory::getDate()->toSql();
    $user = JFactory::getUser();

    //Deletes unwanted attributes.
    unset($address['operation']);
    //Removes the created and created_by attributes as for now they're empty and we want
    //them at the third and fourth position in the columns array.
    unset($address['created']);
    unset($address['created_by']);

    $columns = array('person_id', 'type', 'created', 'created_by', 'history');
    //Sets the first four attributes.
    $values = array($personId, $db->quote($addressType), $db->quote($now), $user->get('id'), 0);

    foreach($address as $key => $value) {
      $columns[] = $key;
      $values[] = $db->quote($value);
    }

    $query->insert('#__snipf_address')
	  ->columns($columns)
	  ->values(implode(',', $values));
    $db->setQuery($query);
    $db->execute();

    //Sets the possible older address rows in the history.
    $query->clear();
    $query->update('#__snipf_address')
	  ->set('history=1')
	  ->where('person_id='.(int)$personId)
	  ->where('type='.$db->quote($addressType))
	  ->where('created < '.$db->quote($now));
    $db->setQuery($query);
    $db->execute();
  }


  /**
   * Updates a given address in database.
   *
   * @param   integer   $personId	The id of the person to whom the address is linked to.
   * @param   mixed	$address	The values of the address.
   * @param   string	$addressType	The address type.
   *
   * @return  void
   *
   */
  public static function updateAddress($personId, $address, $addressType)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Deletes unwanted attributes.
    unset($address['operation']);
    //Both created and modified attributes must not be updated. Moreover we don't have any 
    //filterField function yet to treat their datetime value properly.
    unset($address['created']);
    unset($address['modified']);

    $address = self::setModifyingParams($personId, $address, $addressType);

    $fields = array();
    foreach($address as $fieldName => $value) {
      $fields[] = $fieldName.'='.$db->quote($value);
    }

    $query->update('#__snipf_address')
	  ->set($fields)
	  ->where('person_id='.(int)$personId)
	  ->where('type='.$db->quote($addressType))
	  //Updates the current address.
	  ->where('history=0');
    $db->setQuery($query);
    $db->execute();
  }


  /**
   * If the user has filled in the professional address fields, this function unsures that at
   * least the (usually) mandatory fields are properly set.
   * In case these fields are empty, the form can be sent in case the professional address is optional.
   *
   * @param   mixed	$data		The edit form data.
   *
   * @return  mixed  Array of the possible unfilled fields
   *
   */
  public static function checkProfessionalAddress($data)
  {
    //Gets the mandatory fields according to the address type.
    $mandatory = parse_ini_file(JPATH_BASE.'/components/com_snipf/models/forms/mandatory.ini');
    //Gets the mandatory fields of the professional address.
    $fields = $mandatory['pa'];
    $emptyFields = array();

    //Checks the value of the required fields.
    foreach($fields as $fieldName) {
      //Removes possible space.
      $value = trim($data[$fieldName.'_pa']);

      //Stores the names of the unfilled fields.
      if(empty($value)) {
	$emptyFields[] = $fieldName;
      }
    }

    //All of the fields are empty so the form can be sent anyway.
    if(count($fields) == count($emptyFields)) {
      return array();
    }

    //Returns the possible unfilled required fields 
    return $emptyFields;
  }


  /**
   * Compares the old address values against the new ones. As soon as a difference between
   * those values is noticed, the modifiyng parameters are set with the current user's data. 
   *
   * @param   integer   $personId	The id of the person to whom the address is linked to.
   * @param   mixed	$newAddress	The values of the new address.
   * @param   string	$addressType	The address type.
   *
   * @return  mixed  Array of the new address values.
   *
   */
  public static function setModifyingParams($personId, $newAddress, $addressType) 
  {
    //Gets the corresponding address form.
    $addressForm = new JForm('AddressForm');
    $addressForm->loadFile('components/com_snipf/models/forms/person.xml');

    $fieldset = $addressForm->getFieldset($addressType);
    $fieldNames = array();

    //Collects the field names of the address.
    foreach($fieldset as $field) {
      if($field->getAttribute('name') != 'operation_'.$addressType) {
	//Note: Removes the suffix (ie: _pa, _ha) from the end of the string.
	$fieldNames[] = substr($field->getAttribute('name'), 0, -3);
      }
    }

    //Fetches the old values of the address.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select(implode(',', $fieldNames))
	  ->from('#__snipf_address')
	  ->where('person_id='.(int)$personId)
	  ->where('type='.$db->Quote($addressType))
	  //Fetches the current address.
	  ->where('history=0');
    $db->setQuery($query);
    $oldAddress = $db->loadObject();

    //Gets the current date and time (UTC).
    $now = JFactory::getDate()->toSql();
    $user = JFactory::getUser();
    //These fields are not to be compared.
    $ignoredFields = array('modified', 'modified_by', 'created', 'created_by');

    //Starts comparison between old and new address values.
    foreach($oldAddress as $fieldName => $value) {
      //Checks if the field value has changed since the last saving.
      if(!in_array($fieldName, $ignoredFields) && strcmp($newAddress[$fieldName], $value) !== 0) {
	//Sets the modifying parameters.
	$newAddress['modified'] = $now;
	$newAddress['modified_by'] = $user->get('id');
	//No need to go further.
	break;
      }
    }

    return $newAddress;
  }


  /**
   * Deletes a given address.
   *
   * @param   integer   $personId		The id of the person to whom the address is linked to.
   * @param   string	$addressType		The address type.
   * @param   integer   $history		Flag which indicates whether the address is part of history or not.
   * @param   integer   $idNb			The id number corresponding to the order of the address row to delete
   *						when sorting by date desc.
   *
   * @return  void
   *
   */
  public static function deleteAddress($personId, $addressType, $history, $idNb = 0)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //The address is part of the history.
    if($history) {
      //Gets all of the "created" values (datetime) in the history. 
      $query->select('created')
	    ->from('#__snipf_address')
	    ->where('person_id='.(int)$personId)
	    ->where('type='.$db->quote($addressType))
	    ->where('history=1')
	    ->order('created DESC');
      $db->setQuery($query);
      //Gets result as an indexed array sorted in descending order of datetime.
      $rows = $db->loadColumn();
      //Gets the created value of the address to delete from the given id number.
      $created = $rows[$idNb];

      $query->clear();
      $query->delete('#__snipf_address')
	    ->where('person_id='.(int)$personId)
	    ->where('type='.$db->Quote($addressType))
	    ->where('history=1')
	    ->where('created='.$db->Quote($created));
      $db->setQuery($query);
      $db->execute();
    }
    else { //Current (no mandatory) address
      //The current address is actually not deleted but put into the history.
      $query->update('#__snipf_address')
	    ->set('history=1')
	    ->where('person_id='.(int)$personId)
	    ->where('type='.$db->quote($addressType))
	    ->where('history=0');
      $db->setQuery($query);
      $db->execute();
    }
  }


  /**
   * Renders the address history
   *
   * @param   integer   $personId		The id of the person to whom the address is linked to.
   * @param   string	$addressType		The address type.
   *
   * @return  string				The html address history. 
   *
   */
  public static function renderAddressHistory($personId, $addressType) 
  {
    $history = self::getAddressHistory($personId, $addressType);

    $html = '';
    //var_dump($history);
    if(!empty($history)) {
      $toSkip = array('operation_'.$addressType, 
		      'modified_'.$addressType, 'modified_by_'.$addressType );
      // Creates a new JForm object
      $form = new JForm('Form');
      //Loads the person form.
      $form->loadFile('components/com_snipf/models/forms/person.xml');

      foreach($history as $key => $address) {

	$html .= '<div id="address-history-'.$addressType.'-'.$key.'">';
	$fieldset = $form->getFieldset($addressType);

	foreach($fieldset as $field) {
	  $name = $field->getAttribute('name');

	  if(!in_array($name, $toSkip)) {
	    $field->setValue($address->$name);
	    $field->__set('name', 'history_'.$name.'_'.$key);
	    $field->__set('id', 'history_'.$name.'_'.$key);
	    $field->__set('readonly', 'readonly');

	    $html .= $field->getControlGroup();
	  }
	} 

	$html .= '<div class="address-btn" id="btn-delete-address-history-'.$addressType.'-'.$key.'">
		 <a class="btn btn-danger" href="#">'.JText::_('COM_SNIPF_BUTTON_REMOVE_LABEL').'</a>
		 </div><hr class="history-spacer"></div>';
      }
    }

    return $html;
  }
}


