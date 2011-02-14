<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

jimport('joomla.application.component.view');

class CBrowserJmmViewBrowser extends JView
{
	function display($tpl = null)
	{
		global $mainframe;

		
		$user		= &JFactory::getUser();
		$pathway	= &$mainframe->getPathway();
		$document	= & JFactory::getDocument();
		//$model		= &$this->getModel();

		// Get the parameters of the active menu item
		$menus	= &JSite::getMenu();
		$menu    = $menus->getActive();

		$pparams = &$mainframe->getParams('com_jmediamanager');

		

		// Set the document page title
		// because the application sets a default page title, we need to get it
		// right from the menu item itself
		if (is_object( $menu ) && isset($menu->query['view']) && $menu->query['view'] == 'browser')
		{
			$menu_params = new JParameter( $menu->params );
			if (!$menu_params->get( 'page_title')) 
			{
				//$pparams->set('page_title',	$contact->name);
			}
		} 
		else 
		{
			$pparams->set('page_title',	"JMediaManager");
		}
		$document->setTitle( $pparams->get( 'page_title' ) );

		//set breadcrumbs
		if (isset( $menu ) && isset($menu->query['view']) && $menu->query['view'] != 'browser')
		{
			$pathway->addItem("Uploader", '');
		}

		$this->assignRef('params',		$pparams);

		parent::display($tpl);
	}
}

?>