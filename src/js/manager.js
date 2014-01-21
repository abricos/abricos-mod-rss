/**
* @version $Id$
* @package Abricos
* @copyright Copyright (C) 2008 Abricos. All rights reservedd.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	yahoo: ['tabview'],
	mod:[
	     {name: 'sys', files: ['data.js','form.js']},
	     {name: 'rss', files: ['chanel.js']}
    ]
};
Component.entryPoint = function(){
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;

	var NS = this.namespace, 
		TMG = this.template;
	
	var API = NS.API;
	
	NS.data = NS.data || new Brick.util.data.byid.DataSet('rss');
	var DATA = NS.data;
	
(function(){
	
	var ManagerWidget = function(container){
		this.init(container);
	};
	ManagerWidget.prototype = {
		init: function(container){
			var TM = TMG.build('manager'), T = TM.data, TId = TM.idManager;
			
			container.innerHTML = T['manager'];
			
			var tabView = new YAHOO.widget.TabView(TM.getEl('manager.id'));
			this.tabView = tabView;
			
			this.config = new NS.ConfigWidget(TM.getEl('manager.config'));
			this.chanel = new NS.ChanelWidget(TM.getEl('manager.chanel'));
	
			var __self = this;
			E.on(container, 'click', function(e){
				if (__self.onClick(E.getTarget(e))){ E.stopEvent(e); }
			});
		},
		onClick: function(el){
			if (this.config.onClick(el)){return true;}
			if (this.chanel.onClick(el)){return true;}
			return false;
		}
	};
	NS.ManagerWidget = ManagerWidget; 
})();

(function(){
	
	var ConfigWidget = function(container){ 
		this.init(container); 
	};
	ConfigWidget.prototype = {
		init: function(container){
			var TM = TMG.build('config,configoptionmod,configoption'), 
				T = TM.data, TId = TM.idManager;
			this._TM = TM; this._T = T; this._TId = TId;
			
			container.innerHTML = T['config'];
			
			this.tables = {
				'config': DATA.get('config', true),
				'modules': DATA.get('modules', true)
			};
			this.rows = {
				'config': this.tables['config'].getRows({'mod': 'rss'})
			};
			DATA.onComplete.subscribe(this.onDSUpdate, this, true);
			if (DATA.isFill(this.tables)){
				this.render();
			}
		},
		onDSUpdate: function(type, args){if (args[0].check(['config','modules'])){ this.render(); }},
		destroy: function(){DATA.onComplete.unsubscribe(this.onDSUpdate, this);},
		el: function(name){ return Dom.get(this._TId['config'][name]); },
		elv: function(name){ return Brick.util.Form.getValue(this.el(name)); },
		setelv: function(name, value){ Brick.util.Form.setValue(this.el(name), value); },
		onClick: function(el){
			if (el.id == this._TId['config']['bsave']){
				this.save();
				return true;
			}			
			return false;
		},
		render: function(){
			
			var TM = this._TM, T = this._T, TId = this._TId;
			var __self = this;
			var lst = T['configoptionmod'];
			this.tables['modules'].getRows().foreach(function(row){
				var di = row.cell;
				lst += TM.replace('configoption', {
					'id': di['nm'], 'tl': di['nm']
				});
			});
			this.el('default').innerHTML = lst;
			this.rows['config'].foreach(function(row){
				var di = row.cell;
				var el = __self.el(di['nm']);
				if (!el){ return; }
				__self.setelv(di['nm'], di['ph']);
			});
		},
		save: function(){
			var __self = this;
			this.rows['config'].foreach(function(row){
				var di = row.cell;
				var el = __self.el(di['nm']);
				if (!el){ return; }
				row.update({'ph': __self.elv(di['nm'])});
			});
			this.tables['config'].applyChanges();
			API.dsRequest();
		}
	};
	NS.ConfigWidget = ConfigWidget;	
})();

};

