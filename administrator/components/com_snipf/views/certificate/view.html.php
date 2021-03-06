<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access
 

jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/snipf.php';
require_once JPATH_COMPONENT.'/helpers/process.php';
require_once JPATH_COMPONENT.'/helpers/javascript.php';
 

class SnipfViewCertificate extends JViewLegacy
{
  protected $item;
  protected $form;
  protected $state;
  public $nullDate;
  public $certificateState;
  protected $readonly;

  //Display the view.
  public function display($tpl = null)
  {
    $this->item = $this->get('Item');
    $this->form = $this->get('Form');
    $this->state = $this->get('State');
    $this->nullDate = JFactory::getDbo()->getNullDate();
    $this->certificateState = $this->getCertificateState();
    //Checks if the user is in readonly mode.
    $this->readonly = SnipfHelper::isReadOnly();

    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JFactory::getApplication()->enqueueMessage(implode('<br />', $errors), 'error');
      return false;
    }

    // Creates a new JForm object
    $this->processForm = new JForm('ProcessForm');
    $fileName = 'certificate_process';

    if($this->readonly) {
      $fileName = 'certificate_process_ro';
    }

    $this->processForm->loadFile('components/com_snipf/models/forms/'.$fileName.'.xml');

    JText::script('COM_SNIPF_WARNING_DELETE_PROCESS'); 
    JavascriptHelper::loadJavascriptTexts();

    //Display the toolbar.
    $this->addToolBar();

    $this->setDocument();

    //Display the template.
    parent::display($tpl);
  }


  protected function addToolBar() 
  {
    //Make main menu inactive.
    JFactory::getApplication()->input->set('hidemainmenu', true);

    $user = JFactory::getUser();
    $userId = $user->get('id');

    //Get the allowed actions list
    $canDo = SnipfHelper::getActions($this->state->get('filter.category_id'));
    $isNew = $this->item->id == 0;
    $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);

    //Display the view title (according to the user action) and the icon.
    JToolBarHelper::title($isNew ? JText::_('COM_SNIPF_NEW_CERTIFICATE') : JText::_('COM_SNIPF_EDIT_CERTIFICATE'), 'pencil-2');

    if($isNew) {
      //Check the "create" permission for the new records.
      if($canDo->get('core.create')) {
	JToolBarHelper::apply('certificate.apply', 'JTOOLBAR_APPLY');
	JToolBarHelper::save('certificate.save', 'JTOOLBAR_SAVE');
	JToolBarHelper::custom('certificate.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
      }
    }
    else {
      // Can't save the record if it's checked out.
      if(!$checkedOut) {
	// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
	if($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId)) {
	  // We can save the new record
	  JToolBarHelper::apply('certificate.apply', 'JTOOLBAR_APPLY');
	  JToolBarHelper::save('certificate.save', 'JTOOLBAR_SAVE');

	  // We can save this record, but check the create permission to see if we can return to make a new one.
	  if($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_snipf', 'core.create'))) > 0) {
	    JToolBarHelper::custom('certificate.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
	  }
	}
      }
    }

    JToolBarHelper::divider();
    //
    if($this->canAddProcess() && !$this->readonly) {
      JToolbarHelper::custom('certificate.process.create', 'cogs', '', 'COM_SNIPF_CI_OR_RENEWAL', false);
    }

    JToolBarHelper::cancel('certificate.cancel', 'JTOOLBAR_CANCEL');
  }


  protected function canAddProcess() 
  {
    $db = JFactory::getDbo();

    if(!$this->item->id || $this->item->closure_date != $db->getNullDate() || !empty($this->item->closure_reason)) {
      return false;
    }

    $nbProcesses = $this->item->nb_processes;

    if(!$nbProcesses) {
      return true;
    }

    $lastProcess = $this->item->processes[$nbProcesses - 1];

    if($lastProcess->file_receiving_date == $db->getNullDate() ||
       empty($lastProcess->return_file_number) || $lastProcess->outcome != 'accepted') {
      return false;
    }

    return true;
  }


  /**
   * Method which works out the state of the certificate.
   * It relies on the certificate end date as well as the last process variables.
   *
   * @return  string  The state of the certificate.
   */
  protected function getCertificateState() 
  {
    $nbProcesses = $this->item->nb_processes;

    if(!$nbProcesses) {
      return 'no_process';
    }

    if($this->item->closure_date != $this->nullDate) {
      //The process's cycle is over.
      return 'done';
    }

    $lastProcess = $this->item->processes[$nbProcesses - 1];
    $now = JFactory::getDate()->toSql();

    if(!empty($lastProcess->file_receiving_date) && $lastProcess->file_receiving_date != $this->nullDate) {
      //
      if(empty($lastProcess->commission_date) || $lastProcess->commission_date == $this->nullDate) {
	//The file_receiving_date and return_file_number have just been filled in and
	//saved. The admin must now set the commission_date field.
	//This is a transitory state.
	return 'transitory_pending';
      }
      elseif($lastProcess->outcome == 'pending' || $lastProcess->outcome == 'adjourned' || $lastProcess->outcome == 'rejected') {
	if($now > $this->item->end_date && $nbProcesses > 1 && $lastProcess->outcome != 'rejected') {
	  //outdated && commission_pending
	  return 'overlap'; 
	}
	elseif($now > $this->item->end_date && $nbProcesses > 1 && $lastProcess->outcome == 'rejected') {
	  //outdated && rejected_file
	  return 'rejected_overlap'; 
	}

	if($lastProcess->outcome == 'rejected') {
	  //running && rejected_file
	  return 'rejected_file';
	}
	else {
	  //running && commission_pending
	  return 'commission_pending';
	}
      }
      //For whatever reason there's no ask for renewal and the current process came to an end. 
      elseif($lastProcess->outcome == 'accepted' && $now > $lastProcess->end_process) {
	return 'current_outdated';
      }
      elseif($lastProcess->outcome == 'accepted') {
	return 'running';
      }
    }
    else { //No file has been returned.
      //
      if($nbProcesses == 1) {
	return 'initial_pending';
      }

      if($now > $this->item->end_date) {
	//outdated && file_pending
	return 'outdated';
      }

      return 'file_pending';
    }
  }


  protected function setDocument() 
  {
    //Include the css file.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_snipf/snipf.css');

    //Adds specific css to darken the tab line.
    $style = '.nav-tabs > .active > a,
	      .nav-tabs > .active > a:hover,
	      .nav-tabs > .active > a:focus {
		border: 1px solid #5a5a5a !important;
		border-bottom-color: transparent !important;
	      }

	      .nav-tabs {
		border-bottom: 1px solid #5a5a5a !important;
	      }';

    $doc->addStyleDeclaration($style);
  }
}



