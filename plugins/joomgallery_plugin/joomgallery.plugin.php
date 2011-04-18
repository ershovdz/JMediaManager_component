<?php
//error_reporting(0);
require_once( JPATH_COMPONENT_SITE . DS . 'plugin.class.php' );

class JoomGallery_Plugin extends CJmmPlugin 
{
	public function JoomGallery_Plugin()
	{
	}
	
	public function getConfig()
	{
		$db = &JFactory::getDBO();
		$query = "SELECT * FROM #__joomgallery_config";
		$db->setQuery( $query );
		$rows = $db->loadObjectList();
		$config = & $rows[0];
		
		return $config;
	}
	
	public function getFileName($imgId)
	{
		$db = &JFactory::getDBO();
		$query = "SELECT imgthumbname FROM #__joomgallery WHERE id=" . $db->Quote($imgId) . " LIMIT 1";
		$db->setQuery( $query );
		return $db->loadResult();
	}
	
	public function getCategoryInfo($catid)
	{
		$db = &JFactory::getDBO();
		$query = "SELECT * FROM #__joomgallery_catg WHERE cid=" . $db->Quote($catid) . " LIMIT 1";
		$db->setQuery( $query );
		$rows = $db->loadObjectList();
		return ((isset($rows[0]))? $rows[0] : null);
	}
	
	public function getPhotoInfo($imgId)
	{
		$db = &JFactory::getDBO();
		$query = "SELECT * FROM #__joomgallery WHERE id=" . $db->Quote($imgId) . " LIMIT 1";
		$db->setQuery( $query );
		$rows = $db->loadObjectList();
		return isset($rows[0]) ? $rows[0] : null;
	}
	
	public function galleryExist()
	{
		if(file_exists(JPATH_ROOT . DS . 'components' . DS . 'com_joomgallery' . DS . 'joomgallery.php' ) 
			&& file_exists(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_joomgallery' . DS . 'admin.joomgallery.php'))
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
		$query = "SELECT * FROM #__joomgallery_catg";
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
	
	public function getImagesTree(/*in*/ $catID, /*in*/ $owner, /*in*/$phpsessid, /*out*/& $photoTree)
	{
		$db = &JFactory::getDBO();
		
		$cache = & JFactory::getCache('com_jmediamanager');
		
		$owner_id = $cache->call('CJmmPlugin::getIdByName', $owner);
		$config = $cache->call('JoomGallery_Plugin::getConfig', '');
		
		
		if($catID != 0)
		{
			$query = "SELECT * FROM #__joomgallery WHERE `owner`=" . $db->Quote($owner_id) . " AND `catid`=" . $db->Quote($catID);
		}
		else
		{
			$query = "SELECT * FROM #__joomgallery WHERE `owner`=". $db->Quote($owner_id);
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
			
			$cur_cat = $cache->call('JoomGallery_Plugin::getCategoryInfo', $row->catid);
			
			$size = @filesize(JPATH_BASE . DS . $config->jg_pathoriginalimages . $cur_cat->catpath . DS . $row->imgfilename);
			
			
			$date_time_string = $row->imgdate; //'2000-05-27 02:40:21';

			// Разбиение строки в 2 части - date, time 
			$dt_elements = explode(' ',$date_time_string);

			// Разбиение даты
			$date_elements = explode('-',$dt_elements[0]);

			// Разбиение времени
			$time_elements =  explode(':',$dt_elements[1]);

			// результат
			$date =  mktime($time_elements[0], $time_elements[1],$time_elements[2], $date_elements[1],$date_elements[2], $date_elements[0]);
			
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
									,'imgtitle'     => $row->imgtitle
									,'imgauthor'    => $row->imgauthor
									,'imgdate'      => $date
									,'description'  => $row->imgtext
									,'published'    => $row->published
									,'owner'     	=> $owner
									,'parent_id' 	=> $row->catid
									,'small_name'   => $row->imgfilename
									,'middle_name'  => $row->imgfilename
									,'big_name'   	=> $row->imgfilename
									,'caption'      => $row->imgtitle . $ext
									,'small_path'   => JURI::base() .  $config->jg_paththumbs . preg_replace("/\\\\/", "/", $cur_cat->catpath) . '/'
									,'middle_path'  => JURI::base() .  $config->jg_pathimages . preg_replace("/\\\\/", "/", $cur_cat->catpath) . '/'
									,'big_path' 	=> JURI::base() .  $config->jg_pathoriginalimages . preg_replace("/\\\\/", "/", $cur_cat->catpath) . '/'
									,'size'  		=> $size
									,'tags' 		=> ''
								);
		}
		return true;
	}
	
	public function getProperties(/*out*/ & $properties, /*in*/$login = '')
	{
		$cache = & JFactory::getCache('com_jmediamanager');
		
		$config = $cache->call('JoomGallery_Plugin::getConfig', '');
		
		$sizes_array = array (	
								array("w" => $config->jg_thumbwidth, "h" => $config->jg_thumbheight)
								,array("w" => $config->jg_maxwidth , "h" => $config->jg_maxwidth)
								,array("w" => 3000  , "h" => 2000)
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
		,'owner'      =>1
		,'nestedCat'  =>1
		,'sizes'      =>$sizes_array
		,'minSize'    =>100
		);
	}
	
	public function edit(/*in*/ $to_modify, /*out*/ $output)
	{
		$db = &JFactory::getDBO();
		
		$login		= JRequest::getVar('login');
		
		$cache = & JFactory::getCache('com_jmediamanager');
		
		$owner_id = $cache->call('CJmmPlugin::getIdByName', $login);
		$isAdmin = $cache->call('CJmmPlugin::isAdminLogin', $login);
		$config = $cache->call('JoomGallery_Plugin::getConfig', '');
		
		if($owner_id == '')
		{
			return DB_ERROR;
		}
				
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
							$db->setQuery("SELECT * from #__joomgallery_catg WHERE owner=" . (int)$owner_id . " AND cid='".(int)$node_id."' LIMIT 1");
							$result = $db->query();
							$num_rows = mysql_num_rows($result);
							if($num_rows == 1)
							{
								$db->setQuery("SELECT * from #__joomgallery_catg WHERE owner=" . (int)$owner_id . " AND cid='".(int)$to_modify->param3."' LIMIT 1");
								$result = $db->query();
								$num_rows = mysql_num_rows($result);
								if($num_rows == 1)
								{
									$youcanmove = true;
								}
							}
							else
							{
								$youcanmove = false;
							}
						}
						
						if($youcanmove)
						{
							$cur_cat = JoomGallery_Plugin::getCategoryInfo((int)$node_id);
							if((int)$to_modify->param3)
							{
								$new_parent = JoomGallery_Plugin::getCategoryInfo((int)$to_modify->param3);
								if(!$cur_cat || !$new_parent)
								{
									return DB_ERROR;
								}
								$new_path = $new_parent->catpath . DS . $cur_cat->name . "_" . mktime() . "_" . $cur_cat->cid;
							}
							else
							{
								if(!$cur_cat)
								{
									return DB_ERROR;
								}
								$new_path = $cur_cat->name . "_" . mktime() . "_" . $cur_cat->cid;
							}
							
							if( !JoomGallery_Plugin::moveFolders($cur_cat->catpath, $new_path) )
							{							
								return CATEGORY_ACCESS_DINIED;
							}
							 
							if( !JoomGallery_Plugin::updateNewCatpath($cur_cat->cid, $cur_cat->catpath, $new_path) )
							{
								return DB_ERROR;
							}
							 
							$db->setQuery( "UPDATE #__joomgallery_catg SET parent=".$db->Quote($to_modify->param3)." WHERE cid=".$db->Quote($node_id));
							$result = $db->query();
							if(!$result)
							{
								return DB_ERROR;
							}	
						}
						else
						{
							return CATEGORY_ACCESS_DINIED;
						}
					}
					else if($to_modify->param1 == 'foto')
					{
						$youcanmove = true;
						
						//Check: Do you have privilege to move foto
						if(!$isAdmin)
						{
							$db->setQuery("SELECT * FROM #__joomgallery WHERE owner='".$owner_id."' and id='".(int)$node_id."' LIMIT 1");
							$result = $db->query();
							$num_rows = mysql_num_rows($result);
							if($num_rows == 1)
							{
								$db->setQuery("SELECT * from #__joomgallery_catg WHERE owner=" . (int)$owner_id . " AND cid='".(int)$to_modify->param3."' LIMIT 1");
								$result = $db->query();
								$num_rows = mysql_num_rows($result);
								if($num_rows == 1)
								{
									$youcanmove = true;
								}
							}
							else
							{
								$youcanmove = false;
							}
						}
						if($youcanmove)
						{
							$cur_image = JoomGallery_Plugin::getPhotoInfo($node_id);
							
							if( !$cur_image )
								return DB_ERROR;
								
							if(JoomGallery_Plugin::moveImage($node_id, (int)$to_modify->param3, $cur_image->catid))
							{
								$db->setQuery( "UPDATE #__joomgallery SET catid='".(int)$to_modify->param3."' WHERE id='".(int)$node_id."' ");
								$result = $db->query();
								if(!$result)
								{
									return DB_ERROR;
								}
							}
							else
							{
								return PHOTO_ACCESS_DINIED;
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
					//Check: Do you have privilege to create category
					if($login != '')
					{
						if(!$isAdmin && (int)$to_modify->param1)
						{
							$db->setQuery("SELECT * FROM #__joomgallery_catg WHERE owner=" . $owner_id . " AND cid='".(int)$to_modify->param1."' LIMIT 1");
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
					if((int)$to_modify->param1)
					{
						$parent_cat = JoomGallery_Plugin::getCategoryInfo((int)$to_modify->param1);
						
						if( !$parent_cat )
						{
							return DB_ERROR;
						}
						
						$new_cat_path = $parent_cat->catpath . "/" . mktime();
					}
					else
					{
						$new_cat_path = mktime();
					}
					
					
					
					if( !JoomGallery_Plugin::createFolders($new_cat_path) )
						return CATEGORY_ACCESS_DINIED;
					
					$db->setQuery(
									"INSERT INTO #__joomgallery_catg (cid,name,parent,description,ordering,access,published,owner,catpath) " . 
									"VALUES (NULL,".
											$db->Quote($to_modify->param2).
											",".$db->Quote($to_modify->param1).
											",".$db->Quote($to_modify->param3).
											",".$db->Quote($to_modify->param4).
											",".$db->Quote($to_modify->param5).
											",".$db->Quote($to_modify->param6).
											",".$db->Quote($owner_id).
											",".$db->Quote($new_cat_path).")"
								);
					
					$result = $db->query();
					
					if($result)
					{
						$output = $db->insertid();
						if($output)
						{
						    $alias = $db->Quote(preg_replace("/\./", "", preg_replace("#( ){1,}#", "-", $to_modify->param2)) . "-" . $cid);
							
							$query = "UPDATE #__joomgallery_catg SET alias=" . $alias . " WHERE cid=" . $db->Quote($output);
							$db->setQuery( $query );	
							
							if (!$db->query()) 
							{
								return DB_ERROR;
							}
							return NO_ERROR;
						}
						else
						{
							return DB_ERROR;
						}
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
			jimport('joomla.filesystem.file');
			
			$youcandelete = true;
			
			$nodes = explode(",", $to_modify->param1);
			
			foreach($nodes as $node_id)
			{
				//Check: Do you have privilege to move foto
				if($login != '')
				{
					if(!$isAdmin)
					{
						$db->setQuery("SELECT * FROM #__joomgallery WHERE owner='".$owner_id."' AND id='".(int)$node_id."' LIMIT 1");
						$rows = $db->loadObjectList();
						if( !$rows )
						{
							return PHOTO_ACCESS_DINIED;
						}						
					}
					else
					{
						$db->setQuery("SELECT * FROM #__joomgallery WHERE id='".$node_id."' LIMIT 1");
						$rows = $db->loadObjectList();
						if( !$rows )
						{
							return PHOTO_ACCESS_DINIED;
						}
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
					
					$db->setQuery("DELETE FROM #__joomgallery WHERE id='".(int)$node_id."' ");
					$result = $db->query();
					if(!$result)
					{
						return DB_ERROR;
					}
					else
					{
						$cat_info = JoomGallery_Plugin::getCategoryInfo($row->catid);
		
						if( !$cat_info )
						{
							return DB_ERROR;
						}
						
						$original	= JPATH_BASE . DS . $config->jg_pathoriginalimages . $cat_info->catpath . DS . $row->imgfilename;
						$middle		= JPATH_BASE . DS . $config->jg_pathimages . $cat_info->catpath . DS . $row->imgfilename;
						$small		= JPATH_BASE . DS . $config->jg_paththumbs . $cat_info->catpath . DS . $row->imgfilename;
						
						if( !empty($original) )
						{
							JFile::delete( $original );
						}
						if( !empty($middle) )
						{
							JFile::delete( $middle );
						}
						if( !empty($small) )
						{
							JFile::delete( $small );
						}
						continue;
					}
				}
				else
				{
					return PHOTO_ACCESS_DINIED;
				}
			}
			return NO_ERROR;
		}
		case 'deletecat':
			{
				$youcandelete = true;
								
				//Check: Do you have privilege to move foto
				if($login != '')
				{
					if(!$isAdmin)
					{
						$db->setQuery("SELECT * FROM #__joomgallery_catg WHERE owner='".$owner_id."' and cid='".(int)$to_modify->param1."' LIMIT 1");
						$rows = $db->loadObjectList();
						if($rows)
						{
							$db->setQuery("SELECT * FROM #__joomgallery WHERE catid='".(int)$to_modify->param1."' LIMIT 1");
							$rows = $db->loadObjectList();
							if($rows)
							{
								return CATEGORY_IS_NOT_EMPTY;
							}
							else
							{
								$db->setQuery("SELECT * FROM #__joomgallery_catg WHERE parent='".(int)$to_modify->param1."' LIMIT 1");
								$rows = $db->loadObjectList();
								if($rows)
								{
									return CATEGORY_IS_NOT_EMPTY;
								}
							}
						}
						else
						{
							return CATEGORY_ACCESS_DINIED;
						}
					}
				}
				else
				{
					return CATEGORY_ACCESS_DINIED;
				}
				if($youcandelete)
				{
					$cat_info = JoomGallery_Plugin::getCategoryInfo((int)$to_modify->param1);
					
					if( !$cat_info )
					{
						return DB_ERROR;
					}
					
					if(!JoomGallery_Plugin::deleteFolders($cat_info->catpath))
					{
						return CATEGORY_ACCESS_DINIED;
					}
					$db->setQuery("DELETE FROM #__joomgallery_catg WHERE cid='".(int)$to_modify->param1."' ");
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
				
				$youcanedit = true;
				//Check: Do you have privilege to move foto
				if($login != '')
				{
					if(!$isAdmin)
					{
						
						$db->setQuery("SELECT * FROM #__joomgallery WHERE owner='".$owner_id."' AND id='".(int)$to_modify->param1."' LIMIT 1");
						$rows = $db->loadObjectList();
						
						if($rows)
						{
							$youcanedit = true;
						}
						else
						{
							return PHOTO_ACCESS_DINIED;
						}
					}
				}
				else
				{
					return PHOTO_ACCESS_DINIED;
				}
				if($youcanedit)
				{
					switch($to_modify->param2)
					{
					case 'imgtitle':
						{
							$db->setQuery( "UPDATE #__joomgallery SET imgtitle=".$db->Quote($to_modify->param3)." WHERE id='".(int)$to_modify->param1."' ");
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
					case 'imgauthor':
						{
							$db->setQuery( "UPDATE #__joomgallery SET imgauthor=".$db->Quote($to_modify->param3)." WHERE id='".(int)$to_modify->param1."' ");
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
					case 'imgtext':
						{
							$db->setQuery( "UPDATE #__joomgallery SET imgtext=".$db->Quote($to_modify->param3)." WHERE id='".(int)$to_modify->param1."' ");
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
					case 'published':
						{
							$db->setQuery( "UPDATE #__joomgallery SET published='".(int)$to_modify->param3."' WHERE id='".(int)$to_modify->param1."' ");
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
					}
				}
				else
				{
					return PHOTO_ACCESS_DINIED;;
				}
				break;
			}
		case 'renamecat':
			{
				$youcanedit = true;
								
				//Check: Do you have privilege to move foto
				if($login != '')
				{
					if(!$isAdmin)
					{
						$db->setQuery("SELECT * FROM #__joomgallery_catg WHERE owner='".$owner_id."' AND cid='".(int)$to_modify->param1."' LIMIT 1");
						$rows = $db->loadObjectList();
						if($rows)
						{
							$youcanedit = true;
						}
						else
						{
							return CATEGORY_ACCESS_DINIED;
						}
					}
				}
				else
				{
					return CATEGORY_ACCESS_DINIED;
				}
				if($youcanedit)
				{
					
					$db->setQuery( "UPDATE #__joomgallery_catg SET name='".$to_modify->param2."' WHERE cid='".(int)$to_modify->param1."' ");
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
		jimport('joomla.filesystem.file');
		
		$cache = & JFactory::getCache('com_jmediamanager');
		
		$config = $cache->call('JoomGallery_Plugin::getConfig', '');
		
		
		ini_set('memory_limit', '64M');
		
		if(!isset($_POST['w'.$config->jg_thumbwidth.'h'.$config->jg_thumbheight])
				|| !isset($_POST['w'.$config->jg_maxwidth.'h'.$config->jg_maxwidth])
				|| !isset($_POST['w3000h2000']))
		{
			$this->xmlErrorUploadResponse();
			exit();
		}
		
		//$small_image	= imagecreatefromstring(base64_decode($_POST['w'.$config->jg_thumbwidth.'h'.$config->jg_thumbheight]));
		//$middle_image 	= imagecreatefromstring(base64_decode($_POST['w'.$config->jg_maxwidth.'h'.$config->jg_maxwidth]));
		//$big_image 		= imagecreatefromstring(base64_decode($_POST['w3000h2000']));
		
		$imgname = "";
		$file_name = JRequest::getVar('file', null, 'REQUEST');
		$catid = JRequest::getVar('catId', null, 'REQUEST');
		$owner = JRequest::getVar('login', null, 'REQUEST');
		$photoid =JRequest::getVar('photoid', null, 'REQUEST');
		$imgDescription = JRequest::getVar('imgDescription', null, 'REQUEST', 'string', JREQUEST_ALLOWRAW);
		$imgAuthor = JRequest::getVar('imgAuthor', null, 'REQUEST');
		$imgPublished = JRequest::getVar('imgPublished', null, 'REQUEST');
		
		$cache = & JFactory::getCache('com_jmediamanager');
		$owner_id = $cache->call('CJmmPlugin::getIdByName', $owner);
		
		$imgname = ($imgname != "")? $imgname : $this->dgImgId($catid,'jpg');
		$newfilename = "";
		$photo_from_db = "";
		
		$db = &JFactory::getDBO();
		$origfilename = "";
		$imagetype = array( 1 => 'GIF', 2 => 'JPG', 3 => 'PNG');
		$imginfo = array();
		
		if((int)$catid)
		{
			$cat_info = JoomGallery_Plugin::getCategoryInfo($catid);
			
			if( !$cat_info )
			{
				$this->xmlErrorUploadResponse();
				exit();
			}
		}
		
		if($photoid == "")
		{
			$newfilename = $imgname;
			
			//if($small_image != false && $middle_image != false && $big_image != false)
			//{
				$date = & JFactory::getDate();
				$batchtime   = $date->toMySQL();
				
				//$batchtime = mktime();
				
				$query="SELECT ORDERING FROM #__joomgallery WHERE catid=$catid ORDER BY ORDERING DESC LIMIT 1";
				$db->setQuery( $query );
				
				$res = $db->loadResult();
				$ordering = $res+1;
				$title = $file_name;
				
				//$title = iconv('UTF-8', 'windows-1251', $title);
				
				$query = "INSERT INTO #__joomgallery(id,catid,imgtitle,imgauthor,imgtext,imgdate,ordering,imgvotes,imgvotesum,published,imgfilename,imgthumbname,checked_out,owner,approved) " .
				"VALUES (NULL,'$catid'," . $db->Quote($title) . ",". $db->Quote($imgAuthor) . "," . $db->Quote($imgDescription) . ",'$batchtime','$ordering','0','0'," . $db->Quote($imgPublished) . ",'$newfilename','$newfilename','0','$owner_id',1)";
				$db->setQuery( $query );	
				if (!$db->query()) 
				{
					$this->xmlErrorUploadResponse();
					exit();
				}
				else
				{
					$cid = $db->insertid();
					
					$alias = $db->Quote(preg_replace("/\./", "", preg_replace("#( ){1,}#", "-", $title)));
					
					$query = "UPDATE #__joomgallery SET alias=" . $alias . " WHERE id=" . $cid;
					
					$db->setQuery( $query );	
					
					if (!$db->query()) 
					{
						$this->xmlErrorUploadResponse();
						exit();
					}
				}
				
				if(!$this->base64_to_jpeg($_POST['w'.$config->jg_thumbwidth.'h'.$config->jg_thumbheight], JPATH_BASE . DS . $config->jg_paththumbs . $cat_info->catpath	. DS . "$newfilename")
						|| !$this->base64_to_jpeg($_POST['w'.$config->jg_maxwidth.'h'.$config->jg_maxwidth], JPATH_BASE . DS . $config->jg_pathimages . $cat_info->catpath . DS . "$newfilename")
						|| !$this->base64_to_jpeg($_POST['w3000h2000'], JPATH_BASE . DS . $config->jg_pathoriginalimages . $cat_info->catpath . DS . "$newfilename"))
				{
					$this->xmlErrorUploadResponse();
					exit();
				}
				
				$this->xmlSuccessUploadResponse($cid);
			//}
			//else
			//{
			//	$this->xmlErrorUploadResponse();
			//	exit();
			//}
		}
		else
		{
			//if($small_image != false && $middle_image != false && $big_image != false)
			//{
				$query="SELECT * FROM #__joomgallery WHERE id=$photoid LIMIT 1";
				$db->setQuery( $query );
				$rows = $db->loadObjectList();
				if(!count($rows))
				{
					$this->xmlErrorUploadResponse();
					exit();
				}
				$photo_from_db = & $rows[0];
				$newfilename = $photo_from_db->imgfilename;
				
				
				// Remove the original photos as well
				$original	= JPATH_BASE . DS .$config->jg_pathoriginalimages . $cat_info->catpath . DS . $photo_from_db->imgfilename;
				$middle		= JPATH_BASE . DS .$config->jg_pathimages . $cat_info->catpath . DS . $photo_from_db->imgfilename;
				$small		= JPATH_BASE . DS .$config->jg_paththumbs . $cat_info->catpath . DS . $photo_from_db->imgfilename;
				
				
				if( !empty($original) )
				{
					JFile::delete( $original );
				}
				if( !empty($middle) )
				{
					JFile::delete( $middle );
				}
				if( !empty($small) )
				{
					JFile::delete( $small );
				}
				
				if(!$this->base64_to_jpeg($_POST['w'.$config->jg_thumbwidth.'h'.$config->jg_thumbheight], JPATH_BASE . DS . $config->jg_paththumbs . $cat_info->catpath	. DS . "$newfilename")
						|| !$this->base64_to_jpeg($_POST['w'.$config->jg_maxwidth.'h'.$config->jg_maxwidth], JPATH_BASE . DS . $config->jg_pathimages . $cat_info->catpath . DS . "$newfilename")
						|| !$this->base64_to_jpeg($_POST['w3000h2000'], JPATH_BASE . DS . $config->jg_pathoriginalimages . $cat_info->catpath . DS . "$newfilename"))
				{
					$this->xmlErrorUploadResponse();
					exit();
				}
				
				$this->xmlSuccessUploadResponse($photoid);
			//}
			//else
			//{
			//	$this->xmlErrorUploadResponse();
			//	exit();
			//}
		}
	}
	
	public function moveImage($imgId, $catid_new, $catid_old = 0)
	{
		jimport('joomla.filesystem.file');
		
		require_once(JPATH_BASE . DS . "components" . DS . "com_joomgallery" . DS . "helpers" . DS . "helper.php");
		
		$cache = & JFactory::getCache('com_jmediamanager');
		
		$config = $cache->call('JoomGallery_Plugin::getConfig', '');
		$filename = $cache->call('JoomGallery_Plugin::getFileName', $imgId);
		$catinfo_old = JoomGallery_Plugin::getCategoryInfo($catid_old);
		$catinfo_new = JoomGallery_Plugin::getCategoryInfo($catid_new);

		//check if thumbnail already exists in source directory and not
		//exists already in destination
		//otherwise the file will not be copied
		if(
				JFile::exists(JPATH_BASE . DS . $config->jg_paththumbs.$catinfo_old->catpath . DS. $filename)
				&&
				!JFile::exists(JPATH_BASE . DS . $config->jg_paththumbs.$catinfo_new->catpath . DS . $filename)
				)
		{
			$result = JFile::move(JPATH_BASE . DS . $config->jg_paththumbs.$catinfo_old->catpath.DS.$filename
									, JPATH_BASE . DS . $config->jg_paththumbs.$catinfo_new->catpath.DS.$filename);
			
			if(!$result)
			{	
				return false;
			}
		}
		
		if(
				JFile::exists(JPATH_BASE . DS . $config->jg_pathimages.$catinfo_old->catpath.DS.$filename)
				&&
				!JFile::exists(JPATH_BASE . DS . $config->jg_pathimages.$catinfo_new->catpath.DS.$filename)
				)
		{
			$result = JFile::move(JPATH_BASE . DS . $config->jg_pathimages.$catinfo_old->catpath.DS.$filename
									, JPATH_BASE . DS . $config->jg_pathimages.$catinfo_new->catpath.DS.$filename);
			
			if(!$result)
			{	
				return false;
			}
		}
		
		if(
				JFile::exists(JPATH_BASE . DS . $config->jg_pathoriginalimages.$catinfo_old->catpath.DS.$filename)
				&&
				!JFile::exists(JPATH_BASE . DS . $config->jg_pathoriginalimages.$catinfo_new->catpath.DS.$filename)
				)
		{
			$result = JFile::move(JPATH_BASE . DS . $config->jg_pathoriginalimages.$catinfo_old->catpath.DS.$filename
									, JPATH_BASE . DS . $config->jg_pathoriginalimages.$catinfo_new->catpath.DS.$filename);
			
			if(!$result)
			{	
				return false;
			}
		}

		return true;
	}
	
	public function moveFolders($src, $dest)
	{
		$cache = & JFactory::getCache('com_jmediamanager');
		
		$config = $cache->call('JoomGallery_Plugin::getConfig', '');
		
		$orig_src   = JPath::clean(JPATH_BASE . DS . $config->jg_pathoriginalimages.$src);
		$orig_dest  = JPath::clean(JPATH_BASE . DS . $config->jg_pathoriginalimages.$dest);
		$img_src    = JPath::clean(JPATH_BASE . DS . $config->jg_pathimages.$src);
		$img_dest   = JPath::clean(JPATH_BASE . DS . $config->jg_pathimages.$dest);
		$thumb_src  = JPath::clean(JPATH_BASE . DS . $config->jg_paththumbs.$src);
		$thumb_dest = JPath::clean(JPATH_BASE . DS . $config->jg_paththumbs.$dest);

		// Move the folder of category in originals
		$return = JFolder::move($orig_src, $orig_dest);
		if($return !== true)
		{
			return false;
		}
		else
		{
			// Move the folder of category in details
			$return = JFolder::move($img_src, $img_dest);
			if($return !== true)
			{
				// If not successful
				JFolder::move($orig_dest, $orig_src);
				return false;
			}
			else
			{
				// Move the folder of category in thumbnails
				$return = JFolder::move($thumb_src, $thumb_dest);
				if($return !== true)
				{
					// If not successful
					JFolder::move($orig_dest, $orig_src);
					JFolder::move($img_dest, $img_src);
					return false;
				}
			}
		}

		return true;
	}
	public function createFolders($catpath)
	{
		$cache = & JFactory::getCache('com_jmediamanager');
		
		$config = $cache->call('JoomGallery_Plugin::getConfig', '');
		
		$catpath = JPath::clean($catpath);

		// Create the folder of category in originals
		if(!JFolder::create(JPATH_BASE . DS . $config->jg_pathoriginalimages.$catpath))
		{
			// If not successfull
			return false;
		}
		else
		{
			// Copy the assets/index.html in new folder
			JoomGallery_Plugin::copyIndexHtml(JPATH_BASE . DS . $config->jg_pathoriginalimages.$catpath);

			// Create the folder of category in pictures
			if(!JFolder::create(JPATH_BASE . DS . $config->jg_pathimages.$catpath))
			{
				// If not successful
				JFolder::delete(JPATH_BASE . DS . $config->jg_pathoriginalimages.$catpath);
				return false;
			}
			else
			{
				// Copy the assets/index.html in new folder
				JoomGallery_Plugin::copyIndexHtml(JPATH_BASE . DS . $config->jg_pathimages.$catpath);

				// Create the folder of category in thumbnails
				if(!JFolder::create(JPATH_BASE . DS . $config->jg_paththumbs.$catpath))
				{
					// If not successful
					JFolder::delete(JPATH_BASE . DS . $config->jg_pathoriginalimages.$catpath);
					JFolder::delete(JPATH_BASE . DS . $config->jg_pathimages.$catpath);
					return false;
				}
				else
				{
					// Copy the assets/index.html in new folder
					JoomGallery_Plugin::copyIndexHtml(JPATH_BASE . DS . $config->jg_paththumbs.$catpath);
				}
			}
		}

		return true;
	}
	
	public function deleteFolders($catpath)
	{
		$cache = & JFactory::getCache('com_jmediamanager');
		
		$config = $cache->call('JoomGallery_Plugin::getConfig', '');
		
		$orig_path  = JPath::clean(JPATH_BASE . DS . $config->jg_pathoriginalimages . $catpath);
		$img_path   = JPath::clean(JPATH_BASE . DS . $config->jg_pathimages . $catpath);
		$thumb_path = JPath::clean(JPATH_BASE . DS . $config->jg_paththumbs . $catpath);

		// Delete the folder of category in originals
		if(!JFolder::delete($orig_path))
		{
			// If not successfull
			return false;
		}
		else
		{
			// Delete the folder of category in details
			if(!JFolder::delete($img_path))
			{
				// If not successful
				if(JFolder::create($orig_path))
				{
					JoomFile::copyIndexHtml($orig_path);
				}
				return false;
			}
			else
			{
				// Delete the folder of category in thumbnails
				if(!JFolder::delete($thumb_path))
				{
					// If not successful
					if(JFolder::create($orig_path))
					{
						JoomFile::copyIndexHtml($orig_path);
					}
					if(JFolder::create($img_path))
					{
						JoomFile::copyIndexHtml($img_path);
					}
					return false;
				}
			}
		}

		return true;
	} 
	
	public function copyIndexHtml($folder)
	{
		jimport('joomla.filesystem.file');

		$src  = JPATH_ROOT.DS.'components'.DS.'com_joomgallery'.DS.'assets'.DS.'index.html';
		$dest = JPath::clean($folder.DS.'index.html');

		return JFile::copy($src, $dest);
	}
	
	function updateNewCatpath($catids_values, &$oldpath, &$newpath)
	{
		$db = &JFactory::getDBO();
		
		// Query for sub-categories with parent in $catids_values
		$db->setQuery("SELECT cid
						FROM #__joomgallery_catg
						WHERE parent IN ($catids_values)
						");
			
		$subcatids = $db->loadResultArray();

		if($db->getErrorNum())
		{
			return false;
		}
		
		$catids_values = explode(",", $catids_values);
		
		foreach($catids_values as $catid)
		{
			
			$cur_path = JoomGallery_Plugin::getCategoryInfo($catid);
			$catpath = $cur_path->catpath;

			// Replace former category path with actual one
			$catpath = str_replace($oldpath, $newpath, $catpath);

			// and save it
			$db->setQuery( "UPDATE #__joomgallery_catg SET catpath=".$db->Quote($catpath)." WHERE cid=".$db->Quote($catid));
			$result = $db->query();
			if(!$result)
			{
				return false;
			}	
		}
		
		// Nothing found, return
		if(!count($subcatids))
		{
			return true;
		}

		foreach($subcatids as $subcatid)
		{
			$cur_path = JoomGallery_Plugin::getCategoryInfo($subcatid);
			$catpath = $cur_path->catpath;

			// Replace former category path with actual one
			$catpath = str_replace($oldpath.'/', $newpath.'/', $catpath);

			// and save it
			$db->setQuery( "UPDATE #__joomgallery_catg SET catpath=".$db->Quote($catpath)." WHERE cid=".$db->Quote($subcatid));
			$result = $db->query();
			if(!$result)
			{
				return false;
			}	
		}

		// Split the array in comme seperated string
		$catids_values = implode (',', $subcatids);

		// Call again with sub-categories as parent
		return JoomGallery_Plugin::updateNewCatpath($catids_values, $oldpath, $newpath);
	} 
	
	private function storeUploadedImage($tmp_file, $imgTitle, & $imgDescription, $imgAuthor, $catid, $state, $owner, $id = "")
	{
		jimport('joomla.filesystem.file');
		
		$newfilename = $this->dgImgId($catid,'jpg');
		$cache = & JFactory::getCache('com_jmediamanager');
		$config = $cache->call('JoomGallery_Plugin::getConfig', '');
		$cat_info = JoomGallery_Plugin::getCategoryInfo($catid);
		$owner_id = $cache->call('CJmmPlugin::getIdByName', $owner);
		$cid  = "";
		$db = &JFactory::getDBO();
		
		if($id == "0")
		{
			$date = & JFactory::getDate();
			$batchtime   = $date->toMySQL();
			
			$query="SELECT ORDERING FROM #__joomgallery WHERE catid=$catid ORDER BY ORDERING DESC LIMIT 1";
			$db->setQuery( $query );
			
			$res = $db->loadResult();
			$ordering = $res+1;
			
			$query = "INSERT INTO #__joomgallery(id,catid,imgtitle,imgauthor,imgtext,imgdate,ordering,imgvotes,imgvotesum,published,imgfilename,imgthumbname,checked_out,owner,approved) " .
			"VALUES (NULL,'$catid'," . $db->Quote($imgTitle) . ",". $db->Quote($imgAuthor) . "," . $db->Quote($imgDescription) . ",'$batchtime','$ordering','0','0'," . $db->Quote($state) . ",'$newfilename','$newfilename','0','$owner_id',1)";
			$db->setQuery( $query );	
			if (!$db->query()) 
			{
				return "0";
			}
			else
			{
				$cid = $db->insertid();
				
				$alias = $db->Quote(preg_replace("/\./", "", preg_replace("#( ){1,}#", "-", $imgTitle)));
				$query = "UPDATE #__joomgallery SET alias=" . $alias . " WHERE id=" . $cid;
				$db->setQuery( $query );	
				
				if (!$db->query()) 
				{
					$this->xmlErrorUploadResponse();
					exit();
				}
			}
			
			if(!JoomGallery_Plugin::uploadAndResize($tmp_file, $newfilename, $catid))
			{
				return "0";
			}
			
			return $cid;
		}
		else
		{
			$query="SELECT * FROM #__joomgallery WHERE id=$id LIMIT 1";
			$db->setQuery( $query );
			$rows = $db->loadObjectList();
			if(!count($rows))
			{
				return "0";
			}
			$photo_from_db = & $rows[0];
			$newfilename = $photo_from_db->imgfilename;
			
			// Remove the original photos as well
			$original	= JPATH_BASE . DS .$config->jg_pathoriginalimages . $cat_info->catpath . DS . $photo_from_db->imgfilename;
			$middle		= JPATH_BASE . DS .$config->jg_pathimages . $cat_info->catpath . DS . $photo_from_db->imgfilename;
			$small		= JPATH_BASE . DS .$config->jg_paththumbs . $cat_info->catpath . DS . $photo_from_db->imgfilename;
			
			
			if( !empty($original) )
			{
				JFile::delete( $original );
			}
			if( !empty($middle) )
			{
				JFile::delete( $middle );
			}
			if( !empty($small) )
			{
				JFile::delete( $small );
			}
			
			if(!JoomGallery_Plugin::uploadAndResize($tmp_file, $newfilename, $catid))
			{
				return "0";
			}
			
			return $id;
		}
	}
	
	private function uploadAndResize($tmp_file, $newfilename, $catid)
	{
		jimport('joomla.filesystem.file');
		require_once(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_joomgallery' . DS . 'helpers' . DS . 'config.php');
		require_once(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_joomgallery' . DS . 'helpers' . DS . 'file.php');
		
		$debugoutput = "";
		$cache = & JFactory::getCache('com_jmediamanager');
		$config = $cache->call('JoomGallery_Plugin::getConfig', '');
		$cat_info = JoomGallery_Plugin::getCategoryInfo($catid);
		
		$res = JFile::upload($tmp_file, JPATH_BASE . DS . $config->jg_pathoriginalimages . $cat_info->catpath . DS . "$newfilename");
        
		if(!$res)
        {
          return false;
        }
		
		$res = JoomGallery_Plugin::resizeImage(JPATH_BASE . DS . $config->jg_pathoriginalimages . $cat_info->catpath . DS . "$newfilename",
                                        JPATH_BASE . DS . $config->jg_paththumbs . $cat_info->catpath . DS . "$newfilename",
                                        $config->jg_useforresizedirection,
                                        $config->jg_thumbwidth,
                                        $config->jg_thumbheight,
                                        $config->jg_thumbcreation,
                                        $config->jg_thumbquality,
                                        false,
                                        $config->jg_cropposition
                                        );
		if(!$res)
        {
          return false;
        }
		
		$res = JoomGallery_Plugin::resizeImage(JPATH_BASE . DS . $config->jg_pathoriginalimages . $cat_info->catpath . DS . "$newfilename",
                                          JPATH_BASE . DS . $config->jg_pathimages . $cat_info->catpath . DS . "$newfilename",
                                          false,
                                          $config->jg_maxwidth,
                                          false,
                                          $config->jg_thumbcreation,
                                          $config->jg_picturequality,
                                          true,
                                          0
                                          );
										  
		if(!$res)
        {
          return false;
        }
		return true;
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
		
		$output = $this->storeUploadedImage($_FILES["upload_field"]["tmp_name"], $title, &$desc, "", $catid, "1", $owner, $id);
		
		return;
	}
	
	
	private function chmod($dir, $mode)
	{
		static $ftpOptions;

		if(!isset($ftpOptions))
		{
			// Initialize variables
			jimport('joomla.client.helper');
			$ftpOptions = JClientHelper::getCredentials('ftp');  
		}

		if($ftpOptions['enabled'] == 1)
		{
			// Connect the FTP client
			jimport('joomla.client.ftp');
			$ftp = &JFTP::getInstance(
			$ftpOptions['host'], $ftpOptions['port'], null,
			$ftpOptions['user'], $ftpOptions['pass']
			);
			// Translate path to FTP path
			$dir = JPath::clean(str_replace(JPATH_ROOT, $ftpOptions['root'], $dir), '/');
			return $ftp->chmod($dir, $mode);
		}
		else
		{
			return JPath::setPermissions(JPath::clean($dir), $mode, $mode);
		}
	}
	
	private function fastImageCopyResampled(&$dst_image, $src_image, $dst_x, $dst_y,
											$src_x, $src_y, $dst_w, $dst_h,
											$src_w, $src_h, $quality = 3)
	{
		if(empty($src_image) || empty($dst_image) || $quality <= 0)
		{
			return false;
		}

		if($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h))
		{
			$temp = imagecreatetruecolor($dst_w * $quality + 1, $dst_h * $quality + 1);
			imagecopyresized  ($temp, $src_image, 0, 0, $src_x, $src_y, $dst_w * $quality + 1,
			$dst_h * $quality + 1, $src_w, $src_h);
			imagecopyresampled($dst_image, $temp, $dst_x, $dst_y, 0, 0, $dst_w,
			$dst_h, $dst_w * $quality, $dst_h * $quality);
			imagedestroy      ($temp);
		}
		else
		{
			imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w,
			$dst_h, $src_w, $src_h);
		}
		return true;
	}
	
	private function resizeImage( $src_file, $dest_file, $useforresizedirection,
									$new_width, $thumbheight, $method, $dest_qual, $max_width = false)
	{
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.path');
		
		$cache = & JFactory::getCache('com_jmediamanager');
		$config = $cache->call('JoomGallery_Plugin::getConfig', '');

		// Ensure that the pathes are valid and clean
		$src_file  = JPath::clean($src_file);
		$dest_file = JPath::clean($dest_file);

		// Doing resize instead of thumbnail, copy original and remove it.
		// @TODO check this extensions if needful
		$imagetype = array(1 => 'GIF', 2 => 'JPG', 3 => 'PNG', 4 => 'SWF', 5 => 'PSD',
		6 => 'BMP', 7 => 'TIFF', 8 => 'TIFF', 9 => 'JPC', 10 => 'JP2',
		11 => 'JPX', 12 => 'JB2', 13 => 'SWC', 14 => 'IFF');
		$imginfo = getimagesize($src_file);

		if ($imginfo == null) return false;
		$imginfo[2] = $imagetype[$imginfo[2]];
		// GD can only handle JPG & PNG images
		if ($imginfo[2] != 'JPG' && $imginfo[2] != 'PNG' && $imginfo[2] != 'GIF'
				&& ($method == 'gd1' || $method == 'gd2')) return false;
		// height/width
		$srcWidth  = $imginfo[0];
		$srcHeight = $imginfo[1];
		if ($max_width)
		{
			$ratio = max($srcHeight,$srcWidth) / $new_width ;
			//$ratio = $srcWidth / $new_width;
		}
		else
		{
			// Convert to width ratio
			if ($useforresizedirection)
			{
				$ratio = ($srcWidth / $new_width);
				$testheight = ($srcHeight/$ratio);
				// If new height exceeds the setted max. height
				if($testheight>$thumbheight)
				{
					$ratio = ($srcHeight/$thumbheight);
				}
				// Convert to height ratio
			}
			else
			{
				$ratio = ($srcHeight / $thumbheight);
				$testwidth = ($srcWidth / $ratio);
				// If new width exceeds setted max. width
				if($testwidth > $new_width)
				{
					$ratio = ($srcWidth / $new_width);
				}
			}
		}
		$ratio = max($ratio, 1.0);

		$destWidth  = (int)($srcWidth / $ratio);
		$destHeight = (int)($srcHeight / $ratio);

		// Method for creation of the resized image
		switch($method)
		{
		case 'gd1':
			if(!function_exists('imagecreatefromjpeg'))
			{
				return false;
			}
			if($imginfo[2] == 'JPG')
			{
				$src_img = imagecreatefromjpeg($src_file);
			}
			else
			{
				if ($imginfo[2] == 'PNG')
				{
					$src_img = imagecreatefrompng($src_file);
				}
				else
				{
					$src_img = imagecreatefromgif($src_file);
				}
			}
			if(!$src_img)
			{
				return false;
			}
			$dst_img = imagecreate($destWidth, $destHeight);
			imagecopyresized($dst_img, $src_img, 0, 0, 0, 0, $destWidth,
			(int)$destHeight, $srcWidth, $srcHeight);
			if(!@imagejpeg($dst_img, $dest_file, $dest_qual))
			{
				// Workaround for servers with wwwrun problem
				$dir = dirname($dest_file);
				JoomGallery_Plugin::chmod($dir, 0777);
				imagejpeg($dst_img, $dest_file, $dest_qual);
				JoomGallery_Plugin::chmod($dir, 0755);
			}
			imagedestroy($src_img);
			imagedestroy($dst_img);
			break;
		case 'gd2':
			if(!function_exists('imagecreatefromjpeg'))
			{
				return false;
			}
			if(!function_exists('imagecreatetruecolor'))
			{
				return false;
			}
			if($imginfo[2] == 'JPG')
			{
				$src_img = imagecreatefromjpeg($src_file);
			}
			else
			{
				if($imginfo[2] == 'PNG')
				{
					$src_img = imagecreatefrompng($src_file);
				}
				else
				{
					$src_img = imagecreatefromgif($src_file);
				}
			}

			if(!$src_img)
			{
				return false;
			}
			$dst_img = imagecreatetruecolor($destWidth, $destHeight);

			if ($config->jg_fastgd2thumbcreation == 0)
			{
				imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $destWidth,
				(int)$destHeight, $srcWidth, $srcHeight);
			}
			else
			{
				JoomGallery_Plugin::fastImageCopyResampled($dst_img, $src_img, 0, 0, 0, 0, $destWidth,
				(int)$destHeight, $srcWidth, $srcHeight);
			}

			if(!@imagejpeg($dst_img, $dest_file, $dest_qual))
			{
				// Workaround for servers with wwwrun problem
				$dir = dirname($dest_file);
				JoomGallery_Plugin::chmod($dir, 0777);
				imagejpeg($dst_img, $dest_file, $dest_qual);
				JoomGallery_Plugin::chmod($dir, 0755);
			}
			imagedestroy($src_img);
			imagedestroy($dst_img);
			break;
		case 'im':
			$disabled_functions = explode(',', ini_get('disabled_functions'));
			foreach($disabled_functions as $disabled_function)
			{
				if(trim($disabled_function) == 'exec')
				{
					return false;
				}
			}

			if(!empty($config->jg_impath))
			{
				$convert_path=$config->jg_impath.'convert';
			}
			else
			{
				$convert_path='convert';
			}
			$commands   = ' -resize "'.$destWidth.'x'.$destHeight.'" -quality "'.$dest_qual.'"  -unsharp "3.5x1.2+1.0+0.10"';
			$convert    = $convert_path.' '.$commands.' "'.$src_file.'" "'.$dest_file.'"';
			//echo $convert.'<br />';
			$return_var = null;
			$dummy      = null;
			@exec($convert, $dummy, $return_var);
			if($return_var != 0)
			{
				// Eorkaround for servers with wwwrun problem
				// TODO: necessary here? probably test required
				$dir = dirname($dest_file);
				JoomGallery_Plugin::chmod($dir, 0777);
				@exec($convert, $dummy, $return_var);
				JoomGallery_Plugin::chmod($dir, 0755);
				if($return_var != 0)
				{
					return false;
				}
			}
			break;
		default:
			return false;
		}

		// Set mode of uploaded picture
		JPath::setPermissions($dest_file);

		// We check that the image is valid
		$imginfo = getimagesize($dest_file);
		if(!$imginfo)
		{
			return false;
		}

		return true;
	}
}