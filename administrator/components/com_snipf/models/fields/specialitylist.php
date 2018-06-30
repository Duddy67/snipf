<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


defined('_JEXEC') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
// import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');


//Script which build the select html tag containing the speciality names and ids.

class JFormFieldSpecialityList extends JFormFieldList
{
  protected $type = 'specialitylist';

  protected function getOptions()
  {
    $options = array();
      
    //Get the country names.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('id,name')
	  ->from('#__snipf_speciality')
	  ->where('published=1')
	  ->order('name');
    $db->setQuery($query);
    $specialities = $db->loadObjectList();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_SNIPF_OPTION_SELECT'));

    //Build the select options.
    foreach($specialities as $speciality) {
      $options[] = JHtml::_('select.option', $speciality->id, $speciality->name);
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



