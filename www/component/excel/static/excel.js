if (typeof require != 'undefined') {
	require("tabs.js");
	require("hscroll_bar.js");
	require("vscroll_bar.js");
}

function Excel(container, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;

	this.sheets = [];
	this.addSheet = function(name, icon, onready) {
		var sheet = new ExcelSheet(name, icon, onready);
		this.sheets.push(sheet);
		var tab = container.widget.addTab(name, icon, sheet.container);
		sheet.tab = tab;
		tab.sheet = sheet;
	};
	
	this._layout = function() {
		if (t.tabs.selected != -1)
			t.tabs.tabs[t.tabs.selected].sheet._layout();
	};
	
	require("tabs.js",function(){
		t.tabs = new tabs(container, true);
		if (onready) onready();
	});
	
	addLayoutEvent(container, function() { t._layout(); });
}

function ExcelSheet(name, icon, onready) {
	var t=this;
	this.name = name;
	this.icon = icon;
	
	this._init = function() {
		this.container = document.createElement("DIV");
		this.container.style.position = "relative";
		this.content = document.createElement("DIV");
		this.content.style.overflow = "hidden";
		this.content.style.position = "relative";
		this.content.style.top = "0px";
		this.content.style.left = "0px";
		this.container.appendChild(this.content);
		var t=this;
		require("hscroll_bar.js", function() {
			t.hscroll_bar = new hscroll_bar();
			t.hscroll_bar.element.style.position = "absolute";
			t.container.appendChild(t.hscroll_bar.element);
			if (t.vscroll_bar && onready) onready();
		});
		require("vscroll_bar.js", function() {
			t.vscroll_bar = new vscroll_bar();
			t.vscroll_bar.element.style.position = "absolute";
			t.container.appendChild(t.vscroll_bar.element);
			if (t.hscroll_bar && onready) onready();
		});
		this.columns = [];
		for (var i = 0; i < 250; ++i)
			this.columns.push(new ExcelSheetColumn(this, i));
	};
	
	this.layout = function() {
		if (!this.hscroll_bar || !this.vscroll_bar) {
			setTimeout(function(){t.layout();},1);
			return;
		}
		var w = this.container.clientWidth;
		var h = this.container.clientHeight;
		var hsb_h = this.hscroll_bar.element.offsetHeight;
		var vsb_w = this.vscroll_bar.element.offsetWidth;
		setHeight(this.content, h-hsb_h);
		setHeight(this.vscroll_bar.element, h-hsb_h);
		setWidth(this.content, w-vsb_w);
		setWidth(this.hscroll_bar.element, w-vsb_w);
		this.hscroll_bar.element.style.left = "0px";
		this.hscroll_bar.element.style.top = (h-hsb_h)+"px";
		this.vscroll_bar.element.style.top = "0px";
		this.vscroll_bar.element.style.left = (w-vsb_w)+"px";
		fireLayoutEventFor(this.vscroll_bar.element);
		fireLayoutEventFor(this.hscroll_bar.element);
	};
	
	this._init();
	addLayoutEvent(this.container, function() { t.layout(); });
}

function getExcelColumnName(index) {
	var name = String.fromCharCode("A".charCodeAt(0)+(index%26));
	index = Math.floor(index/26);
	while (index > 0) {
		name = String.fromCharCode("A".charCodeAt(0)+(index%26)-1)+name;
		index = Math.floor(index/26);
	};
	return name;
}

function ExcelSheetColumn(sheet, index) {
	var t=this;
	this.sheet = sheet;
	this.index = index;
	this.name = getExcelColumnName(index);
	this.width = 35;
	
	this._init = function() {
		this.header = document.createElement("DIV");
		this.header.style.borderBottom = "1px solid black";
		this.header.style.borderLeft = "1px solid black";
		this.header.style.backgroundColor = "#D0D0D0";
		this.header.style.textAlign = "center";
		this.header.style.verticalAlign = "middle";
		this.header.style.fontWeight = "bold";
		this.header.style.height = "16px";
		this.header.style.width = this.width+"px";
		this.header.style.top = "0px";
		var x = 50;
		for (var i = 0 ; i < index; ++i) x += sheet.columns[i].width+1;
		this.header.style.left = x+"px";
		this.header.style.position = "absolute";
		this.header.innerHTML = this.name;
		this.sheet.content.appendChild(this.header);
	};
	this._init();
}