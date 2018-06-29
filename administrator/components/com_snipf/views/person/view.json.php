<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined( '_JEXEC' ) or die; // No direct access
 
jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/address.php';
 

/**
 * JSON Person View class. Mainly used for Ajax request. 
 */
class SnipfViewPerson extends JViewLegacy
{
  public function display($tpl = null)
  {
    $jinput = JFactory::getApplication()->input;
    $context = $jinput->get('context', '', 'str');
    $personId = $jinput->get('person_id', 0, 'uint');
    $results = array();

    if($context == 'get_persons') {
      $showTime = (int)$jinput->get('show_time', 0, 'uint');
      // Get some data from the models
      $model = $this->getModel();

      $positions = $model->getPositions($personId, $showTime);
      $results['position'] = $positions;
    }
    elseif($context == 'delete_item') {
      $itemName = $jinput->get('item_name', '', 'str');
      $history = $jinput->get('history', 0, 'uint');
      $idNb = $jinput->get('id_nb', 0, 'uint');
      $itemType = $jinput->get('item_type', '', 'str');

      if($itemName == 'address') {
	AddressHelper::deleteAddress($personId, $itemType, $history, $idNb);
	$results['render'] = AddressHelper::renderAddressHistory($personId, $itemType);
      }
      else { // beneficiary
	BeneficiaryHelper::deleteBeneficiary($personId, $itemType, $history, $idNb);
	$results['render'] = BeneficiaryHelper::renderBeneficiaryHistory($personId, $itemType);
      }
    }
    else {
      echo new JResponseJson($results, JText::_('COM_SNIPF_ERROR_UNKNOWN_CONTEXT'), true);
      return;
    }

    echo new JResponseJson($results);
  }
}



