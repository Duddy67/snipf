<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controlleradmin');
include_once JPATH_ROOT.'/administrator/components/com_snipf/helpers/tcpdf.php';
 

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

    $model = $this->getModel('Certificates');
    $data = $model->getDataFromCurrentQuery();

    //Gets the task string.
    $task = $this->input->post->get('task', '', 'str');
    //Gets the document type from the task string.
    preg_match('#^certificates\.generateDocument\.([a-z_]+)$#', $task, $matches);
    $documentType = $matches[1];

    if($documentType == 'csv') {
      $csvFileName = SnipfHelper::generateCSV($data);
      $uri = JUri::getInstance();
      $csvUrl = $uri->root().'administrator/components/com_snipf/csv/download.php?csv_file='.$csvFileName;

      $this->setRedirect($csvUrl);

      return true;
    }
    else {
      if($documentType == 'pdf_new_ci' || $documentType == 'pdf_labels') {
	$personIds = array();
	$template = 'subscription_letter';

	foreach($data as $certificate) {
	  if(!in_array($certificate->person_id, $personIds)) {
	    $personIds[] = $certificate->person_id;
	  }
	}

	$db = JFactory::getDbo();
	$query = $db->getQuery(true);
	//Gets some data from the person.
	$query->select('p.lastname, p.firstname, person_title, a.street, a.additional_address, a.postcode, a.city, c.alpha_3')
	      ->from('#__snipf_person AS p')
	      ->join('INNER', '#__snipf_address AS a ON a.person_id=p.id AND a.type=p.mail_address_type AND history=0')
	      ->join('LEFT', '#__snipf_country AS c ON a.country_code=c.alpha_2')
	      ->where('p.id IN('.implode(',', $personIds).')');
	$db->setQuery($query);
	$persons = $db->loadObjectList(); 

	//Adds some extra variables.
	foreach($persons as $person) {
	  $person->current_date = JHtml::_('date', new JDate(), JText::_('DATE_FORMAT_LC1'));
	  $person->title = JText::_('COM_SNIPF_OPTION_'.$person->person_title);
	  $person->country = JText::_('COM_SNIPF_LANG_COUNTRY_'.$person->alpha_3);
	}

	$data = $persons;

	if($documentType == 'pdf_labels') {
	  $template = 'subscription_labels';
	  $data = array();
	  $item = new JObject;
	  $nbPerPage = 4;
	  $index = 1;

	  //For labels we need to display 4 person's data per page. So the data has to be
	  //slightly reorganized.
	  foreach($persons as $key => $person) {
	    //Turns object into associative array.
	    $person = (array)$person; 

	    foreach($person as $attribute => $value) {
	      //Postfixes each person's attribute. 
	      $item->{$attribute.'_'.$index} = $value;
	    }

	    //The loop reaches the number of person per page (or the end of the array).
	    if($index % $nbPerPage == 0 || !isset($persons[$key + 1])) {
	      //Stores the set of person data.
	      $data[] = $item;
	      //Starts a new set of person's data.
	      $item = new JObject;
	      $index = 1;
	    }
	    else {
	      $index++;
	    }
	  }
	}

	TcpdfHelper::generatePDF($data, $template);

	return true;
      }
    }
  }
}



