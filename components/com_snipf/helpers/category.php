<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2017 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

/**
 * SNIPF Component Category Tree
 *
 * @static
 * @package     Joomla.Site
 * @subpackage  com_snipf
 * @since       1.6
 */
class SnipfCategories extends JCategories
{
  public function __construct($options = array())
  {
    $options['table'] = '#__snipf_person';
    $options['extension'] = 'com_snipf';

    /* IMPORTANT: By default publish parent function invoke a field called "state" to
     *            publish/unpublish (but also archived, trashed etc...) an item.
     *            Since our field is called "published" we must informed the 
     *            JCategories publish function in setting the "statefield" index of the 
     *            options array
    */
    $options['statefield'] = 'published';

    parent::__construct($options);
  }
}
