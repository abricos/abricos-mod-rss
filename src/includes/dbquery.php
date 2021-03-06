<?php
/**
 * @package Abricos
 * @subpackage RSS
 * @copyright 2008-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/**
 * Статичные функции запросов к базе данных
 *
 * @package Abricos
 * @subpackage RSS
 */
class RSSQuery {

    public static function RecordList(Ab_Database $db, $chanelid, $count){
        $chanelid = bkint($chanelid);
        $sql = "
			SELECT 
				b.recordid as id,
				b.title as tl,
				b.link as lnk,
				b.body as body,
				b.pubdate as pdt,
				c.prefix as pfx
			FROM ".$db->prefix."rss_chanelsource a
			LEFT JOIN ".$db->prefix."rss_sourcerecord sr ON sr.sourceid=a.sourceid
			LEFT JOIN ".$db->prefix."rss_record b ON sr.recordid=b.recordid
			LEFT JOIN ".$db->prefix."rss_source c ON a.sourceid=c.sourceid
			".($chanelid > 0 ? "WHERE a.chanelid=".bkint($chanelid)."" : "")."
			ORDER BY pdt DESC
			LIMIT ".bkint($count)."
		";
        return $db->query_read($sql);
    }

    public static function RecordInfoByLink(Ab_Database $db, $link){
        $sql = "
			SELECT 
				recordid as id 
			FROM ".$db->prefix."rss_record
			WHERE link='".bkstr($link)."'
			LIMIT 1
		";
        return $db->query_first($sql);
    }

    public static function RecordAppend(Ab_Database $db, $sourceid, $link, $title, $body, $author, $pubdate, $category = ''){

        // есть ли уже запись с таким линком
        $info = RSSQuery::RecordInfoByLink($db, $link);
        $recordid = 0;
        if (empty($info)){
            $sql = "
				INSERT INTO ".$db->prefix."rss_record
				(link, title, body, author, pubdate, category) VALUES (
					'".bkstr($link)."',
					'".bkstr($title)."',
					'".bkstr($body)."',
					'".bkstr($author)."',
					'".bkint($pubdate)."',
					'".bkstr($category)."'
				)
			";
            $db->query_write($sql);
            $recordid = $db->insert_id();
        } else {
            $recordid = $info['id'];
        }
        $sql = "
			INSERT IGNORE INTO ".$db->prefix."rss_sourcerecord
			(sourceid, recordid) VALUES (
				'".bkint($sourceid)."',
				'".bkint($recordid)."'
			)
		";
        $db->query_write($sql);
    }

    public static function ChanelSourceRemoveSource(Ab_Database $db, $sourceid){
        $sql = "
			DELETE FROM ".$db->prefix."rss_chanelsource
			WHERE sourceid=".bkint($sourceid)." 
		";
        $db->query_write($sql);
    }

    public static function ChanelSourceRemove(Ab_Database $db, $chanelid){
        $sql = "
			DELETE FROM ".$db->prefix."rss_chanelsource
			WHERE chanelid=".bkint($chanelid)." 
		";
        $db->query_write($sql);
    }

    public static function ChanelSourceUpdateFromArray(Ab_Database $db, $chanelid, $sourceids){
        RSSQuery::ChanelSourceRemove($db, $chanelid);
        $arr = array();
        foreach ($sourceids as $id){
            array_push($arr, "(".bkint($chanelid).", ".bkint($id).")");
        }
        if (empty($arr)){
            return;
        }
        $sql = "
			INSERT INTO ".$db->prefix."rss_chanelsource
			(chanelid, sourceid) VALUES
			".implode(',', $arr)." 
		";
        $db->query_write($sql);
    }

    public static function ChanelSourceList(Ab_Database $db){
        $sql = "
			SELECT 
				chanelsourceid as id,
				chanelid as cid,
				sourceid as sid
			FROM ".$db->prefix."rss_chanelsource
		";
        return $db->query_read($sql);
    }

    public static function SourceRemove(Ab_Database $db, $sourceid){
        $sql = "
			DELETE FROM ".$db->prefix."rss_source
			WHERE sourceid=".bkint($sourceid)." 
		";
        $db->query_write($sql);
        RSSQuery::ChanelSourceRemoveSource($db, $sourceid);
    }

    public static function SourceUpdate(Ab_Database $db, $data){
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

    public static function SourceAppend(Ab_Database $db, $data){
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

    public static function SourceList(Ab_Database $db){
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

    public static function SourceListByChanelId(Ab_Database $db, $chanelid){
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

    public static function ChanelRemove(Ab_Database $db, $chanelid){
        $sql = "
			DELETE FROM ".$db->prefix."rss_chanel
			WHERE chanelid=".bkint($chanelid)." 
		";
        $db->query_write($sql);
        RSSQuery::ChanelSourceRemove($db, $chanelid);
    }

    public static function ChanelUpdate(Ab_Database $db, $data){
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
        RSSQuery::ChanelSourceUpdateFromArray($db, $data->id, $data->sourcelist);
    }

    public static function ChanelUpdateLastGrabber(Ab_Database $db, $chanelid, $checktime){
        $sql = "
			UPDATE ".$db->prefix."rss_chanel
			SET lastcheck=".bkint($checktime)."
			WHERE chanelid=".bkint($chanelid)." 
		";
        $db->query_write($sql);
    }

    public static function ChanelAppend(Ab_Database $db, $data){
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
        RSSQuery::ChanelSourceUpdateFromArray($db, $id, $data->sourcelist);
    }

    public static function ChanelList(Ab_Database $db){
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

    public static function Chanel(Ab_Database $db, $chanelid){
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

    public static function ChanelFirst(Ab_Database $db){
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
