<?php
// ensure this file is being included by a parent file
if ( !defined( '_JEXEC' ) ) { die( 'Direct Access to this location is not allowed.' ); }

class jmmAdminFileSystem 
{
	var $functions			=	array();
	/**
	 * Constructor
	 *
	 * @param  array  $functions   array of functions
	 * @return jmmAdminFileSystem
	 */
	function jmmAdminFileSystem( &$functions ) 
	{
		if ( isset( $functions['_constructor'] ) ) 
		{
			call_user_func_array( $functions['_constructor'], array( &$functions ) );
		}
		$this->functions					=&	$functions;
	}
	
	function & getInstance( $purePHP = false )
	{
		static $singleInstance				=	array( false => null, true => null );
		static $emptyArray					=	array();
		if ( ( ! isset( $singleInstance[$purePHP] ) ) || ( $singleInstance[$purePHP] === null ) ) 
		{
			if ( $purePHP === true )
			{
				$singleInstance[$purePHP]	=	new jmmAdminFileSystem( $emptyArray );
			}
			elseif ( is_array( $purePHP ) ) 
			{
				$singleInstance[$purePHP]	=	new jmmAdminFileSystem( $purePHP );
			}
			else 
			{
				global $jmm_AdminFileFunctions;
	
				$singleInstance[$purePHP]	=	new jmmAdminFileSystem( $jmm_AdminFileFunctions );
			}
		}
		return $singleInstance[$purePHP];
	}
	function isUsingStandardPHP( )
	{
		return ( count( $this->functions ) == 0 );
	}
	/**
	 * DIRECTORY METHODS:
	 */
	/**
	 * creates a directory
	 *
	 * @param  string   $pathname    The directory path
	 * @param  int      $mode        Default is 0777 like PHP function's default relying on Umask
	 * @param  boolean  $recursive   PHP 5.0.0+
	 * @param  resource $context     PHP 5.0.0+: see streams
	 * @return boolean               Returns TRUE on success or FALSE on failure
	 */
	function mkdir( $pathname, $mode = 0777, $recursive = null, $context = null ) 
	{
		if ( isset( $this->functions['mkdir'] ) )
		{
			return call_user_func_array( $this->functions['mkdir'], array( $pathname, $mode, $recursive, $context ) );
		} 
		else 
		{
			if ( version_compare( phpversion(), '5.0.0', '>=' ) ) 
			{
				return ( is_null( $context ) ? mkdir( $pathname, $mode, $recursive ) : mkdir( $pathname, $mode, $recursive, $context ) );
			} 
			else 
			{
				if ( $recursive ) 
				{
					$parts					=	explode( '/', $pathname );
					$n						=	count( $parts );
					if ( $n < 1 ) 
					{
						if ( substr( $base, -1, 1 ) == '/' ) 
						{
							$base =	substr( $base, 0, -1 );
						}
						return mkdir( $base, $mode );
					}
					else 
					{
						$path =	$base;
						for ( $i = 0; $i < $n; $i++ ) 
						{
							$path			.=	$parts[$i] . '/';
							if ( ! file_exists( $path ) ) 
							{
								if ( ! mkdir( substr( $path, 0, -1 ), $mode ) ) 
								{
									return false;
								}
							}
						}
						return true;
					}
				} 
				else
				{
					return mkdir( $pathname, $mode );
				}
			}
		}
	}
	function rmdir( $dirname, $context = null ) 
	{
		if ( isset( $this->functions['rmdir'] ) ) 
		{
			return call_user_func_array( $this->functions['rmdir'], array( $dirname, $context ) );
		} 
		else
		{
			return ( is_null( $context ) ? rmdir( $dirname ) : rmdir( $dirname, $context ) );
		}
	}
	function is_dir( $filename ) 
	{
		if ( isset( $this->functions['is_dir'] ) ) 
		{
			return call_user_func_array( $this->functions['is_dir'], array( $filename ) );
		}
		else 
		{
			return is_dir( $filename );
		}
	}
	/**
	 * DIRECTORY LISTING METHODS:
	 */	
	function opendir( $path, $context = null ) 
	{
		if ( isset( $this->functions['opendir'] ) ) 
		{
			return call_user_func_array( $this->functions['opendir'], array( $path, $context ) );
		}
		else 
		{
			return ( is_null( $context ) ? opendir( $path ) : opendir( $path, $context ) );
		}
	}
	function readdir( $dir_handle )
	{
		if ( isset( $this->functions['readdir'] ) ) 
		{
			return call_user_func_array( $this->functions['readdir'], array( $dir_handle ) );
		} 
		else 
		{
			return readdir( $dir_handle );
		}
	}
	function closedir( $dir_handle ) 
	{
		if ( isset( $this->functions['closedir'] ) ) 
		{
			call_user_func_array( $this->functions['closedir'], array( $dir_handle ) );
		} 
		else 
		{
			closedir( $dir_handle );
		}
	}
	/**
	 * FILES/DIRECTORY METHODS:
	 */
	function rename( $old_name, $new_name, $context = null )
	{
		if ( isset( $this->functions['rename'] ) )
		{
			return call_user_func_array( $this->functions['rename'], array( $old_name, $new_name, $context ) );
		} 
		else 
		{
			return ( is_null( $context ) ? rename( $old_name, $new_name ) : rename( $old_name, $new_name, $context ) );
		}
	}
	function file_exists( $filename )
	{
		if ( isset( $this->functions['file_exists'] ) )
		{
			return call_user_func_array( $this->functions['file_exists'], array( $filename ) );
		}
		else 
		{
			return file_exists( $filename );
		}
	}
	/**
	 * FILES METHODS:
	 */
	function is_writable( $filename )
	{
		if ( isset( $this->functions['is_writable'] ) )
		{
			return call_user_func_array( $this->functions['is_writable'], array( $filename ) );
		} 
		else 
		{
			return is_writable( $filename );
		}
	}
	function is_file( $filename )
	{
		if ( isset( $this->functions['is_file'] ) ) 
		{
			return call_user_func_array( $this->functions['is_file'], array( $filename ) );
		} 
		else 
		{
			return is_file( $filename );
		}
	}
	function chmod( $pathname, $mode ) 
	{
		if ( isset( $this->functions['chmod'] ) ) 
		{
			return call_user_func_array( $this->functions['chmod'], array( $pathname, $mode ) );
		} 
		else
		{
			return chmod( $pathname, $mode );
		}
	}
	function copy( $source, $dest, $context = null ) 
	{
		if ( isset( $this->functions['copy'] ) ) 
		{
			return call_user_func_array( $this->functions['copy'], array( $source, $dest, $context ) );
		} 
		else
		{
			return ( is_null( $context ) ? copy( $source, $dest ) : copy( $source, $dest, $context ) );
		}
	}
	function unlink( $filename, $context = null ) 
	{
		if ( isset( $this->functions['unlink'] ) )
		{
			return call_user_func_array( $this->functions['unlink'], array( $filename, $context ) );
		} 
		else {
			return ( is_null( $context ) ? unlink( $filename ) : unlink( $filename, $context ) );
		}
	}
	function file_put_contents( $file, $data, $flags = null, $context = null ) 
	{
		if ( isset( $this->functions['file_put_contents'] ) )
		{
			return call_user_func_array( $this->functions['file_put_contents'], array( $file, $data, $flags, $context ) );
		} 
		elseif( is_callable( 'file_put_contents' ) )
		{
			return ( is_null( $context ) ? file_put_contents( $file, $data, $flags ) : file_put_contents( $file, $data, $flags, $context ) );
		} 
		else
		{
			// php 4 emulation:
			// define('FILE_APPEND', 1);
			$mode				=	( ( $flags == 1 ) || ( strtoupper( $flags ) == 'FILE_APPEND' ) ) ? 'a' : 'w';
			$f					=	@fopen( $file, $mode );
			if ( $f !== false) 
			{
				if ( is_array( $data ) ) 
				{
					$data		=	implode( '', $data );
				}
				$bytes_written	=	fwrite( $f, $data );
				fclose( $f );
				if ( ( $bytes_written === false ) && ( $mode == 'w' ) ) 
				{
					@unlink( $file );
				}
				return $bytes_written;
			}
			else
			{
				return false;
			}
		}
	}
	function move_uploaded_file( $path, $new_path ) 
	{
		if ( isset( $this->functions['move_uploaded_file'] ) ) 
		{
			if ( is_uploaded_file( $path ) ) 
			{
				return call_user_func_array( $this->functions['move_uploaded_file'], array( $path, $new_path ) );
			} 
			else
			{
				return false;
			}
		} 
		else
		{
			return move_uploaded_file( $path, $new_path );
		}
	}
	/**
	 * UTILITY METHODS:
	 */
	function deldir( $dir ) 
	{
		$current_dir		=	$this->opendir( $dir );
		if ( $current_dir !== false ) 
		{
			while ( false !== ( $entryname = $this->readdir( $current_dir ) ) ) 
			{
				if ( $entryname != '.' and $entryname != '..' ) 
				{
					if ( is_dir( $dir . DS .$entryname ) )
					{
						$this->deldir( _jmmPathName( $dir . DS . $entryname ) );
					}
					else 
					{
						$this->unlink( $dir . DS . $entryname );
					}
				}
			}
			$this->closedir( $current_dir );
		}
		return $this->rmdir( $dir );
	}
}



if (is_callable("jimport")) {
	function _jmmconstructFSJJ( &$functions ) {
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.archive');
		jimport('joomla.filesystem.path');
	}
	function _jmmrenameFileDirJJ( $old_name, $new_name ) 
	{
		if ( is_file( $old_name ) ) {
			return JFile::move( $old_name, $new_name );
		} elseif ( is_dir( $old_name ) ) {
			return JFolder::move( $old_name, $new_name );
		} else {
			return false;
		}
	}
	function _jmmchmodJJ( $pathname, $mode )
	{
		jimport( 'joomla.client.helper' );
		$FTPOptions		=	JClientHelper::getCredentials( 'ftp' );
		if ( $FTPOptions['enabled'] == 1 ) {
			jimport( 'joomla.client.ftp' );
			$ftp		=&	JFTP::getInstance($FTPOptions['host'], $FTPOptions['port'], null, $FTPOptions['user'], $FTPOptions['pass']);
	
			//Translate path to FTP account:
			$dest		=	JPath::clean(str_replace( JPATH_ROOT, $FTPOptions['root'], $pathname), '/' );
			return $ftp->chmod( $pathname, $mode );
		} else {
			return @chmod( $pathname, $mode );
		}
	}
	global $jmm_AdminFileFunctions;
	$jmm_AdminFileFunctions	=	array(	'_constructor'		=>	'_jmmconstructFSJJ',
										'mkdir'				=>	array( 'JFolder', 'create' ),
										'rmdir'				=>	array( 'JFolder', 'delete' ),
										'is_dir'			=>	null,
										'opendir'			=>	null,
										'readdir'			=>	null,
										'closedir'			=>	null,
										'rename'			=>	'_jmmrenameFileDirJJ',
										'file_exists'		=>	null,
										'is_writable'		=>	null,
										'is_file'			=>	null,
										'chmod'				=>	'_jmmchmodJJ',
										'copy'				=>	array( 'JFile', 'copy' ),
										'unlink'			=>	array( 'JFile', 'delete' ),
										'file_put_contents'	=>	array( 'JFile', 'write' ),
										'move_uploaded_file'=>	array( 'JFile', 'upload' ),
									 );
}