<?php
/**
 * @package Abricos
 * @subpackage RSS
 * @copyright 2008-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

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

    public function __construct($title, $link, $description = "", $pubDate = 0){
        $this->title = $title;
        $this->link = $link;
        $this->description = $description;
        $this->pubDate = $pubDate;
    }
}

/**
 * RSS writer
 *
 * @package Abricos
 * @subpackage RSS
 */
class RssWriter2_0 {

    public function Header(){
        header("Expires: Mon, 26 Jul 2005 15:00:00 GMT");
        header("Content-Type: text/xml; charset=utf-8");
        header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
    }

    public function Open(){

        $link = Abricos::$adress->host.Abricos::$adress->requestURI;
        print ("<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<rss version=\"2.0\">
<channel>
	<title>".SystemModule::$instance->GetPhrases()->Get('site_name')."</title>
	<link>".$link."</link>
	<description><![CDATA[]]></description>
	<language>".Abricos::$LNG."</language>
	<managingEditor>".SystemModule::$instance->GetPhrases()->Get('admin_mail')."</managingEditor>
	<generator>".SystemModule::$instance->GetPhrases()->Get('admin_mail')."</generator>
	<pubDate>".gmdate("D, d M Y H:i:s")."</pubDate>
");
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
            foreach ($item->category as $category){
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
 * RSS Grabber
 *
 * @package Abricos
 * @subpackage RSS
 */
class RSSGrabber {

    /**
     * @var Ab_Database
     */
    public $db = null;

    public $chanelid = 0;

    public $chanel = null;

    /**
     * @var RSSManager
     */
    public $manager = null;


    public function __construct($chanel){
        $this->chanelid = $chanel['id'];
        $this->chanel = $chanel;
        $this->module = Abricos::GetModule('rss')->GetManager();
        $this->Grabber();
    }

    private function Grabber(){
        $chanel = $this->chanel;
        $sec = $chanel ['chm'] * 60;
        $lastupdate = $chanel ['chl'] * 1;
        if ($lastupdate > 0 && TIMENOW - $sec < $lastupdate){
            return;
        }
        $rows = RSSQuery::SourceListByChanelId(Abricos::$db, $this->chanelid);
        while (($row = Abricos::$db->fetch_array($rows))){
            $this->GrabberSource($row);
        }
        RSSQuery::ChanelUpdateLastGrabber(Abricos::$db, $chanel['id'], TIMENOW);
    }

    private function GrabberSource($source){
        $xml_parser = xml_parser_create("UTF-8");
        $rss_parser = new RSSParser($source);

        xml_set_object($xml_parser, $rss_parser);
        xml_set_element_handler($xml_parser, "startElement", "endElement");
        xml_set_character_data_handler($xml_parser, "characterData");
        $fp = fopen($source['url'], "r");
        if (!$fp){
            return;
        }
        while (($data = fread($fp, 4096))){
            xml_parse($xml_parser, $data, feof($fp));
        }
        fclose($fp);
        xml_parser_free($xml_parser);
    }
}

/**
 * Парсер rss новостей
 *
 * @package Abricos
 * @subpackage RSS
 */
class RSSParser {

    public $insideItem = false;
    public $tag = "";
    public $title = "";
    public $description = "";
    public $originalLink = "";
    public $dt = "";

    public $source = null;

    public function __construct($source){
        $this->source = $source;
    }

    public function startElement($parser, $tagName, $attrs){
        if ($this->insideItem){
            $this->tag = $tagName;
        } elseif ($tagName == "ITEM") {
            $this->insideItem = true;
        }
    }

    public function endElement($parser, $tagName){
        if ($tagName == "ITEM"){
            $pubdate = strtotime($this->dt);
            RSSQuery::RecordAppend(Abricos::$db, $this->source['id'], $this->originalLink, $this->title, $this->description, '', $pubdate, '');
            $this->title = "";
            $this->originalLink = "";
            $this->description = "";
            $this->dt = "";
            $this->insideItem = false;
        }
    }

    public function characterData($parser, $data){
        if ($this->insideItem){
            switch ($this->tag){
                case "TITLE" :
                    $this->title .= $data;
                    break;
                case "DESCRIPTION" :
                    $this->description .= $data;
                    break;
                case "LINK" :
                    $this->originalLink .= $data;
                    break;
                case "PUBDATE" :
                    $this->dt .= $data;
                    break;
            }
        }
    }
}


