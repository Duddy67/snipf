<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.



class SnipfHelper
{
  //Create the tabs bar ($viewName = name of the active view).
  public static function addSubmenu($viewName)
  {
    $user = JFactory::getUser();

    JHtmlSidebar::addEntry(JText::_('COM_SNIPF_SUBMENU_PERSONS'),
				      'index.php?option=com_snipf&view=persons', $viewName == 'persons');

    JHtmlSidebar::addEntry(JText::_('COM_SNIPF_SUBMENU_CERTIFICATES'),
				      'index.php?option=com_snipf&view=certificates', $viewName == 'certificates');

    JHtmlSidebar::addEntry(JText::_('COM_SNIPF_SUBMENU_SUBSCRIPTIONS'),
				      'index.php?option=com_snipf&view=subscriptions', $viewName == 'subscriptions');

    if($user->get('isRoot')) {
      JHtmlSidebar::addEntry(JText::_('COM_SNIPF_SUBMENU_POSITIONS'),
					'index.php?option=com_snipf&view=positions', $viewName == 'positions');

      JHtmlSidebar::addEntry(JText::_('COM_SNIPF_SUBMENU_SRIPFS'),
					'index.php?option=com_snipf&view=sripfs', $viewName == 'sripfs');

      JHtmlSidebar::addEntry(JText::_('COM_SNIPF_SUBMENU_SPECIALITIES'),
					'index.php?option=com_snipf&view=specialities', $viewName == 'specialities');

      JHtmlSidebar::addEntry(JText::_('COM_SNIPF_SUBMENU_COUNTRIES'),
					'index.php?option=com_snipf&view=countries', $viewName == 'countries');

      JHtmlSidebar::addEntry(JText::_('COM_SNIPF_SUBMENU_CATEGORIES'),
					'index.php?option=com_categories&extension=com_snipf', $viewName == 'categories');

      if($viewName == 'categories') {
	$document = JFactory::getDocument();
	$document->setTitle(JText::_('COM_SNIPF_ADMINISTRATION_CATEGORIES'));
      }
    }
  }


  //Get the list of the allowed actions for the user.
  public static function getActions($catIds = array())
  {
    $user = JFactory::getUser();
    $result = new JObject;

    $actions = array('core.admin', 'core.manage', 'core.create', 'core.edit',
		     'core.edit.own', 'core.edit.state', 'core.delete');

    //Get from the core the user's permission for each action.
    foreach($actions as $action) {
      //Check permissions against the component. 
      if(empty($catIds)) { 
	$result->set($action, $user->authorise($action, 'com_snipf'));
      }
      else {
	//Check permissions against the component categories.
	foreach($catIds as $catId) {
	  if($user->authorise($action, 'com_snipf.category.'.$catId)) {
	    $result->set($action, $user->authorise($action, 'com_snipf.category.'.$catId));
	    break;
	  }

	  $result->set($action, $user->authorise($action, 'com_snipf.category.'.$catId));
	}
      }
    }

    return $result;
  }

  //Build the user list for the filter.
  public static function getUsers($itemName)
  {
    // Create a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('u.id AS value, u.name AS text');
    $query->from('#__users AS u');
    //Get only the names of users who have created items, this avoids to
    //display all of the users in the drop down list.
    $query->join('INNER', '#__snipf_'.$itemName.' AS i ON i.created_by = u.id');
    $query->group('u.id');
    $query->order('u.name');

    // Setup the query
    $db->setQuery($query);

    // Return the result
    return $db->loadObjectList();
  }


  public static function generateCSV($data)
  {
    $columns = array('person_id', 'old_id', 'person_title', 'firstname', 'lastname', 'maiden_name', 'status', 'birthdate',
		     'retirement_date', 'deceased_date','country_of_birth', 'city_of_birth', 'region_of_birth', 'citizenship',
		     'email_ha', 'mail_address_type', 'subscription_status', 'adhesion_date', 'resignation_date','deregistration_date', 
		     'reinstatement_date', 'demand_origin', 'active_retired', 'cqp1', 'employer_name', 'employer_activity', 
		     'ape_code', 'position', 'comments_ws', 'law_company', 'honor_member', 'honor_member_date', 'street', 
		     'additional_address', 'postcode', 'city', 'country', 'cee', 'phone', 'mobile', 'fax', 'sripf_name', 
		     'email_pa', 'street_pa', 'additional_address_pa', 'postcode_pa', 'city_pa', 'country_pa', 'cee_pa', 'phone_pa', 
                     'mobile_pa', 'fax_pa');
    $items = $headers = array();
 
    foreach($data as $key => $row) {
      $item = array();
      foreach($columns as $fieldName) {
	$item[] = $row->$fieldName;

	if($key == 0) {
	  //Stores the headers during the first parent loop.
	  $headers[] = JText::_('COM_SNIPF_CSV_'.strtoupper($fieldName));
	}
      }

      $items[] = $item;
    }

    array_unshift($items, $headers);

    //Names the new CSV file after the current datetime.
    $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
    $csvFileName = preg_replace('#[:| ]#', '-', $now);

    $fp = fopen('components/com_snipf/csv/files/'.$csvFileName.'.csv', 'w');

    foreach($items as $key => $fields) {
      fputcsv($fp, $fields);
    }

    fclose($fp);

    return $csvFileName.'.csv';
  }


  /**
   * Creates a Joomla user and linked it to a person.
   *
   * @param integer $personId	The id of the person.
   *
   * @return void
   */
  public static function createUser($personId)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Gets some person data.
    $query->select('p.user_id, p.lastname, p.firstname, p.alias, p.email, s.group_id AS sripf_group_id')
	  ->from('#__snipf_person AS p')
	  ->join('LEFT', '#__snipf_address AS a ON a.person_id=p.id AND a.type="ha" AND a.history=0')
	  ->join('LEFT', '#__snipf_sripf AS s ON s.id=a.sripf_id')
	  ->where('p.id='.(int)$personId);
    $db->setQuery($query);
    $person = $db->loadObject(); 

    if((int)$person->user_id) {
      //This person is already linked to a Joomla user.
      return;
    }

    $data = array();
    $data['name'] = $person->firstname.' '.$person->lastname;
    $data['username'] = $person->email;
    $data['email'] = $person->email;
    $password = JUserHelper::genRandomPassword();
    $data['password'] = $password;
    $data['password2'] = $password;
    $data['block'] = 0;
    $data['groups'] = array(2, $person->sripf_group_id); //Registered + sripf group id
    $data['requireReset'] = 1;  //Require user to reset password on next login.
    //Extra data used as a flag to indicate that the Joomla user is created from the SNIPF
    //component (note: used in the SNIPF user plugin).
    $data['initial_registration'] = 1;

    //return; //To prevent sending emails during tests.

    // Generate a new JUser Object
    // Note: It's important to set the "0" otherwise your admin user information will be loaded
    $user = JFactory::getUser(0); 
    // Now adds the new user to the database.
    if(!$user->bind($data)) { // Bind the data and if it fails raise an error
      JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_SNIPF_CANNOT_CREATE_USER', $user->getError()), 'error');
      return false;
    }   
          
    if(!$user->save()) { // Now check if the new user is saved
      JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_SNIPF_CANNOT_CREATE_USER', $user->getError()), 'error');
      return false;
    }   

    // Get the id of the user newly created.
    $query->clear();
    $query->select('id')
          ->from('#__users')
          ->where('email='.$db->Quote($person->email));
    $db->setQuery($query);
    $userId = $db->loadResult();

    //Links the new Joomla user to the person.
    $query->clear();
    $query->update('#__snipf_person')
          ->set('user_id='.(int)$userId)
	  ->where('id='.(int)$personId);
    $db->setQuery($query);
    $db->execute();

    //Note: The Joomla system will send an information email automaticaly to the new user. 
  }


  /**
   * Creates a Joomla user and linked it to a person.
   * Note: Currently not used.
   *
   * @param integer $personId	The id of the user.
   * @param array   $message    The subject and body of the message.
   * @param boolean $html       Flag which force the email to be sent in html format.
   *
   * @return boolean     True on success, false otherwise.
   */
  public static function sendEmail($userId, $message, $html = false)
  {
    //A reference to the global mail object (JMail) is fetched through the JFactory object. 
    //This is the object creating our mail.
    $mailer = JFactory::getMailer();

    $config = JFactory::getConfig();
    $sender = array($config->get('mailfrom'),
		    $config->get('fromname'));

    $mailer->setSender($sender);

    $user = JFactory::getUser($userId);
    $recipient = $user->email;

    $mailer->addRecipient($recipient);

    //Set the subject and body of the email.
    $body = $message['body'];
    $mailer->setSubject($message['subject']);

    if($html) {
      //We want the body message in HTML.
      $mailer->isHTML(true);
      $mailer->Encoding = 'base64';
    }

    $mailer->setBody($body);

    $send = $mailer->Send();

    //Check for error.
    if($send !== true) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_SNIPF_CONFIRMATION_EMAIL_FAILED'), 'warning');
      return false;
    }
    else {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_SNIPF_CONFIRMATION_EMAIL_SUCCESS'));
    }

    return true;
  }


  /**
   * Deletes Joomla's items programmaticaly.
   *
   * @param mixed  $itemIds	An array of item ids or a single item id.
   * @param string $itemName	The name of the item.
   *
   * @return void
   */
  public static function deleteItems($itemIds, $itemName)
  {
    if(!is_array($itemIds)) {
      //Ensures we have an integer.
      if(ctype_digit($itemIds)) {
	$itemIds = array($itemIds);
      }
      else {
	return false;
      }
    }

    $model = JModelLegacy::getInstance($itemName, 'SnipfModel');
    $model->delete($itemIds);
  }


  /**
   * Update a mapping table according to the variables passed as arguments.
   *
   * @param string $table	The name of the table to update (eg: #__table_name).
   * @param array  $columns	Array of table's column, (primary key name must be set as the first array's element).
   * @param array  $data	Array of JObject containing the column values, (values order must match the column order).
   * @param array  $ids		Array containing the ids of the items to update.
   * @param string $where	Extra WHERE clause.
   *
   * @return void
   */
  public static function updateMappingTable($table, $columns, $data, $ids, $where = '')
  {
    //Ensure we have a valid primary key.
    if(isset($columns[0]) && !empty($columns[0])) {
      $pk = $columns[0];
    }
    else {
      return;
    }

    // Create a new query object.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Delete all the previous items linked to the primary id(s).
    $query->delete($db->quoteName($table));
    $query->where($pk.' IN('.implode(',', $ids).')');

    if(!empty($where)) {
      $query->where($where);
    }

    $db->setQuery($query);
    $db->execute();

    //If no item has been defined no need to go further. 
    if(count($data)) {
      //List of the numerical fields (no quotes must be used).
      $integers = array('id','item_id','person_id','sripf_id');

      //Build the VALUES clause of the INSERT MySQL query.
      $values = array();
      foreach($ids as $id) {
	foreach($data as $itemValues) {
	  //Set the primary id to link the item with.
	  $row = $id.',';

	  foreach($itemValues as $key => $value) {
	    //Handle the null value.
	    if($value === null) {
	      $row .= 'NULL,';
	    }
	    //No numerical values must be quoted.
	    elseif(in_array($key, $integers)) {
	      $row .= $value.',';
	    }
	    else { //Quote the other value types.
	      $row .= $db->Quote($value).',';
	    }
	  }

	  //Remove comma from the end of the string.
	  $row = substr($row, 0, -1);
	  //Insert a new row in the "values" clause.
	  $values[] = $row;
	}
      }

      //Insert a new row for each item linked to the primary id(s).
      $query->clear();
      $query->insert($db->quoteName($table));
      $query->columns($columns);
      $query->values($values);
      $db->setQuery($query);
      $db->execute();
    }

    return;
  }


  /**
   * Returns a date (from a calendar field) in the UTC format. The filter attribute must
   * be set to raw or the date won't be treated. It is used when the date offset is not
   * needed.
   *
   * @param string  $formName		The name of the form which contains the date field.
   * @param string  $fieldsetName	The name of the fieldset which contains the date field.
   * @param string  $fieldName		The name of the date field.
   * @param string  $value		The value of the date
   *
   * @return string			The raw date in the UTC format.
   */
  public static function getUTCDate($formName, $fieldsetName, $fieldName, $value)
  {
    //Gets the date field.
    $form = new JForm('Form');
    $form->loadFile('components/com_snipf/models/forms/'.$formName.'.xml');
    $field = $form->getFieldset($fieldsetName)[$fieldName];

    //Rules out the cases where the field value has not to be treated.
    if($value == JFactory::getDbo()->getNullDate() || empty($value) || $field->getAttribute('type') != 'calendar' ||
       $field->getAttribute('filter') != 'raw' || $field->getAttribute('translateformat') === null ||
       $field->getAttribute('translateformat') == 'false') {
      return $value;
    }

    //Sets the date format according to the showtime value; 
    $showTime = (string)$field->getAttribute('showtime');
    $showTime = ($showTime && $showTime != 'false');
    $format = JText::_('DATE_FORMAT_FILTER_DATE');

    if($showTime) {
      $format = JText::_('DATE_FORMAT_FILTER_DATETIME');
    }

    $date = date_parse_from_format($format, $value);
    $value = (int)$date['year'].'-'.(int)$date['month'].'-'.(int)$date['day'];

    if($showTime) {
      $value .= ' '.(int)$date['hour'].':'.(int)$date['minute'].':'.(int)$date['second'];
    }

    // Returns the UTC raw date. 
    return JFactory::getDate($value, 'UTC')->toSql();
  }


  /**
   * Specific function which turns data stored in a Json array into a plain text. 
   *
   * @param string  $extraData		The extra data stored in a JSOn array.
   *
   * @return string			The CQP1 extradata as plain text.
   */
  public static function getCqp1ExtraDataText($extraData)
  {
    if(empty($extraData)) {
      return $extraData;
    }

    $skippedFields = array('speciality_id', 'end_date');
    $data = json_decode($extraData);
    $text = '';

    foreach($data as $key => $value) {
      if(!in_array($key, $skippedFields)) {
	if(is_null($value)) {
	  $value = JText::_('COM_SNIPF_NOT_FILLED');
	}
	elseif($key == 'commission_date') {
	  $value = JHtml::_('date', $value, JText::_('DATE_FORMAT_FILTER_DATE'));
	}

	$text .= JText::_('COM_SNIPF_FIELD_'.strtoupper($key).'_LABEL').': '.$value."\r";
      }
    }

    return $text;
  }


  /**
   * Checks whether the current user is in readonly mode.
   *
   * @return boolean	True if the current user is in readonly mode, false otherwise.
   */
  public static function isReadOnly()
  {
    //Gets the groups the user belongs to.
    $user = JFactory::getUser();
    $groups = implode(',', $user->getAuthorisedViewLevels());

    //Gets the groups allowed to read and write the component items.
    //Those groups have to be set in the component's configuration. However the super user
    //group is considered as read and write by default.
    $component = JComponentHelper::getComponent('com_snipf');
    $readWriteGoups = $component->getParams()->get('readwrite_groups');

    if($user->get('isRoot') || ($readWriteGoups !== null && in_array($groups, $readWriteGoups))) {
      return false;
    }

    return true;
  }


  /**
   * Collects the sripf ids linked to the current user. 
   *
   * @return array	An array filled with the user's sripf ids or an empty array otherwise. 
   */
  public static function getUserSripfs()
  {
    $user = JFactory::getUser();

    //First gets the corresponding person id.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('id')
	  ->from('#__snipf_person')
	  ->where('user_id='.(int)$user->get('id'));
    $db->setQuery($query);
    $personId = $db->loadResult();

    if($personId === null) {
      return array();
    }

    $now = JFactory::getDate()->toSql();

    //Collects the sripf ids from the valid positions.
    $query->clear();
    $query->select('DISTINCT pp.sripf_id')
	  ->from('#__snipf_person_position_map AS pp')
	  ->join('LEFT', '#__snipf_position AS p ON p.id=pp.position_id')
	  ->where('pp.person_id='.(int)$personId.' AND p.readonly=1')
	  ->where('(pp.end_date='.$db->Quote($db->getNullDate()).' OR pp.end_date > '.$db->Quote($now).')');
    $db->setQuery($query);

    return $db->loadColumn();
  }
}


