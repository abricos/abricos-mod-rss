<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage RSS
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

require_once 'dbquery.php';

class RSSManager extends Ab_ModuleManager {
	
	/**
	 * 
	 * @var RSSModule
	 */
	public $module = null;
	
	public function __construct(RSSModule $module){
		parent::__construct($module);
	}
	
	public function IsAdminRole(){
		return $this->IsRoleEnable(RSSAction::ADMIN);
	}
	
	public function IsManagerRole(){
		return $this->IsRoleEnable(RSSAction::MANAGER);
	}
	
	public function IsViewRole(){
		return $this->IsRoleEnable(RSSAction::VIEW);
	}
	
	public function DSProcess($name, $rows){
		$p = $rows->p;
		switch ($name){
			case 'config':
				foreach ($rows->r as $r){
					if ($r->f=='u'){ $this->ConfigUpdate($p->mod, $r->d->nm, $r->d->ph); }
				}
				break;
			case 'chanel':
				foreach ($rows->r as $r){
					if ($r->f=='a'){ $this->ChanelAppend($r->d); }
					if ($r->f=='u'){ $this->ChanelUpdate($r->d); }
					if ($r->f=='d'){ $this->ChanelRemove($r->d->id); }
				}
				break;
			case 'source':
				foreach ($rows->r as $r){
					if ($r->f=='a'){ $this->SourceAppend($r->d); }
					if ($r->f=='u'){ $this->SourceUpdate($r->d); }
					if ($r->f=='d'){ $this->SourceRemove($r->d->id); }
				}
				break;
		}
	}
		
	public function DSGetData($name, $rows){
		$p = $rows->p;
		switch ($name){
			case 'modules': return $this->ModuleList();
			case 'config': return $this->Config($p->mod);
			case 'chanel': return $this->ChanelList();
			case 'source': return $this->SourceList();
			case 'chanelsource': return $this->ChanelSourceList();
			case 'record': return $this->RecordList($p->chanelid);
			case 'online': return $this->Online();
		}
		
		return null;
	}
	
	public function ChanelAppend($d){
		if (!$this->IsAdminRole()){ return; }
		return RSSQuery::ChanelAppend($this->db, $d); 
	}
	
	public function ChanelUpdate($d){
		if (!$this->IsAdminRole()){ return; }
		 RSSQuery::ChanelUpdate($this->db, $d);
	}
	
	public function ChanelRemove($chanelid){
		if (!$this->IsAdminRole()){ return; }
		RSSQuery::ChanelRemove($this->db, $chanelid);
	}

	public function SourceAppend($d){
		if (!$this->IsAdminRole()){ return; }
		return RSSQuery::SourceAppend($this->db, $d);
	}
	
	public function SourceUpdate($d){
		if (!$this->IsAdminRole()){ return; }
		RSSQuery::SourceUpdate($this->db, $d);
	}
	
	public function SourceRemove($sourceid){
		if (!$this->IsAdminRole()){ return; }
		RSSQuery::SourceRemove($this->db, $sourceid);
	}
	
	public function ModuleList(){
		if (!$this->IsAdminRole()){ return; }
		$modules = Abricos::$modules;
		$modules->RegisterAllModule();
		$arr = array();
		foreach ($modules->table as $childmod){
			if (!method_exists($childmod, 'RssMetaLink')){
				continue;
			}
			$row = array();
			$row['nm'] = $childmod->name;
			array_push($arr, $row);
		}
		return $arr;
	}
	
	public function Config($mod){
		if (!$this->IsAdminRole()){ return; }
		Brick::$builder->phrase->PreloadByModule($mod);
		return Brick::$builder->phrase->GetArray($mod);
	}
	
	public function ConfigUpdate($mod, $name, $value){
		if (!$this->IsAdminRole()){ return; }
		Brick::$builder->phrase->PreloadByModule($mod);
		Brick::$builder->phrase->Set($mod, $name, $value);
		Brick::$builder->phrase->Save();
	}
	
	public function ChanelList(){
		if (!$this->IsViewRole()){ return; }
		return RSSQuery::ChanelList($this->db);
	}
	
	public function SourceList(){
		if (!$this->IsAdminRole()){ return; }
		return RSSQuery::SourceList($this->db);		
	}

	public function ChanelSourceList(){
		if (!$this->IsAdminRole()){ return; }
		return RSSQuery::ChanelSourceList($this->db);
	}
	
	private function Grabber($chanel){
		require_once 'grabber.php';
		$grabber = new RSSGrabber($chanel);
	}
	
	public function RecordList($chanelid){
		if (!$this->IsViewRole()){ return; }
		$chanel = RSSQuery::Chanel ($this->db, $chanelid);
		if (empty($chanel)){
			$chanel = RSSQuery::ChanelFirst($this->db);
		}
		if (empty($chanel)){ return; }
		
		$this->Grabber($chanel);
		
		return RSSQuery::RecordList($this->db, $chanelid, $chanel['gcnt']);
	}
	
	public function Online(){
		if (!$this->IsViewRole()){ return; }
		$chanels = RSSQuery::ChanelList($this->db);
		while (($chanel = $this->db->fetch_array($chanels))) {
			$this->Grabber($chanel);
		}
		return RSSQuery::RecordList($this->db, 0, 10);
	}
}


/**
 * Элемент RSS записи
 * 
 * @package Abricos 
 * @subpackage RSS
 */
class RSSItem {
	
	public $title = "";
	public $link = "";
	public $description = "";
	
	public $pubDate = 0;
	public $autor = "";
	public $category = array();
	public $modTitle = "";
	
	public function __construct($title, $link, $description="", $pubDate = 0){
		$this->title = $title;
		$this->link = $link;
		$this->description = $description;
		$this->pubDate = $pubDate;
	}
}

/**
 * RSS writer
 * @package Abricos
 * @subpackage RSS
 */
class RssWriter2_0 {
	
	public function Header(){
		header("Expires: Mon, 26 Jul 2005 15:00:00 GMT");
		header("Content-Type: text/xml; charset=utf-8");
		header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
		header("Pragma: no-cache");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	}
	
	public function Open(){
		
		$link = Abricos::$adress->host.Abricos::$adress->requestURI;
		print (
"<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<rss version=\"2.0\">
<channel>
	<title>".Brick::$builder->phrase->Get('sys', 'site_name')."</title>
	<link>".$link."</link>
	<description><![CDATA[]]></description>
	<language>".LNG."</language>
	<managingEditor>".Brick::$builder->phrase->Get('sys', 'admin_mail')."</managingEditor>
	<generator>".Brick::$builder->phrase->Get('sys', 'admin_mail')."</generator>
	<pubDate>". gmdate("D, d M Y H:i:s") ."</pubDate>
"
		);		
	}
	
	public function WriteItem(RSSItem $item, $addModTitle = false){
		print ("
		<item>");
		
		$title = $item->title;
		if ($addModTitle && !empty($item->modTitle)){
			$title = "[".$item->modTitle."] ".$title;
		}
		
		print("
			<title><![CDATA[".$title."]]></title>
			<guid isPermaLink=\"true\">".$item->link."</guid>
			<link>".$item->link."</link>			
			<description><![CDATA[".$item->description."]]></description>");
		if ($item->pubDate > 0){
			print("
			<pubDate>".gmdate("D, d M Y H:i:s", $item->pubDate)."</pubDate>");
		}
		if (!empty($item->autor)){
			print("
			<author>".$item->autor."</author>");
		}
		if (!empty($item->category)){
			foreach($item->category as $category){
				print("<category>".$category."</category>");
			}
		}
		print ("
		</item>");
	}
	
	public function Close(){
		print ("
</channel>
</rss>");
		exit;
	}
}



?>