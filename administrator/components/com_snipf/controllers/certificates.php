<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controlleradmin');
 

class SnipfControllerCertificates extends JControllerAdmin
{
  /**
   * Proxy for getModel.
   * @since 1.6
  */
  public function getModel($name = 'Certificate', $prefix = 'SnipfModel', $config = array('ignore_request' => true))
  {
    $model = parent::getModel($name, $prefix, $config);
    return $model;
  }


  public function generateDocument()
  {
    // Check for request forgeries
    JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

    $model = $this->getModel('Persons');
    $data = $model->getDataFromCurrentQuery();

    //Gets the task string.
    $task = $this->input->post->get('task', '', 'str');
    //Gets the document type from the task string.
    preg_match('#^persons\.generateDocument\.([a-z]+)$#', $task, $matches);
    $documentType = $matches[1];

    if($documentType == 'csv') {
      $csvFileName = SnipfHelper::generateCSV($data);
      $uri = JUri::getInstance();
      $csvUrl = $uri->root().'administrator/components/com_snipf/csv/download.php?csv_file='.$csvFileName;

      $this->setRedirect($csvUrl);

      return true;
    }
    else {
      if($documentType == 'pdf_new_ci') {
	$personIds = array();
	foreach($data as $certificate) {
	  if(!in_array($certificate->person_id)) {
	    $personIds[] = $certificate->person_id;
	  }
	}

	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	//Gets some person data.
	$query->select('lastname, firstname, alias, email')
	      ->from('#__snipf_person')
	      ->where('id IN('.implode(',', $personIds).')');
	$db->setQuery($query);
	$persons = $db->loadObjectList(); 
      }

      TcpdfHelper::generatePDF($data, 'person_pdf');
    }
  }
}



