<?php

function _jmmPathName( $p_path, $p_addtrailingslash = true ) 
{
	if ( substr( PHP_OS, 0, 3 ) == 'WIN' )	
	{
		$f = '/';
		$t = '\\';
	} 
	else 
	{
		$f = '\\';
		$t = '/';
	}

	$retval	= str_replace( $f, $t, $p_path );						
	if ( $p_addtrailingslash )
	{
		if ( substr( $retval, -1 ) != $t )
		{
			$retval		.=	$t;
		}
	}
	$prepend = ( substr( $retval, 0, 2 ) == $t . $t ) ? $t : '';	// check for UNC path //
	$retval = $prepend . str_replace( $t . $t, $t, $retval );		// Remove double // while keeping UNC if needed
	return $retval;
}

function jmmimport( $lib ) 
{
	static $imported = array();
	
	if ( ! isset( $imported[$lib] ) ) 
	{
		$imported[$lib]			=	true;

		$liblow					=	strtolower( $lib );
		$pathAr					=	explode( '.', $liblow );
		
		array_pop( $pathAr );
		$filepath		=	implode( '/', $pathAr ) . (count( $pathAr ) ? '/' : '' ) . $liblow . '.php';
			
		require_once( JPATH_BASE.DS.'components/com_jmediamanager/library/' . $filepath );
	}
} 

function uploadFile( $filename, &$userfile_name, &$msg ) 
{
	jmmimport( 'jmm.adminfilesystem' );
	$adminFS			=&	jmmAdminFileSystem::getInstance();

	$baseDir			=  JPATH_ADMINISTRATOR.DS.'components/com_jmediamanager/tmp/';

	$userfile_name		=	$baseDir . $userfile_name;
	
	if ( file_exists( $baseDir ) ) 
	{
		if ( is_writable( $baseDir ) )
		{
			if ( move_uploaded_file( $filename, $userfile_name ) ) 
			{
				return true;
			} 
			else 
			{
				$msg = sprintf( 'Failed to move uploaded file to %s directory.', '<code>' . htmlspecialchars( $baseDir ) . '</code>' );
			}
		} 
		else 
		{
			$msg = sprintf( 'Upload failed as %s directory is not writable.', '<code>' . htmlspecialchars( $baseDir ) . '</code>' );
		}
	} 
	else 
	{
		$msg = sprintf( 'Upload failed as %s directory does not exist.', '<code>' . htmlspecialchars( $baseDir ) . '</code>' );
	}
	return false;
}