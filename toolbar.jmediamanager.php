<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

require_once( JApplicationHelper::getPath( 'toolbar_html' ) );

$client	=& JApplicationHelper::getClientInfo(JRequest::getVar('client', '0', '', 'int'));

switch ($task)
{
	default:
	{
		TOOLBAR_jmm::_DEFAULT($client);
		break;
	}
}