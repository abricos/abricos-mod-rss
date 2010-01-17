<?php
/**
 * @version $Id: manager.php 183 2009-11-20 13:16:15Z roosit $
 * @package Abricos
 * @subpackage RSS
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

class RSSManager {
	
	/**
	 * 
	 * @var CMSModuleRss
	 */
	public $module = null;
	
	/**
	 * 
	 * @var CMSDatabase
	 */
	public $db = null;
	
	public $user = null;
	
	public function RSSManager(CMSModuleRss $module){
		
		$this->module = $module;
		$this->db = $module->registry->db;
		
		$this->user = $module->registry->session->userinfo;
	}
	/*
	public function IsAdminRole(){
		return $this->module->permission->CheckAction(CompanyAction::COMPANY_ADMIN) > 0;
	}
	
	public function IsViewRole(){
		return $this->module->permission->CheckAction(CompanyAction::COMPANY_VIEW) > 0;
	}
	/**/
	
	public function DSProcess($name, $rows){
		$p = $rows->p;
		switch ($name){
			case 'config':
				Brick::$builder->phrase->PreloadByModule($p->mod);
				foreach ($rows->r as $r){
					if ($r->f=='u'){ Brick::$builder->phrase->Set($p->mod, $r->d->nm, $r->d->ph); }
				}
				Brick::$builder->phrase->Save();
				break;
			case 'chanel':
				foreach ($rows->r as $r){
					if ($r->f=='a'){ CMSQRss::ChanelAppend(Brick::$db, $r->d); }
					if ($r->f=='u'){ CMSQRss::ChanelUpdate(Brick::$db, $r->d); }
					if ($r->f=='d'){ CMSQRss::ChanelRemove(Brick::$db, $r->d->id); }
				}
				break;
			case 'source':
				foreach ($rows->r as $r){
					if ($r->f=='a'){ CMSQRss::SourceAppend(Brick::$db, $r->d); }
					if ($r->f=='u'){ CMSQRss::SourceUpdate(Brick::$db, $r->d); }
					if ($r->f=='d'){ CMSQRss::SourceRemove(Brick::$db, $r->d->id); }
				}
				break;
		}
	}
	
	public function DSGetData($name, $rows){
		$p = $rows->p;
		switch ($name){
			case 'modules':
				Brick::$modules->RegisterAllModule();
				$arr = array();
				foreach (Brick::$modules->table as $childmod){
					if (method_exists($childmod, 'RssMetaLink')){
						$row = array();
						$row['nm'] = $childmod->name;
						array_push($arr, $row);
					}
				}
				return $arr;
			case 'config':
				Brick::$builder->phrase->PreloadByModule($tsrs->p->mod);
				return Brick::$builder->phrase->GetArray($tsrs->p->mod);
			case 'chanel':
				return CMSQRss::ChanelList(Brick::$db);
			case 'source':
				return CMSQRss::SourceList(Brick::$db);
			case 'chanelsource':
				return CMSQRss::ChanelSourceList(Brick::$db);
		}
		
		return null;
	}
	
}



/**
 * Элемент RSS записи
 * @package Abricos 
 * @subpackage RSS
 */
class CMSRssWriter2_0Item {
	
	public $title = "";
	public $link = "";
	public $description = "";
	
	public $pubDate = "";
	public $autor = "";
	public $category = array();
	
	public function __construct($title, $link, $description){
		$this->title = $title;
		$this->link = $link;
		$this->description = $description;
	}
}

/**
 * RSS writer
 * @package Abricos
 * @subpackage RSS
 */
class CMSRssWriter2_0 {
	
	public function Header(){
		header("Expires: Mon, 26 Jul 2005 15:00:00 GMT");
		header("Content-Type: text/xml; charset=utf-8");
		header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
		header("Pragma: no-cache");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	}
	
	public function Open(){
		
		$link = Brick::$cms->adress->host.Brick::$cms->adress->requestURI;
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
	
	public function WriteItem(CMSRssWriter2_0Item $item){
		print ("
		<item>");
		
		print("
			<title><![CDATA[".$item->title."]]></title>
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

/**
 * Статичные функции запросов к базе данных
 * @package Abricos
 * @subpackage RSS
 */
class CMSQRss {
	
	public static function RecordList(CMSDatabase $db, $chanelid, $count){
		$sql = "
			SELECT 
				b.recordid as id,
				b.title as tl,
				b.link as lnk,
				b.body as body,
				b.pubdate as pdt,
				c.prefix as pfx
			FROM ".$db->prefix."rss_chanelsource a
			LEFT JOIN ".$db->prefix."rss_record b ON a.sourceid=b.sourceid
			LEFT JOIN ".$db->prefix."rss_source c ON a.sourceid=c.sourceid
			WHERE a.chanelid=".bkint($chanelid)."
			ORDER BY pdt DESC
			LIMIT ".bkint($count)."
		";
		return $db->query_read($sql);
	}

	public static function RecordCheck(CMSDatabase $db, $sourceid, $link){
		$sql = "
			SELECT recordid 
			FROM ".$db->prefix."rss_record
			WHERE sourceid=".bkint($sourceid)." AND link='".bkstr($link)."'
			LIMIT 1
		";
		$row = $db->query_first($sql);
		return !empty($row);
	}
	
	public static function RecordAppend(CMSDatabase $db, $sourceid, $link, $title, $body, $author, $pubdate, $category=''){
		if (CMSQRss::RecordCheck($db, $sourceid, $link)){ return; }
		$sql = "
			INSERT INTO ".$db->prefix."rss_record
			(sourceid, link, title, body, author, pubdate, category) VALUES 
			(
				'".bkint($sourceid)."',
				'".bkstr($link)."',
				'".bkstr($title)."',
				'".bkstr($body)."',
				'".bkstr($author)."',
				'".bkint($pubdate)."',
				'".bkstr($category)."'
			)
		";
		$db->query_write($sql);
	}
	
	public static function ChanelSourceRemoveSource(CMSDatabase $db, $sourceid){
		$sql = "
			DELETE FROM ".$db->prefix."rss_chanelsource
			WHERE sourceid=".bkint($sourceid)." 
		";
		$db->query_write($sql);
	}
	
	public static function ChanelSourceRemove(CMSDatabase $db, $chanelid){
		$sql = "
			DELETE FROM ".$db->prefix."rss_chanelsource
			WHERE chanelid=".bkint($chanelid)." 
		";
		$db->query_write($sql);
	}
	
	public static function ChanelSourceUpdateFromArray(CMSDatabase $db, $chanelid, $sourceids){
		CMSQRss::ChanelSourceRemove($db, $chanelid);
		$arr = array();
		foreach ($sourceids as $id){
			array_push($arr, "(".bkint($chanelid).", ".bkint($id).")");
		}
		if (empty($arr)){ return; }
		$sql = "
			INSERT INTO ".$db->prefix."rss_chanelsource
			(chanelid, sourceid) VALUES
			".implode(',', $arr)." 
		";
		$db->query_write($sql);
	}
	
	public static function ChanelSourceList(CMSDatabase $db){
		$sql = "
			SELECT 
				chanelsourceid as id,
				chanelid as cid,
				sourceid as sid
			FROM ".$db->prefix."rss_chanelsource
		";
		return $db->query_read($sql);
	}
	
	public static function SourceRemove(CMSDatabase $db, $sourceid){
		$sql = "
			DELETE FROM ".$db->prefix."rss_source
			WHERE sourceid=".bkint($sourceid)." 
		";
		$db->query_write($sql);
		CMSQRss::ChanelSourceRemoveSource($db, $sourceid);
	}
	
	public static function SourceUpdate(CMSDatabase $db, $data){
		$sql = "
			UPDATE ".$db->prefix."rss_source
			SET
				name='".bkstr($data->nm)."',
				descript='".bkstr($data->dsc)."',
				url='".bkstr($data->url)."',
				prefix='".bkstr($data->pfx)."'
			WHERE sourceid=".bkint($data->id)." 
		";
		$db->query_write($sql);
	}
	
	public static function SourceAppend(CMSDatabase $db, $data){
		$sql = "
			INSERT INTO ".$db->prefix."rss_source
			(name, descript, url, prefix, dateline) VALUES 
			(
				'".bkstr($data->nm)."',
				'".bkstr($data->dsc)."',
				'".bkstr($data->url)."',
				'".bkstr($data->pfx)."',
				".TIMENOW."
			)
		";
		$db->query_write($sql);
	}
	
	public static function SourceList(CMSDatabase $db){
		$sql = "
			SELECT 
				sourceid as id,
				name as nm,
				descript as dsc,
				url,
				prefix as pfx
			FROM ".$db->prefix."rss_source
		";
		return $db->query_read($sql);
	}
	
	public static function SourceListByChanelId(CMSDatabase $db, $chanelid){
		$sql = "
			SELECT 
				b.sourceid as id,
				b.name as nm,
				b.descript as dsc,
				b.url,
				b.prefix as pfx
			FROM ".$db->prefix."rss_chanelsource a
			LEFT JOIN ".$db->prefix."rss_source b ON a.sourceid=b.sourceid
			WHERE a.chanelid=".bkint($chanelid)."
		";
		return $db->query_read($sql);
	}
	
	public static function ChanelRemove(CMSDatabase $db, $chanelid){
		$sql = "
			DELETE FROM ".$db->prefix."rss_chanel
			WHERE chanelid=".bkint($chanelid)." 
		";
		$db->query_write($sql);
		CMSQRss::ChanelSourceRemove($db, $chanelid);
	}
	
	public static function ChanelUpdate(CMSDatabase $db, $data){
		$sql = "
			UPDATE ".$db->prefix."rss_chanel
			SET
				name='".bkstr($data->nm)."',
				descript='".bkstr($data->dsc)."',
				checkmin=".bkint($data->chm).",
				getcount=".bkint($data->gcnt)."
			WHERE chanelid=".bkint($data->id)." 
		";
		$db->query_write($sql);
		CMSQRss::ChanelSourceUpdateFromArray($db, $data->id, $data->sourcelist);
	}
	
	public static function ChanelUpdateLastGrabber(CMSDatabase $db, $chanelid, $checktime){
		$sql = "
			UPDATE ".$db->prefix."rss_chanel
			SET lastcheck=".bkint($checktime)."
			WHERE chanelid=".bkint($chanelid)." 
		";
		$db->query_write($sql);
	}
	
	public static function ChanelAppend(CMSDatabase $db, $data){
		$sql = "
			INSERT INTO ".$db->prefix."rss_chanel
			(name, descript, checkmin, getcount, dateline) VALUES 
			(
				'".bkstr($data->nm)."',
				'".bkstr($data->dsc)."',
				".bkint($data->chm).",
				".bkint($data->gcnt).",
				".TIMENOW."
			)
		";
		$db->query_write($sql);
		$id = $db->insert_id();
		CMSQRss::ChanelSourceUpdateFromArray($db, $id, $data->sourcelist);
	}
	
	public static function ChanelList(CMSDatabase $db){
		$sql = "
			SELECT
				chanelid as id, 
				name as nm,
				descript as dsc,
				checkmin as chm,
				lastcheck as chl,
				getcount as gcnt,
				disabled as off
			FROM ".$db->prefix."rss_chanel
			ORDER BY name
		";
		return $db->query_read($sql);
	}
	
	public static function Chanel(CMSDatabase $db, $chanelid){
		$sql = "
			SELECT
				chanelid as id, 
				name as nm,
				descript as dsc,
				checkmin as chm,
				lastcheck as chl,
				getcount as gcnt,
				disabled as off
			FROM ".$db->prefix."rss_chanel
			WHERE chanelid=".bkint($chanelid)."
			LIMIT 1
		";
		return $db->query_first($sql);
	}
	
	public static function ChanelFirst(CMSDatabase $db){
		$sql = "
			SELECT
				chanelid as id, 
				name as nm,
				descript as dsc,
				checkmin as chm,
				lastcheck as chl,
				getcount as gcnt,
				disabled as off
			FROM ".$db->prefix."rss_chanel
			ORDER BY chanelid
			LIMIT 1
		";
		return $db->query_first($sql);
	}
}


?>