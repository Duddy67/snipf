<?php
/**
 * @package SNIPF 
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

// No direct access.
defined('_JEXEC') or die; 
//Allows to keep the tab state identical in edit form after saving.
JHtml::_('behavior.tabstate');

//Check against the user permissions.
if(!JFactory::getUser()->authorise('core.manage', 'com_snipf')) {
  JFactory::getApplication()->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
  return false;
}

// Include dependancies
jimport('joomla.application.component.controller');

$controller = JControllerLegacy::getInstance('Snipf');

//Execute the requested task (set in the url).
//If no task is set then the "display' task will be executed.
$controller->execute(JFactory::getApplication()->input->get('task'));

$controller->redirect();



