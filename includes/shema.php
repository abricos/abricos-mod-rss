<?php
/**
 * Схема таблиц модуля
 * @version $Id$
 * @package Abricos
 * @subpackage RSS
 * @copyright Copyright (C) 2008 Abricos. All rights reservedd.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

$charset = "CHARACTER SET 'utf8' COLLATE 'utf8_general_ci'";
$updateManager = CMSRegistry::$instance->modules->updateManager; 
$db = CMSRegistry::$instance->db;
$pfx = $db->prefix;

if ($updateManager->serverVersion == '1.0.1'){
	$updateManager->serverVersion = '0.2.1';
}

if ($updateManager->isInstall()){
	// RSS канал
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."rss_chanel (
		  `chanelid` int(10) unsigned NOT NULL auto_increment,
		  `name` varchar(100) NOT NULL default '' COMMENT 'Имя канала',
		  `descript` varchar(250) NOT NULL default '' COMMENT 'Краткое описание канала',
		  `checkmin` int(4) unsigned NOT NULL default '30' COMMENT 'Проверять каждые n минут',
		  `lastcheck` int(10) unsigned NOT NULL default '0' COMMENT 'Последняя проверка',
		  `getcount` int(3) unsigned NOT NULL default '25' COMMENT 'Отдавать пользователю кол-во последних записей',
		  `disabled` tinyint(1) unsigned NOT NULL default '0' COMMENT 'Отключить канал',
		  `dateline` int(10) unsigned NOT NULL default '0',
		  `deldate` int(10) unsigned NOT NULL default '0',
		  PRIMARY KEY  (`chanelid`)
		 )".$charset
	);

	// RSS источник
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."rss_source (
		  `sourceid` int(10) unsigned NOT NULL auto_increment,
		  `name` varchar(100) NOT NULL default '' COMMENT 'Имя',
		  `descript` varchar(250) NOT NULL default '' COMMENT 'Краткое описание',
		  `url` varchar(250) NOT NULL default '' COMMENT 'URL',
		  `prefix` varchar(100) NOT NULL default '' COMMENT 'Префикс - в общем канале',
		  `dateline` int(10) unsigned NOT NULL default '0',
		  `deldate` int(10) unsigned NOT NULL default '0',
		  PRIMARY KEY  (`sourceid`)
		 )".$charset
	);

	// принадлежность источника к каналу
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."rss_chanelsource (
		  `chanelsourceid` int(10) unsigned NOT NULL auto_increment,
		  `chanelid` int(10) unsigned NOT NULL default '0' COMMENT 'Идентификатор канала',
		  `sourceid` int(10) unsigned NOT NULL default '0' COMMENT 'Идентификатор источника',
		  PRIMARY KEY  (`chanelsourceid`)
		 )".$charset
	);

	// Прочитанные записи из источника
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."rss_record (
		  `recordid` int(10) unsigned NOT NULL auto_increment,
		  `sourceid` int(10) unsigned NOT NULL default '0' COMMENT 'Идентификатор источника',
		  `title` varchar(250) NOT NULL default '' COMMENT 'Краткое описание',
		  `link` varchar(250) NOT NULL default '' COMMENT 'Ссылка новости',
		  `body` TEXT NOT NULL COMMENT 'Тело новости',
		  `author` varchar(50) NOT NULL default '' COMMENT 'Автор',
		  `category` varchar(50) NOT NULL default '' COMMENT 'Категория',
		  `pubdate` int(10) unsigned NOT NULL default '0' COMMENT 'Дата публикации',
		  PRIMARY KEY (`recordid`)
		 )".$charset
	);
}

if ($updateManager->isUpdate('0.2.2.1')){
	CMSRegistry::$instance->modules->GetModule('rss')->permission->Install();
}

if ($updateManager->isUpdate('0.2.2.2')){
	
	require_once 'dbquery.php';
	
	
	// принадлежность записи к источнику
	$db->query_write("
		CREATE TABLE IF NOT EXISTS ".$pfx."rss_sourcerecord (
		  `sourcerecordid` int(10) unsigned NOT NULL auto_increment,
		  `sourceid` int(10) unsigned NOT NULL default '0' COMMENT 'Идентификатор источника',
		  `recordid` int(10) unsigned NOT NULL default '0' COMMENT 'Идентификатор канала',
		  PRIMARY KEY  (`sourcerecordid`),
		  KEY `sourceid` (`sourceid`),
		  UNIQUE KEY `sourcerecord` (`sourceid`, `recordid`)
		 )".$charset
	);
	
	$slist = $db->query_read("
		SELECT 
			sourceid as id
		FROM ".$db->prefix."rss_source
	");
	while (($source = $this->db->fetch_array($slist))) {
		$rlist = $db->query_read("
			SELECT 
				b.recordid as id,
				b.title as tl,
				b.link as lnk,
				b.body as body,
				b.pubdate as pdt
			FROM ".$db->prefix."rss_record b
			WHERE b.sourceid=".bkint($source['id'])."
		");
		while (($r = $this->db->fetch_array($rlist))) {
			RSSQuery::RecordAppend($db, $source['id'], $r['lnk'], $r['tl'], $r['body'], '', $r['pdt']);
		}
	}
	
	$db->query_write("ALTER TABLE `".$pfx."rss_record` DROP sourceid");
	$db->query_write("ALTER TABLE `".$pfx."rss_record` ADD UNIQUE KEY `link` ( `link` )");
}
?>