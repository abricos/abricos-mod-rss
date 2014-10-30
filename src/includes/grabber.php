<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage RSS
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

/**
 * RSS Grabber
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
	
	
	public function __construct($chanel) {
		$this->chanelid = $chanel['id'];
		$this->chanel = $chanel;
		$this->module = Abricos::GetModule('rss')->GetManager();
		$this->Grabber();
	}
	
	private function Grabber() {
		$chanel = $this->chanel;
		$sec = $chanel ['chm'] * 60;
		$lastupdate = $chanel ['chl'] * 1;
		if ($lastupdate > 0 && TIMENOW - $sec < $lastupdate) { return; }
		$rows = RSSQuery::SourceListByChanelId(Abricos::$db, $this->chanelid);
		while (($row = Abricos::$db->fetch_array($rows))) {
			$this->GrabberSource($row);
		}
		RSSQuery::ChanelUpdateLastGrabber(Abricos::$db, $chanel['id'], TIMENOW);
	}
	
	private function GrabberSource($source) {
		$xml_parser = xml_parser_create("UTF-8");
		$rss_parser = new RSSParser($source);

		xml_set_object($xml_parser, $rss_parser);
		xml_set_element_handler($xml_parser, "startElement", "endElement" );
		xml_set_character_data_handler ($xml_parser, "characterData" );
		$fp = fopen ($source['url'], "r" );
		if (!$fp){ return; }
		while (($data = fread($fp, 4096))){
			xml_parse($xml_parser, $data, feof($fp));
		}
		fclose($fp);
		xml_parser_free($xml_parser);
	}
}

/**
 * Парсер rss новостей
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
	
	public function startElement($parser, $tagName, $attrs) {
		if ($this->insideItem) {
			$this->tag = $tagName;
		} elseif ($tagName == "ITEM") {
			$this->insideItem = true;
		}
	}
	public function endElement($parser, $tagName) {
		if ($tagName == "ITEM") {
			$pubdate = strtotime($this->dt);
			RSSQuery::RecordAppend(Abricos::$db, $this->source['id'], $this->originalLink, $this->title, $this->description, '', $pubdate, '');
			$this->title = "";
			$this->originalLink = "";
			$this->description = "";
			$this->dt = "";
			$this->insideItem = false;
		}
	}
	public function characterData($parser, $data) {
		if ($this->insideItem) {
			switch ($this->tag) {
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

?>