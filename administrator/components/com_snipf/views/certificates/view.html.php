<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access
 
jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/snipf.php';
require_once JPATH_COMPONENT.'/helpers/javascript.php';
 

class SnipfViewCertificates extends JViewLegacy
{
  protected $items;
  protected $state;
  protected $pagination;
  public $nullDate;
  public $now;
  public $endValidityStates;
  public $certificateState;
  protected $readonly;

  //Display the view.
  public function display($tpl = null)
  {
    $this->items = $this->get('Items');
    $this->state = $this->get('State');
    $this->pagination = $this->get('Pagination');
    $this->filterForm = $this->get('FilterForm');
    $this->activeFilters = $this->get('ActiveFilters');
    $this->nullDate = JFactory::getDbo()->getNullDate();
    $this->now = JFactory::getDate()->toSql();
    $this->endValidityStates = $this->getModel()->endValidityStates;
    $this->certificateState = $this->state->get('filter.certificate_state');
    //Checks if the user is in readonly mode.
    $this->readonly = SnipfHelper::isReadOnly();

    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JFactory::getApplication()->enqueueMessage(implode('<br />', $errors), 'error');
      return false;
    }

    $this->getCertificateState();

    JavascriptHelper::loadJavascriptTexts();

    //Display the tool bar.
    $this->addToolBar();

    $this->setDocument();
    $this->sidebar = JHtmlSidebar::render();

    //Display the template.
    parent::display($tpl);
  }


  //Build the toolbar.
  protected function addToolBar() 
  {
    //Display the view title and the icon.
    JToolBarHelper::title(JText::_('COM_SNIPF_CERTIFICATES_TITLE'), 'stack');

    //Get the allowed actions list
    $canDo = SnipfHelper::getActions();

    //Note: We check the user permissions only against the component since 
    //the certificate items have no categories.
    if($canDo->get('core.create')) {
      JToolBarHelper::addNew('certificate.add', 'JTOOLBAR_NEW');
    }

    //Notes: The Edit icon might not be displayed since it's not (yet ?) possible 
    //to edit several items at a time.
    if($canDo->get('core.edit') || $canDo->get('core.edit.own')) {
      JToolBarHelper::editList('certificate.edit', 'JTOOLBAR_EDIT');
    }

    if($canDo->get('core.edit.state')) {
      JToolBarHelper::divider();
      JToolBarHelper::custom('certificates.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
      JToolBarHelper::custom('certificates.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
      JToolBarHelper::divider();
      JToolBarHelper::archiveList('certificates.archive','JTOOLBAR_ARCHIVE');
      JToolBarHelper::custom('certificates.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
      JToolBarHelper::trash('certificates.trash','JTOOLBAR_TRASH');
    }

    if($canDo->get('core.delete')) {
      JToolBarHelper::divider();
      JToolBarHelper::deleteList('', 'certificates.delete', 'JTOOLBAR_DELETE');
    }

    if($this->state->get('filter.certificate_state') == 'initial_running_no_membership') {
      JToolBarHelper::custom('certificates.generateDocument.pdf_new_ci', 'file-2.png', 'file-2_f2.png','COM_SNIPF_PDF_LETTERS', false);
      JToolBarHelper::custom('certificates.generateDocument.pdf_labels', 'file-2.png', 'file-2_f2.png','COM_SNIPF_PDF_LABELS', false);
    }

    if($canDo->get('core.admin')) {
      JToolBarHelper::divider();
      JToolBarHelper::preferences('com_snipf', 550);
    }
  }


  /**
   * Method which works out the state of each certificate.
   * It relies on the certificate end date as well as the last process variables to infer
   * the state of the last or 2 last processes of the certificate.
   *
   * @return  void
   */
  protected function getCertificateState() 
  {
    foreach($this->items as $key => $item) {
      if($item->process_nb === null) {
	$item->process_states = array('no_process');
      }
      else {
	//Starts with the pending states of the initial process (ie: CI).
	if($item->end_date == $this->nullDate && empty($item->return_file_number) && $item->process_nb == 1) {
	  $item->process_states = array('initial_pending');
	}
	elseif($item->end_date == $this->nullDate && !empty($item->return_file_number) &&
	       $item->process_nb == 1 && empty($item->closure_reason)) {
	  $item->process_states = array('commission_pending');

	  if($item->outcome == 'rejected') {
	    $item->process_states = array('rejected_pending');
	  }
	}
	elseif(empty($item->closure_reason)) { //The certificate is opened.

	  if($item->end_date > $this->now) { //Certificate validity is running.
	    if(!empty($item->return_file_number) && $item->outcome == 'accepted') {
	      $item->process_states = array('running');
	    }
	    elseif(!empty($item->return_file_number) && $item->outcome != 'accepted' &&
		    $item->outcome != 'rejected') { //pending, adjourned
	      $item->process_states = array('running', 'commission_pending');
	    }
	    elseif(empty($item->return_file_number) && $item->outcome != 'rejected') {
	      $item->process_states = array('running', 'file_pending');
	    }
	    elseif($item->outcome == 'rejected') {
	      $item->process_states = array('running', 'rejected');
	    }
	  }
	  else { //Certificate validity is outdated.
	    if(!empty($item->return_file_number) && $item->outcome == 'accepted') {
	      $item->process_states = array('outdated');
	    }
	    elseif(!empty($item->return_file_number) && $item->outcome != 'accepted' && 
		   $item->outcome != 'rejected') { //pending, adjourned
	      $item->process_states = array('outdated', 'commission_pending');
	    }
	    elseif(empty($item->return_file_number)) {
	      $item->process_states = array('outdated', 'file_pending');
	    }
	    elseif($item->outcome == 'rejected') {
	      $item->process_states = array('outdated', 'rejected');
	    }
	  }
	}
	else { //The certificate is closed.
	  if($item->outcome != 'accepted') {
	    $item->process_states = array($item->closure_reason, 'obsolete');
	  }
	  else {
	    $item->process_states = array($item->closure_reason);
	  }
	}

	//Sets the names of the processes of each certificate.
	if($item->process_nb == 1) {
	  $item->process_names = array(JText::_('COM_SNIPF_INITIAL_CERTIFICATE'));
	}
	elseif($item->process_nb == 2) {
	  $item->process_names = array(JText::sprintf('COM_SNIPF_RENEWAL_NB_X', 1));

	  if(count($item->process_states) == 2) {
	    array_unshift($item->process_names, JText::_('COM_SNIPF_INITIAL_CERTIFICATE'));
	  }
	}
	else {
	  $current = $item->process_nb - 1;
	  $item->process_names = array(JText::sprintf('COM_SNIPF_RENEWAL_NB_X', $current));

	  if(count($item->process_states) == 2) {
	    $previous = $item->process_nb - 2;
	    array_unshift($item->process_names, JText::sprintf('COM_SNIPF_RENEWAL_NB_X', $previous));
	  }
	}
      }
    }
  }


  protected function setDocument() 
  {
    //Include css file.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_snipf/snipf.css');
  }
}


