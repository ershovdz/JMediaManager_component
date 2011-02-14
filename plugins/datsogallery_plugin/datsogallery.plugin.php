<?php
defined( '_JEXEC' ) or die( 'Restricted access' );

//error_reporting(0);
require_once( JPATH_COMPONENT_SITE . DS . 'plugin.class.php' );

class DatsoGallery_Plugin extends CJmmPlugin 
{
	public function DatsoGallery_Plugin()
	{
	}
	
	public function galleryExist()
	{
		if(file_exists(JPATH_ROOT . DS . 'components' . DS . 'com_datsogallery' . DS . 'datsogallery.php') 
				&& file_exists(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_datsogallery'. DS . 'admin.datsogallery.php'))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function getCategoryTree(/*out*/& $categoryTree)
	{
		$db = &JFactory::getDBO();
		$query = "SELECT * FROM #__datsogallery_catg";
		$db->setQuery( $query );
		$rows = $db->loadObjectList();
		if ($db->getErrorNum()) 
		{
			return false;
		}
		
		$k = 0;
		for ($i = 0, $n = count($rows); $i < $n; $i++) 
		{
			$row = & $rows[$i];
			$categoryTree[] = array(   
									'id'            =>$row->cid
									,'owner'     	=>''
									,'parent_id' 	=>$row->parent
									,'small_name'   =>''
									,'middle_name'  =>''
									,'big_name'   	=>''
									,'caption'  	=>$row->name
									,'small_path'   =>''
									,'middle_path'  =>''
									,'big_path'  	=>''
									);
		}
		return 999;
	}
	
	public function getImagesTree(/*in*/ $catID, /*in*/ $owner, $phpsessid, /*out*/& $photoTree)
	{
		require (JPATH_BASE .DS . 'administrator/components/com_datsogallery/config.datsogallery.php');
		
		$plugin		= JRequest::getVar('plugin', "", 'REQUEST');
		$cache = & JFactory::getCache('com_jmediamanager');
		$owner_id = $cache->call('CJmmPlugin::getIdByName', $owner);
		
		$db = &JFactory::getDBO();
		if($catID != 0)
		{
			$query = "SELECT * FROM #__datsogallery WHERE `owner_id`=" . $db->Quote($owner_id) . " AND `catid`=" . $db->Quote($catID);
		}
		else
		{
			$query = "SELECT * FROM #__datsogallery WHERE `owner_id`=". $db->Quote($owner_id);
		}
		
		$db->setQuery( $query );
		$rows = $db->loadObjectList();
		if ($db->getErrorNum()) 
		{
			return false;
		}
		
		$k = 0;
		for ($i = 0, $n = count($rows); $i < $n; $i++) 
		{
			$row = & $rows[$i];
			
			$size = @filesize(JPATH_BASE . $ad_pathoriginals . DS . $row->imgoriginalname);
			
			$small_url = "index2.php?option=com_jmediamanager&no_html=1&login={$owner}"
						. "&phpsessid={$phpsessid}&controller=server&func=getImage&width={$ad_thumbwidth}"
						. "&height={$ad_thumbheight}&crop=1&cropratio={$ad_cropratio}&image={$row->imgoriginalname}&catid={$row->catid}";
			
			$middle_url = "index2.php?option=com_jmediamanager&no_html=1&login={$owner}"
						. "&phpsessid={$phpsessid}&controller=server&func=getImage&width={$ad_maxwidth}"
						. "&height={$ad_maxheight}&crop=0&cropratio=0&image={$row->imgoriginalname}&catid={$row->catid}";
			
			$big_url = "index2.php?option=com_jmediamanager&no_html=1&login={$owner}"
						. "&phpsessid={$phpsessid}&controller=server&func=getImage&width={$ad_orgwidth}"
						. "&height={$ad_orgheight}&crop=0&cropratio=0&image={$row->imgoriginalname}&catid={$row->catid}";
			
			
			$ext = "";
			if(strtolower(substr($row->imgtitle, -3)) != "jpg"
				&& strtolower(substr($row->imgtitle, -4)) != "jpeg"
				&& strtolower(substr($row->imgtitle, -3)) != "png"
				&& strtolower(substr($row->imgtitle, -3)) != "gif")
			{
				$ext = ".jpg";
			}
			
			$photoTree[] = array(   
									'id'         	=> $row->id
									,'imgtitle'     => $row->imgtext . $ext
									,'imgauthor'    => $row->imgauthor
									,'imgdate'    	=> $row->imgdate
									,'description'  => $row->imgtext
									,'published'    => $row->published
									,'owner'     	=> $owner
									,'parent_id' 	=> $row->catid
									,'small_name'   => ''
									,'middle_name'  => ''
									,'big_name'   	=> ''
									,'caption'      => $row->imgtitle . $ext
									,'small_path'   => JURI::base() .  $small_url
									,'middle_path'  => JURI::base() .  $middle_url
									,'big_path'  	=> JURI::base() .  $big_url
									,'size'  		=> $size
									,'tags' 		=> ''
								);
		}
		return true;
	}
	
	public function getProperties(/*out*/ & $properties, /*in*/$login = '')
	{
		require_once(JPATH_BASE .DS."administrator/components/com_datsogallery/config.datsogallery.php");
		
		$sizes_array = array (	
								//array("w" => $ad_thumbwidth, "h" => $ad_thumbheight)
								//,array("w" => $ad_maxwidth  , "h" => $ad_maxheight)
								array("w" => $ad_orgwidth  , "h" => $ad_orgheight)
							);
		
		$properties[] = array(
		'imgtitle'    =>1
		,'size'       =>1 
		,'lastmod'    =>1
		,'id'         =>1
		,'imgauthor'  =>1
		,'published'  =>1
		,'catid'      =>1
		,'imgtext'    =>1
		,'tags'	      =>0
		,'owner'      =>0
		,'nestedCat'  =>1
		,'sizes'      =>$sizes_array
		,'minSize'    =>150
		,'saveOrig'   =>!$ad_orgresize
		);
	}
	
	public function edit(/*in*/ $to_modify, /*out*/ $output)
	{
		jimport('joomla.filesystem.file');
		require_once(JPATH_BASE .DS."administrator/components/com_datsogallery/config.datsogallery.php");
		
		$db = &JFactory::getDBO();
		$login		= JRequest::getVar('login', null, 'REQUEST');
		
		$cache = & JFactory::getCache('com_jmediamanager');
		$isAdmin = $cache->call('CJmmPlugin::isAdminLogin', $login);
		$owner_id = $cache->call('CJmmPlugin::getIdByName', $login);
		
		switch($to_modify->type)
		{
		case 'move':
			{
				$nodes = explode(",", $to_modify->param2);
				foreach($nodes as $node_id)
				{
					if($to_modify->param1 == 'cat')
					{
						$youcanmove = true;
						
						//Check: Do you have privilege to move category
						if(!$isAdmin)
						{
							//$db->setQuery(" select * from #__datsogallery_catg where cid='".(int)$node_id."' ");
							//$result = $db->query();
							//$num_rows = mysql_num_rows($result);
							//if($num_rows == 1)
							//{
								$youcanmove = false;
							//}
							//else
							//{
							//	$youcanmove = false;
							//}
						}
						
						if($youcanmove)
						{
							$db->setQuery( "update #__datsogallery_catg set parent='".(int)$to_modify->param3."' where cid='".(int)$node_id."' ");
							$result = $db->query();
							if($result)
							{
								continue;
							}
							else
							{
								return DB_ERROR;
							}
						}
						else
						{
							return CATEGORY_ACCESS_DINIED;
						}
					}
					
					if($to_modify->param1 == 'foto')
					{
						$youcanmove = true;
						
						//Check: Do you have privilege to move foto
						if(!$isAdmin)
						{
							$db->setQuery("SELECT * FROM #__datsogallery WHERE owner_id='".$owner_id."' AND id='".(int)$node_id."' ");
							$result = $db->query();
							$num_rows = mysql_num_rows($result);
							if($num_rows == 1)
							{
								$youcanmove = true;
							}
							else
							{
								$youcanmove = false;
							}
						}
						if($youcanmove)
						{
							$db->setQuery( "update #__datsogallery set catid='".(int)$to_modify->param3."' where id='".(int)$node_id."' ");
							$result = $db->query();
							if($result)
							{
								continue;
							}
							else
							{
								return DB_ERROR;
							}
						}
						else
						{
							return PHOTO_ACCESS_DINIED;
						}
					}
				}
				return NO_ERROR;
			}
		case 'createcat':
			{
				$youcancreate = true;
				
				if($to_modify->param1)
				{
					//Check: Do you have privilege to move foto
					if($login != '')
					{
						if(!$isAdmin && (int)$to_modify->param1)
						{
							$db->setQuery("SELECT * FROM #__datsogallery_catg WHERE parent='".(int)$to_modify->param1."' ");
							$result = $db->query();
							$num_rows = mysql_num_rows($result);
							if($num_rows == 1)
							{
								$youcancreate = true;
							}
							else
							{
								$youcancreate = false;
							}
						}
					}
					else
					{
						$youcancreate = false;
					}
				}
				if($youcancreate)
				{
					$db->setQuery("INSERT INTO #__datsogallery_catg (cid,name,parent,description,ordering,access,published) VALUES (NULL,".$db->Quote($to_modify->param2).",'".(int)$to_modify->param1."',".$db->Quote($to_modify->param3).",".$db->Quote($to_modify->param4).",".$db->Quote($to_modify->param5).",".$db->Quote($to_modify->param6).")");
					$result = $db->query();
					if($result)
					{
						$output = mysql_insert_id();
						return NO_ERROR;
					}
					else
					{
						return DB_ERROR;
					}
				}
				else
				{
					return CATEGORY_ACCESS_DINIED;
				}
				break;
			}
		case 'deletefoto':
			{
				$youcandelete = true;
				$rows = "";
				$errors = NO_ERROR;
				
				
				$nodes = explode(",", $to_modify->param1);
				
				foreach($nodes as $node_id)
				{
					//Check: Do you have privilege to move foto
					if($login != '')
					{
						if(!$isAdmin)
						{
							$db->setQuery("SELECT * FROM #__datsogallery WHERE owner_id='".$owner_id."' AND id='".$node_id."' LIMIT 1");
							$rows = $db->loadObjectList();
							$num_rows = count($rows);
							if($num_rows == 1)
							{
								$youcandelete = true;
							}
							else
							{
								$youcandelete = false;
							}
						}
						else
						{
							$db->setQuery("SELECT * FROM #__datsogallery WHERE id='".$node_id."' LIMIT 1");
							$rows = $db->loadObjectList();
						}
					}
					else
					{
						$youcandelete = false;
					}
					
					if($youcandelete)
					{
						if(!count($rows))
						{
							continue;
						}
						$row = & $rows[0];
						$db->setQuery("DELETE FROM #__datsogallery WHERE id='".(int)$node_id."' ");
						$result = $db->query();
						if($result)
						{
							// Remove the original photos as well
							$original	= JPATH_BASE . $ad_pathoriginals . DS . $row->imgoriginalname;
							//$middle		= JPATH_BASE . $ad_pathimages 	 . DS . $row->imgoriginalname;
							//$small		= JPATH_BASE . $ad_paththumbs 	 . DS . $row->imgoriginalname;
							if( !empty($original) )
							{
								@JFile::delete( $original );
							}
							//if( !empty($middle) )
							//{
							//	@JFile::delete( $middle );
							//}
							//if( !empty($small) )
							//{
							//	@JFile::delete( $small );
							//}
							continue;
						}
						else
						{
							return DB_ERROR;
						}
					}
					else
					{
						return PHOTO_ACCESS_DINIED;
					}
				}
				break;
			}
		case 'deletecat':
			{
				$youcandelete = true;
				
				//Check: Do you have privilege to move foto
				if($login != '')
				{
					if(!$isAdmin)
					{
						$youcandelete = false;
					}
				}
				else
				{
					$youcandelete = false;
				}
				if($youcandelete)
				{
					$db->setQuery("DELETE FROM #__datsogallery_catg WHERE cid='".(int)$to_modify->param1."' ");
					$result = $db->query();
					if($result)
					{
						return NO_ERROR;
					}
					else
					{
						return DB_ERROR;
					}
				}
				else
				{
					return CATEGORY_ACCESS_DINIED;
				}
				
				break;
			}
		case 'editfoto':
			{
				//$to_modify->param3 = iconv('windows-1251', 'UTF-8', $to_modify->param3);
				$youcanedit = true;
				
				
				//Check: Do you have privilege to move foto
				if($login != '')
				{
					if(!$isAdmin)
					{
						$db->setQuery("SELECT id FROM #__datsogallery WHERE owner_id='".$owner_id."' and id='".(int)$to_modify->param1."' LIMIT 1");
						$result = $db->loadResult();
						
						if($result)
						{
							$youcanedit = true;
						}
						else
						{
							$youcanedit = false;
						}
					}
				}
				else
				{
					$youcanedit = false;
				}
				if($youcanedit)
				{
					switch($to_modify->param2)
					{
					case 'imgtitle':
						{
							
							$db->setQuery( "UPDATE #__datsogallery SET imgtitle=".$db->Quote($to_modify->param3)
											." WHERE id='".(int)$to_modify->param1."' ");
							$result = $db->query();
							break;
						}
					case 'imgauthor':
						{
							
							$db->setQuery( "UPDATE #__datsogallery SET imgauthor=".$db->Quote($to_modify->param3)
											." WHERE id='".(int)$to_modify->param1."' ");
							$result = $db->query();
							break;
						}
					case 'imgtext':
						{
							$db->setQuery( "UPDATE #__datsogallery SET imgtext=".$db->Quote($to_modify->param3)
											." WHERE id='".(int)$to_modify->param1."' ");
							$result = $db->query();
							break;
						}
					case 'published':
						{
							$db->setQuery( "UPDATE #__datsogallery SET published='".(int)$to_modify->param3
											."' WHERE id='".(int)$to_modify->param1."' ");
							$result = $db->query();
							break;
						}
					}
				}
				else
				{
					return PHOTO_ACCESS_DINIED;
				}
				break;
			}
		case 'renamecat':
			{
				$youcanedit = true;
				
				//Check: Do you have privilege to rename category
				if($login != '')
				{
					if(!$isAdmin)
					{
						$youcanedit = true;
					}
				}
				else
				{
					$youcanedit = false;
				}
				if($youcanedit)
				{
					
					$db->setQuery( "UPDATE #__datsogallery_catg SET name=".$db->Quote($to_modify->param2)." WHERE cid='".(int)$to_modify->param1."' ");
					$result = $db->query();
				}
				else
				{
					return CATEGORY_ACCESS_DINIED;
				}
				break;
			}
		}
		return NO_ERROR;
	}
	
	public function removeFile($srcFilename, $srcFilePath) 
	{
		$removeFilename = $srcFilePath . '/' . $srcFilename;
		if (unlink($removeFilename))
		{
			return true;
		} 
		else
		{
			return false;
		}
	}
	public function dgImgId($catid,$imgext)
	{
		return substr(strtoupper(md5(uniqid(time()))), 5, 12).'-'.$catid.'.'.strtolower($imgext);
	}
	
	public function base64_to_jpeg($encoded_string, $outputfile ) 
	{ 
		$ifp = fopen( $outputfile, "wb" ); 
		fwrite( $ifp, base64_decode( $encoded_string ) ); 
		fclose( $ifp ); 
		  /* return output filename */ 
		return( $outputfile ); 
	}

	public function upload()
	{	
		$owner = JRequest::getVar('login', null, 'REQUEST');
		$db = &JFactory::getDBO();
		
		$cache = & JFactory::getCache('com_jmediamanager');
		$isAdmin = $cache->call('CJmmPlugin::isAdminLogin', $owner);
		$owner_id = $cache->call('CJmmPlugin::getIdByName', $owner);
		
		jimport('joomla.filesystem.file');
		
		ini_set('memory_limit', '32M');
		
		require_once(JPATH_BASE .DS."administrator/components/com_datsogallery/config.datsogallery.php");
		
		if(/*!isset($_POST['w'.$ad_thumbwidth.'h'.$ad_thumbheight])
				|| !isset($_POST['w'.$ad_maxwidth.'h'.$ad_maxheight])*/
				!isset($_POST['w'.$ad_orgwidth.'h'.$ad_orgheight]))
		{
			$this->xmlErrorUploadResponse();
			exit();
		}
		
		//$small_image	= $_POST['w'.$ad_thumbwidth.'h'.$ad_thumbheight];//imagecreatefromstring(base64_decode($_POST['w'.$ad_thumbwidth.'h'.$ad_thumbheight]));
		//$middle_image 	= $_POST['w'.$ad_maxwidth.'h'.$ad_maxheight];//imagecreatefromstring(base64_decode($_POST['w'.$ad_maxwidth.'h'.$ad_maxheight]));
		$big_image 		= $_POST['w'.$ad_orgwidth.'h'.$ad_orgheight];//imagecreatefromstring(base64_decode($_POST['w'.$ad_orgwidth.'h'.$ad_orgheight]));
		
		$imgname = "";
		$file_name = JRequest::getVar('file', null, 'POST');
		$catid = JRequest::getVar('catId', null, 'POST');
		
		$photoid =JRequest::getVar('photoid', null, 'POST');
		$imgDescription = JRequest::getVar('imgDescription', null, 'POST', 'string', JREQUEST_ALLOWRAW);
		$imgAuthor = JRequest::getVar('imgAuthor', null, 'POST');
		$imgPublished = JRequest::getVar('imgPublished', null, 'POST');
		
		$imgname = ($imgname != "")? $imgname : $this->dgImgId($catid,'jpg');
		$newfilename = "";
		$photo_from_db = "";
		
		$origfilename = "";
		$imagetype = array( 1 => 'GIF', 2 => 'JPG', 3 => 'PNG');
		$imginfo = array();
		
		$useruploaded = $isAdmin ? 0 : 1;
		
		if($photoid == "")
		{
			$newfilename = $imgname;
			
			//if($small_image != false && $middle_image != false && $big_image != false)
			//{
				$batchtime = mktime();
				
				$query="SELECT ORDERING FROM #__datsogallery WHERE catid=$catid ORDER BY ORDERING DESC LIMIT 1";
				$db->setQuery( $query );
				
				$res = $db->loadResult();
				$ordering = $res+1;
				$title = $file_name;
				
				//$title = iconv('UTF-8', 'windows-1251', $title);
				$query = "INSERT INTO #__datsogallery(id,catid,imgtitle,imgauthor,imgtext," . 
						 "imgdate,imgcounter,ordering,imgvotes,imgvotesum,published,imgoriginalname," . 
						 "checked_out,owner_id,approved,useruploaded) VALUES" .
						 " (NULL,'$catid'," . $db->Quote($title) . ",". $db->Quote($imgAuthor) . 
						 "," . $db->Quote($imgDescription) . ",'$batchtime','0','$ordering','0','0'," . 
						 $db->Quote($imgPublished) . ", '$newfilename','0','$owner_id',1," . $useruploaded  . ")";
				$db->setQuery( $query );	
				if (!$db->query()) 
				{
					$this->xmlErrorUploadResponse();
					exit();
				}
				else
				{
					$cid = $db->insertid();
				}
				
				//if(!$ad_orgresize) // Save originals
				//{
					if(/*!$this->base64_to_jpeg($small_image, JPATH_BASE . $ad_paththumbs."/$newfilename")//!imagejpeg($small_image, JPATH_BASE . $ad_paththumbs."/$newfilename", $ad_thumbquality)
							|| !$this->base64_to_jpeg($middle_image, JPATH_BASE . $ad_pathimages."/$newfilename")*///!imagejpeg($middle_image, JPATH_BASE . $ad_pathimages."/$newfilename", $ad_thumbquality)
							!$this->base64_to_jpeg($big_image, JPATH_BASE . $ad_pathoriginals."/$newfilename"))//!imagejpeg($big_image, JPATH_BASE . $ad_pathoriginals."/$newfilename", 91))
					{
						$db->setQuery("DELETE FROM #__datsogallery WHERE id='".(int)$cid."' ");
						$db->query();
						
						$this->xmlErrorUploadResponse();
						exit();
					}
				//}
				//else
				//{
				//	if(!imagejpeg($small_image, JPATH_BASE . $ad_paththumbs."/$newfilename", $ad_thumbquality)
				//			|| !imagejpeg($middle_image, JPATH_BASE . $ad_pathimages."/$newfilename", $ad_thumbquality)
				//			|| !imagejpeg($big_image, JPATH_BASE . $ad_pathoriginals."/$newfilename", $ad_thumbquality))
				//	{
				//		$db->setQuery("DELETE FROM #__datsogallery WHERE id='".(int)$cid."' ");
				//		$db->query();
						
				//		$this->xmlErrorUploadResponse();
				//		exit();
				//	}
				//}
				
				$this->xmlSuccessUploadResponse($cid);
			//}
			//else
			//{
			//	$this->xmlErrorUploadResponse();
			//}
		}
		else
		{
			$query="SELECT * FROM #__datsogallery WHERE id=$photoid LIMIT 1";
			$db->setQuery( $query );
			$rows = $db->loadObjectList();
			if(!count($rows))
			{
				$this->xmlErrorUploadResponse();
				exit();
			}
			$photo_from_db = & $rows[0];
			$newfilename = $photo_from_db->imgoriginalname;
			
			
			// Remove the original photos as well
			$original	= JPATH_BASE . $ad_pathoriginals . DS . $photo_from_db->imgoriginalname;
			//$midle		= JPATH_BASE . $ad_pathimages 	 . DS . $photo_from_db->imgoriginalname;
			//$small		= JPATH_BASE . $ad_paththumbs 	 . DS . $photo_from_db->imgoriginalname;
			
			
			if( !empty($original) )
			{
				@JFile::delete( $original );
			}
			//if( !empty($midle) )
			//{
			//	@JFile::delete( $midle );
			//}
			//if( !empty($small) )
			//{
			//	@JFile::delete( $small );
			//}
			
			if(/*!$this->base64_to_jpeg($small_image, JPATH_BASE . $ad_paththumbs."/$newfilename")//!imagejpeg($small_image, JPATH_BASE . $ad_paththumbs."/$newfilename", $ad_thumbquality)
							|| !$this->base64_to_jpeg($middle_image, JPATH_BASE . $ad_pathimages."/$newfilename")//!imagejpeg($middle_image, JPATH_BASE . $ad_pathimages."/$newfilename", $ad_thumbquality)
							|| */!$this->base64_to_jpeg($big_image, JPATH_BASE . $ad_pathoriginals."/$newfilename"))//!imagejpeg($big_image, JPATH_BASE . $ad_pathoriginals."/$newfilename", 91))
			//{
			 //if(!imagejpeg($small_image, JPATH_BASE . $ad_paththumbs."/$newfilename")
			//		 || !imagejpeg($middle_image, JPATH_BASE . $ad_pathimages."/$newfilename")
			//		 || !imagejpeg($big_image, JPATH_BASE . $ad_pathoriginals."/$newfilename"))
			 {
				 $this->xmlErrorUploadResponse();
				 exit();
			 }
			
			$this->xmlSuccessUploadResponse($photoid);
		}
	}
	public function getImage($image, $width, $height, $crop, $cropratio, $catid)
	{
		require_once(JPATH_BASE .DS."administrator/components/com_datsogallery/config.datsogallery.php");
		require_once(JPATH_BASE .DS."administrator/components/com_datsogallery/images.datsogallery.php");
		
		$wm = $width < $ad_maxwidth ? 0 : $ad_showwatermark;
		
		resize($image, $width, $height, $crop, $cropratio, $wm, $catid);
		$cacheimage = getCacheFile($image, $width, $height, $catid, $cropratio);
		
		header('Last-Modified: '.date('r'));
		header('Accept-Ranges: bytes');
		header('Content-Length: '.(filesize($cacheimage)));
		header('Content-Type: image/jpeg');
		ob_clean();
		readfile($cacheimage);
		exit;
	}
	
	public function mupload($imgTitle, & $imgDescription, $imgAuthor, $catid, $state, $owner, $uploaderID)
	{
		$tmp_file = JPATH_BASE .DS . "components" . DS . "com_jmediamanager" . DS . "tmp" . DS . $uploaderID . ".jpg";

		if($state == "uploading")
		{
			if(file_exists($tmp_file))
			{
				$fp = fopen($tmp_file,"ab");
				
				$file_size = filesize($_FILES["upload_field"]["tmp_name"]);
				if(!$file_size)
				{
					echo "0";
					exit();
				}
				
				$handle = fopen($_FILES["upload_field"]["tmp_name"], 'rb') or die("error opening file");

				$content = fread($handle, $file_size) or die("error reading file");
				
				fwrite($fp, $content);
				fclose($fp);
				fclose($handle);
			}
			else
			{
				if(!copy($_FILES["upload_field"]["tmp_name"],  $tmp_file))
				{
					echo "0";
					exit();
				} 
			}
			
			echo $uploaderID;
			exit;
		}
		else if($state == "uploaded")
		{
			$this->storeUploadedImage($tmp_file, $imgTitle, & $imgDescription, $imgAuthor, $catid, $state, $owner);
		}
		else if($state == "clean")
		{
			jimport('joomla.filesystem.file');
			
			if( !empty($tmp_file) )
			{
				@JFile::delete( $tmp_file );
			}
		}
	}
	private function storeUploadedImage($tmp_file, $imgTitle, & $imgDescription, $imgAuthor, $catid, $state, $owner, $id = "")
	{
		require_once(JPATH_BASE .DS."administrator/components/com_datsogallery/config.datsogallery.php");
		jimport('joomla.filesystem.file');
		
		$db = &JFactory::getDBO();
		
		$cache = & JFactory::getCache('com_jmediamanager');
		$isAdmin = $cache->call('CJmmPlugin::isAdminLogin', $owner);
		$owner_id = $cache->call('CJmmPlugin::getIdByName', $owner);
		
		if($id == "0")
		{
			$newfilename = $this->dgImgId($catid,'jpg');
			
			$batchtime = mktime();
			
			$query="SELECT ORDERING FROM #__datsogallery WHERE catid=$catid ORDER BY ORDERING DESC LIMIT 1";
			$db->setQuery( $query );
			
			$res = $db->loadResult();
			$ordering = $res+1;
			
			$query = "INSERT INTO #__datsogallery(id,catid,imgtitle,imgauthor,imgtext,imgdate,"
			. "imgcounter,ordering,imgvotes,imgvotesum,published,imgoriginalname,checked_out,owner_id,approved,useruploaded) "
			. " VALUES (NULL,'$catid'," . $db->Quote($imgTitle) . ",". $db->Quote($imgAuthor) . "," . $db->Quote($imgDescription) . 
			",'$batchtime','0','$ordering','0','0','1','$newfilename','0','$owner_id','1','" . !$isAdmin  . "')";
			$db->setQuery( $query );	
			if (!$db->query()) 
			{
				return "0";
			}
			else
			{
				$cid = $db->insertid();
			}
			
			if( !copy($tmp_file, JPATH_BASE . $ad_pathoriginals."/$newfilename"))
			{
				$db->setQuery("DELETE FROM #__datsogallery WHERE id='".(int)$cid."' ");
				$db->query();
				@unlink( $tmp_file );
				
				return "0";
			}
			
			@unlink( $tmp_file );
			
			//$this->xmlSuccessUploadResponse($cid);
			return $cid;
		}
		else
		{
			$query="SELECT * FROM #__datsogallery WHERE id=$id LIMIT 1";
			$db->setQuery( $query );
			$rows = $db->loadObjectList();
			if(!count($rows))
			{
				return "0";
			}
			
			$photo_from_db = & $rows[0];
			$newfilename = $photo_from_db->imgoriginalname;
			
			
			// Remove the original photos as well
			$original	= JPATH_BASE . $ad_pathoriginals . DS . $photo_from_db->imgoriginalname;
			
			if( !empty($original) )
			{
				@JFile::delete( $original );
			}
			
			if( !copy($tmp_file, JPATH_BASE . $ad_pathoriginals."/$newfilename"))
			{
				return "0";
			}
			
			return $id;
		}
	}
	
	public function file_upload($title, $id, $catid, /*out*/ &$output)
	{
		$owner = JRequest::getVar('login', null, 'REQUEST');
		$output = "0";
		$file_size = filesize($_FILES["upload_field"]["tmp_name"]);
		$desc = "";
		
		if(!$file_size)
		{
			return;
		}
		
		//if(count($category) && (int)$category['catid'] && (int)$category['secid']) //upload to category
		//{
		$output = $this->storeUploadedImage($_FILES["upload_field"]["tmp_name"], $title, &$desc, "", $catid, "1", $owner, $id);
		//}
		
		return;
	}
}