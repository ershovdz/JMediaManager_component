<?php
// Check for request forgeries
defined( '_JEXEC' ) or die( 'Restricted access' );

//require_once( JPATH_COMPONENT . DS . 'helper' . DS . 'installer.php' );
require_once (JPATH_COMPONENT . DS . 'admin.jmediamanager.html.php');

class CJmmControllerComponent extends CJmmController
{
	/**
	* Custom Constructor
	*/
	
	var $plg_instance;
	
	function __construct( $default = array())
	{
		$default['default_task'] = 'show';
		parent::__construct( $default );
		$this->plg_instance = null;
	}
	
	//Show installed plugins
	function show() 
	{
		global $option;
		
		$db		= & JFactory::getDBO();
		
		$query = "SELECT * FROM #__jmm_plugin";
		$db->setQuery( $query );
		$rows = $db->loadObjectList();
		if ($db->getErrorNum()) 
		{
			throw new CJmmException('Could not connect to the database');
		}
		
		CJmmView::showPlugins($rows, $option);
		return true;
	}
	
	function remove()
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );
		global $mainframe;
		
		jmmimport( 'jmm.adminfilesystem' );
		
		try
		{
			// Initialize some variables
			$db			= & JFactory::getDBO();
			$cid		= JRequest::getVar('cid', array(), 'method', 'array');
			$cid		= array(JFilterInput::clean(@$cid[0], 'cmd'));
			$option		= JRequest::getCmd('option');
			
			$query = 'SELECT name FROM #__jmm_plugin' .
			' WHERE id = '.$db->Quote($cid[0]);
			$db->setQuery($query);
			$rows = $db->loadObjectList();
			
			if ($db->getErrorNum()) throw new CJmmException($db->stderr());
			if(!count($rows)) throw new CJmmException(JText::_('Plugin not found'));
			
			$query = 'DELETE FROM #__jmm_plugin' .
			' WHERE id = '.$db->Quote($cid[0]);
			$db->setQuery($query);
			$db->query();		
			
			if ($db->getErrorNum()) throw new CJmmException($db->stderr());
			
			$plg_folder	= JPATH_COMPONENT_SITE . DS . 'plugins' . DS . strtolower($rows[0]->name);

			if (!is_dir( $plg_folder)) 
			{
				return JError::raiseWarning( 500, JText::_('Plugin not found') );
			}
			else
			{
				$fs		=&	jmmAdminFileSystem::getInstance();
				$fs->deldir( $plg_folder );	
			}
		}
		catch(CJmmException $e)
		{
			return JError::raiseWarning( 500, $e->getMessage() );
		}
		
		$mainframe->redirect('index.php?option='.$option);
	}
	
	function createPluginInstance($id)
	{
		$db	   = & JFactory::getDBO();
		$query = "SELECT * FROM #__jmm_plugin WHERE `id`=" . $db->Quote($id) . " LIMIT 1";
		$db->setQuery($query);
		
		$actPlg = $db->loadObjectList();
		if ($db->getErrorNum()) 
		{
			return false;
		}
		
		$pluginFile = strtolower(str_replace("_", ".", str_replace(" ", ".", $actPlg[0]->name))) . ".php";
		
		if(!file_exists(JPATH_COMPONENT_SITE . DS . 'plugins' . DS . strtolower($actPlg[0]->name) . DS . $pluginFile))
		{
			throw new CJmmException('Could not find plugin file');
		}
		
		require_once( JPATH_COMPONENT_SITE . DS . 'plugins' . DS . strtolower($actPlg[0]->name) . DS . $pluginFile );
		
		$plgClass = $actPlg[0]->name;
		
		$this->plg_instance = new $plgClass();
	}
	
	function make_default()
	{
		global $mainframe;

		// Initialize some variables
		$db		= & JFactory::getDBO();
		$cid	= JRequest::getVar('cid', array(), 'method', 'array');
		$cid	= array(JFilterInput::clean(@$cid[0], 'cmd'));
		$option	= JRequest::getCmd('option');
		
		$this->createPluginInstance($cid[0]);
		
		if(!$this->plg_instance->galleryExist())
		{
			return JError::raiseWarning( 500, 'The gallery is not installed' );
		}
		
		$query = 'UPDATE #__jmm_plugin SET status=1' .
		' WHERE id = '.$db->Quote($cid[0]);
		$db->setQuery($query);
		$db->query();
		
		$query = 'UPDATE #__jmm_plugin SET status=0' .
		' WHERE id <> '.$db->Quote($cid[0]);
		$db->setQuery($query);
		$db->query();
		
		$mainframe->redirect('index.php?option='.$option);
	}
}