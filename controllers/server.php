<?php
require_once( JPATH_COMPONENT . DS . 'plugin.class.php' );

interface IServer 
{
	public function auth();
	public function getXMLTree();
	public function getCatXMLTree();
	public function getImages();
	public function getProp();
	public function edit();
	public function upload();
	public function mupload();
	public function file_upload();
}

class ServerAnswer extends CJmmController
{
	function __construct( $default = array())
	{
		$default['default_task'] = 'auth';
		return parent::__construct( $default );
	}
	
	public function startServerAnswer()
	{
		header('Content-Type: text/xml');
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		echo "<response>\n";
	}
	
	public function endServerAnswer()
	{
		echo "</response>\n";
	}
	
	public function startXMLTreeAnswer()
	{
		header('Content-Type: text/xml');
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		echo "<node type=\"root\" status=\"hidden\">\n";
		echo "<caption>Ваши фотографии</caption>\n";
		echo "<cid>0</cid>\n";
		echo "<children>";
	}
	
	public function endXMLTreeAnswer()
	{
		echo "</children>\n";
		echo "</node>\n";
	}
	
	public function answer($result = 0, $error = false)
	{
		if(!$error)
		{
			$this->startServerAnswer();
			echo $result . "\n";
			$this->endServerAnswer();
		}
		else
		{
			$this->startServerAnswer();
			echo "<error>\n";
			echo $result . "\n";
			echo "</error>\n";
			$this->endServerAnswer();
		}
	}
}

class CServerJmmController extends ServerAnswer implements IServer
{
	var $photoTree;
	
	var $categoryTree;
	
	var $gallery_plg_instance;
	
	var $content_plg_instance;
	
	function __construct( $default = array())
	{
		$default['default_task'] = 'auth';
		if(!parent::__construct( $default )) return false;
		
		$db	   = & JFactory::getDBO();
		$query = "SELECT * FROM #__jmm_plugin WHERE `status`=1 LIMIT 1";
		$db->setQuery($query);
		
		$actPlg = $db->loadObjectList();
		if ($db->getErrorNum()) 
		{
			throw new CJmmException($db->stderr());
		}
		
		$pluginFile = strtolower(str_replace("_", ".", str_replace(" ", ".", $actPlg[0]->name))) . ".php";
		
		if(!file_exists(JPATH_COMPONENT . DS . 'plugins' . DS . strtolower($actPlg[0]->name) . DS . $pluginFile))
		{
			throw new CJmmException('Could not find plugin file');
		}
		
		require_once( JPATH_COMPONENT . DS . 'plugins' . DS . strtolower($actPlg[0]->name) . DS . $pluginFile );
		
		$plgClass = $actPlg[0]->name;
		
		$this->gallery_plg_instance = new $plgClass();
		
		require_once( JPATH_COMPONENT . DS . 'plugins' . DS . 'content_plugin' . DS . 'content.plugin.php' );
		
		$this->content_plg_instance = new Content_Plugin();
	}
	
	public function auth()
	{
		try
		{
			$login		= JRequest::getVar('login', null, 'REQUEST');
			$password	= JRequest::getVar('password', null, 'REQUEST');
			$phpsessid	= JRequest::getVar('phpsessid', null, 'REQUEST');
			
			if($phpsessid == '')
			{
				$phpsessid = $this->checkByPassword($login, $password);
				
				if ($phpsessid) 
				{
					$this->answer($phpsessid);
					exit();
				}
			}
			else
			{
				if($this->checkBySessId($phpsessid))
				{
					$this->answer(1);
					exit();
				}
			}
			
			$this->answer(0);
			exit();
		}
		catch(CJmmException $e)
		{
			$this->answer(0);
			exit();
		}
	}
	
	private function checkByPassword($login = '', $password = '')
	{
		jimport('joomla.user.helper');
		
		if($login != '' && $password != '')
		{
			$db =& JFactory::getDBO();
			$query = 'SELECT `id`, `password`, `gid`'
			. ' FROM `#__users`'
			. ' WHERE username=' . $db->Quote( $login );
			
			$db->setQuery( $query );
			$result = $db->loadObject();

			if($result)
			{
				$parts	= explode( ':', $result->password );
				$crypt	= $parts[0];
				$salt	= @$parts[1];
				$testcrypt = JUserHelper::getCryptedPassword($password, $salt);

				if ($crypt == $testcrypt) 
				{
					// Register the needed session variables
					$session =& JFactory::getSession();
					//$session->set('user', $instance);
					
					// Get the session object
					$table = & JTable::getInstance('session');
					$table->load( $session->getId() );

					$table->guest 		= "0";
					$table->username 	= $login;
					$table->userid 		= intval(JUserHelper::getUserId($login));
					$table->usertype 	= "Registred";
					$table->gid 		= "0";

					$table->update();

					
					// Hit the user last visit field
					//$instance->setLastVisit();
					return $session->getId();
				}
			}
		}
		return false;
	}
	
	private function checkBySessId($phpsessid = '')
	{
		if($phpsessid != '')
		{
			$db =& JFactory::getDBO();
			$query = 'SELECT username FROM `#__session`'
			. ' WHERE session_id=' . $db->Quote( $phpsessid );
			$db->setQuery( $query );
			$result = $db->loadResult();
			
			if($result)
			{
				return true;
			}	
		}
		return false;
	}
	
	public function checkCredentials()
	{
		$login		= JRequest::getVar('login', null, 'REQUEST');
		$password	= JRequest::getVar('password', null, 'REQUEST');
		$phpsessid	= session_id();//JRequest::getVar('phpsessid', null, 'REQUEST');
		
		if($password != "")
		{
			if ($this->checkByPassword($login, $password)) 
			{
				return true;
			}
		}
		else
		{
			if($this->checkBySessId($phpsessid))
			{
				return true;
			}
			else
			{
				$this->answer(SESSION_EXPIRED, true);
				exit();
			}
		}
		
		return false;
	}
	
	private function getCatPhoto($catID)
	{
		$login		= JRequest::getVar('login', null, 'REQUEST');
		
		if(!$this->photoTree) return;
		
		foreach($this->photoTree as $node)
		{
			if($node['owner'] == $login && $node['parent_id'] == $catID)
			{
				echo "<children>\n";
				echo "<node type=\"module\" ";
				echo "id=\"".$node['id']."\" ";
				echo "active=\"true\" ";
				echo "status=\"visible\"> ";
				echo " <caption>".$node['caption'] . "</caption>\n";
				echo " <cid>".$node['id']."</cid>\n";
				echo " <parent>".$node['parent_id']."</parent>\n";
				echo " <small_url>".$node['small_path'] . $node['small_name'] . "</small_url>\n";
				echo " <midle_url>".$node['middle_path'] . $node['middle_name'] . "</midle_url>\n";
				echo " <big_url>".$node['big_path'] . $node['big_name'] . "</big_url>\n";
				echo " <modified>".$node['imgdate'] . "</modified>\n";
				echo "</node>\n";
				echo "</children>\n";
			}
		}
	}	
	
	function getSubCatTree($parentCatId, $catOnly = false)
	{
		foreach ($this->categoryTree as $node)
		{
			if(!strcmp($node['parent_id'],$parentCatId))
			{
				echo "<children>\n";
				echo "\n <node type=\"page\" id=\"".$node['id']."\" active=\"true\" status=\"visible\">";
				echo "\n <caption>".$node['caption']."</caption>\n";
				echo "\n <cid>".$node['id']."</cid>\n";
				echo "\n <parent>".$node['parent_id']."</parent>\n";
				
				if(!$catOnly)
				{
					$this->getCatPhoto($node['id']);
				}
				$this->getSubCatTree($node['id'], $catOnly);
				echo "</node>\n";
				echo "\n </children>\n";
			}
		}
	}
	
	public function getImage()
	{
		if($this->checkCredentials())
		{
			$image		= JRequest::getVar('image', null, 'REQUEST');
			//$owner		= JRequest::getVar('owner', null, 'REQUEST');
			//$phpsessid	= JRequest::getVar('phpsessid', null, 'REQUEST');
			$catid	= JRequest::getVar('catid', null, 'REQUEST');
			$width	= JRequest::getVar('width', null, 'REQUEST');
			$height	= JRequest::getVar('height', null, 'REQUEST');
			$crop	= JRequest::getVar('crop', null, 'REQUEST');
			$cropratio	= JRequest::getVar('cropratio', null, 'REQUEST');
			//$plugin = JRequest::getVar('plugin', null, 'REQUEST');
			
			$this->gallery_plg_instance->getImage($image, $width, $height, $crop, $cropratio, $catid);
			
		}
		else
		{
			exit;
		}
	}
	
	public function getArticle()
	{
		if($this->checkCredentials())
		{
			$id		= JRequest::getVar('id', null, 'REQUEST');
			$this->content_plg_instance->getArticle($id);
		}
		else
		{
			exit;
		}
	}
	
	function getXMLTree()
	{
		$phpsessid	=  session_id();//JRequest::getVar('phpsessid', null, 'REQUEST');
		$mainframe =& JFactory::getApplication('site');
		$pluginName = JRequest::getVar('plugin', null, 'REQUEST');
		$currentPlugin = $this->gallery_plg_instance;
		
		if($pluginName == "Joomla.Article")
		{
			$currentPlugin = $this->content_plg_instance;
		}
		
		if($this->checkCredentials())
		{
			$login		= JRequest::getVar('login', null, 'REQUEST');
			
			
			if(!$currentPlugin->getCategoryTree(&$this->categoryTree) 
					|| /*999 == true */ 999 != $currentPlugin->getImagesTree(0, $login, $phpsessid, &$this->photoTree) 
					|| !$this->categoryTree)
			{
				$this->startXMLTreeAnswer();
				$this->endXMLTreeAnswer();
				return;
			}
			
			$this->startXMLTreeAnswer();
			
			//print_r($this->categoryTree);
			
			foreach ($this->categoryTree as $node)
			{
				if(!strcmp($node['parent_id'], "0"))
				{
					echo "\n <node type=\"page\" id=\"".$node['id']."\" active=\"true\" status=\"visible\">";
					echo "\n <caption>".$node['caption']."</caption>\n";
					echo "\n <cid>".$node['id']."</cid>\n";
					echo "\n <parent>".$node['parent_id']."</parent>\n";
					
					$this->getCatPhoto($node['id']);
					$this->getSubCatTree($node['id']);
					
					echo "</node>\n";
				}
			}
			
			$this->endXMLTreeAnswer();
			return;
		}
		else
		{
			$mainframe->redirect("index.php");
		}		
	}
	
	function getCatXMLTree()
	{
		$pluginName = JRequest::getVar('plugin', null, 'REQUEST');
		$currentPlugin = $this->gallery_plg_instance;
		
		if($pluginName == "Joomla.Article")
		{
			$currentPlugin = $this->content_plg_instance;
		}
		
		if($this->checkCredentials())
		{
			if(!$currentPlugin->getCategoryTree(&$this->categoryTree))
			{
				$this->startXMLTreeAnswer();
				$this->endXMLTreeAnswer();
				return;
			}
			
			$this->startXMLTreeAnswer();
			
			foreach ($this->categoryTree as $node)
			{
				if($node['parent_id'] == 0)
				{
					echo "\n <node type=\"page\" id=\"".$node['id']."\" active=\"true\" status=\"visible\">";
					echo "\n <caption>".$node['caption']."</caption>\n";
					echo "\n <cid>".$node['id']."</cid>\n";
					echo "\n <parent>".$node['parent_id']."</parent>\n";
					
					$this->getSubCatTree($node['id'], true);
					
					echo "</node>\n";
				}					
			}
			$this->endXMLTreeAnswer();
			return;
		}
		else
		{
			$this->startXMLTreeAnswer();
			$this->endXMLTreeAnswer();
			return;
		}
	}
	
	public function getImages()
	{
		$phpsessid	=  session_id();//JRequest::getVar('phpsessid', null, 'REQUEST');
		$images = array();
		$pluginName = JRequest::getVar('plugin', null, 'REQUEST');
		$currentPlugin = $this->gallery_plg_instance;
		
		if($pluginName == "Joomla.Article")
		{
			$currentPlugin = $this->content_plg_instance;
		}
		
		if($this->checkCredentials())
		{
			$categoryPhotos = array();
			$login		= JRequest::getVar('login', null, 'REQUEST');
			$catid		= JRequest::getVar('catid', null, 'REQUEST');
			
			if(/* 999 == true*/ 999 != $currentPlugin->getImagesTree($catid, $login, $phpsessid, & $categoryPhotos))
			{
				$o = array('images'=>$images);
				echo json_encode($o);
				return;
			}
			if(!count($categoryPhotos))
			{
				$o = array('images'=>$images);
				echo json_encode($o);
				return;
			}
			
			foreach ($categoryPhotos as $node)
			{
				$images[] = array(
				'imgtitle'	=>$node['caption'],
				'size'		=>$node['size'], 
				'lastmod'	=>$node['imgdate'],
				'small_url'	=>$node['small_path'] . $node['small_name'],
				'middle_url'=>$node['middle_path'] . $node['middle_name'],
				'big_url'	=>$node['big_path'] . $node['big_name'],
				'id'		=>$node['id'],
				'imgauthor'	=>$node['imgauthor'],
				'published'	=>$node['published'],
				'catid'		=>$node['parent_id'],
				'imgtext'	=>$node['description'],
				'tags'		=> $node['tags']
				);
			}
		}
		
		$o = array('images'=>$images);
		echo json_encode($o);
		return;
	}
	
	public function getProp()
	{
		if($this->checkCredentials())
		{
			$login		= JRequest::getVar('login', null, 'REQUEST');
			
			$this->gallery_plg_instance->getProperties($properties, $login);
			$o = array('properties'=>$properties);
			echo json_encode($o);
			return;
		}
		else
		{
			$properties = array();
			$o = array('properties'=>$properties);
			echo json_encode($o);
			return;
		}
	}
	
	public function edit()
	{
		if($this->checkCredentials())
		{
			$to_modify = new sEditNode;
			
			$to_modify->type		= JRequest::getVar('type',null,'REQUEST');
			$to_modify->param1		= JRequest::getVar('param1',null,'REQUEST');
			$to_modify->param2		= JRequest::getVar('param2',null,'REQUEST');
			$to_modify->param3		= JRequest::getVar('param3',null, 'REQUEST', 'string', JREQUEST_ALLOWRAW);
			$to_modify->param4		= JRequest::getVar('param4',null,'REQUEST');
			$to_modify->param5		= JRequest::getVar('param5',null,'REQUEST');
			$to_modify->param6		= JRequest::getVar('param6',null,'REQUEST');
			
			$pluginName = JRequest::getVar('plugin', null, 'REQUEST');
			$currentPlugin = $this->gallery_plg_instance;
		
			if($pluginName == "Joomla.Article")
			{
				$currentPlugin = $this->content_plg_instance;
			}
			
			$out = '';
			$result = $currentPlugin->edit($to_modify, &$out);
			
			if($this->isSuccess($result))
			{
				if($to_modify->type != 'createcat')
				{
					$out = "0";
				}
				header('Content-Type: text/xml');
				echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
				echo "<response>\n";
				echo $out . "\n";
				echo "</response>\n";
				return;
			}
			else
			{
				header('Content-Type: text/xml');
				echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
				echo "<response>\n";
				echo "<error>\n";
				echo $result. "\n";
				echo "</error>\n";
				echo "</response>\n";
				return;
			}
		}
		else
		{
			return;
		}
	}
	
	public function upload()
	{
		if($this->checkCredentials())
		{
			$this->gallery_plg_instance->upload();
		}
		else
		{
			header('Content-Type: text/xml');
			echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
			echo "<response>\n";
			echo "0\n";
			echo "</response>\n";
		}
	}
	
	public function file_upload()
	{
		$title = JRequest::getVar('title', null, 'REQUEST');
		$id = JRequest::getVar('id', null, 'REQUEST');
		$catid = JRequest::getVar('catid', null, 'REQUEST');
		$output = "0";
		
		$pluginName = JRequest::getVar('plugin', null, 'REQUEST');
		$currentPlugin = $this->gallery_plg_instance;
		
		if($pluginName == "Joomla.Article")
		{
			$currentPlugin = $this->content_plg_instance;
		}
		
		
		if($this->checkCredentials())
		{
			$currentPlugin->file_upload($title, $id, $catid, &$output);
		}
		
		header('Content-Type: text/xml');
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		echo "<response>\n";
		echo $output . "\n";
		echo "</response>\n";
		
	}
	
	public function mupload()
	{
		$imgTitle = JRequest::getVar('imgTitle', null, 'REQUEST');
		$uploaderID = JRequest::getVar('uploaderID', null, 'REQUEST');
		$imgDescription = JRequest::getVar('imgDescription', null, 'REQUEST');
		$imgAuthor = JRequest::getVar('imgAuthor', null, 'REQUEST');
		$catid = JRequest::getVar('catid', null, 'REQUEST');
		$state = JRequest::getVar('state', null, 'REQUEST');
		$owner = JRequest::getVar('login', null, 'REQUEST');
		
		if($this->checkCredentials())
		{
			$this->gallery_plg_instance->mupload($imgTitle, & $imgDescription, $imgAuthor, $catid, $state, $owner, $uploaderID);
		}
		else
		{
			header('Content-Type: text/xml');
			echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
			echo "<response>\n";
			echo "0\n";
			echo "</response>\n";
		}
	}

	private function isSuccess($result)
	{
		switch($result)
		{
		case NO_ERROR:
			{
				return 1;
			}
		default:
			{
				return 0;
			}
		}
	}
}