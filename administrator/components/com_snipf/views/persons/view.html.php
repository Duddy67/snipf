<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access
 
jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/snipf.php';
 

class SnipfViewPersons extends JViewLegacy
{
  protected $items;
  protected $state;
  protected $pagination;
  protected $readonly;

  //Display the view.
  public function display($tpl = null)
  {
    $this->items = $this->get('Items');
    $this->state = $this->get('State');
    $this->pagination = $this->get('Pagination');
    $this->filterForm = $this->get('FilterForm');
    $this->activeFilters = $this->get('ActiveFilters');
    $model = JModelLegacy::getInstance('Person', 'SnipfModel');
    $nullDate = '0000-00-00 00:00:00';
    //Checks if the user is in readonly mode.
    $this->readonly = SnipfHelper::isReadOnly();

    //Check for errors.
    if(count($errors = $this->get('Errors'))) {
      JFactory::getApplication()->enqueueMessage(implode('<br />', $errors), 'error');
      return false;
    }

    //Sets the certification and subscription statuses.
    foreach($this->items as $item) {
      $item->subscription_status = 'no_membership';
      if(($item->deregistration_date == $nullDate && $item->resignation_date == $nullDate) || $item->reinstatement_date > $nullDate) {
	$item->subscription_status = 'membership';
      }

      $item->certification_status = $model->getCertificationStatus($item->id);
    }

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
    JToolBarHelper::title(JText::_('COM_SNIPF_PERSONS_TITLE'), 'users');

    //Get the allowed actions list
    $canDo = SnipfHelper::getActions();
    $user = JFactory::getUser();

    //The user is allowed to create or is able to create in one of the component categories.
    if($canDo->get('core.create') || (count($user->getAuthorisedCategories('com_snipf', 'core.create'))) > 0) {
      JToolBarHelper::addNew('person.add', 'JTOOLBAR_NEW');
    }

    if($canDo->get('core.edit') || $canDo->get('core.edit.own') || 
       (count($user->getAuthorisedCategories('com_snipf', 'core.edit'))) > 0 || 
       (count($user->getAuthorisedCategories('com_snipf', 'core.edit.own'))) > 0) {
      JToolBarHelper::editList('person.edit', 'JTOOLBAR_EDIT');
    }

    if($canDo->get('core.edit.state')) {
      JToolBarHelper::divider();
      JToolBarHelper::custom('persons.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
      JToolBarHelper::custom('persons.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
      JToolBarHelper::divider();
      JToolBarHelper::archiveList('persons.archive','JTOOLBAR_ARCHIVE');
      JToolBarHelper::custom('persons.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
      JToolBarHelper::trash('persons.trash','JTOOLBAR_TRASH');
    }

    //Check for delete permission.
    if($canDo->get('core.delete') || count($user->getAuthorisedCategories('com_snipf', 'core.delete'))) {
      JToolBarHelper::divider();
      JToolBarHelper::deleteList('', 'persons.delete', 'JTOOLBAR_DELETE');
    }

    //JToolBarHelper::custom('persons.generateDocument.pdf', 'file-2.png', 'file-2_f2.png','COM_SNIPF_GENERATE_PDF', false);

    if($canDo->get('core.admin')) {
      JToolBarHelper::divider();
      JToolBarHelper::preferences('com_snipf', 550);
    }
  }


  protected function setDocument() 
  {
    //Include css file (if needed).
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_snipf/snipf.css');
  }
}


