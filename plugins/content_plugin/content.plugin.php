<?php
defined( '_JEXEC' ) or die( 'Restricted access' );

//error_reporting(0);
require_once( JPATH_COMPONENT_SITE . DS . 'plugin.class.php' );

class Content_Plugin extends CJmmPlugin 
{
	public function Content_Plugin()
	{
	}
	
	public function galleryExist()
	{
		return true; //com_content exists
	}
	
	private function getDirTree(& $categoryTree, $path)
	{
		if($handle = opendir($path))
		{
			while(false !== ($file = readdir($handle)))
			{
				if($file != "." && $file != "..")
				{
					if( is_dir($path . DS . $file))
					{
						$categoryTree[] = array(   
						'id'            =>$path . DS . $file
						,'owner'     	=>''
						,'parent_id' 	=>$path
						,'small_name'   =>''
						,'middle_name'  =>''
						,'big_name'   	=>''
						,'caption'  	=>$file
						,'small_path'   =>''
						,'middle_path'  =>''
						,'big_path'  	=>''
						);
						$this->getDirTree(&$categoryTree, $path . DS . $file);
					}
				}
			}
			closedir($handle);
		}
		return true;
	}
	private function getSectionsAndCategoryTree(& $categoryTree)
	{
		$db					=& JFactory::getDBO();
		
		$query = 'SELECT s.*, g.name AS groupname, u.name AS editor'
		. ' FROM #__sections AS s'
		. ' LEFT JOIN #__content AS cc ON s.id = cc.sectionid'
		. ' LEFT JOIN #__users AS u ON u.id = s.checked_out'
		. ' LEFT JOIN #__groups AS g ON g.id = s.access'
		. ' GROUP BY s.id'
		. ' ORDER BY s.ordering'
		;
		$db->setQuery( $query );
		$sections = $db->loadObjectList();
		if ($db->getErrorNum()) 
		{
			return false;
		}
		
		$query = 'SELECT  c.*, c.checked_out as checked_out_contact_category, g.name AS groupname, u.name AS editor, COUNT( DISTINCT s2.checked_out ) AS checked_out_count'
		. ' , z.title AS section_name'
		. ' FROM #__categories AS c'
		. ' LEFT JOIN #__users AS u ON u.id = c.checked_out'
		. ' LEFT JOIN #__groups AS g ON g.id = c.access'
		. ' LEFT JOIN #__content AS s2 ON s2.catid = c.id AND s2.checked_out > 0'
		. ' LEFT JOIN #__sections AS z ON z.id = c.section'
		. ' WHERE c.section NOT LIKE "%com_%"'
		. ' AND c.published != -2'
		. ' GROUP BY c.id'
		. ' ORDER BY z.title, c.ordering'
		;
		$db->setQuery( $query);
		$categories = $db->loadObjectList();
		if ($db->getErrorNum()) 
		{
			return false;
		}
		
		$section_size = count($sections);
		$category_size = count($categories);
		
		for($i = 0; $i < $section_size; $i++)
		{
			$categoryTree[] = array(   
			'id'            =>"section-" . $sections[$i]->id
			,'owner'     	=>''
			,'parent_id' 	=>"0"
			,'small_name'   =>''
			,'middle_name'  =>''
			,'big_name'   	=>''
			,'caption'  	=>$sections[$i]->title
			,'small_path'   =>''
			,'middle_path'  =>''
			,'big_path'  	=>''
			);
		}
		for($i = 0; $i < $category_size; $i++)
		{
			$categoryTree[] = array(   
			'id'            =>"section-" . $categories[$i]->section . "-category-" . $categories[$i]->id
			,'owner'     	=>''
			,'parent_id' 	=>"section-" . $categories[$i]->section
			,'small_name'   =>''
			,'middle_name'  =>''
			,'big_name'   	=>''
			,'caption'  	=>$categories[$i]->title
			,'small_path'   =>''
			,'middle_path'  =>''
			,'big_path'  	=>''
			);
			
		}
		
		return true;
	}
	private function getContentTree(& $photoTree, $phpsessid)
	{
		$cache = & JFactory::getCache('com_jmediamanager');
		$login		= JRequest::getVar('login', null, 'REQUEST');
		$owner_id = $cache->call('CJmmPlugin::getIdByName', $login);
		
		$db					=& JFactory::getDBO();
		
		$query = "SELECT * FROM #__content AS c WHERE c.created_by=" . $owner_id . " AND c.state='1'";
				
		$db->setQuery($query);
		$articles = $db->loadObjectList();

		if ($db->getErrorNum()) 
		{
			return false;
		}
		
		
		$articles_size = count($articles);
		
		for($i = 0; $i < $articles_size; $i++)
		{
			$get_url = "index2.php?option=com_jmediamanager&no_html=1&login={$login}"
			. "&phpsessid={$phpsessid}&controller=server&func=getArticle&id={$articles[$i]->id}";
			
			$title = str_replace("?", "", $articles[$i]->title);
			$title = str_replace('!', "", $title);
			$title = str_replace("+", "", $title);
			$title = str_replace("\\", "", $title);
			$title = str_replace("/", "", $title);
			$title = str_replace("<", "", $title);
			$title = str_replace(">", "", $title);
			$title = str_replace("*", "", $title);
			$title = str_replace("|", "", $title);
			$title = str_replace("\"", "'", $title);
			
			$photoTree[] = array(   
						'id'         	=> $articles[$i]->id
						,'imgtitle'     => $title . ".html"
						,'imgauthor'    => ""
						,'imgdate'    	=> $articles[$i]->modified
						,'description'  => ""
						,'published'    => "1"
						,'owner'     	=> $login
						,'parent_id' 	=> "section-" . $articles[$i]->sectionid . "-category-" . $articles[$i]->catid
						,'small_name'   => ''
						,'middle_name'  => ''
						,'big_name'   	=> ''
						,'caption'      => $title . ".html"
						,'small_path'   => JURI::base() . $get_url
						,'middle_path'  => JURI::base() . $get_url
						,'big_path'  	=> JURI::base() . $get_url
						,'size'  		=> 0
						,'tags' 		=> ''
						);
		}
		
		return true;
	}
	
	public function getArticle($id)
	{
		$cache = & JFactory::getCache('com_jmediamanager');
		$login		= JRequest::getVar('login', null, 'REQUEST');
		$owner_id = $cache->call('CJmmPlugin::getIdByName', $login);
		
		$db					=& JFactory::getDBO();
		
		$query = "SELECT * FROM #__content AS c WHERE c.id=" . $db->Quote($id) . " LIMIT 1";
				
		$db->setQuery($query);
		$articles = $db->loadObjectList();
		
		$articles_size = count($articles);

		if ($db->getErrorNum() || $articles_size == 0) 
		{
			echo "DATABASE ERROR";
			return DB_ERROR;
		}
		
		$article = $articles[0]->introtext . $articles[0]->fulltext;
		$article = str_replace("img src=\"images/", "img src=\"" . JURI::base() . "images/", $article);
		
		
		header('Last-Modified: '.date('r'));
		header('Accept-Ranges: bytes');
		header('Content-Length: '.(strlen($article)));
		header('Content-Type: text/html; charset=utf-8');
		ob_clean();
		echo $article;
		
		//readfile("C:\\xampp\\htdocs\\juploader\\index.php");
		exit;
	}
	
	private function getImgTree(& $photoTree, $path, $owner)
	{
		if($handle = opendir($path))
		{
			while(false !== ($file = readdir($handle)))
			{
				if($file != "." && $file != "..")
				{
					if( !is_dir($path . DS . $file))
					{
						$relative_url = str_replace(DS, "/", str_replace(JPATH_BASE . DS . "images", "", $path)) . "/" . $file;
						$size = @filesize($path . DS . $file);
						
						$photoTree[] = array(   
						'id'         	=> $path . DS . $file
						,'imgtitle'     => $file
						,'imgauthor'    => ""
						,'imgdate'    	=> ""
						,'description'  => ""
						,'published'    => "1"
						,'owner'     	=> $owner
						,'parent_id' 	=> $path
						,'small_name'   => ''
						,'middle_name'  => ''
						,'big_name'   	=> ''
						,'caption'      => $file
						,'small_path'   => JURI::base() . "images" . $relative_url
						,'middle_path'  => JURI::base() . "images" . $relative_url
						,'big_path'  	=> JURI::base() . "images" . $relative_url
						,'size'  		=> $size
						,'tags' 		=> ''
						);
					}
					else
					{
						$this->getImgTree(&$photoTree, $path . DS .$file, $owner);
					}
				}
			}
			closedir($handle);
		}
	}
	
	public function getCategoryTree(/*out*/& $categoryTree)
	{
		// $categoryTree[] = array(   
						// 'id'            =>JPATH_BASE . DS . "images"
						// ,'owner'     	=>''
						// ,'parent_id' 	=>0
						// ,'small_name'   =>''
						// ,'middle_name'  =>''
						// ,'big_name'   	=>''
						// ,'caption'  	=>"images"
						// ,'small_path'   =>''
						// ,'middle_path'  =>''
						// ,'big_path'  	=>''
						// );
		 // $categoryTree[] = array(   
						 // 'id'            =>"jmmg"
						 // ,'owner'     	=>''
						 // ,'parent_id' 	=>0
						 // ,'small_name'   =>''
						 // ,'middle_name'  =>''
						 // ,'big_name'   	=>''
						 // ,'caption'  	=>"jmmg"
						 // ,'small_path'   =>''
						 // ,'middle_path'  =>''
						 // ,'big_path'  	=>''
						 // );
		
		
		return (
				//$this->getDirTree(& $categoryTree, JPATH_BASE . DS . "images")
					$this->getSectionsAndCategoryTree(& $categoryTree)
				);
	}
	
	public function getImagesTree(/*in*/ $catID, /*in*/ $owner, $phpsessid, /*out*/& $photoTree)
	{
		//$this->getImgTree(& $photoTree, JPATH_BASE . DS . "images", $owner);
		$this->getContentTree(& $photoTree, $phpsessid);
		
		return true;
	}
	
	public function getProperties(/*out*/ & $properties, /*in*/$login = '')
	{
		require_once(JPATH_BASE .DS."administrator/components/com_datsogallery/config.datsogallery.php");
		
		$sizes_array = array (	
				array("w" => "0"  , "h" => "0")
		);
		
		$properties[] = array(
		'imgtitle'    =>1
		,'size'       =>0 
		,'lastmod'    =>1
		,'id'         =>1
		,'imgauthor'  =>0
		,'published'  =>1
		,'catid'      =>1
		,'imgtext'    =>0
		,'tags'	      =>0
		,'owner'      =>0
		,'nestedCat'  =>0
		,'sizes'      =>$sizes_array
		,'minSize'    =>0
		,'saveOrig'   =>1
		);
	}
	
	public function edit(/*in*/ $to_modify, /*out*/ $output)
	{
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');
		require_once(JPATH_BASE .DS."administrator/components/com_datsogallery/config.datsogallery.php");
		
		$db = &JFactory::getDBO();
		$login		= JRequest::getVar('login', null, 'REQUEST');
		
		$cache = & JFactory::getCache('com_jmediamanager');
		$isManagerLogin = $cache->call('CJmmPlugin::isManagerLogin', $login);
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
						return CATEGORY_ACCESS_DINIED;
					}
					
					// if($to_modify->param1 == 'foto')
					// {
						// if($isManagerLogin)
						// {
							// if (!is_readable($node_id) && !is_writable($to_modify->param3)) 
							// {
								// if (@ rename($node_id,  $to_modify->param3)) 
								// {
									// continue;
								// }
							// }
						// }
						// return PHOTO_ACCESS_DINIED;
					// }
					
					if($to_modify->param1 == 'foto')
					{
						if($isManagerLogin)
						{
							preg_match('/(?P<section>\w+)\-(?P<secid>\d+)\-(?P<category>\w+)\-(?P<catid>\d+)/', $to_modify->param3, $path );
							if(count($path) && (int)$path['catid'] && (int)$path['secid'])
							{
								
								$db->setQuery( "UPDATE #__content SET catid=".(int)$path['catid'].
												", sectionid=" . (int)$path['secid'] .
												" WHERE id=".(int)$node_id);
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
						}
						return PHOTO_ACCESS_DINIED;
					}
				}
				return NO_ERROR;
			}
		case 'createcat':
			{
				preg_match('/(?P<section>\w+)\-(?P<secid>\d+)\-(?P<category>\w+)\-(?P<catid>\d+)/', $to_modify->param1, $category );
				preg_match('/(?P<section>\w+)\-(?P<secid>\d+)/', $to_modify->param1, $section );
				
				if($isManagerLogin)
				{
					$alias = JFilterOutput::stringURLSafe($to_modify->param2);

					if(trim(str_replace('-','',$alias)) == '') 
					{
						$datenow =& JFactory::getDate();
						$alias = $datenow->toFormat("%Y-%m-%d-%H-%M-%S");
					}
					
					if($to_modify->param1 == "0") // create new section
					{
						$db->setQuery("INSERT INTO #__sections (id,title,alias,scope,access,published,image_position) VALUES"
										. " (NULL,"
										.$db->Quote($to_modify->param2)
										. "," . $db->Quote($alias)
										. ",'content'"
										.",0"
										.",1"
										.",'left'"
										.")"
										);
						
						$result = $db->query();
						if($result)
						{
							$output = "section-" . mysql_insert_id();
							return NO_ERROR;
						}
						else
						{
							return DB_ERROR;
						}
					}
					else if(count($category) && (int)$category['catid'] && (int)$category['secid']) //Create new sub category
					{
						return DB_ERROR;
					}
					else if(count($section) && (int)$section['secid'])
					{
						$db->setQuery("INSERT INTO #__categories (id,title,parent_id,alias,section,access,published,image_position) VALUES" . 
										" (NULL,"
										.$db->Quote($to_modify->param2)
										.",0"
										.",".$db->Quote($alias)
										.",".$db->Quote((int)$section['secid'])
										.",0"
										.",1"
										.",'left'"
										.")"
										);
										$result = $db->query();
										if($result)
										{
											$output = "section-" . (int)$section['secid'] . "-category-" . mysql_insert_id();
											return NO_ERROR;
										}
										else
										{
											return DB_ERROR;
										}
					}
				}
				// else if(count($section) && (int)$section['secid'])
				// {
					// $db->setQuery("INSERT INTO #__section (id,title,access,published,image_position) VALUES" . 
							// " (NULL,'"
							// .$db->Quote($to_modify->param2)
							// .",0"
							// .",1"
							// .",'left'"
							// .")"
					// );
					// $result = $db->query();
					// if($result)
					// {
						// $output = mysql_insert_id();
						// return NO_ERROR;
					// }
					// else
					// {
						// return DB_ERROR;
					// }
				// }
				// else // Create new directory
				// {
					// if(JFolder::create($to_modify->param1 . DS . $to_modify->param2))
					// {
						// $output = $to_modify->param1 . DS . $to_modify->param2;
						// return NO_ERROR;
					// }
					// return CATEGORY_ACCESS_DINIED; 
				// }
				break;
			}
		case 'deletefoto':
			{
				$nodes = explode(",", $to_modify->param1);
				
				foreach($nodes as $node_id)
				{
					if($isManagerLogin)
					{
						if((int)$node_id) //delete article
						{
							$db->setQuery("DELETE FROM #__content WHERE id='".(int)$node_id."' ");
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
						
						return PHOTO_ACCESS_DINIED;
					}
				}
				break;
			}
		case 'deletecat':
			{
				if($isManagerLogin)
				{
					preg_match('/(?P<section>\w+)\-(?P<secid>\d+)\-(?P<category>\w+)\-(?P<catid>\d+)/', $to_modify->param1, $category );
					preg_match('/(?P<section>\w+)\-(?P<secid>\d+)/', $to_modify->param1, $section );
					
					if(count($category) && (int)$category['catid']) //delete category
					{
						$db->setQuery("DELETE FROM #__categories WHERE id='".(int)$category['catid']."' ");
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
					else if(count($section) && (int)$section['secid']) // delete section
					{
						$db->setQuery("DELETE FROM #__sections WHERE id='".(int)$section['secid']."' ");
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
					return CATEGORY_ACCESS_DINIED;
				}
				break;
			}
		case 'editfoto':
			{
				if($isManagerLogin)
				{
					switch($to_modify->param2)
					{
					case 'imgtitle':
						{
							if((int)$to_modify->param1)
							{
								$alias = JFilterOutput::stringURLSafe($to_modify->param3);

								if(trim(str_replace('-','',$alias)) == '') 
								{
									$datenow =& JFactory::getDate();
									$alias = $datenow->toFormat("%Y-%m-%d-%H-%M-%S");
								}

								$db->setQuery( "UPDATE #__content SET title=".$db->Quote($to_modify->param3)
								. ", alias=" .$db->Quote($alias)
								. " WHERE id=".$db->Quote((int)$to_modify->param1));
								
								$result = $db->query();
								if(!$result)
								{
									return DB_ERROR;
								}
								else
								{
									return NO_ERROR;
								}	
							}
							return PHOTO_ACCESS_DINIED;
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
				if($isManagerLogin)
				{
					preg_match('/(?P<section>\w+)\-(?P<secid>\d+)\-(?P<category>\w+)\-(?P<catid>\d+)/', $to_modify->param1, $category );
					preg_match('/(?P<section>\w+)\-(?P<secid>\d+)/', $to_modify->param1, $section );
					
					$alias = JFilterOutput::stringURLSafe($to_modify->param2);

					if(trim(str_replace('-','',$alias)) == '') 
					{
						$datenow =& JFactory::getDate();
						$alias = $datenow->toFormat("%Y-%m-%d-%H-%M-%S");
					}
					
					if(count($category) && (int)$category['catid'] && (int)$category['secid']) //rename category
					{
						$db->setQuery("UPDATE #__categories SET title=".$db->Quote($to_modify->param2). ", alias=" . $db->Quote($alias) . " WHERE id='".(int)$category['catid']."' ");
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
					else if(count($section) && (int)$section['secid']) // rename section
					{
						$db->setQuery("UPDATE #__sections SET title=".$db->Quote($to_modify->param2).", alias=" . $db->Quote($alias) . " WHERE id='".(int)$section['secid']."'");
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
						if(!@ rename($to_modify->param1
									, dirname($to_modify->param1) . DS . $to_modify->param2))
						{
							return CATEGORY_ACCESS_DINIED;
						}
					}
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
			
			//echo $query;
			
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
	
	public function file_upload($title, $id, $catid, /*out*/ &$output)
	{
		$output = "0";
		// $file_size = filesize($_FILES["upload_field"]["tmp_name"]);
		
		// if(!$file_size)
		// {
			// return;
		// }
		
		// $handle = fopen($_FILES["upload_field"]["tmp_name"], 'rb');
		
		// if($handle === FALSE)
		// {
			// return;
		// }

		// $content = fread($handle, $file_size);
		
		// if($content === FALSE)
		// {
			// return;
		// }
		
		// fclose($handle);
		
		$content = JRequest::getVar( 'text', '', 'post', 'string', JREQUEST_ALLOWRAW );
		
		preg_match('/(?P<section>\w+)\-(?P<secid>\d+)\-(?P<category>\w+)\-(?P<catid>\d+)/', $catid, $category );
					
		if(count($category) && (int)$category['catid'] && (int)$category['secid']) //upload to category
		{
			$output = $this->storeUploadedArticle( $title, &$content, $id, $category['catid'], $category['secid'] );
		}
		
		return;
	}
	
	function storeUploadedArticle( $title, &$text, $id, $catid, $sectionid )
	{
		// Get submitted text from the request variables
		//$text = iconv("WINDOWS-1251", "UTF-8", &$text);
		
		$output = "0";
		$login		= JRequest::getVar('login', null, 'REQUEST');
		
		$cache = & JFactory::getCache('com_jmediamanager');
		$isManagerLogin = $cache->call('CJmmPlugin::isManagerLogin', $login);
		$owner_id = $cache->call('CJmmPlugin::getIdByName', $login);
		
		
		$row = & JTable::getInstance('content');
		
		$row->title = $title;
		
		$row->alias = JFilterOutput::stringURLSafe($title);

		if(trim(str_replace('-','',$row->alias)) == '') 
		{
			$datenow =& JFactory::getDate();
			$row->alias = $datenow->toFormat("%Y-%m-%d-%H-%M-%S");
		}
		$row->sectionid = $sectionid;
		
		$row->catid = $catid;
		$row->version = 0;
		$row->introtext = "";
		$row->fulltext = "";
		$row->id = $id;
		$row->modified 		= "";
		$row->modified_by 	= 0;
		$row->created_by 	=  "";
		$row->created = "";
		$row->publish_up = "";
		$row->publish_down = "";
		$row->state = 0;
		$row->attribs = "";
		$row->metadesc = "";
		$row->metakey = "";
		$row->metadata = "";
		$row->parentid	= 0;
		
		// Initialize variables
		$db		= & JFactory::getDBO();
		$user			= & JFactory::getUser();
		$dispatcher 	= & JDispatcher::getInstance();
		JPluginHelper::importPlugin('content');

		$nullDate	= $db->getNullDate();

		// sanitise id field
		$row->id = (int) $row->id;

		$isNew = true;
		
		if ($row->id) 
		{
			$isNew = false;
			$datenow =& JFactory::getDate();
			$row->modified 		= $datenow->toMySQL();
			$row->modified_by 	= $owner_id;
			
			// find section name
			$query = 'SELECT a.version' .
					 ' FROM #__content AS a' .
				     ' WHERE a.id = '. (int) $row->id;
			$db->setQuery($query);
			$row->version = $db->loadResult();
		}

		$row->created_by 	=  $owner_id;

		if ($row->created && strlen(trim( $row->created )) <= 10) {
			$row->created 	.= ' 00:00:00';
		}

		$config =& JFactory::getConfig();
		$tzoffset = $config->getValue('config.offset');
		$date =& JFactory::getDate($row->created, $tzoffset);
		$row->created = $date->toMySQL();

		// Append time if not added to publish date
		if (strlen(trim($row->publish_up)) <= 10) {
			$row->publish_up .= ' 00:00:00';
		}

		$date =& JFactory::getDate($row->publish_up, $tzoffset);
		$row->publish_up = $date->toMySQL();

		// Handle never unpublish date
		$row->publish_down = $nullDate;
		
		$row->state	= 1;
		
		$row->attribs = "show_title=\n"
						. "link_titles=\n"
						. "show_intro=\n"
						. "show_section=\n"
						. "link_section=\n"
						. "show_category=\n"
						. "link_category=\n"
						. "show_vote=\n"
						. "show_author=\n"
						. "show_create_date=\n"
						. "show_modify_date=\n"
						. "show_pdf_icon=\n"
						. "show_print_icon=\n"
						. "show_email_icon=\n"
						. "language=\n"
						. "keyref=\n"
						. "readmore=\n";
		

		// Get metadata string
		
		$row->metadesc = "";
		$row->metakey = "";
		$row->metadata = "robots=\n"
						 . "author=\n";
				

		// Prepare the content for saving to the database
				//ContentHelper::saveContentPrep( $row );
				
		// Clean text for xhtml transitional compliance
		$text		= str_replace( '<br>', '<br />', $text );

		// Search for the {readmore} tag and split the text up accordingly.
		$pattern = '#<hr\s+id=("|\')system-readmore("|\')\s*\/*>#i';
		$tagPos	= preg_match($pattern, $text);

		if ( $tagPos == 0 )
		{
			$row->introtext	= $text;
		} 
		else
		{
			list($row->introtext, $row->fulltext) = preg_split($pattern, $text, 2);
		}

		// Filter settings
		jimport( 'joomla.application.component.helper' );
		$config	= JComponentHelper::getParams( 'com_content' );
		$user	= &JFactory::getUser();
		$gid	= $user->get( 'gid' );

		$filterGroups	=  $config->get( 'filter_groups' );
		
		// convert to array if one group selected
		if ( (!is_array($filterGroups) && (int) $filterGroups > 0) ) 
		{ 
			$filterGroups = array($filterGroups);
		}

		if (is_array($filterGroups) && in_array( $gid, $filterGroups ))
		{
			$filterType		= $config->get( 'filter_type' );
			$filterTags		= preg_split( '#[,\s]+#', trim( $config->get( 'filter_tags' ) ) );
			$filterAttrs	= preg_split( '#[,\s]+#', trim( $config->get( 'filter_attritbutes' ) ) );
			switch ($filterType)
			{
				case 'NH':
					$filter	= new JFilterInput();
					break;
				case 'WL':
					$filter	= new JFilterInput( $filterTags, $filterAttrs, 0, 0, 0);  // turn off xss auto clean
					break;
				case 'BL':
				default:
					$filter	= new JFilterInput( $filterTags, $filterAttrs, 1, 1 );
					break;
			}
			//$row->introtext	= $filter->clean( $row->introtext );
			//$row->fulltext	= $filter->clean( $row->fulltext );
		} 
		elseif(empty($filterGroups) && $gid != '25') // no default filtering for super admin (gid=25)
		{ 
			$filter = new JFilterInput( array(), array(), 1, 1 );
			//$row->introtext	= $filter->clean( $row->introtext );
			//$row->fulltext	= $filter->clean( $row->fulltext );
		}
		
		// Increment the content version number
		$row->version++;

		$result = $dispatcher->trigger('onBeforeContentSave', array(&$row, $isNew));
		
		if(in_array(false, $result, true)) 
		{
			return $output;
		}
		
		// Store the content to the database
		if($isNew)
		{
			$query = "INSERT INTO #__content("
								. "id"
								. ",sectionid"
								. ",catid"
								. ",title" 
								. ",introtext"
								. "," .$db->nameQuote("fulltext")
								. ",metadesc"
								. ",metakey"
								//. ",metadata"
								//. ",attribs"
								. ",created_by"
								. ",created"
								. ",modified"
								. ",modified_by"
								. ",publish_up"
								. ",publish_down"
								. ",state"
								. ",version"
								. ",parentid) "
								. " VALUES (NULL"
								. "," . $db->Quote($row->sectionid)
								. "," . $db->Quote($row->catid)
								. "," . $db->Quote($row-> title)
								. "," . $db->Quote($row->introtext)
								. "," . $db->Quote($row->fulltext)
								. "," . $db->Quote($row->metadesc)
								. "," . $db->Quote($row->metakey)
								//. "," . $db->Quote($row->metadata)
								//. "," . $db->Quote($row->attribs)
								. "," . $db->Quote($row->created_by)
								. "," . $db->Quote($row->created)
								. "," . $db->Quote($row->modified)
								. "," . $db->Quote($row->modified_by)
								. "," . $db->Quote($row->publish_up)
								. "," . $db->Quote($row->publish_down)
								. "," . $db->Quote($row->state)
								. "," . $db->Quote($row->version)
								. "," . $db->Quote($row->parentid)
								. ")";
					
			$db->setQuery( $query );	
			if (!$db->query()) 
			{
				return $output;
			}
			else
			{
				$output = $db->insertid();
			}
		}
		else
		{
			$query = "UPDATE #__content SET "
								. "introtext=". $db->Quote($row->introtext)
								. "," . $db->nameQuote("fulltext") . "="       . $db->Quote($row->fulltext)
								. ",created_by="   . $db->Quote($row->created_by)
								. ",created="      . $db->Quote($row->created)
								. ",modified="     . $db->Quote($row->modified)
								. ",modified_by="  . $db->Quote($row->modified_by)
								. ",publish_up="   . $db->Quote($row->publish_up)
								. ",publish_down=" . $db->Quote($row->publish_down)
								. ",version="      . $db->Quote($row->version)
								. " WHERE id=" . $db->Quote($row->id);
								
					
			$db->setQuery( $query );	
			if (!$db->query()) 
			{
				return $output;
			}
			else
			{
				$output = $row->id;
			}
		}
		
		// Check the article and update item order
		//$row->checkin();
		//row->reorder('catid = '.(int) $row->catid.' AND state >= 0');

		$cache = & JFactory::getCache('com_content');
		$cache->clean();

		$dispatcher->trigger('onAfterContentSave', array(&$row, $isNew));

		return $output;
	}
}