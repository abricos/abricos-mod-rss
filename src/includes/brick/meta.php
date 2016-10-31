<?php
/**
 * @package Abricos
 * @subpackage RSS
 * @copyright 2008-2016 Alexander Kuzmin
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @author Alexander Kuzmin <roosit@abricos.org>
 */

$mod = Brick::$modman;
if (!method_exists($mod, 'RssMetaLink')) {

    $default = Abricos::GetModule('rss')->GetPhrases()->Get('default')->value;
    if (!empty($default)) {
        $mod = Abricos::GetModule($default);
        if (!method_exists($mod, 'RssMetaLink')) {
            return;
        }
    } else {
        return;
    }
}
$brick = Brick::$builder->brick;

$brick->content = str_replace("{v#link}", $mod->RssMetaLink(), $brick->param->var['link']);
