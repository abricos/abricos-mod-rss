<?php 
/**
 * Модуль "RSS"
 * 
 * @version $Id$
 * @package Abricos 
 * @subpackage RSS
 * @copyright Copyright (C) 2008 Abricos. All rights reservedd.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

$modRss = new CMSModuleRss();
CMSRegistry::$instance->modules->Register($modRss);

/**
 * Модуль "RSS"
 * формат запроса http://domain.com/rss/{имя модуля}/{параметры}
 *
 * @package Abricos
 * @subpackage RSS
 */
class CMSModuleRss extends CMSModule {
	
	private $_manager = null;
	
	public function __construct(){
		$this->version = "0.2.1";
		$this->name = "rss";
		$this->takelink = "rss";
		
		$this->permission = new RSSPermission($this);
	}
	
	public function GetManager(){
		if (is_null($this->_manager)){
			require_once CWD.'/modules/rss/includes/manager.php';
			$this->_manager = new RSSManager($this);
		}
		return $this->_manager;
	}
	
	public function RssMetaLink(){
		return $this->registry->adress->host."/rss/rss/";
	}
	
	public function RssWrite(CMSRssWriter2_0 $writer){
		require_once CWD.'/modules/rss/includes/grabber.php';
		
		$chanelid = $this->registry->adress->dir[2];
		
		$chanel = CMSQRss::Chanel ($this->registry->db, $chanelid);
		if (empty($chanel)){
			$chanel = CMSQRss::ChanelFirst($this->registry->db);
		}
		$grabber = new CMSRssGrabber($writer, $chanel);
		$grabber->Write();
	}
	
}

class RSSAction {
	const RSS_VIEW = 10;
	const RSS_MANAGER = 30;
	const RSS_ADMIN = 50;
}

class RSSPermission extends CMSPermission {
	
	public function RSSPermission(CMSModuleRss $module){
		
		$defRoles = array(
			new CMSRole(RSSAction::RSS_VIEW, 1, USERGROUPID_ALL),
			new CMSRole(RSSAction::RSS_MANAGER, 1, USERGROUPID_REGISTERED),
			new CMSRole(RSSAction::RSS_ADMIN, 1, USERGROUPID_ADMINISTRATOR)
		);
		
		parent::CMSPermission($module, $defRoles);
	}
	
	public function GetRoles(){
		$roles = array();
		$roles[RSSAction::RSS_VIEW] = $this->CheckAction(RSSAction::RSS_VIEW);
		$roles[RSSAction::RSS_MANAGER] = $this->CheckAction(RSSAction::RSS_MANAGER);
		$roles[RSSAction::RSS_ADMIN] = $this->CheckAction(RSSAction::RSS_ADMIN);
		return $roles;
	}
}


?>