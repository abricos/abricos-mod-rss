/*
 @version $Id$
 @package Abricos
 @copyright Copyright (C) 2008 Abricos All rights reserved.
 @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

var Component = new Brick.Component();
Component.requires = {
    mod: [
        {name: 'sys', files: ['data.js']},
        {name: 'online', files: ['manager.js']}
    ]
};
Component.entryPoint = function(){

    var Dom = YAHOO.util.Dom,
        E = YAHOO.util.Event,
        L = YAHOO.lang;

    var NS = this.namespace,
        TMG = this.template,
        API = NS.API;

    if (!NS.data){
        NS.data = new Brick.util.data.byid.DataSet('rss');
    }

    var buildTemplate = function(w, templates){
        var TM = TMG.build(templates), T = TM.data, TId = TM.idManager;
        w._TM = TM;
        w._T = T;
        w._TId = TId;
    };

    var ONL = Brick.mod.online;

    var RSSMainOnline = function(){
        RSSMainOnline.superclass.constructor.call(this, 'rss', 'main');
    };
    YAHOO.extend(RSSMainOnline, ONL.OnlineElement, {
        onLoad: function(){
            buildTemplate(this, 'widget,title,row,header');
            var TM = this._TM, T = this._T;

            this.setTitleValue(T['title']);
            this.getBody().innerHTML = TM.replace('widget');

            this.tables = new Brick.mod.sys.TablesManager(NS.data, ['online'], {'owner': this});
            this.tables.request();
        },
        destroy: function(){
            this.tables.destroy();
            RSSMainOnline.superclass.destroy.call(this);
        },
        onDataLoadWait: function(tables){
            this.showWait();
        },
        onDataLoadComplete: function(tables){
            this.hideWait();
            var TM = this._TM, lst = "";

            var bs = {};

            tables.get('online').getRows().foreach(function(row){

                var di = row.cell,
                    h = di['pfx'];
                if (!bs[h]){
                    bs[h] = [];
                }
                var a = bs[h];
                a[a.length] = di;
            });

            for (var n in bs){
                lst += TM.replace('header', {
                    'tl': n
                });
                var a = bs[n];
                for (var i = 0; i < a.length; i++){
                    var di = a[i];
                    lst += TM.replace('row', {
                        'id': di['id'],
                        'pfx': di['pfx'],
                        'dl': Brick.dateExt.convert(di['pdt'], 1),
                        'tl': di['tl'],
                        'link': di['lnk']
                    });
                }
            }

            TM.getEl('widget.list').innerHTML = lst;
        },
        refresh: function(){
            this.showWait();
            this.tables.get('online').clear();
            this.tables.request();
        }

    });
    ONL.manager.register(new RSSMainOnline());

};