<?php
/**
 * $Id: default.php 11917 2009-05-29 19:37:05Z ian $
 */
defined( '_JEXEC' ) or die( 'Restricted access' );

		$user = &JFactory::getUser(); 
		$db = & JFactory::getDBO();
		$db->setQuery("SELECT session_id FROM #__session WHERE userid=".$user->get('id')." AND client_id=0 LIMIT 1");
		$phpsessid = $db->loadResult();
				

		$component = JComponentHelper::getComponent( 'com_jmediamanager' );
		$jmmParams = new JParameter($component->params);
		
		$head = '<script type="text/javascript" src="' . JURI::base() . 'components/com_jmediamanager/views/browser/tmpl/swfobject.js"></script>
		<script type="text/javascript"> 
		<!-- For version detection, set to min. required Flash Player version, or 0 (or 0.0.0), for no version detection. --> 
            var swfVersionStr = "10.0.0";
            <!-- To use express install, set to playerProductInstall.swf, otherwise the empty string. -->
            var xiSwfUrlStr = "' . JURI::base() . 'components/com_jmediamanager/views/browser/tmpl/playerProductInstall.swf";
           var flashvars = {domainurl: "' . JURI::base() 
							.'", username: "' . $user->get("username") 
							.'", phpsessid: "'. $phpsessid 
							.'", bkgrcolor: "' . ($jmmParams->get('flash_panel_background') ? $jmmParams->get('flash_panel_background') : "0xFFFFFF") 
							.'", wincolor: "' . ($jmmParams->get('flash_panel_win_color') ? $jmmParams->get('flash_panel_win_color') :  "0xEAEAEA")
							.'", fontcolor: "' . ($jmmParams->get('flash_panel_font_color') ? $jmmParams->get('flash_panel_font_color') :  "0x222222")
							.'", lang: "' 	. ($jmmParams->get('default_language') ? $jmmParams->get('default_language') :  "en_US") .'"};
            var params = {};
            params.quality = "high";
            params.bgcolor = "#FFFFFF";
            params.allowscriptaccess = "sameDomain";
            params.allowfullscreen = "true";
            var attributes = {};
            attributes.id = "JMediaManager";
            attributes.name = "JMediaManager";
            attributes.align = "middle";
            swfobject.embedSWF(
                "' . JURI::base() . 'components/com_jmediamanager/views/browser/tmpl/JMediaManager.swf", "flashContent", 
               "' . ($jmmParams->get("flash_panel_width")? $jmmParams->get("flash_panel_width") : "100%")  .'", "' . ($jmmParams->get("flash_panel_height")? $jmmParams->get("flash_panel_height") : "650px") . '", 
                swfVersionStr, xiSwfUrlStr, 
                flashvars, params, attributes);
			<!-- JavaScript enabled so display the flashContent div in case it is not replaced with a swf object. -->
			swfobject.createCSS("#flashContent", "text-align:center;");
			//alert(flashvars.phpsessid);
        </script>';
		global $mainframe;
		$mainframe->addCustomHeadTag($head);
		?>
       
        <div id="flashContent" style="height:650px; text-align: center;">
        	<p>
	        	<?php echo  JText::_('NO_FLASH');?>
			</p>
			<script type="text/javascript"> 
				var pageHost = ((document.location.protocol == "https:") ? "https://" :	"http://"); 
				document.write("<a href=\'http://www.adobe.com/go/getflashplayer\'><img src=\'" 
								+ pageHost + "www.adobe.com/images/shared/download_buttons/get_flash_player.gif\' alt=\'Get Adobe Flash player\' /></a>" ); 
			</script> 
        </div>
	   	
       	<noscript>
            <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="100%" height="100%" id="JMediaManager">
                <param name="movie" value="' . JURI::base() . 'components/com_jmediamanager/views/browser/tmpl/JMediaManager.swf" />
                <param name="quality" value="high" />
                <param name="bgcolor" value="#ffffff" />
                <param name="allowScriptAccess" value="sameDomain" />
                <param name="allowFullScreen" value="true" />
                <!--[if !IE]>-->
                <object type="application/x-shockwave-flash" data="' . JURI::base() . 'components/com_jmediamanager/views/browser/tmpl/JMediaManager.swf" width="500px" height="500px">
                    <param name="quality" value="high" />
                    <param name="bgcolor" value="#ffffff" />
                    <param name="allowScriptAccess" value="sameDomain" />
                    <param name="allowFullScreen" value="true" />
                <!--<![endif]-->
                <!--[if gte IE 6]>-->
                	<p> 
                		<?php echo  JText::_('NO_FLASH');?>
                	</p>
                <!--<![endif]-->
                    <a href="http://www.adobe.com/go/getflashplayer">
                        <img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash Player" />
                    </a>
                <!--[if !IE]>-->
                </object>
                <!--<![endif]-->
            </object>
	    </noscript>