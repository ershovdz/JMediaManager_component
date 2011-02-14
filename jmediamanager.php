<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

require_once( JPATH_COMPONENT_ADMINISTRATOR.DS.'helper'.DS.'exception.php' );


// Require the base controller
require_once( JPATH_COMPONENT.DS.'controller.php' );

// Require specific controller if requested
$controllerType = JRequest::getCmd( 'controller', 'browser', 'REQUEST');

$path = JPATH_COMPONENT.DS.'controllers'.DS.$controllerType.'.php';

if (file_exists($path)) 
{
	require_once $path;
	
	switch($controllerType)
	{
		case "server":
		{
			$classname	= 'CServerJmmController';
			break;
		}
		case "browser":
		{
			$classname	= 'CBrowserJmmController';
			break;
		}
	}
	// Create the controller
	
	$controller	= new $classname( );

	JResponse::setHeader( 'Expires', 'Mon, 26 Jul 1997 05:00:00 GMT', true );

	$controller->execute( JRequest::getCmd( 'func', null, 'REQUEST') );
	$controller->redirect();
}
else
{
	throw new CJmmException('Could not find the server file');
}