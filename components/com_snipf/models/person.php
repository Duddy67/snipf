<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.


class SnipfModelPerson extends JModelItem
{

  protected $_context = 'com_snipf.person';

  /**
   * Method to auto-populate the model state.
   *
   * Person. Calling getState in this method will result in recursion.
   *
   * @since   1.6
   *
   * @return void
   */
  protected function populateState()
  {
    $app = JFactory::getApplication('site');

    // Load state from the request.
    $pk = $app->input->getInt('id');
    $this->setState('person.id', $pk);

    //Load the global parameters of the component.
    $params = $app->getParams();
    $this->setState('params', $params);

    $this->setState('filter.language', JLanguageMultilang::isEnabled());
  }


  //Returns a Table object, always creating it.
  public function getTable($type = 'Person', $prefix = 'SnipfTable', $config = array()) 
  {
    return JTable::getInstance($type, $prefix, $config);
  }


  /**
   * Method to get a single record.
   *
   * @param   integer  $pk  The id of the primary key.
   *
   * @return  mixed    Object on success, false on failure.
   *
   * @since   12.2
   */
  public function getItem($pk = null)
  {
    $pk = (!empty($pk)) ? $pk : (int)$this->getState('person.id');
    $user = JFactory::getUser();

    if($this->_item === null) {
      $this->_item = array();
    }

    if(!isset($this->_item[$pk])) {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->select($this->getState('list.select', 'p.id,p.lastname,p.alias,p.intro_text,p.full_text,p.catid,p.published,'.
				     'p.checked_out,p.checked_out_time,p.created,p.created_by,p.access,p.params,p.metadata,'.
				     'p.metakey,p.metadesc,p.hits,p.publish_up,p.publish_down,p.language,p.modified,p.modified_by'))
	    ->from($db->quoteName('#__snipf_person').' AS p')
	    ->where('p.id='.$pk);

      // Join on category table.
      $query->select('ca.title AS category_title, ca.alias AS category_alias, ca.access AS category_access')
	    ->join('LEFT', '#__categories AS ca on ca.id = p.catid');

      // Join on user table.
      $query->select('us.name AS author')
	    ->join('LEFT', '#__users AS us on us.id = p.created_by');

      // Join over the categories to get parent category titles
      $query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias')
	    ->join('LEFT', '#__categories as parent ON parent.id = ca.parent_id');

      // Filter by language
      if($this->getState('filter.language')) {
	$query->where('p.language in ('.$db->quote(JFactory::getLanguage()->getTag()).','.$db->quote('*').')');
      }

      if((!$user->authorise('core.edit.state', 'com_snipf')) && (!$user->authorise('core.edit', 'com_snipf'))) {
	// Filter by start and end dates.
	$nullDate = $db->quote($db->getNullDate());
	$nowDate = $db->quote(JFactory::getDate()->toSql());
	$query->where('(p.publish_up = '.$nullDate.' OR p.publish_up <= '.$nowDate.')')
	      ->where('(p.publish_down = '.$nullDate.' OR p.publish_down >= '.$nowDate.')');
      }

      $db->setQuery($query);
      $data = $db->loadObject();

      if(is_null($data)) {
	JFactory::getApplication()->enqueueMessage(JText::_('COM_SNIPF_ERROR_PERSON_NOT_FOUND'), 'error');
	return false;
      }

      // Convert parameter fields to objects.
      $registry = new JRegistry;
      $registry->loadString($data->params);

      $data->params = clone $this->getState('params');
      $data->params->merge($registry);

      $user = JFactory::getUser();
      // Technically guest could edit an article, but lets not check that to improve performance a little.
      if(!$user->get('guest')) {
	$userId = $user->get('id');
	$asset = 'com_snipf.person.'.$data->id;

	// Check general edit permission first.
	if($user->authorise('core.edit', $asset)) {
	  $data->params->set('access-edit', true);
	}

	// Now check if edit.own is available.
	elseif(!empty($userId) && $user->authorise('core.edit.own', $asset)) {
	  // Check for a valid user and that they are the owner.
	  if($userId == $data->created_by) {
	    $data->params->set('access-edit', true);
	  }
	}
      }

      // Get the tags
      $data->tags = new JHelperTags;
      $data->tags->getItemTags('com_snipf.person', $data->id);

      $this->_item[$pk] = $data;
    }

    return $this->_item[$pk];
  }


  public function getCertificates($pk = 0)
  {
    $pk = (!empty($pk)) ? $pk : (int) $this->getState('person.id');
    $now = JFactory::getDate()->toSql();

    $db = JFactory::getDbo();
    $query = $db->getQuery(true);

    $query->select($this->getState('list.select', 'c.number, c.end_date, ip.commission_date AS initial_commission_date,'.
	                                          'lp.commission_date AS current_commission_date, s.name AS speciality,'.
						  'c.complement_1, c.complement_2'))
	  ->from('#__snipf_certificate AS c')
	  ->join('INNER', '#__snipf_process AS lp ON lp.item_id=c.id AND lp.item_type="certificate" AND lp.is_last=1')
	  ->join('INNER', '#__snipf_process AS ip ON ip.item_id=c.id AND ip.item_type="certificate" AND ip.number=1')
	  ->join('LEFT', '#__snipf_speciality AS s ON s.id=c.speciality_id')
	  ->where('c.person_id='.$pk)
	  ->where('c.published=1 AND c.closure_reason="" AND c.end_date > '.$db->Quote($now));
    $db->setQuery($query);

    return $db->loadObjectList();
  }


  /**
   * Increment the hit counter for the person.
   *
   * @param   integer  $pk  Optional primary key of the person to increment.
   *
   * @return  boolean  True if successful; false otherwise and internal error set.
   */
  public function hit($pk = 0)
  {
    $input = JFactory::getApplication()->input;
    $hitcount = $input->getInt('hitcount', 1);

    if($hitcount) {
      $pk = (!empty($pk)) ? $pk : (int) $this->getState('person.id');

      $table = JTable::getInstance('Person', 'SnipfTable');
      $table->load($pk);
      $table->hit($pk);
    }

    return true;
  }
}

