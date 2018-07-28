<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die; //No direct access to this file.
 
jimport('joomla.application.component.controlleradmin');
include_once JPATH_ROOT.'/administrator/components/com_snipf/helpers/tcpdf.php';
require_once JPATH_ROOT.'/administrator/components/com_snipf/helpers/snipf.php';
 

class SnipfControllerPersons extends JControllerAdmin
{
  /**
   * Proxy for getModel.
   * @since 1.6
  */
  public function getModel($name = 'Person', $prefix = 'SnipfModel', $config = array('ignore_request' => true))
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
      $this->setRedirect(TcpdfHelper::generatePDF($data, 'person_pdf'));

      return true;
    }
  }
}



