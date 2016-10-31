<?php
/**
 * @package Abricos
 * @subpackage RSS
 * @copyright 2008-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

/** @var RSSApp $app */

$app = Abricos::GetApp('rss');

$write = new RssWriter2_0();
$write->Header();
$write->Open();

$dir = Abricos::$adress->dir;
$isBos = isset($dir[2]) && $dir[2] === 'bos';

$app = Abricos::GetApp(isset($dir[1]) ? $dir[1] : 'rss');

if (!empty($app) && method_exists($app, 'RSS_GetItemList')){
    $data = $app->RSS_GetItemList($isBos);

    $asr = array();
    $modTitle = null;
    $isViewGroup = false;

    foreach ($data as $item){
        array_push($asr, $item->pubDate);
        if (is_null($modTitle)){
            $modTitle = $item->modTitle;
        }
        if ($modTitle != $item->modTitle){
            $isViewGroup = true;
        }
    }
    rsort($asr);
    for ($i = 0; $i < count($asr); $i++){
        $ndata = array();
        $move = false;
        foreach ($data as $item){
            if ($item->pubDate == $asr[$i] && !$move){
                $move = true;
                $write->WriteItem($item, $isViewGroup);
            } else {
                array_push($ndata, $item);
            }
        }
        $data = $ndata;
    }
}

$write->Close();
