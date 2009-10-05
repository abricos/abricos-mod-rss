<?php
/**
* @version $Id: index.php 776 2009-04-29 10:21:54Z AKuzmin $
* @package CMSBrick
* @copyright Copyright (C) 2008 CMSBrick. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

$adress = Brick::$cms->adress;

$mod = Brick::$modules->GetModule($adress->dir[1]);
if (empty($mod)){
	exit;
}

$write = new CMSRssWriter2_0();
$write->Header();
$write->Open();

$mod->RssWrite($write);

$write->Close();
?>