<?php
// ensure this file is being included by a parent file
if ( ! ( defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

/**
* Installer class
* @package JMediaManager
* @subpackage Installer
* @abstract
*/
require_once( JPATH_COMPONENT . DS . 'helper' . DS . 'functions.php' );

class CJmmInstaller 
{
	// name of the XML file with installation information
	var $i_installfilename	= "";
	var $i_installarchive	= "";
	var $i_installdir		= "";
	var $i_iswin			= false;
	var $i_errno			= 0;
	var $i_error			= "";
	var $i_installtype		= "";
	var $i_unpackdir		= "";
	var $i_docleanup		= true;

	/** @var string The directory where the element is to be installed */
	var $i_elementdir = '';
	/** @var string The name of the Mambo element */
	var $i_elementname = '';
	/** @var string The name of a special atttibute in a tag */
	var $i_elementspecial = '';
	/** @var object A DOMIT XML document */
	var $i_xmldocument		= null;

	var $i_hasinstallfile = null;
	var $i_installfile = null;

	/**
	* Constructor
	*/
	function CJmmInstaller() 
	{
		jmmimport( 'jmm.adminfilesystem' );
		$this->i_iswin = (substr(PHP_OS, 0, 3) == 'WIN');
	}
	/**
	* Uploads and unpacks a file
	* @param string The uploaded package filename or install directory
	* @param boolean True if the file is an archive file
	* @return boolean True on success, False on error
	*/
	function upload($p_filename = null, $p_unpack = true, $p_findinstallfile = true ) 
	{
		$this->i_iswin = (substr(PHP_OS, 0, 3) == 'WIN');
		$this->installArchive( $p_filename );

		if ($p_unpack)
		{
			if ($this->extractArchive())
			{
				if ( $p_findinstallfile ) 
				{
					return $this->findInstallFile();
				} 
				else 
				{
					return true;
				}
			}
			else
			{
				return false;
			}
		} 
		else
		{
			return false;
		}
	}
	/**
	* Extracts the package archive file
	* @return boolean True on success, False on error
	*/
	function extractArchive()
	{
		$base_Dir			=	JPATH_ADMINISTRATOR.DS.'components/com_jmediamanager/tmp/';
		$archivename		=	$this->installArchive();
		$tmpdir				=	uniqid( 'install_' );

		$extractdir			=	 $base_Dir . $tmpdir;
		
		$this->unpackDir( $extractdir );

		if ( preg_match( "/\\.zip\$/i", $archivename ) ) 
		{
			// Extract functions
			jmmimport( 'pcl.pclziplib' );
			$zipfile		=	new PclZip( $archivename );
			if($this->isWindows()) 
			{
				define('OS_WINDOWS',1);
			}
			else 
			{
				define('OS_WINDOWS',0);
			}

			$ret			=	$zipfile->extract( PCLZIP_OPT_PATH, $extractdir );
			if($ret == 0) 
			{
				$this->setError( 1, 'Unrecoverable error "'.$zipfile->errorName(true).'"' );
				return false;
			}
		}
		else 
		{
			jmmimport( 'pcl.tar' );	// includes/Archive/Tar.php' );
			$archive		=&	new Archive_Tar( $archivename );
			$archive->setErrorHandling( PEAR_ERROR_PRINT );

			if ( ! $archive->extractModify( $extractdir, '' ) ) 
			{
				$this->setError( 1, 'Extract Error' );
				return false;
			}
		}

		$this->installDir( $extractdir );

		// Try to find the correct install dir. in case that the package have subdirs
		// Save the install dir for later cleanup
		$filesindir			=	$this->readDirectory( $this->installDir(), '' );

		if ( count( $filesindir ) == 1 ) 
		{
			if ( is_dir( $extractdir . $filesindir[0] ) ) 
			{
				$this->installDir(  $extractdir . $filesindir[0] );
			}
		}
		return true;
	}
	
	function readDirectory( $path, $filter='.', $recurse=false, $fullpath=false  ) 
	{
		$arr						=	array();
		if ( ! @is_dir( $path ) ) 
		{
			return $arr;
		}
		$handle						=	opendir( $path );

		while ( true == ( $file = readdir( $handle ) ) )
		{
			$dir					=	 $path.'/'.$file;
			$isDir					=	is_dir( $dir );
			if ( ( $file != "." ) && ( $file != ".." ) )
			{
				if ( preg_match( "/$filter/", $file ) )
				{
					if ( $fullpath ) {
						$arr[]		=	trim(  $path . '/' . $file );
					} else {
						$arr[]		=	trim( $file );
					}
				}
				if ( $recurse && $isDir ) 
				{
					$arr2			=	$this->readDirectory( $dir, $filter, $recurse, $fullpath );
					foreach ( $arr2 as $k => $n ) 
					{
						$arr2[$k]	=	$file . '/' . $n;
					}
					$arr			=	array_merge( $arr, $arr2 );
				}
			}
		}
		closedir( $handle );
		asort( $arr );
		return $arr;
	}

	
	/**
	* Tries to find the package XML file
	* @return boolean True on success, False on error
	*/
	function findInstallFile() 
	{
		$found = false;
		// Search the install dir for an xml file
		$dir = $this->installDir();
		
		$files = $this->readDirectory($dir , '.xml$', true, false );

		if (count( $files ) > 0) 
		{
			foreach ($files as $file) 
			{
				$packagefile	=&	$this->isPackageFile( $dir . '/' . $file );
				if (!is_null( $packagefile ) && !$found ) 
				{
					$this->i_xmldocument =& $packagefile;
					return true;
				}
			}
			$this->setError( 1, 'ERROR: Could not find a XML setup file in the package.' );
			return false;
		} 
		else 
		{
			$this->setError( 1, 'ERROR: Could not find a XML setup file in the package.' );
			return false;
		}
	}
	/**
	* @param string A file path
	* @return object A DOMIT XML document, or null if the file failed to parse
	*/
	function & isPackageFile( $p_file ) 
	{
		$null		=	null;
		if ( ! file_exists( $p_file ) ) 
		{
			return $null;
		}
		jmmimport('jmm.xml.simplexml');
		$xmlString	=	trim( file_get_contents( $p_file ) );

		$element	=&	new CJmmSimpleXMLElement( $xmlString );
		
		if ( $element->name() != 'jmminstall' )
		{
			return $null;
		}
		// Set the type
		$this->installType( $element->attributes( 'type' ) );
		$this->installFilename( $p_file );
		return $element;
	}
	/**
	* Loads and parses the XML setup file
	* @return boolean True on success, False on error
	*/
	function readInstallFile()
	{
		if ($this->installFilename() == "") 
		{
			$this->setError( 1, 'No filename specified' );
			return false;
		}

		jmmimport('jmm.xml.simplexml');

		if ( file_exists( $this->installFilename() ) )
		{
			$xmlString = trim( file_get_contents( $this->installFilename() ) );

			$this->i_xmldocument	=&	new CJmmSimpleXMLElement( $xmlString );
			if ( count( $this->i_xmldocument->children() ) == 0 ) 
			{
				return false;
			}
		}
		$main_element	=&	$this->i_xmldocument;

		// Check that it's am installation file
		if ($main_element->name() != 'jmminstall')
		{
			$this->setError( 1, 'File :"' . $this->installFilename() . '" is not a valid Joomla installation file' );
			return false;
		}
		$this->installType( $main_element->attributes( 'type' ) );
		return true;
	}
	/**
	* Abstract install method
	*/
	function install()
	{
		die( 'Method "install" cannot be called by class ' . strtolower(get_class( $this )) );
	}
	/**
	* Abstract uninstall method
	*/
	function uninstall() 
	{
		die( 'Method "uninstall" cannot be called by class ' . strtolower(get_class( $this )) );
	}
	/**
	* return to method
	*/
	function returnTo( $option, $task ) 
	{
		return "index2.php?option=$option&task=$task";
	}
	/**
	* @param string Install from directory
	* @param string The install type
	* @return boolean
	*/
	function preInstallCheck( $p_fromdir, $type='plugin' )
	{

		if (!is_null($p_fromdir)) 
		{
			$this->installDir($p_fromdir);
		}

		if (!$this->installfile())
		{
			$this->findInstallFile();
		}

		if (!$this->readInstallFile())
		{
			$this->setError( 1, 'Installation file not found:<br />' . $this->installDir() );
			return false;
		}
		
		if (trim($this->installType()) != trim($type)) 
		{
			$this->setError( 1, 'XML setup file is not for a "'.$type.'".' );
			return false;
		}
		

		// In case there where an error doring reading or extracting the archive
		if ($this->errno())
		{
			return false;
		}

		return true;
	}
	/**
	* @param string The tag name to parse
	* @param string An attribute to search for in a filename element
	* @param string The value of the 'special' element if found
	* @param boolean True for Administrator components
	* @return mixed Number of file or False on error
	*/
	function parseFiles( $tagName='files', $special='', $specialError='', $adminFiles=0 ) 
	{
		
		// Find files to copy
		$jmmInstallXML	=&	$this->i_xmldocument;

		$files_element	=&	$jmmInstallXML->getElementByPath( $tagName );
		if ( ! ( $files_element ))
		{
			return 0;
		}
	
		$copyfiles = array();

		foreach ( $files_element->filename as $file ) 
		{
			$copyfiles[]		=	$file->data();
		}
	
		$result					=	$this->copyFiles( $this->installDir(), $this->elementDir(), $copyfiles );

		return $result;
	}

	/**
	* @param string Source directory
	* @param string Destination directory
	* @param array array with filenames
	* @param boolean True is existing files can be replaced
	* @return boolean True on success, False on error
	*/
	function copyFiles( $p_sourcedir, $p_destdir, $p_files, $overwrite=false )
	{
		if (is_array( $p_files ) && count( $p_files ) > 0)
		{
			$adminFS			=&	jmmAdminFileSystem::getInstance();
			
			foreach($p_files as $_file) 
			{
				$filesource		=	_jmmPathName( _jmmPathName( $p_sourcedir ) . $_file, false ); 
				$filedest		=	_jmmPathName( _jmmPathName( $p_destdir ) . $_file, false );

				if ( ! file_exists( $filesource ) ) 
				{
					$this->setError( 1, "File $filesource does not exist!" );
					return false;
				} 
				else if ( file_exists( $filedest ) && ! $overwrite )
				{
					$this->setError( 1, "There is already a file called $filedest - Are you trying to install the same Plugin twice?" );
					return false;
				}
				else if ( ! $adminFS->copy( $filesource, $filedest ) ) 
				{
					$this->setError( 1, "Failed to copy file: $filesource to $filedest" );
					return false;
				} 
			}
		}
		else 
		{
			return false;
		}
		return count( $p_files );
	}
	/**
	* Copies the XML setup file to the element Admin directory
	* Used by Plugin Installer
	* @return boolean True on success, False on error
	*/
	function copySetupFile( $where='admin' )
	{
		if ($where == 'admin')
		{
			return $this->copyFiles( $this->installDir(), $this->componentAdminDir(), array( basename( $this->installFilename() ) ), true );
		}
		else if ($where == 'front') 
		{
			return $this->copyFiles( $this->installDir(), $this->elementDir(), array( basename( $this->installFilename() ) ), true );
		}
		return false;
	}

	/**
	* @param int The error number
	* @param string The error message
	*/
	function setError( $p_errno, $p_error ) 
	{
		$this->errno( $p_errno );
		$this->error( $p_error );
	}
	/**
	* @param boolean True to display both number and message
	* @param string The error message
	* @return string
	*/
	function getError($p_full = false) 
	{
		if ($p_full) 
		{
			return $this->errno() . " " . $this->error();
		} else {
			return $this->error();
		}
	}
	/**
	* @param string The name of the property to set/get
	* @param mixed The value of the property to set
	* @return The value of the property
	*/
	function setVar( $name, $value=null ) 
	{
		if (!is_null( $value )) 
		{
			$this->$name = $value;
		}
		return $this->$name;
	}

	function installFilename( $p_filename = null ) 
	{
		if(!is_null($p_filename)) 
		{
			if($this->isWindows()) 
			{
				$this->i_installfilename = str_replace('/','\\',$p_filename);
			} 
			else 
			{
				$this->i_installfilename = str_replace('\\','/',$p_filename);
			}
		}
		return $this->i_installfilename;
	}

	function installType( $p_installtype = null ) 
	{
		return $this->setVar( 'i_installtype', $p_installtype );
	}

	function error( $p_error = null ) {
		return $this->setVar( 'i_error', $p_error );
	}

	function installArchive( $p_filename = null ) 
	{
		return $this->setVar( 'i_installarchive', $p_filename );
	}

	function installDir( $p_dirname = null ) 
	{
		return $this->setVar( 'i_installdir', $p_dirname );
	}

	function unpackDir( $p_dirname = null ) 
	{
		return $this->setVar( 'i_unpackdir', $p_dirname );
	}

	function isWindows()
	{
		return $this->i_iswin;
	}

	function errno( $p_errno = null ) 
	{
		return $this->setVar( 'i_errno', $p_errno );
	}

	function hasInstallfile( $p_hasinstallfile = null ) 
	{
		return $this->setVar( 'i_hasinstallfile', $p_hasinstallfile );
	}

	function installfile( $p_installfile = null ) 
	{
		return $this->setVar( 'i_installfile', $p_installfile );
	}

	function elementDir( $p_dirname = null )	
	{
		return $this->setVar( 'i_elementdir', $p_dirname );
	}

	function elementName( $p_name = null )	
	{
		return $this->setVar( 'i_elementname', $p_name );
	}
	function elementSpecial( $p_name = null )	
	{
		return $this->setVar( 'i_elementspecial', $p_name );
	}
	/**
	* Warning: needs jmmAdminFileSystem  File-system loaded to use
	* 
	* @param  string  $base  An existing base path
	* @param  string  $path  A path to create from the base path
	* @param  int     $mode  Directory permissions
	* @return boolean         True if successful
	*/
	function mosMakePath( $base, $path='', $mode = null ) 
	{
		// convert windows paths
		$path =	preg_replace( "/(\\/){2,}|(\\\\){1,}/",'/', $path );

		// check if dir exists
		if ( file_exists( $base . $path ) )
		{
			return true;
		}

		// set mode
		$origmask				=	null;
		if ( isset( $mode ) )
		{
			$origmask =	@umask(0);
		} 
		else
		{
			$mode = 0755;		// 0777;
		}

		$ret = true;
		if ( $path == '' )
		{
			while ( substr( $base, -1, 1 ) == '/' ) 
			{
				$base =	substr( $base, 0, -1 );
			}
			$adminFS = &jmmAdminFileSystem::getInstance();
			$ret = $adminFS->mkdir( $base, $mode );
		} 
		else 
		{
			$parts	= explode( '/', $path );
			$n	    = count( $parts );
			$path	= $base;
			
			for ( $i = 0 ; $i < $n ; $i++ ) 
			{
				$path			.=	$parts[$i];
				if ( ! file_exists( $path ) )
				{
					$adminFS	=&	jmmAdminFileSystem::getInstance();
					if ( ! $adminFS->mkdir( $path, $mode ) )
					{
						$ret	=	false;
						break;
					}
				}
				$path			.=	'/';
			}
		}
		if ( isset( $origmask ) ) 
		{
			@umask( $origmask );
		}
		return $ret;
	}

}	// end class CJmmInstaller

function cleanupInstall( $userfile_name, $resultdir) 
{
	if ( file_exists( $resultdir ) ) 
	{
		$adminFS		=&	jmmAdminFileSystem::getInstance();
		$adminFS->deldir( $resultdir );
		if ( $userfile_name )
		{
			$adminFS->unlink( _jmmPathName( $userfile_name, false ) );
		}
	}
}

class CPluginJmmInstaller extends CJmmInstaller 
{
	/** @var string The element type */
	var $elementType			=	'plugin';
	var $checkdbErrors			=	null;
	var $checkdbLogs			=	null;

	/**
	* Constructor
	*/
	function CPluginJmmInstaller() 
	{
		$this->CJmmInstaller();
	}

	/**
	* Custom install method
	* @param boolean True if installing from directory
	*/
	function install( $p_fromdir = null )
	{
		global $ueConfig, $_PLUGINS;
        
		$db		= & JFactory::getDBO();
		
		if (!$this->preInstallCheck( $p_fromdir,$this->elementType )) 
		{
			return false;
		}

		try
		{
			$jmmInstallXML			=&	$this->i_xmldocument;

			// Get name
			$e						=	&$jmmInstallXML->getElementByPath( 'name' );
			if(!$e) throw new CJmmException('XML plugin file is not valid');
			
			$this->elementName( $e->data() );
			$cleanedElementName		=	str_replace(array(" ","."),array("_","_"),$this->elementName());

			// Get plugin filename
			$files_element			=	&$jmmInstallXML->getElementByPath( 'files' );
			if(!$files_element) throw new CJmmException('XML plugin file is not valid');
			
			foreach ( $files_element->children() as $file ) 
			{
				if ($file->attributes( "plugin" )) 
				{
					$this->elementSpecial( $file->attributes( "plugin" ) );
				}
			}
			$fileNopathNoext		=	null;
			$matches			=	array();
			if ( preg_match("/^.*[\\/\\\\](.*)\\..*$/", $this->installFilename(), $matches ) ) 
			{
				$fileNopathNoext	=	$matches[1];
			}
			if ( ! ( $fileNopathNoext && ( $this->elementSpecial() == $fileNopathNoext ) ) ) 
			{
				$this->setError( 1, 'Installation filename `' . $fileNopathNoext . '` (with .xml) does not match main php file plugin attribute `'  . $this->elementSpecial() . '` in the plugin xml file<br />' );
				return false;
			}
			$cleanedMainFileName	=	str_replace(array(" ","."),array("_","_"),$this->elementSpecial());
			
			$v						=	&$jmmInstallXML->getElementByPath( 'version' );
			if(!$v) throw new CJmmException('XML plugin file is not valid');
			
			$version				=	$v->data();
			
			$a						=	&$jmmInstallXML->getElementByPath( 'author' );
			if(!$a) throw new CJmmException('XML plugin file is not valid');
			
			$author					=	$a->data();
			
			$am						=	&$jmmInstallXML->getElementByPath( 'authoremail' );
			if(!$am) throw new CJmmException('XML plugin file is not valid');
			
			$authoremail			=	$am->data();
			
			$d						=&	$jmmInstallXML->getElementByPath( 'description' );
			if ( $d !== false ) 
			{
				$desc				=	$this->elementName() . '<div>' . $d->data() . '</div>';
				$this->setError( 0, $desc );
			}
		}
		catch(CJmmException $e)
		{
			$this->setError( 1, $e->getMessage());
			return false;
		}
		
		$this->elementDir( JPATH_COMPONENT_SITE . DS . 'plugins' .DS . /*$type . DS .*/ $cleanedMainFileName );	
		
		if (file_exists($this->elementDir())) 
		{
			$this->setError( 1, 'Another plugin is already using directory: "' . $this->elementDir() . '"' );
			return false;
		}

		//$parentFolder			=	preg_replace( '/\/[^\/]*\/?$/', '/', $this->elementDir() );
		
		if(!file_exists($this->elementDir()) && !$this->mosMakePath($this->elementDir()))
		{
			$this->setError( 1, 'Failed to create directory' .' "' . $this->elementDir() . '"' );
			return false;
		}

		// Copy files from package:
		if ($this->parseFiles( 'files', 'plugin', 'No file is marked as plugin file' ) === false)
		{
			cleanupInstall( null, $this->elementDir() );	// try removing directory and content just created successfully
			return false;
		}

		// Check to see if plugin already exists in db
		
		try
		{
			$db->setQuery( "SELECT id FROM #__jmm_plugin WHERE name = " . $db->Quote($cleanedElementName));
			if (!$db->query()) 
			{
				$this->setError( 1, 'SQL error' .': ' . $db->stderr( true ) );
				cleanupInstall( null, $this->elementDir() );	// try removing directory and content just created successfully
				return false;
			}

			$pluginid 				=	$db->loadResult();

			if( ! $pluginid )
			{
				$db->setQuery( 'INSERT INTO #__jmm_plugin (id, name, version, author, authoremail, status) '
							.'VALUES(' 
								. $db->Quote(null) . 
								',' . $db->Quote($cleanedElementName) . 
								',' . $db->Quote($version) . 
								',' . $db->Quote($author) . 
								',' . $db->Quote($authoremail) . 
								',' . $db->Quote(0) . 
							   ')');
							
				if (!$db->query()) 
				{
					$this->setError( 1, 'SQL error' .': ' . $db->stderr( true ) );
					cleanupInstall( null, $this->elementDir() );	// try removing directory and content just created successfully
					return false;
				}
			}
		}
		catch(CJmmException $e)
		{
			$this->setError( 1, 'Database error:' .' "' .  $db->stderr( true ) . '"' );
			return false;
		}
		return true;
	}
	
	function getXml( $id ) 
	{
		
		$db		= & JFactory::getDBO(); 
		
		$db->setQuery( "SELECT `name`, `type`, `status` FROM #__jmm_plugin WHERE `id` = " . (int) $id );
		$row			=	null;
		$db->loadObject( $row );
		
		if ( $db->getErrorNum() ) 
		{
			return $db->stderr();
		}
		
		if ( $row == null ) 
		{
			return 'Invalid object id';
		}

		$this->elementDir( JPATH_COMPONENT.DS . 'components/com_jmediamanager/plugins/' . $row->type . '/');
		
		$this->installFilename( $this->elementDir() . $row->element . '.xml' );

		if ( ! ( file_exists( $this->installFilename() ) && is_readable( $this->installFilename() ) ) )
		{
			return $row->name .' '. "has no readable xml file " . $this->i_installfilename;
		}

		jmmimport('jmm.xml.simplexml');
		return new CJmmSimpleXMLElement( trim( file_get_contents( $this->installFilename() ) ) );
	}
	/**
	 * Checks the plugin's database tables and upgrades if needed
	 * Backend-use only.
	 *
	 * Sets for $this->getErrors() $this->checkdbErrors and for $this->getLogs() $this->checkdbLogs
	 *
	 * @param  int             $pluginId
	 * @param  boolean         $upgrade    False: only check table, True: upgrades table (depending on $dryRun)
	 * @param  boolean         $dryRun     True: doesn't do the modifying queries, but lists them, False: does the job
	 * @return boolean|string              True: success: see logs, False: error, see errors, string: error
	 */
	function getErrors( ) 
	{
		return $this->checkdbErrors;
	}
	function getLogs( ) 
	{
		return $this->checkdbLogs;
	}
	/**
	 * Checks that plugin is properly installed and sets, if returned true:
	 * $this->i_elementdir   To the directory of the plugin (with final / )
	 * $this->i_xmldocument  To a CJmmSimpleXMLElement of the XML file
	 *
	 * @param  int     $id
	 * @param  string  $option
	 * @param  int     $client
	 * @param  string  $action
	 * @return boolean
	 */
	function checkPluginGetXml( $id, $option, $client = 0, $action = 'Uninstall' ) 
	{
		
		$db = & JFactory::getDBO();
		
		$db->setQuery( "SELECT `name`, `type`, `status` FROM #__jmm_plugin WHERE `id` = " . (int) $id );

		$row			=	null;
		$db->loadObject( $row );
		if ( $db->getErrorNum() ) 
		{
			PluginsView::showInstallMessage( $db->stderr(), $action . ' -  error' ,
			$this->returnTo( $option, 'show') );
			return false;
		}
		if ($row == null) 
		{
			PluginsView::showInstallMessage( 'Invalid object id', $action . ' -  error' ,
			$this->returnTo( $option, 'show') );
			return false;
		}

		$this->elementDir( JPATH_COMPONENT.DS . 'components/com_jmediamanager/plugins/' . $row->type . '/' );
		
		$this->installFilename( $this->elementDir() . $row->element . '.xml' );

		if ( ! ( file_exists( $this->i_installfilename ) && is_readable( $this->i_installfilename ) ) ) 
		{
			PluginsView::showInstallMessage( $row->name .' '. "has no readable xml file " . $this->i_installfilename . ", and might not be uninstalled completely." ,
			$action . ' -  warning', $this->returnTo( $option, 'showPlugins') );
		}

		// see if there is an xml install file, must be same name as element
		if ( file_exists( $this->i_installfilename ) && is_readable( $this->i_installfilename ) ) 
		{
			jmmimport('jmm.xml.simplexml');
			$this->i_xmldocument	=&	new CJmmSimpleXMLElement( trim( file_get_contents( $this->i_installfilename ) ) );
		} 
		else 
		{
			$this->i_xmldocument	=	null;
		}
		return true;
	}
	/**
	 * plugin uninstaller with best effort depending on what it finds.
	 *
	 * @param  int     $id
	 * @param  string  $option
	 * @param  int     $client
	 * @param  string  $action
	 * @return boolean
	 */
	function uninstall( $id, $option, $client = 0 ) 
	{
		$database = & JFactory::getDBO();
		
		if ( $this->checkPluginGetXml( $id, $option, $client ) ) 
		{
			if ( ( $this->i_xmldocument !== null ) && count( $this->i_xmldocument->children() ) > 0 ) 
			{
				$jmmInstallXML	=&	$this->i_xmldocument;
				
				// get the element name:
				$e =& $jmmInstallXML->getElementByPath( 'name' );
				$this->elementName( $e->data() );
				
				// get the files element
				$files_element =& $jmmInstallXML->getElementByPath( 'files' );
				if ( $files_element ) 
				{

					if ( count( $files_element->children() ) ) 
					{
						foreach ( $files_element->children() as $file) 
						{
							if ($file->attributes( "plugin" )) 
							{
								$this->elementSpecial( $file->attributes( "plugin" ) );
								break;
							}
						}
						$cleanedMainFileName = strtolower(str_replace(array(" ","."),array("","_"),$this->elementSpecial()));
					}

					// Is there an uninstallfile
					$uninstallfile_elemet = &$jmmInstallXML->getElementByPath( 'uninstallfile' );
					if ( $uninstallfile_elemet !== false )
					{
						if (is_file( $this->i_elementdir . $uninstallfile_elemet->data()))
						{
							global $_PLUGINS;		// needed for the require_once below !
							require_once( $this->i_elementdir . $uninstallfile_elemet->data());
							$ret = call_user_func_array("plug_".$cleanedMainFileName."_uninstall", array());

							if ($ret != '') 
							{
								$this->setError( 0, $ret );
							}
						}
					}

					$adminFS					=&	jmmAdminFileSystem::getInstance();

					foreach ( $files_element->children() as $file ) 
					{
						// delete the files
						$filename				=	$file->data();
						if ( file_exists( $this->i_elementdir . $filename ) ) 
						{
							$parts				=	pathinfo( $filename );
							$subpath			=	$parts['dirname'];
							if ( $subpath <> '' && $subpath <> '.' && $subpath <> '..' ) 
							{
								$result			=	$adminFS->deldir( _jmmPathName( $this->i_elementdir . $subpath . '/' ) );
							} 
							else 
							{
								$result			=	$adminFS->unlink( _jmmPathName( $this->i_elementdir . $filename, false ) );
							}
						}
					}
					
					// Are there any SQL queries??
					$query_element = &$jmmInstallXML->getElementByPath( 'uninstall/queries' );
					if ( $query_element !== false ) 
					{
						foreach ( $query_element->children() as $query )
						{
							$database->setQuery( trim( $query->data() ) );
							if ( ! $database->query() )
							{
								$this->setError( 1, "SQL Error " . $database->stderr( true ) );
								return false;
							}
						}
					}

					// remove XML file from front
					$xmlRemoveResult	=	$adminFS->unlink(  _jmmPathName( $this->i_installfilename, false ) );
					$filesRemoveResult	=	true;

						// delete the non-system folders if empty
						if ( count( jmmReadDirectory( $this->i_elementdir ) ) < 1 ) 
						{
							$filesRemoveResult	=	$adminFS->deldir( $this->i_elementdir );
						}

					if ( ! $xmlRemoveResult ) 
					{
						PluginsView::showInstallMessage( 'Could not delete XML file: ' . _jmmPathName( $this->i_installfilename, false ) . ' due to permission error. Please remove manually.', 'Uninstall -  warning',
						$this->returnTo( $option, 'showPlugins') );
					}
					if ( ! $filesRemoveResult ) 
					{
						PluginsView::showInstallMessage( 'Could not delete directory: ' . $this->i_elementdir . ' due to permission error. Please remove manually.', 'Uninstall -  warning',
						$this->returnTo( $option, 'showPlugins') );
					}
				}
			}

			$database->setQuery( "DELETE FROM #__jmm_plugin WHERE id = " . (int) $id );
			if (!$database->query())
			{
				$msg = $database->stderr;
				PluginsView::showInstallMessage( 'Cannot delete plugin database entry due to error: ' . $msg, 'Uninstall -  error',
				$this->returnTo( $option, 'showPlugins') );
				return false;
			}
		
			return true;
		}
		return false;
	}
}