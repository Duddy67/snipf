<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controlleradmin');
include_once JPATH_ROOT.'/administrator/components/com_snipf/helpers/tcpdf.php';
require_once JPATH_ROOT.'/administrator/components/com_snipf/helpers/snipf.php';
 

class SnipfControllerSubscriptions extends JControllerAdmin
{
  /**
   * Proxy for getModel.
   * @since 1.6
  */
  public function getModel($name = 'Subscription', $prefix = 'SnipfModel', $config = array('ignore_request' => true))
  {
    $model = parent::getModel($name, $prefix, $config);
    return $model;
  }


  public function generateDocument()
  {
    // Check for request forgeries
    JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

    $model = $this->getModel('Subscriptions');
    $data = $model->getDataFromCurrentQuery();
    //Gets a possible selection.
    $selection = $this->input->post->get('cid', array());
    $personIds = array();

    foreach($data as $subscription) {
      if(!in_array($subscription->person_id, $personIds)) {

	if(empty($selection) || (!empty($selection) && in_array($subscription->id, $selection))) { 
	  $personIds[] = $subscription->person_id;
	}
      }
    }

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    //Gets some data from the person.
    $query->select('p.lastname, p.firstname, person_title, a.street, a.additional_address,'.
	           'a.postcode, a.city, a.phone, a.mobile, a.fax, sr.name AS sripf_name, c.alpha_3, s.honor_member')
	  ->from('#__snipf_person AS p')
	  ->join('INNER', '#__snipf_address AS a ON a.person_id=p.id AND a.type=p.mail_address_type AND history=0')
	  ->join('LEFT', '#__snipf_country AS c ON a.country_code=c.alpha_2')
	  ->join('LEFT', '#__snipf_subscription AS s ON s.person_id=p.id')
	  ->join('LEFT', '#__snipf_sripf AS sr ON sr.id=a.sripf_id')
	  ->where('p.id IN('.implode(',', $personIds).')');
    $db->setQuery($query);
    $persons = $db->loadObjectList(); 

    //Adds some extra variables.
    foreach($persons as $person) {
      $person->person_title = JText::_('COM_SNIPF_OPTION_'.$person->person_title);
      $person->country = JText::_('COM_SNIPF_LANG_COUNTRY_'.$person->alpha_3);
    }

    //Gets the task string.
    $task = $this->input->post->get('task', '', 'str');
    //Gets the document type from the task string.
    preg_match('#^subscriptions\.generateDocument\.([a-z_]+)$#', $task, $matches);
    $documentType = $matches[1];

    if($documentType == 'pdf_labels') {
      $template = 'subscription_labels';
      //Sets the pdf option array.
      $options = array('margins' => array('left' => 5, 'top' => 5, 'right' => 5, 'bottom' => 5),
		       'format' => array('orientation' => 'L', 'type' => 'DYMO'),
		       'font_size' => 11);
      $data = array();
      $item = new JObject;
      $nbPerPage = 1;
      $index = 1;

      //For labels we need to display 1 person's data per page. So the data has to be
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

      TcpdfHelper::generatePDF($data, $template, $options);

      return true;
    }
    elseif($documentType == 'csv') {
      $persons = $this->getAllPersonData($personIds);
      $csvFileName = SnipfHelper::generateCSV($persons);
      $uri = JUri::getInstance();
      $csvUrl = $uri->root().'administrator/components/com_snipf/csv/download.php?csv_file='.$csvFileName;

      $this->setRedirect($csvUrl);

      return true;
    }
  }


  public function getAllPersonData($personIds)
  {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $nullDatetime = $db->getNullDate();
    //Gets some data from the person.
    $query->select('p.*, ha.*, s.*, ws.*, p.id AS person_id, pa.street AS street_pa, pa.additional_address AS additional_address_pa,'.
	           'pa.postcode AS postcode_pa, pa.employer_name AS employer_name_pa, pa.city AS city_pa, pa.phone AS phone_pa,'.
		   'pa.mobile AS mobile_pa, pa.fax AS fax_pa, pa.cee AS cee_pa, sr.name AS sripf_name, hac.alpha_3 AS alpha_3_ha,'.
		   'pac.alpha_3 AS alpha_3_pa, bc.alpha_3 AS alpha_3_bc, czc.alpha_3 AS alpha_3_cz, rg.lang_var AS region_lang_var')
	  ->from('#__snipf_person AS p')
	  //Gets the personal address
	  ->join('INNER', '#__snipf_address AS ha ON ha.person_id=p.id AND ha.type="ha" AND ha.history=0')
	  ->join('LEFT', '#__snipf_country AS hac ON ha.country_code=hac.alpha_2')
	  //Gets the professional address
	  ->join('LEFT', '#__snipf_address AS pa ON pa.person_id=p.id AND pa.type="pa" AND pa.history=0')
	  ->join('LEFT', '#__snipf_country AS pac ON pa.country_code=pac.alpha_2')
	  //Gets birth country
	  ->join('LEFT', '#__snipf_country AS bc ON p.country_of_birth=bc.alpha_2')
	  //Gets citizenship country
	  ->join('LEFT', '#__snipf_country AS czc ON p.citizenship=czc.alpha_2')
	  //Gets birth region 
	  ->join('LEFT', '#__snipf_region AS rg ON rg.code=p.region_of_birth')
	  ->join('LEFT', '#__snipf_subscription AS s ON s.person_id=p.id')
	  ->join('LEFT', '#__snipf_sripf AS sr ON sr.id=ha.sripf_id')
	  ->join('LEFT', '#__snipf_work_situation AS ws ON ws.person_id=p.id')
	  ->where('p.id IN('.implode(',', $personIds).')');
    $db->setQuery($query);
    $persons = $db->loadObjectList(); 

    //Gets the timezone from the server offset.
    $timezone = new DateTimeZone(JFactory::getConfig()->get('offset'));
    $dates = array('adhesion_date', 'resignation_date', 'deregistration_date', 'reinstatement_date',
		   'honor_member_date', 'retirement_date', 'birthdate', 'deceased_date');
    //Dates which don't need offset.
    $utcDates = array('retirement_date', 'birthdate', 'deceased_date');

    foreach($persons as $person) {
      $person->person_title = JText::_('COM_SNIPF_OPTION_'.$person->person_title);
      $person->country_of_birth = JText::_('COM_SNIPF_LANG_COUNTRY_'.$person->alpha_3_bc);
      $person->citizenship = JText::_('COM_SNIPF_LANG_COUNTRY_'.$person->alpha_3_cz.'_CTZ');
      $person->country_ha = JText::_('COM_SNIPF_LANG_COUNTRY_'.$person->alpha_3_ha);
      $person->country_pa = JText::_('COM_SNIPF_LANG_COUNTRY_'.$person->alpha_3_pa);
      $person->region_of_birth = JText::_($person->region_lang_var);
      $person->status = JText::_('COM_SNIPF_OPTION_'.$person->status);
      $person->mail_address_type = JText::_('COM_SNIPF_TAB_'.$person->mail_address_type);
      $person->active_retired = JText::_('COM_SNIPF_YES_NO_'.$person->active_retired);
      $person->cqp1 = JText::_('COM_SNIPF_YES_NO_'.$person->cqp1);
      $person->cee = JText::_('COM_SNIPF_YES_NO_'.$person->cee);
      $person->cee_pa = JText::_('COM_SNIPF_YES_NO_'.$person->cee_pa);
      $person->honor_member = JText::_('COM_SNIPF_YES_NO_'.$person->honor_member);

      //Applies offset on dates.
      foreach($dates as $dateName) {
	if($person->$dateName > $db->getNullDate()) {
	  if(!in_array($dateName, $utcDates)) {
	    $date = new JDate($person->$dateName); 
	    $person->$dateName = JHtml::_('date', $date->setTimezone($timezone), JText::_('DATE_FORMAT_FILTER_DATE'));
	  }
	  else {
	    $person->$dateName = JHtml::_('date', $person->$dateName, JText::_('DATE_FORMAT_FILTER_DATE'));
	  }
	}
	else {
	  $person->$dateName = '';
	}
      }
    }

    //Sets the subscription status.
    if(($person->deregistration_date == $nullDatetime && $person->resignation_date == $nullDatetime) ||
       $person->reinstatement_date > $nullDatetime) {
      $person->subscription_status = JText::_('COM_SNIPF_OPTION_MEMBERSHIP');
    }
    else {
      $person->subscription_status = JText::_('COM_SNIPF_OPTION_NO_MEMBERSHIP');
    }

    return $persons;
  }
}



