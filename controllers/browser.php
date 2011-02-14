<?php
// Require specific controller if requested
$path = JPATH_COMPONENT.DS.'view'.DS.'browser.php';


class CBrowserJmmController extends CJmmController
{
	function __construct( $default = array())
	{
		$default['default_task'] = 'display';
		return parent::__construct( $default );
	}
	
	public function display()
	{
		$user = &JFactory::getUser();
		
		if(!$user->get('id'))
		{
			$msg = JText::_( 'You are not authorised to view this resource.');
			$link = JRoute::_('index.php?option=com_user&view=login', false);
			$this->setRedirect($link, $msg);
			return;
		}
		
		$document =& JFactory::getDocument();
		
		$viewName	= "browser";
		$viewType	= $document->getType();
		
		// Set the default view name from the Request
		$view = &$this->getView($viewName, $viewType);
		
		
		
		// Display the view
		$view->assign('error', $this->getError());
		
		//$view->display();
		
		$option = JRequest::getCmd('option');
		$cache =& JFactory::getCache($option, 'view');
		$cache->get($view, 'display');
	}
}
