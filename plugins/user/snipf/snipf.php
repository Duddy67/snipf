<?php
/**
 * @package SNIPF
 * @copyright Copyright (c)2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */


// No direct access
defined('_JEXEC') or die;
require_once(JPATH_ROOT.'/administrator/components/com_snipf/tables/person.php');



class plgUserSnipf extends JPlugin
{
  /**
   * Application object
   *
   * @var    JApplicationCms
   * @since  3.2
   */
  protected $app;


  /**
   * Constructor
   *
   * @access      protected
   * @param       object  $subject The object to observe
   * @param       array   $config  An array that holds the plugin configuration
   * @since       1.5
   */
  public function __construct(& $subject, $config)
  {
    parent::__construct($subject, $config);
    JFormHelper::addFieldPath(__DIR__ . '/fields');
    $this->loadLanguage();
  }


  /**
   * @param	string	$context	The context for the data
   * @param	int		$data		The user id
   * @param	object
   *
   * @return	boolean
   * @since	1.6
   */
  function onContentPrepareData($context, $data)
  {
    return true;
  }


  /**
   * @param	JForm	$form	The form to be altered.
   * @param	array	$data	The associated data for the form.
   *
   * @return	boolean
   * @since	1.6
   */
  function onContentPrepareForm($form, $data)
  {
    return true;
  }


  /** 
   * Method is called before user data is stored in the database
   *
   * @param   array    $user   Holds the old user data.
   * @param   boolean  $isNew  True if a new user is stored.
   * @param   array    $data   Holds the new user data.
   *
   * @return  boolean
   *
   * @since   3.1
   * @throws  InvalidArgumentException on invalid date.
   */
  public function onUserBeforeSave($user, $isNew, $data)
  {   
    //A new user is about to be created from the com_users Joomla component and not from the
    //Snipf component.
    if($isNew && !isset($data['initial_registration'])) {
      $table = JTable::getInstance('Person', 'SnipfTable');

      // Verify that the email doesn't already exist in the person table.
      if($table->load(array('email' => $data['email']))) {
	JError::raiseError(500, JText::_('PLG_SNIPF_DATABASE_ERROR_PERSON_UNIQUE_EMAIL'));
	return false;
      }
    }

    return true;
  }


  /**
   * Saves user profile data
   *
   * @param   array    $data    entered user data
   * @param   boolean  $isNew   true if this is a new user
   * @param   boolean  $result  true if saving the user worked
   * @param   string   $error   error message
   *
   * @return  boolean
   */
  function onUserAfterSave($data, $isNew, $result, $error)
  {
    if(!$isNew && $result) {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      //Gets the email value of the corresponding person (if exists).
      $query->select('email')
	    ->from('#__snipf_person')
	    ->where('user_id='.(int)$data['id']);
      $db->setQuery($query);
      $email = $db->loadResult();

      if($email !== null) {
	//Updates some user's variables with the person's email value.
	$fields = array('email='.$db->Quote($email), 'username='.$db->Quote($email));
	$query->clear();
	$query->update('#__users')
	      ->set($fields)
	      ->where('id='.(int)$data['id']);
	$db->setQuery($query);
	$db->execute();
      }
    }
  }


  /**
   * Method is called before user data is deleted from the database.
   *
   * @param	array		$user		Holds the user data
   *
   * @return  void
   */
  public function onUserBeforeDelete($user)
  {   
  }


  /**
   * Remove all user profile information for the given user ID
   *
   * Method is called after user data is deleted from the database
   *
   * @param	array		$user		Holds the user data
   * @param	boolean		$success	True if user was succesfully stored in the database
   * @param	string		$msg		Message
   */
  function onUserAfterDelete($user, $success, $msg)
  {
  }
}

