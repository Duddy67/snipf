<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.



class BeneficiaryHelper
{

  /**
   * Gets the current beneficiary and default beneficiary (and their corresponding address) for a given person.
   *
   * @param   integer  $personId  The id of the person to whom the beneficiaries are linked to.
   *
   * @return  mixed  Array of beneficiary objects.
   *
   */
  public static function getBeneficiaries($personId)
  {
    $beneficiaries = array();
    $types = array('bfc', 'dbfc');

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    foreach($types as $type) {
      $query->clear();
      $query->select('b.firstname AS firstname_'.$type.', b.lastname AS lastname_'.$type.', b.created AS created_'.$type.','.
		     'a.street AS street_'.$type.', a.additional_address AS additional_address_'.$type.', a.city AS city_'.$type.','.
		     'a.postcode AS postcode_'.$type.', a.country_code AS country_code_'.$type.', b.created_by AS created_by_'.$type)
	    ->from('#__snipf_beneficiary AS b')
	    ->join('INNER', '#__snipf_address AS a ON a.person_id = b.person_id')
	    ->where('b.person_id='.(int)$personId)
	    ->where('b.type='.$db->Quote($type))
	    ->where('a.type='.$db->Quote($type))
	    //Fetches the current beneficiary.
	    ->where('b.history=0')
	    ->where('a.history=0');
      $db->setQuery($query);
      $beneficiaries[$type] = $db->loadObject();
    }

    return $beneficiaries;
  }


  /**
   * Gets the beneficiary history for a given person.
   *
   * @param   integer  $personId  	The id of the person to whom the beneficiary is linked to.
   * @param   integer  $beneficiaryType	The type of the beneficiary.
   *
   * @return  mixed  Array of beneficiary objects.
   *
   */
  public static function getBeneficiaryHistory($personId, $beneficiaryType)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('b.firstname AS firstname_'.$beneficiaryType.', b.lastname AS lastname_'.$beneficiaryType.','.
		   'b.created AS created_'.$beneficiaryType.', b.created_by AS created_by_'.$beneficiaryType.','.
		   'a.street AS street_'.$beneficiaryType.','.
		   'a.additional_address AS additional_address_'.$beneficiaryType.', a.city AS city_'.$beneficiaryType.','.
		   'a.postcode AS postcode_'.$beneficiaryType.', a.country_code AS country_code_'.$beneficiaryType)
	  ->from('#__snipf_beneficiary AS b')
	  ->join('INNER', '#__snipf_address AS a ON a.person_id = b.person_id')
	  ->where('b.person_id='.(int)$personId)
	  //The created value is used as foreign key. 
	  ->where('b.created = a.created')
	  ->where('b.type='.$db->Quote($beneficiaryType))
	  ->where('a.type='.$db->Quote($beneficiaryType))
	  //Fetches the beneficiaries in the history.
	  ->where('b.history=1')
	  ->where('a.history=1')
	  ->order('b.created DESC');
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
  public static function saveBeneficiaries($personId, $beneficiaries)
  {
    $types = array('bfc', 'dbfc');

    foreach($types as $type) {
      //If the lastname mandatory field is not empty it means that the beneficiary has
      //been correctly set so it can be created.
      //The "insert" value means that a new beneficiary has been created.
      if((empty($beneficiaries[$type]['operation']) && !empty($beneficiaries[$type]['lastname'])) ||
	  $beneficiaries[$type]['operation'] == 'insert') {
	self::insertBeneficiary($personId, $beneficiaries[$type], $type);
      }
      elseif($beneficiaries[$type]['operation'] == 'update') {
	self::updateBeneficiary($personId, $beneficiaries[$type], $type);
      }
    }
  }


  /**
   * Inserts a beneficiary and his address in database.
   *
   * @param   integer   $personId		The id of the person to whom the beneficiary is linked to.
   * @param   mixed	$beneficiary		The values of the beneficiary.
   * @param   string	$beneficiaryType	The beneficiary type.
   *
   * @return  void
   *
   */
  public static function insertBeneficiary($personId, $beneficiary, $beneficiaryType)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Gets the current date and time (UTC).
    $now = JFactory::getDate()->toSql();
    $user = JFactory::getUser();

    //Deletes unwanted attributes.
    unset($beneficiary['operation']);
    //Removes the created and created_by attributes as for now they're empty and we want
    //them at the third and fourth position in the columns array.
    unset($beneficiary['created']);
    unset($beneficiary['created_by']);

    //Beneficiary data have to be spread in 2 tables.
    $tables = array('beneficiary', 'address');

    //Inserts data in the corresponding table.
    foreach($tables as $table) {
      $columns = array('person_id', 'type', 'created', 'created_by', 'history');
      //Sets the first four attributes.
      $values = array($personId, $db->quote($beneficiaryType), $db->quote($now), $user->get('id'), 0);

      foreach($beneficiary as $key => $value) {
	//Sorts the data to insert according to the table. 
	if($table == 'beneficiary' && $key != 'firstname' && $key != 'lastname') {
	  continue;
	}

	if($table == 'address' && ($key == 'firstname' || $key == 'lastname')) {
	  continue;
	}

	$columns[] = $key;
	$values[] = $db->quote($value);
      }

      //Inserts the new beneficiary as well as his address in database.
      $query->clear();
      $query->insert('#__snipf_'.$table)
	    ->columns($columns)
	    ->values(implode(',', $values));
      $db->setQuery($query);
      $db->execute();

      //Sets the possible older beneficiary rows and the corresponding address rows in the history.
      $query->clear();
      $query->update('#__snipf_'.$table)
	    ->set('history=1')
	    ->where('person_id='.(int)$personId)
	    ->where('type='.$db->quote($beneficiaryType))
	    ->where('created < '.$db->quote($now));
      $db->setQuery($query);
      $db->execute();
    }
  }


  /**
   * Updates a given beneficiary and his address in database.
   *
   * @param   integer   $personId	The id of the person to whom the beneficiary is linked to.
   * @param   mixed	$beneficiary	The values of the beneficiary.
   * @param   string	$beneficiaryType	The beneficiary type.
   *
   * @return  void
   *
   */
  public static function updateBeneficiary($personId, $beneficiary, $beneficiaryType)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Deletes unwanted attributes.
    unset($beneficiary['operation']);
    //Both created and created_by attributes must not be updated. Moreover we don't have any 
    //filterField function yet to treat the datetime value properly.
    unset($beneficiary['created']);
    unset($beneficiary['created_by']);

    $tables = array('beneficiary', 'address');
    //Updates the current beneficiary and his address.
    foreach($tables as $table) {
      $fields = array();
      foreach($beneficiary as $fieldName => $value) {
	//Sorts the data to insert according to the table. 
	if($table == 'beneficiary' && $fieldName != 'firstname' && $fieldName != 'lastname') {
	  continue;
	}

	if($table == 'address' && ($fieldName == 'firstname' || $fieldName == 'lastname')) {
	  continue;
	}

	$fields[] = $fieldName.'='.$db->quote($value);
      }

      $query->clear();
      $query->update('#__snipf_'.$table)
	    ->set($fields)
	    ->where('person_id='.(int)$personId)
	    ->where('type='.$db->quote($beneficiaryType))
	    ->where('history=0');
      $db->setQuery($query);
      $db->execute();
    }
  }


  /**
   * If the user has filled in a beneficiary fields, this function unsures that at
   * least the (usually) mandatory fields are properly set.
   * In case these fields are empty, the form can be sent since beneficiaries are optional.
   *
   * @param   string	$beneficiaryType	The beneficiary type to check.
   * @param   mixed	$data			The edit form data.
   *
   * @return  mixed  Array of the possible unfilled fields
   *
   */
  public static function checkBeneficiary($beneficiaryType, $data)
  {
    //Gets the mandatory fields according to the beneficiary type.
    $mandatory = parse_ini_file(JPATH_BASE.'/components/com_snipf/models/forms/mandatory.ini');
    $fields = $mandatory[$beneficiaryType];
    $emptyFields = array();

    //Checks the value of the required fields.
    foreach($fields as $fieldName) {
      //Removes possible space.
      $value = trim($data[$fieldName.'_'.$beneficiaryType]);

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
   * Deletes a given beneficiary and his address.
   *
   * @param   integer   $personId		The id of the person to whom the beneficiary is linked to.
   * @param   string	$beneficiaryType	The beneficiary type.
   * @param   integer   $history		Flag which indicates whether the beneficiary is part of history or not.
   * @param   integer   $idNb			The id number corresponding to the order of the beneficiary row to delete
   *						when sorting by date desc.
   *
   * @return  void
   *
   */
  public static function deleteBeneficiary($personId, $beneficiaryType, $history, $idNb = 0)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $tables = array('beneficiary', 'address');

    //The beneficiary is part of the history.
    if($history) {
      //Gets all of the "created" values (datetime) in the history. 
      $query->select('created')
	    ->from('#__snipf_beneficiary')
	    ->where('person_id='.(int)$personId)
	    ->where('type='.$db->quote($beneficiaryType))
	    ->where('history=1')
	    ->order('created DESC');
      $db->setQuery($query);
      //Gets result as an indexed array sorted in descending order of datetime.
      $rows = $db->loadColumn();
      //Gets the created value of the beneficiary to delete from the given id number.
      $created = $rows[$idNb];

      foreach($tables as $table) {
	$query->clear();
	$query->delete('#__snipf_'.$table)
	      ->where('person_id='.(int)$personId)
	      ->where('type='.$db->Quote($beneficiaryType))
	      ->where('history=1')
	      ->where('created='.$db->Quote($created));
	$db->setQuery($query);
	$db->execute();
      }
    }
    else { //Current beneficiary.
      //The current beneficiary and his address are actually not deleted but put into the history.
      foreach($tables as $table) {
	$query->clear();
	$query->update('#__snipf_'.$table)
	      ->set('history=1')
	      ->where('person_id='.(int)$personId)
	      ->where('type='.$db->quote($beneficiaryType))
	      ->where('history=0');
	$db->setQuery($query);
	$db->execute();
      }
    }
  }


  /**
   * Renders the beneficiary history
   *
   * @param   integer   $personId		The id of the person to whom the beneficiary is linked to.
   * @param   string	$beneficiaryType	The beneficiary type.
   *
   * @return  string				The html beneficiary history. 
   *
   */
  public static function renderBeneficiaryHistory($personId, $beneficiaryType) 
  {
    $history = self::getBeneficiaryHistory($personId, $beneficiaryType);

    $html = '';
    //var_dump($history);
    if(!empty($history)) {
      $toSkip = array('operation_'.$beneficiaryType);
      // Creates a new JForm object
      $form = new JForm('Form');
      //Loads the person form.
      $form->loadFile('components/com_snipf/models/forms/person.xml');

      foreach($history as $key => $beneficiary) {

	$html .= '<div id="beneficiary-history-'.$beneficiaryType.'-'.$key.'">';
	$fieldset = $form->getFieldset($beneficiaryType);

	foreach($fieldset as $field) {
	  $name = $field->getAttribute('name');

	  if(!in_array($name, $toSkip)) {
	    $field->setValue($beneficiary->$name);
	    $field->__set('name', 'history_'.$name.'_'.$key);
	    $field->__set('id', 'history_'.$name.'_'.$key);
	    $field->__set('readonly', 'readonly');

	    $html .= $field->getControlGroup();
	  }
	} 

	$html .= '<div class="beneficiary-btn" id="btn-delete-beneficiary-history-'.$beneficiaryType.'-'.$key.'">
		 <a class="btn btn-danger" href="#">'.JText::_('COM_SNIPF_BUTTON_REMOVE_LABEL').'</a>
		 </div><hr class="history-spacer"></div>';
      }
    }

    return $html;
  }
}


