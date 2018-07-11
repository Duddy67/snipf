<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.



class JavascriptHelper
{
  /**
   * Build and load Javascript functions which return different kind of data,
   * generaly as a JSON array.
   *
   * @param array Array containing the names of the functions to build and load.
   * @param array Array of possible arguments to pass to the PHP functions.
   * @param string Data returned as a string by the getData JS function.
   *
   * @return void
   */
  public static function loadJavascriptFunctions($names, $args = array(), $data = '')
  {
    $js = array();
    //Create a name space in order put functions into it.
    $js = 'var snipf = { '."\n";

    //Include the required functions.

    //Returns region names and codes used to build option tags.
    if(in_array('region', $names)) {
      $regions = self::getRegions();
      $js .= 'getRegions: function() {'."\n";
      $js .= ' return '.$regions.';'."\n";
      $js .= '},'."\n";
    }

    //Returns the current date format.
    if(in_array('dateformat', $names)) {
      //Gets the current date format.
      $format = JText::_('DATE_FORMAT_FILTER_DATETIME');
      //Both i and s characters have to be replaced. 
      $format = preg_replace('#(i)#', 'M', $format);
      $format = preg_replace('#(s)#', 'S', $format);
      //Adds a % charachter before each letter.
      $datetimeFormat = preg_replace('#([a-zA-Z]{1})#', '%$1', $format);
      //Removes time from the format.
      $dateFormat = preg_replace('#( %H:%M:%S)$#', '', $datetimeFormat);

      $js .= 'getDateFormat: function(time) {'."\n";
      $js .= ' if(time === true) {'."\n";
      $js .= '   return "'.$datetimeFormat.'";'."\n";
      $js .= ' }'."\n";
      $js .= '   return "'.$dateFormat.'";'."\n";
      $js .= '},'."\n";
    }

    //Returns the position names and ids used to build option tags.
    if(in_array('position', $names)) {
      $positions = self::getPositions();
      $js .= 'getPositions: function() {'."\n";
      $js .= ' return '.$positions.';'."\n";
      $js .= '},'."\n";
    }

    //Returns the office names and ids used to build option tags.
    if(in_array('office', $names)) {
      $offices = self::getOffices();
      $js .= 'getOffices: function() {'."\n";
      $js .= ' return '.$offices.';'."\n";
      $js .= '},'."\n";
    }

    //Remove coma from the end of the string, (-2 due to the carriage return "\n").
    $js = substr($js, 0, -2); 

    $js .= '};'."\n\n";

    //Place the Javascript code into the html page header.
    $doc = JFactory::getDocument();
    $doc->addScriptDeclaration($js);

    return;
  }


  /**
   * Loads texts needed in javascript code.
   *
   * @return void
   */
  public static function loadJavascriptTexts()
  {
    $jsText = array('BUTTON_ADD_LABEL', 'BUTTON_REMOVE_LABEL', 'BUTTON_SELECT_LABEL',
		    'POSITION_TITLE', 'POSITION_LABEL', 'OFFICE_TITLE', 'OFFICE_LABEL',
		    'COMMENTS_TITLE', 'COMMENTS_LABEL', 'OPTION_SELECT', 'START_DATE_TITLE',
		    'START_DATE_LABEL', 'END_DATE_TITLE', 'END_DATE_LABEL',
		    'WARNING_DELETE_ADDRESS', 'WARNING_DELETE_BENEFICIARY',
		    'FIELD_CLOSURE_DATE_LABEL', 'WARNING_FIELD_EMPTY');

    foreach($jsText as $text) {
      JText::script('COM_SNIPF_'.$text); 
    }
  }


  /**
   * Collects region codes and names as a JSON array.
   *
   * @return JSON array
   */
  public static function getRegions()
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Get all the regions from the region list.
    $query->select('r.country_code, r.code, r.lang_var')
	  ->from('#__snipf_region AS r')
	  //Get only regions which country they're linked with is published (to minimized
	  //the number of regions to load).
	  ->join('LEFT', '#__snipf_country AS c ON r.country_code=c.alpha_2')
	  ->where('c.published=1');
    $db->setQuery($query);
    $results = $db->loadObjectList();

    //Build the regions array.
    $regions = array();
    //Set text value in the proper language.
    foreach($results as $result) {
      //Add the country code to the region name to get an easier search.
      $regions[] = array('code' => $result->code, 'text' => $result->country_code.' - '.JText::_($result->lang_var));
    }

    return json_encode($regions);
  }


  /**
   * Returns position ids and names as a JSON array.
   *
   * @return JSON array
   */
  public static function getPositions()
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Gets all the published positions from the table.
    $query->select('id,name')
	  ->from('#__snipf_position')
	  ->where('published=1');
    $db->setQuery($query);
    $results = $db->loadObjectList();

    $positions = array();
    foreach($results as $result) {
      $position = array('id' => $result->id, 'text' => $result->name);

      $positions[] = $position;
    }

    return json_encode($positions);
  }


  /**
   * Returns office ids and names as a JSON array.
   *
   * @return JSON array
   */
  public static function getOffices()
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    //Gets all the published offices from the table.
    $query->select('id,name')
	  ->from('#__snipf_office')
	  ->where('published=1');
    $db->setQuery($query);
    $results = $db->loadObjectList();

    $offices = array();
    foreach($results as $result) {
      $office = array('id' => $result->id, 'text' => $result->name);

      $offices[] = $office;
    }

    return json_encode($offices);
  }
}


