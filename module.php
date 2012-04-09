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
		$this->version = "0.2.4";
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
		return $this->registry->adress->host."/rss/";
	}

	// если RSS без параметров, то все модули, иначе подписанный канал
	public function RSS_GetItemList(){
		$chanelid = $this->registry->adress->dir[1];
		$ret = array();
		
		if (empty($chanelid)){
			$ret = $this->RSS_GetItemListAll();
		}else{
			$manager = $this->GetManager();
			$rows = $manager->RecordList($chanelid);
			while (($row = $this->registry->db->fetch_array($rows))) {
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
		
		Abricos::$instance->modules->RegisterAllModule();
		$modules = Abricos::$instance->modules->GetModules();
			
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

Abricos::ModuleRegister(new RSSModule());

?>