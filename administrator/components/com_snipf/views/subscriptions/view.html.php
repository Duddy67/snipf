<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined( '_JEXEC' ) or die; // No direct access
 
jimport( 'joomla.application.component.view');
require_once JPATH_COMPONENT.'/helpers/snipf.php';
 

class SnipfViewSubscriptions extends JViewLegacy
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

    foreach($this->items as $item) {
      //Sets the payment status according to the values of the cads payments.
      if($item->current_year_process) {
	$item->payment_status = 'partially_paid';
	if($item->cads_payment) {
	  $item->payment_status = 'paid';
	}
      }
      else { // There is no current year.
	//Just in case.
	$item->payment_status = 'no_process';
	if($item->last_registered_year) {
	  $item->payment_status = 'unpaid';
	}
      }

      //Sets the subscription status.
      $item->subscription_status = 'membership';
      if(($item->deregistration_date > $nullDate || $item->resignation_date > $nullDate) && $item->reinstatement_date == $nullDate) {
	$item->subscription_status = 'no_longer_membership';
      }

      //Sets the person status.
      $item->person_status = $model->getCertificationStatus($item->person_id);
      if($item->status == 'retired' || $item->status == 'deceased') {
	$item->person_status = $item->status;
      }
      elseif($item->cqp1) {
	$item->person_status = 'cqp1';
      }
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
    JToolBarHelper::title(JText::_('COM_SNIPF_SUBSCRIPTIONS_TITLE'), 'file-2');

    //Get the allowed actions list
    $canDo = SnipfHelper::getActions();

    //Note: We check the user permissions only against the component since 
    //the subscription items have no categories.
    if($canDo->get('core.create')) {
      JToolBarHelper::addNew('subscription.add', 'JTOOLBAR_NEW');
    }

    //Notes: The Edit icon might not be displayed since it's not (yet ?) possible 
    //to edit several items at a time.
    if($canDo->get('core.edit') || $canDo->get('core.edit.own')) {
      JToolBarHelper::editList('subscription.edit', 'JTOOLBAR_EDIT');
    }

    if($canDo->get('core.edit.state')) {
      JToolBarHelper::divider();
      JToolBarHelper::custom('subscriptions.publish', 'publish.png', 'publish_f2.png','JTOOLBAR_PUBLISH', true);
      JToolBarHelper::custom('subscriptions.unpublish', 'unpublish.png', 'unpublish_f2.png', 'JTOOLBAR_UNPUBLISH', true);
      JToolBarHelper::divider();
      JToolBarHelper::archiveList('subscriptions.archive','JTOOLBAR_ARCHIVE');
      JToolBarHelper::custom('subscriptions.checkin', 'checkin.png', 'checkin_f2.png', 'JTOOLBAR_CHECKIN', true);
      JToolBarHelper::trash('subscriptions.trash','JTOOLBAR_TRASH');
    }

    if($canDo->get('core.delete')) {
      JToolBarHelper::divider();
      JToolBarHelper::deleteList('', 'subscriptions.delete', 'JTOOLBAR_DELETE');
    }

    if(!$this->readonly || ($this->readonly && !empty(SnipfHelper::getUserSripfs()))) {
      if($this->state->get('filter.sripf_id') != '') {
	JToolBarHelper::custom('subscriptions.generateDocument.pdf_labels', 'file-2.png', 'file-2_f2.png','COM_SNIPF_PDF_LABELS', false);
      }

      JToolBarHelper::custom('subscriptions.generateDocument.csv', 'file-2.png', 'file-2_f2.png','COM_SNIPF_GENERATE_CSV', false);
    }

    if($canDo->get('core.admin')) {
      JToolBarHelper::divider();
      JToolBarHelper::preferences('com_snipf', 550);
    }
  }


  protected function setDocument() 
  {
    //Include css file.
    $doc = JFactory::getDocument();
    $doc->addStyleSheet(JURI::base().'components/com_snipf/snipf.css');
  }
}


