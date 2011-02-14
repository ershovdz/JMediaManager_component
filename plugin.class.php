<?php
defined( '_JEXEC' ) or die( 'Restricted access' );

define('NO_ERROR',				    0);
define('CATEGORY_ACCESS_DINIED', 	100);
define('CATEGORY_IS_NOT_EMPTY', 	110);
define('PHOTO_ACCESS_DINIED', 		200);
define('DB_ERROR', 					300);
define('SESSION_EXPIRED', 			400);
define('UPLOAD_LIMIT_REACHED',		500);

class sEditNode
{
	var $param1;
	var $param2;
	var $param3;
	var $param4;
	var $param5;
	var $param6;
};

interface IJmmPlugin
{
	public function getCategoryTree(/*out*/& $categoryTree);
	
	public function getImagesTree(/*in*/ $catID, /*in*/ $owner, /*in*/$phpsessid, /*out*/& $photoTree);
	
	public function edit(/*in*/ $to_modify, /*out*/ $output);
	
	public function galleryExist();
	
	public function getProperties(/*out*/ & $properties, /*in*/$login = '');
	
	public function getImage($image, $width, $height, $crop, $cropratio, $catid);
	
	public function upload();
	
	public function mupload($imgTitle, & $imgDescription, $imgAuthor, $catid, $state, $owner, $uploaderID);
	
	public function file_upload($title, $id, $catid, &$output);
}

class CJmmPlugin implements IJmmPlugin
{
	public function getCategoryTree(/*out*/& $categoryTree){}
	
	public function getImagesTree(/*in*/ $catID, /*in*/ $owner, /*in*/$phpsessid, /*out*/& $photoTree){}
	
	public function edit(/*in*/ $to_modify, /*out*/ $output){}
	
	public function galleryExist(){}
	
	public function getProperties(/*out*/ & $properties, /*in*/$login = '') {}
	
	public function file_upload($title, $id, $catid, &$output) {}
	
	public function xmlHeader()
	{
		header('Content-Type: text/xml');
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	}
	
	public function xmlErrorUploadResponse()
	{
		$this->xmlHeader();
		echo "<response>\n";
		echo "8880\n";
		echo "</response>\n";
	}
	public function xmlSuccessUploadResponse(/*$url,*/ $id)
	{
		echo "<response>\n";
		//echo "<url>" .$url . "</url>\n";
		echo "<photoid>" . $id . "</photoid>\n";
		echo "</response>\n";
	}
	
	public function getImage($image, $width, $height, $crop, $cropratio, $catid) {}
	
	public static function isAdminId($id)
	{
		try
		{
			$db = &JFactory::getDBO();
			$query = "SELECT usertype FROM #__users WHERE id=" . $db->Quote($id);
			$db->setQuery( $query );
			$type = $db->loadResult();
			if($type == "Super Administrator")
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		catch(CJmmException $e)
		{
			return false;
		}
	}
	public static function isManagerLogin($login)
	{
		try
		{
			$db = &JFactory::getDBO();
			$query = "SELECT usertype FROM #__users WHERE username=" . $db->Quote($login);
			$db->setQuery( $query );
			$type = $db->loadResult();
			if($type == "Super Administrator"
				|| $type == "Manager")
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		catch(CJmmException $e)
		{
			return false;
		}
	}
	public static function isAdminLogin($login)
	{
		try
		{
			$db = &JFactory::getDBO();
			$query = "SELECT usertype FROM #__users WHERE username=" . $db->Quote($login);
			$db->setQuery( $query );
			$type = $db->loadResult();
			if($type == "Super Administrator")
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		catch(CJmmException $e)
		{
			return false;
		}
	}
	public static function getIdByName($login)
	{
		try
		{
			$db = &JFactory::getDBO();
			$query = "SELECT id FROM #__users WHERE username=" . $db->Quote($login) . " LIMIT 1";
			$db->setQuery( $query );
			
			return $db->loadResult();
		}
		catch(CJmmException $e)
		{
			return 0;
		}
	}
	
	public static function getNameById($user_id)
	{
		try
		{
			$db = &JFactory::getDBO();
			$query = "SELECT name FROM #__users WHERE id=" . $db->Quote($user_id) . " LIMIT 1";
			$db->setQuery( $query );
			
			return $db->loadResult();
		}
		catch(CJmmException $e)
		{
			return '';
		}
	}
	
	public function upload() {}
	
	public function mupload($imgTitle, & $imgDescription, $imgAuthor, $catid, $state, $owner, $uploaderID) {}
}
?>