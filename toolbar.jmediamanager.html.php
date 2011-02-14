<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

class TOOLBAR_jmm
{
	function _DEFAULT(&$client)
	{
		JToolBarHelper::title( JText::_( 'JMediaManager' ), 'tb-main' );

		JToolBarHelper::makeDefault('make_default');
		
		JToolBarHelper::preferences( 'com_jmediamanager','400');
	}
	
}