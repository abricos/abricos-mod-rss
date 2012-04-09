<?php
/**
 * @version $Id$
 * @package Abricos
 * @subpackage RSS
 * @copyright Copyright (C) 2008 Abricos. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * @author Alexander Kuzmin (roosit@abricos.org)
 */

$manager = Abricos::GetModule('rss')->GetManager();

$write = new CMSRssWriter2_0();
$write->Header();
$write->Open();


$dir = Abricos::$adress->dir[1];
if (empty($dir)){
	$dir = "rss";
}

$mod = Abricos::GetModule($dir);
if (!empty($mod)){

	if(method_exists($mod, 'RSS_GetItemList')){
		$data = $mod->RSS_GetItemList(Abricos::$adress->dir[2] == 'bos');

		$asr = array();
		$modTitle = null;
		$isViewGroup = false;
		
		foreach($data as $item){
			array_push($asr, $item->pubDate);
			if (is_null($modTitle)){
				$modTitle = $item->modTitle;
			}
			if ($modTitle != $item->modTitle){
				$isViewGroup = true;
			}
		}
		rsort($asr);
		for ($i=0; $i<count($asr); $i++){
			$ndata = array();
			$move = false;
			foreach($data as $item){
				if ($item->pubDate == $asr[$i] && !$move){
					$move = true;
					$write->WriteItem($item, $isViewGroup);
				}else{
					array_push($ndata, $item);
				}
			}
			$data = $ndata;
		}
	}
	
}

$write->Close();

?>