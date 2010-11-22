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

$modRss = new RSSModule();
CMSRegistry::$instance->modules->Register($modRss);

/**
 * Модуль "RSS"
 * формат запроса http://domain.com/rss/{имя модуля}/{параметры}
 *
 * @package Abricos
 * @subpackage RSS
 */
class RSSModule extends CMSModule {
	
	private $_manager = null;
	
	public function __construct(){
		$this->version = "0.2.2.1";
		$this->name = "rss";
		$this->takelink = "rss";
		
		$this->permission = new RSSPermission($this);
	}
	
	/**
	 * @return RSSManager
	 */
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
		$chanelid = $this->registry->adress->dir[2];
		$manager = $this->GetManager();
		$manager->RSSWrite($writer, $chanelid);
	}
	
}

class RSSAction {
	const VIEW = 10;
	const MANAGER = 30;
	const ADMIN = 50;
}

class RSSPermission extends CMSPermission {
	
	public function RSSPermission(RSSModule $module){
		
		$defRoles = array(
			new CMSRole(RSSAction::VIEW, 1, User::UG_GUEST),
			new CMSRole(RSSAction::VIEW, 1, User::UG_REGISTERED),
			new CMSRole(RSSAction::VIEW, 1, User::UG_ADMIN),
			
			new CMSRole(RSSAction::MANAGER, 1, User::UG_ADMIN),
			new CMSRole(RSSAction::ADMIN, 1, User::UG_ADMIN)
		);
		
		parent::CMSPermission($module, $defRoles);
	}
	
	public function GetRoles(){
		return array(
			RSSAction::VIEW => $this->CheckAction(RSSAction::VIEW),
			RSSAction::MANAGER => $this->CheckAction(RSSAction::MANAGER),
			RSSAction::ADMIN => $this->CheckAction(RSSAction::ADMIN)
		);
	}
}


?>