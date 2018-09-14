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


//Script which build the select html tag containing the country names and codes.

class JFormFieldCountryList extends JFormFieldList
{
  protected $type = 'countrylist';

  protected function getOptions()
  {
    $options = array();

    //Get the country names.
    $db = JFactory::getDbo();
    $query = $db->getQuery(true);
    $query->select('alpha_2,name,lang_var')
	  ->from('#__snipf_country')
	  ->where('published=1')
	  ->order('alpha_3');
    $db->setQuery($query);
    $countries = $db->loadObjectList();

    //Build the first option.
    $options[] = JHtml::_('select.option', '', JText::_('COM_SNIPF_OPTION_SELECT'));

    //Build the select options.
    foreach($countries as $country) {
      if($this->id == 'jform_citizenship') {
	$country->lang_var = $country->lang_var.'_CTZ';
      }

      $options[] = JHtml::_('select.option', $country->alpha_2, (empty($country->lang_var)) ? JText::_($country->name) : JText::_($country->lang_var));
    }

    // Merge any additional options in the XML definition.
    $options = array_merge(parent::getOptions(), $options);

    return $options;
  }
}



