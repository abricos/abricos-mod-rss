/**
* @version $Id$
* @package Abricos
* @copyright Copyright (C) 2008 Abricos. All rights reservedd.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	mod:[{name: 'sys', files: ['data.js', 'form.js']}]
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
	
	var ChanelWidget = function(container){ 
		this.init(container); 
	};
	ChanelWidget.prototype = {
		init: function(container){
			var TM = TMG.build('widget,table,tablesource,widget,row,rowsource'), 
				T = TM.data, TId = TM.idManager;
			this._TM = TM; this._T = T; this._TId = TId;

			container.innerHTML = T['widget'];
		
			this.tables = {
				'chanel': DATA.get('chanel', true),
				'source': DATA.get('source', true),
				'chanelsource': DATA.get('chanelsource', true)
			};
			DATA.onComplete.subscribe(this.onDSComplete, this, true);
			if (DATA.isFill(this.tables)){
				this.render();
			}
		},
		onDSComplete: function(type, args){ 
			if (args[0].check(['chanel'])){ this.renderChanel(); } 
			if (args[0].check(['source'])){ this.renderSource(); } 
		},
		renderChanel: function(){
			var TM = this._TM, T = this._T, TId = this._TId;

			var lst = "", di, rows = this.tables['chanel'].getRows();
			var url = '/rss/rss/';
			rows.foreach(function(row){
				di = row.cell;
				lst += TM.replace('row', {
					'id': di['id'], 'nm': di['nm'], 
					'url': url+di['id']+'/', 
					'chm': di['chm'],
					'chl': Brick.dateExt.convert(di['chl'])
				});
			});
			TM.getEl('widget.table').innerHTML = TM.replace('table', {'rows': lst});
		},
		renderSource: function(){
			var TM = this._TM, T = this._T, TId = this._TId;
			
			var lst = "", di, rows = this.tables['source'].getRows();
			rows.foreach(function(row){
				di = row.cell;
				lst += TM.replace('rowsource', {
					'id': di['id'], 'nm': di['nm'], 'url': di['url']
				});
			});
			TM.getEl('widget.tablesource').innerHTML = TM.replace('tablesource', {'rows': lst});
		},
		onClick: function(el){
			var TId = this._TId;
			switch(el.id){
			case TId['widget']['addchanel']:
				new NS.ChanelEditorPanel(DATA.get('chanel').newRow());
				return true;
			case TId['widget']['addsource']:
				new NS.SourceEditorPanel(DATA.get('source').newRow());
				return true;
			}
			var prefix = el.id.replace(/([0-9]+$)/, '');
			var numid = el.id.replace(prefix, "");
			
			switch(prefix){
			case (TId['row']['edit']+'-'): 
				new NS.ChanelEditorPanel(DATA.get('chanel').getRows().getById(numid)); 
				return true;
			case (TId['row']['remove']+'-'): 
				DATA.get('chanel').getRows().getById(numid).remove();
				DATA.get('chanel').applyChanges();
				DATA.request();
				return true;
			case (TId['rowsource']['edit']+'-'): 
				new NS.SourceEditorPanel(DATA.get('source').getRows().getById(numid)); 
				return true;
			case (TId['rowsource']['remove']+'-'): 
				DATA.get('source').getRows().getById(numid).remove();
				DATA.get('source').applyChanges();
				DATA.request();
				return true;
			}
			return false;
		}
	};
	NS.ChanelWidget = ChanelWidget;
})();

(function(){

	var ChanelEditorPanel = function(row){
		this.row = row;
		ChanelEditorPanel.superclass.constructor.call(this, {
			modal: true, fixedcenter: true
		});
	};
	YAHOO.extend(ChanelEditorPanel, Brick.widget.Panel, {
		el: function(name){ return Dom.get(this._TId['editorchanel'][name]); },
		elv: function(name){ return Brick.util.Form.getValue(this.el(name)); },
		setel: function(el, value){ Brick.util.Form.setValue(el, value); },
		setelv: function(name, value){ Brick.util.Form.setValue(this.el(name), value); },
		initTemplate: function(){
			var TM = TMG.build('editorchanel,option,edchtable,edchrow'), T = TM.data, TId = TM.idManager;
			this._TM = TM; this._T = T; this._TId = TId;

			var lst = "";
			DATA.get('source').getRows().foreach(function(row){
				lst += TM.replace('option', {'id': row.id,'nm': row.cell['nm']});
			});
			return TM.replace('editorchanel', {'nslist': lst});
		},
		onLoad: function(){
			this.el('badd').style.display = this.row.isNew() ? '' : 'none';
			this.el('bsave').style.display = this.row.isNew() ? 'none' : '';
			
			var di = this.row.cell;
			this.setelv('nm', di['nm']);
			this.setelv('dsc', di['dsc']);
			this.setelv('chm', di['chm']);
			this.setelv('gcnt', di['gcnt']);
			
			var rowchanelsource = DATA.get('chanelsource').getRows().filter({'cid': di['id']});
			var rowsource = DATA.get('source').getRows();
			var sourcelist = {};
			rowchanelsource.foreach(function(row){
				var fr = rowsource.getById(row.cell['sid']);
				if (L.isNull(fr)){ return; }
				sourcelist[fr.id] = fr;
			});
			this.sourcelist = sourcelist;
			this.renderSourceList();
		},
		renderSourceList: function(){
			var TM = this._TM, T = this._T, TId = this._TId;

			var lst = "", di;
			for (var id in this.sourcelist){
				di = this.sourcelist[id].cell;
				lst += TM.replace('edchrow', {'id': di['id'], 'nm': di['nm']});
			}
			this.el('table').innerHTML = TM.replace('edchtable', {'rows': lst});
		},
		addSource: function(){
			var id = this.elv('newsource');
			if (!id){ return; }
			var row = DATA.get('source').getRows().getById(id);
			if (L.isNull(row)){ return; }
			this.sourcelist[id] = row;
			this.renderSourceList();
		},
		removeSource: function(removeid){
			var newlist = {};
			for (var id in this.sourcelist){
				if (id != removeid){newlist[id] = this.sourcelist[id]; }
			}
			this.sourcelist = newlist;
			this.renderSourceList();
		},
		onClick: function(el){
			var tp = this._TId['editorchanel']; 
			switch(el.id){
			case tp['baddsource']: this.addSource(); return true;
			case tp['badd']: this.save(); return true;
			case tp['bsave']: this.save(); return true;
			case tp['bcancel']: this.close(); return true;
			}
			var prefix = el.id.replace(/([0-9]+$)/, '');
			var numid = el.id.replace(prefix, "");
			switch(prefix){
			case (this._TId['edchrow']['remove']+'-'): this.removeSource(numid); return true; 
			}
			return false;
		},
		save: function(){
			var row = this.row, table = DATA.get('chanel');
			var slist = [];
			for (var id in this.sourcelist){
				slist[slist.length] = id;
			}
			row.update({
				'nm': this.elv('nm'),
				'dsc': this.elv('dsc'),
				'chm': this.elv('chm'),
				'gcnt': this.elv('gcnt'),
				'sourcelist': slist
			});
			if (row.isNew()){ table.getRows().add(row); }
			table.applyChanges();
			
			var ctable = DATA.get('chanelsource');
			ctable.getRows().clear();
			ctable.applyChanges();
			
			DATA.request();
			this.close();
		}
	});
	NS.ChanelEditorPanel = ChanelEditorPanel;
	
})();

(function(){

	
	var SourceEditorPanel = function(row){
		this.row = row;
		SourceEditorPanel.superclass.constructor.call(this, {
			modal: true, fixedcenter: true
		});
	};
	YAHOO.extend(SourceEditorPanel, Brick.widget.Panel, {
		el: function(name){ return Dom.get(this._TId['editorsource'][name]); },
		elv: function(name){ return Brick.util.Form.getValue(this.el(name)); },
		setel: function(el, value){ Brick.util.Form.setValue(el, value); },
		setelv: function(name, value){ Brick.util.Form.setValue(this.el(name), value); },
		initTemplate: function(){
			var TM = TMG.build('editorsource'), T = TM.data, TId = TM.idManager;
			this._TM = TM; this._T = T; this._TId = TId;
			return T['editorsource'];
		},
		onLoad: function(){
			var di = this.row.cell;
			this.setelv('nm', di['nm']);
			this.setelv('url', di['url']);
			this.setelv('dsc', di['dsc']);
			this.setelv('pfx', di['pfx']);
			this.el('badd').style.display = this.row.isNew() ? '' : 'none';
			this.el('bsave').style.display = this.row.isNew() ? 'none' : '';
		},
		onClick: function(el){
			var tp = this._TId['editorsource']; 
			switch(el.id){
			case tp['badd']: 
			case tp['bsave']: this.save(); return true;
			case tp['bcancel']: this.close(); return true;
			}
			return false;
		},
		save: function(){
			var row = this.row, table = DATA.get('source');
			row.update({
				'nm': this.elv('nm'),
				'url': this.elv('url'),
				'dsc': this.elv('dsc'),
				'pfx': this.elv('pfx')
			});
			if (row.isNew()){ table.getRows().add(row); }
			table.applyChanges();
			DATA.request();
			this.close();
		}
	});
	NS.SourceEditorPanel = SourceEditorPanel;
	
})();
};
