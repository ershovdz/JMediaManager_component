<?php
// no direct access
defined('_JEXEC') or die('Restricted access');

require_once( JPATH_COMPONENT.DS.'helper'.DS.'exception.php' );

// Require the base controller
require_once( JPATH_COMPONENT.DS.'controller.php' );

$toolbar = '<link rel="stylesheet" href="' . JURI::base() . 'components/com_jmediamanager/css/toolbar.css" type="text/css" />';
$mainframe->addCustomHeadTag($toolbar);

// Require specific controller if requested
if($controller = JRequest::getWord('controller', 'component')) 
{
	$path = JPATH_COMPONENT.DS.'controllers'.DS.$controller.'.php';
	if (file_exists($path)) 
	{
		require_once $path;
	} 
	else 
	{
		throw new CJmmException("Could not find controller file");
	}
}

// Set the helper directory
JHTML::addIncludePath( JPATH_COMPONENT.DS.'helper' );

// Create the controller
$classname	= 'CJmmController'.ucfirst($controller);
$controller	= new $classname( );

JResponse::setHeader( 'Expires', 'Mon, 26 Jul 1997 05:00:00 GMT', true );

// Perform the Request task
$controller->execute( JRequest::getCmd( 'task' ) );
$controller->redirect();