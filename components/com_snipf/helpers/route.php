<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * SNIPF Component Route Helper
 *
 * @static
 * @package     Joomla.Site
 * @subpackage  com_snipf
 * @since       1.5
 */
abstract class SnipfHelperRoute
{
  /**
   * Get the person route.
   *
   * @param   integer  $id        The route of the person item.
   * @param   integer  $catid     The category ID.
   * @param   integer  $language  The language code.
   *
   * @return  string  The article route.
   *
   * @since   1.5
   */
  public static function getPersonRoute($id, $catid = 0, $language = 0)
  {
    // Create the link
    $link = 'index.php?option=com_snipf&view=person&id='.$id;

    if((int) $catid > 1) {
      $link .= '&catid='.$catid;
    }

    if($language && $language !== '*' && JLanguageMultilang::isEnabled()) {
      $link .= '&lang='.$language;
    }

    return $link;
  }


  /**
   * Get the category route.
   *
   * @param   integer  $catid     The category ID.
   * @param   integer  $language  The language code.
   *
   * @return  string  The person route.
   *
   * @since   1.5
   */
  public static function getCategoryRoute($catid, $language = 0)
  {
    if($catid instanceof JCategoryNode) {
      $id = $catid->id;
    }
    else {
      $id = (int) $catid;
    }

    if($id < 1) {
      $link = '';
    }
    else {
      $link = 'index.php?option=com_snipf&view=category&id='.$id;

      if($language && $language !== '*' && JLanguageMultilang::isEnabled()) {
	$link .= '&lang='.$language;
      }
    }

    return $link;
  }


  /**
   * Get the form route.
   *
   * @param   integer  $id  The form ID.
   *
   * @return  string  The person route.
   *
   * @since   1.5
   */
  public static function getFormRoute($id)
  {
    return 'index.php?option=com_snipf&task=person.edit&n_id='.(int)$id;
  }
}
