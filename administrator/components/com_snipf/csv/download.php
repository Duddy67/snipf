<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

//Since this file is called directly and it doesn't belong to any component, 
//module or plugin, we need first to initialize the Joomla framework in order to use 
//the Joomla API methods.
 
//Initialize the Joomla framework
define('_JEXEC', 1);
//First we get the number of letters we want to substract from the path.
$length = strlen('/administrator/components/com_snipf');
//Turn the length number into a negative value.
$length = $length - ($length * 2);
//
define('JPATH_BASE', substr(dirname(__DIR__), 0, $length));
//Get the required files
require_once (JPATH_BASE.'/includes/defines.php');
require_once (JPATH_BASE.'/includes/framework.php');
//Create the application
$mainframe = JFactory::getApplication('site');

//Get the id number passed through the url.
$jinput = JFactory::getApplication()->input;
$csvFile = $jinput->get('csv_file', '', 'string');

if(!preg_match('#\.csv#', $csvFile)) {
  echo 'Invalid file.';
  return;
}

if(!empty($csvFile)) {
  //Build the path to the file.
  $download = JPATH_BASE.'/administrator/components/com_snipf/csv/files/'.$csvFile;

  if(file_exists($download) === false) {
    echo 'The file cannot be found.';
    return;
  }

  $fileSize = filesize($download);

  header('Content-Description: File Transfer');
  header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
  header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');   // Date in the past
  header('Content-type: text/csv');
  header('Content-Transfer-Encoding: binary');
  header('Content-length: '.$fileSize);
  header("Content-Disposition: attachment; filename=\"".$csvFile."\"");
  ob_clean();
  flush();
  readfile($download);

  exit;
}
else { //The document id is unset.
  echo 'The document doesn\'t exist.';
}


?>
