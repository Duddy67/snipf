<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access
 

jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/snipf.php';
 

class SnipfViewSubscription extends JViewLegacy
{
  protected $item;
  protected $form;
  protected $state;
  protected $readonly;

  //Display the view.
  public function display($tpl = null)
  {
    $this->item = $this->get('Item');
    $this->form = $this->get('Form');
    $this->state = $this->get('State');
    //Checks if the user is in readonly mode.
    $this->readonly = SnipfHelper::isReadOnly();

    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JFactory::getApplication()->enqueueMessage(implode('<br />', $errors), 'error');
      return false;
    }

    // Creates a new JForm object
    $this->processForm = new JForm('ProcessForm');
    $fileName = 'subscription_process';

    if($this->readonly) {
      $fileName = 'subscription_process_ro';
    }

    $this->processForm->loadFile('components/com_snipf/models/forms/'.$fileName.'.xml');

    JText::script('COM_SNIPF_WARNING_DELETE_PROCESS'); 
    JText::script('COM_SNIPF_WARNING_INVALID_YEAR'); 

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
    JToolBarHelper::title($isNew ? JText::_('COM_SNIPF_NEW_SUBSCRIPTION') : JText::_('COM_SNIPF_EDIT_SUBSCRIPTION'), 'pencil-2');

    if($isNew) {
      //Check the "create" permission for the new records.
      if($canDo->get('core.create')) {
	JToolBarHelper::apply('subscription.apply', 'JTOOLBAR_APPLY');
	JToolBarHelper::save('subscription.save', 'JTOOLBAR_SAVE');
	JToolBarHelper::custom('subscription.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
      }
    }
    else {
      // Can't save the record if it's checked out.
      if(!$checkedOut) {
	// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
	if($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId)) {
	  // We can save the new record
	  JToolBarHelper::apply('subscription.apply', 'JTOOLBAR_APPLY');
	  JToolBarHelper::save('subscription.save', 'JTOOLBAR_SAVE');

	  // We can save this record, but check the create permission to see if we can return to make a new one.
	  if($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_snipf', 'core.create'))) > 0) {
	    JToolBarHelper::custom('subscription.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
	  }
	}
      }
    }

    JToolBarHelper::divider();
    //
    if($this->item->id && JFactory::getApplication()->input->get('process', '', 'string') == '' && !$this->readonly) {
      JToolbarHelper::custom('subscription.process.create', 'cogs', '', 'COM_SNIPF_NEW_SUBSCRIPTION', false);
    }

    JToolBarHelper::cancel('subscription.cancel', 'JTOOLBAR_CANCEL');
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



