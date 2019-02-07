<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
jimport('joomla.application.component.modeladmin');
require_once JPATH_ADMINISTRATOR.'/components/com_snipf/helpers/snipf.php';
require_once JPATH_ADMINISTRATOR.'/components/com_snipf/helpers/address.php';
require_once JPATH_ADMINISTRATOR.'/components/com_snipf/helpers/beneficiary.php';
use Joomla\CMS\HTML\HTMLHelper;


class SnipfModelPerson extends JModelAdmin
{
  //Prefix used with the controller messages.
  protected $text_prefix = 'COM_SNIPF';

  //Returns a Table object, always creating it.
  //Table can be defined/overrided in tables/itemname.php file.
  public function getTable($type = 'Person', $prefix = 'SnipfTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  public function getForm($data = array(), $loadData = true) 
  {
    //Loads the corresponding form according to the user's privileges.
    $formName = 'person';
    if(SnipfHelper::isReadOnly()) {
      $formName = 'person_ro';
    }

    $form = $this->loadForm('com_snipf.person', $formName, array('control' => 'jform', 'load_data' => $loadData));

    if(empty($form)) {
      return false;
    }

    return $form;
  }


  protected function loadFormData() 
  {
    // Check the session for previously entered form data.
    $data = JFactory::getApplication()->getUserState('com_snipf.edit.person.data', array());

    if(empty($data)) {
      $data = $this->getItem();
    }

    return $data;
  }


  /**
   * Method to get a single record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  mixed  Object on success, false on failure.
   */
  public function getItem($pk = null)
  {
    if($item = parent::getItem($pk)) {
      //Get both intro_text and full_text together as persontext
      $item->persontext = trim($item->full_text) != '' ? $item->intro_text."<hr id=\"system-readmore\" />".$item->full_text : $item->intro_text;

      //Get tags for this item.
      if(!empty($item->id)) {
	$item->tags = new JHelperTags;
	$item->tags->getTagIds($item->id, 'com_snipf.person');
      }

      //Gets the person's addresses.
      $item->addresses = AddressHelper::getAddresses($item->id);
      //Gets the person's beneficiaries.
      $item->beneficiaries = BeneficiaryHelper::getBeneficiaries($item->id);

      //Gets the work situation data.
      $db = $this->getDbo();
      $query = $db->getQuery(true);
      $query->select('employer_name, employer_activity, ape_code, position, comments, law_company')
	    ->from('#__snipf_work_situation')
	    ->where('person_id='.(int)$item->id);
      $db->setQuery($query);
      $item->work_situation = $db->loadObject();

      $currentYear = date("Y");
      $query->clear();
      $query->select('sub.id, IFNULL(sp.item_id, 0) AS process_id, IFNULL(sp.cads_payment, 0) AS cads_payment')
	    ->from('#__snipf_subscription AS sub')
	    ->join('LEFT', '#__snipf_process AS sp ON sp.item_id=sub.id AND sp.item_type="subscription" AND sp.name='.$db->Quote($currentYear))
	    ->where('sub.person_id='.(int)$item->id);
      $db->setQuery($query);
      $subscription = $db->loadObject();

      $item->subscription_status = 'no_membership';
      if($subscription) {
	if($subscription->cads_payment) {
	  $item->subscription_status = 'membership';
	}
	//No process for the current year or no cads payment.
	elseif($subscription->process_id == 0 || $subscription->cads_payment == 0) {
	  $item->subscription_status = 'unpaid';
	}
      }

      $item->certification_status = $this->getCertificationStatus($item->id);
      $item->extra_data_text = SnipfHelper::getCqp1ExtraDataText($item->cqp1_extra_data);
    }

    return $item;
  }


  /**
   * Loads ContentHelper for filters before validating data.
   *
   * @param   object  $form   The form to validate against.
   * @param   array   $data   The data to validate.
   * @param   string  $group  The name of the group(defaults to null).
   *
   * @return  mixed  Array of filtered data if valid, false otherwise.
   *
   * @since   1.1
   */
  public function validate($form, $data, $group = null)
  {
    //Gets the ini file to determine which fields have to be required according to the
    //item type.
    $mandatory = parse_ini_file(JPATH_BASE.'/components/com_snipf/models/forms/mandatory.ini');

    //Checks the professional addresses.
    if($data['mail_address_type'] == 'pa') {
      //Gets the mandatory fields of the professional address.
      $fields = $mandatory['pa'];

      //Makes the addresse's fields mandatory.
      foreach($fields as $fieldName) {
	$form->setFieldAttribute($fieldName.'_pa', 'required', 'true');
      }
    }

    //Checks whether some fields of the professional address have been filled in.
    $professionalAddressFields = AddressHelper::checkProfessionalAddress($data);

    //The professional address can be either optional or mandatory depending on the
    //mail_address_type value.
    if(!empty($professionalAddressFields) || (empty($professionalAddressFields) && $data['mail_address_type'] == 'pa')) {
      //The professional address fields have been partially filled in.
      JFactory::getApplication()->enqueueMessage(JText::_('COM_SNIPF_WARNING_INCORRECT_PROFESSIONAL_ADDRESS'), 'warning');
      //Makes the addresse's fields mandatory.
      foreach($professionalAddressFields as $fieldName) {
	$form->setFieldAttribute($fieldName.'_'.$professionalAddressType, 'required', 'true');
      }
    }

    //Moves to the beneficiaries.

    $beneficiaryTypes = array('bfc', 'dbfc');
    //Checks fields of each type of beneficiary.
    foreach($beneficiaryTypes as $beneficiaryType) {
      $beneficiaryFields = BeneficiaryHelper::checkBeneficiary($beneficiaryType, $data);
      if(!empty($beneficiaryFields)) {
	//The beneficiary fields have been partially filled in.
        JFactory::getApplication()->enqueueMessage(JText::_('COM_SNIPF_WARNING_INCORRECT_'.strtoupper($beneficiaryType)), 'warning');
	//Makes the beneficiary's fields mandatory.
	foreach($beneficiaryFields as $fieldName) {
	  $form->setFieldAttribute($fieldName.'_'.$beneficiaryType, 'required', 'true');
	}
      }
    }

    return parent::validate($form, $data, $group);
  }


  /**
   * Prepare and sanitise the table data prior to saving.
   *
   * @param   JTable  $table  A JTable object.
   *
   * @return  void
   *
   * @since   1.6
   */
  protected function prepareTable($table)
  {
    // Set the publish date to now
    if($table->published == 1 && (int)$table->publish_up == 0) {
      $table->publish_up = JFactory::getDate()->toSql();
    }

    if($table->published == 1 && intval($table->publish_down) == 0) {
      $table->publish_down = $this->getDbo()->getNullDate();
    }
  }


  /**
   * Saves the manually set order of records.
   *
   * @param   array    $pks    An array of primary key ids.
   * @param   integer  $order  +1 or -1
   *
   * @return  mixed
   *
   * @since   12.2
   */
  public function saveorder($pks = null, $order = null)
  {

    //Hand over to the parent function.
    return parent::saveorder($pks, $order);
  }


  /**
   * Computes the certification status for a given person. 
   * The certification status is computed according to the state of the 
   * certificates owned by the given person.
   *
   * @param   integer  $personId  The id of the person.
   *
   * @return string  The certification status. 
   */
  public function getCertificationStatus($personId)
  {
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('c.closure_reason, c.end_date, p.number, p.outcome')
	  ->from('#__snipf_certificate AS c')
	  ->join('INNER', '#__snipf_process AS p ON p.item_id=c.id AND p.item_type="certificate"')
	  ->where('c.person_id='.(int)$personId)
	  ->where('c.published=1 AND p.is_last=1');
    $db->setQuery($query);
    $certificates = $db->loadObjectList();

    $nbCertificates = count($certificates);
    $now = JFactory::getDate()->toSql();
    $initialCertificate = $outdatedCertificat = 0;

    foreach($certificates as $certificate) {
      //As soon as a retired or deceased closure reason is found, it means that the person 
      //has to be formerly certified. 
      if($certificate->closure_reason == 'retired' || $certificate->closure_reason == 'deceased') {
	return 'formerly_certified';
      }

      if(empty($certificate->closure_reason) && $certificate->end_date > $now) {
	return 'certified';
      }

      if(empty($certificate->closure_reason) && $certificate->end_date < $now) {
	$outdatedCertificat++;
      }

      //Initial certificates. There can be several of them for the same person.
      if($certificate->number == 1 && $certificate->end_date == $db->getNullDate()) {
	$initialCertificate++;
      }
    }

    if(!$nbCertificates || $initialCertificate == $nbCertificates) {
      return 'no_certificate';
    }

    //The person owns at least one outdated certificate.
    if($outdatedCertificat) {
      return 'outdated';
    }

    //If no certification status has matched so far the person 
    //has to be no longer certified
    return 'no_longer_certified';
  }


  /**
   * Method to get a single record.
   *
   * @param   integer  $pk		The id of the primary key.
   * @param   boolean  $showTime	Indicates whether date values show time or not.
   *
   * @return  array    An array of position items.
   */
  public function getPositions($pk = null, $showTime = false)
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState($this->getName().'.id');

    $db = $this->getDbo();
    $query = $db->getQuery(true);
    //Get the positions linked to the person if any.
    $query->select('position_id, sripf_id, start_date, end_date, comments AS position_comments')
	  ->from('#__snipf_person_position_map')
	  ->where('person_id='.(int)$pk);
    $db->setQuery($query);
    $positions = $db->loadAssocList();

    //Gets the current date format.
    $format = JText::_('DATE_FORMAT_FILTER_DATE');
    if($showTime) {
      $format = JText::_('DATE_FORMAT_FILTER_DATETIME');
    }

    //Formats the date values according to the current format.
    foreach($positions as $key => $position) {

      if($positions[$key]['start_date'] == $db->getNullDate()) {
	//Important: or HTMLHelper returns a random and invalid date.
	$positions[$key]['start_date'] = '';
      }
      else {
	$positions[$key]['start_date'] = HTMLHelper::date($position['start_date'], $format);
      }

      if($positions[$key]['end_date'] == $db->getNullDate()) {
	$positions[$key]['end_date'] = '';
      }
      else {
	$positions[$key]['end_date'] = HTMLHelper::date($position['end_date'], $format);
      }
    }

    return $positions;
  }


  /**
   * Method to test whether a record can be deleted.
   *
   * @param   object  $record  A record object.
   *
   * @return  boolean  True if allowed to delete the record. Defaults to the permission for the component.
   *
   * @since   1.6
   */
  protected function canDelete($record)
  {
    //First checks that the person to delete is not being edited.
    if($record->checked_out) {
      JFactory::getApplication()->enqueueMessage(JText::_('COM_SNIPF_WARNING_ITEM_IS_BEING_EDITED'), 'warning');
      return false;
    }

    //Then checks that none of the certificates linked to the person is being edited.
    $db = $this->getDbo();
    $query = $db->getQuery(true);
    $query->select('number, checked_out')
	  ->from('#__snipf_certificate')
	  ->where('person_id='.(int)$record->id);
    $db->setQuery($query);
    $certificates = $db->loadObjectList();

    foreach($certificates as $certificate) {
      if($certificate->checked_out) {
	JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_SNIPF_WARNING_CERTIFICATE_IS_BEING_EDITED', $certificate->name), 'warning');
	return false;
      }
    }

    return parent::canDelete($record);
  }
}

