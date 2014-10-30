<?php 
/**
 * Модуль "RSS"
 * 
 * @package Abricos 
 * @subpackage RSS
 * @copyright Copyright (C) 2008 Abricos. All rights reservedd.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

/**
 * Модуль "RSS"
 * формат запроса http://domain.com/rss/{имя модуля}/{параметры}
 *
 * @package Abricos
 * @subpackage RSS
 */
class RSSModule extends Ab_Module {
	
	private $_manager = null;
	
	public function __construct(){
		$this->version = "0.2.6-dev";
		$this->name = "rss";
		$this->takelink = "rss";
		
		$this->permission = new RSSPermission($this);
	}
	
	/**
	 * @return RSSManager
	 */
	public function GetManager(){
		if (is_null($this->_manager)){
			require_once 'includes/manager.php';
			$this->_manager = new RSSManager($this);
		}
		return $this->_manager;
	}
	
	public function RssMetaLink(){
		return Abricos::$adress->fetch_host()."/rss/";
	}

	// если RSS без параметров, то все модули, иначе подписанный канал
	public function RSS_GetItemList(){
		$chanelid = Abricos::$adress->dir[1];
		$ret = array();
		
		if (empty($chanelid)){
			$ret = $this->RSS_GetItemListAll();
		}else{
			$manager = $this->GetManager();
			$rows = $manager->RecordList($chanelid);
			while (($row = Abricos::$db->fetch_array($rows))) {
				$title = $row['tl'];
				if (!empty($row['pfx'])){
					$title = $row['pfx'].": ".$title;
				}
				$item = new RSSItem($title, $row['lnk'], $row['body'], $row['pdt']);
				array_push($ret, $item);
			}
		}
		return $ret;
	}
	
	
	public function RSS_GetItemListAll($inBosUI = false, $onemod = ""){
		$ret = array();
		
		Abricos::$modules->RegisterAllModule();
		$modules = Abricos::$modules->GetModules();
			
		foreach ($modules as $name => $module){
			if ($name == 'rss' || $name == 'bos' || !method_exists($module, 'RSS_GetItemList')){
				continue;
			}
			if (!empty($onemod) && $name != $onemod){
				continue;
			}
			$data = $module->RSS_GetItemList($inBosUI);
			$ret = array_merge_recursive($ret, $data);
		}
		return $ret;
	}
}

class RSSAction {
	const VIEW = 10;
	const MANAGER = 30;
	const ADMIN = 50;
}

class RSSPermission extends Ab_UserPermission {
	
	public function __construct(RSSModule $module){
		
		$defRoles = array(
			new Ab_UserRole(RSSAction::VIEW, Ab_UserGroup::GUEST),
			new Ab_UserRole(RSSAction::VIEW, Ab_UserGroup::REGISTERED),
			new Ab_UserRole(RSSAction::VIEW, Ab_UserGroup::ADMIN),
			
			new Ab_UserRole(RSSAction::MANAGER, Ab_UserGroup::ADMIN),
			new Ab_UserRole(RSSAction::ADMIN, Ab_UserGroup::ADMIN)
		);

        parent::__construct($module, $defRoles);
	}
	
	public function GetRoles(){
		return array(
			RSSAction::VIEW => $this->CheckAction(RSSAction::VIEW),
			RSSAction::MANAGER => $this->CheckAction(RSSAction::MANAGER),
			RSSAction::ADMIN => $this->CheckAction(RSSAction::ADMIN)
		);
	}
}

Abricos::ModuleRegister(new RSSModule());

?>