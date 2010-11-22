/**
* @version $Id$
* @package Abricos
* @copyright Copyright (C) 2008 Abricos. All rights reservedd.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[
	     {name: 'user', files: ['cpanel.js']}
	]
};
Component.entryPoint = function(){
	
	if (!Brick.env.user.isAdmin()){ return; }
	
	var cp = Brick.mod.user.cp;
	
	var menuItem = new cp.MenuItem(this.moduleName);
	menuItem.icon = '/modules/sys/images/cp_icon.gif';
	menuItem.titleId = 'rss.admin.cp.title';
	menuItem.entryComponent = 'api';
	menuItem.entryPoint = 'Brick.mod.rss.API.showRSSManagerWidget';
	
	cp.MenuManager.add(menuItem);
};