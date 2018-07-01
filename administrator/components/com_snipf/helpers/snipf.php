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
    JHtmlSidebar::addEntry(JText::_('COM_SNIPF_SUBMENU_PERSONS'),
				      'index.php?option=com_snipf&view=persons', $viewName == 'persons');

    JHtmlSidebar::addEntry(JText::_('COM_SNIPF_SUBMENU_CERTIFICATES'),
				      'index.php?option=com_snipf&view=certificates', $viewName == 'certificates');

    JHtmlSidebar::addEntry(JText::_('COM_SNIPF_SUBMENU_SUBSCRIPTIONS'),
				      'index.php?option=com_snipf&view=subscriptions', $viewName == 'subscriptions');

    JHtmlSidebar::addEntry(JText::_('COM_SNIPF_SUBMENU_POSITIONS'),
				      'index.php?option=com_snipf&view=positions', $viewName == 'positions');

    JHtmlSidebar::addEntry(JText::_('COM_SNIPF_SUBMENU_OFFICES'),
				      'index.php?option=com_snipf&view=offices', $viewName == 'offices');

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


  public static function getUTCDate($format, $dateValue, $showTime = true, $filter = 'user_utc')
  {
    $date = date_parse_from_format($format, $dateValue);
    $value = (int)$date['year'].'-'.(int)$date['month'].'-'.(int)$date['day'];

    if($showTime) {
      $value .= ' '.(int)$date['hour'].':'.(int)$date['minute'].':'.(int)$date['second'];
    }

    $offset = self::getOffset($filter);

    // Returns date as an SQL formatted datetime string in UTC.
    return JFactory::getDate($dateValue, $offset)->toSql();
  }


  public static function getOffset($filter = 'user_utc')
  {
    // Get the user timezone setting defaulting to the server timezone setting.
    $offset = JFactory::getUser()->getTimezone();

    if($filter == 'server_utc') {
      // Get the server timezone setting.
      $offset = JFactory::getConfig()->get('offset');
    }

    return $offset;
  }


  public static function generateCSV($data)
  {
    $headers = array('firstname', 'lastname', 'created', 'user', 'category_title');
    $items = array();
 
    foreach($data as $row) {
      $item = array();
      foreach($headers as $fieldName) {
	$item[] = $row->$fieldName;
      }

      $items[] = $item;
    }

    array_unshift($items, $headers);

    //Names the new CSV file after the current datetime.
    $now = JFactory::getDate('now', JFactory::getConfig()->get('offset'))->toSql(true);
    $csvFileName = preg_replace('#[:| ]#', '-', $now);

    $fp = fopen('components/com_snipf/csv/'.$csvFileName.'.csv', 'w');

    foreach($items as $key => $fields) {
      fputcsv($fp, $fields);
    }

    fclose($fp);

    return $csvFileName.'.csv';
  }


  /**
   * Deletes Joomla's items programmaticaly.
   *
   * @param mixed	An array of item ids or a single item id.
   * @param string	The name of the item.
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
   * @param string  The name of the table to update (eg: #__table_name).
   * @param array  Array of table's column, (primary key name must be set as the first array's element).
   * @param array  Array of JObject containing the column values, (values order must match the column order).
   * @param array  Array containing the ids of the items to update.
   * @param string Extra WHERE clause.
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
      $integers = array('id','item_id','person_id','office_id');

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
}


