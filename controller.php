<?php

/**
* @package		JMediaManager
* @subpackage	Component
*/
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.controller');

class CJmmController extends JController
{
	function __construct( $config = array() )
	{	
		parent::__construct( $config );
		return true;
	}
}