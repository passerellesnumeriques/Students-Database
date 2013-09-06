if (typeof require != 'undefined') {
	require("tabs.js");
}

function Excel(container, onready) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;

	this.sheets = [];
	this.addSheet = function(name, icon, columns, rows, onready) {
		var sheet = new ExcelSheet(name, icon, columns, rows, onready);
		this.sheets.push(sheet);
		var tab = container.widget.addTab(name, icon, sheet.container);
		sheet.tab = tab;
		tab.sheet = sheet;
	};
	
	this._layout = function() {
		if (t.tabs.selected != -1)
			t.tabs.tabs[t.tabs.selected].sheet.layout();
	};
	
	require("tabs.js",function(){
		t.tabs = new tabs(container, true);
		if (onready) onready(t);
	});
	
	addLayoutEvent(container, function() { t._layout(); });
}

function ExcelSheet(name, icon, columns, rows, onready) {
	var t=this;
	this.name = name;
	this.icon = icon;
	
	this.getCell = function(col, row) { return this.cells[col][row]; };
	this.getColumn = function(col) { return this.columns[col]; };
	this.getRow = function(row) { return this.rows[row]; };
	
	this._init = function() {
		this.container = document.createElement("DIV");
		this.container.style.position = "relative";
		this.content = document.createElement("DIV");
		this.content.style.position = "relative";
		this.content.style.top = "0px";
		this.content.style.left = "0px";
		this.container.appendChild(this.content);
		// create corner
		this.corner = document.createElement("DIV");
		this.corner.style.width = "50px";
		this.corner.style.height = "16px";
		this.corner.style.left = "0px";
		this.corner.style.top = "0px";
		this.corner.style.position = "absolute";
		this.corner.style.zIndex=5;
		this.corner.style.backgroundColor = "#D0D0F0";
		this.corner.style.borderRight = "1px solid #808080";
		this.corner.style.borderBottom = "1px solid #808080";
		this.content.appendChild(this.corner);
		this.column_headers_container = document.createElement("DIV");
		this.content.appendChild(this.column_headers_container);
		this.column_headers_container.style.position = "absolute";
		this.column_headers_container.style.top = "0px";
		this.column_headers_container.style.left = "50px";
		this.row_headers_container = document.createElement("DIV");
		this.content.appendChild(this.row_headers_container);
		this.row_headers_container.style.position = "absolute";
		this.row_headers_container.style.top = "16px";
		this.row_headers_container.style.left = "0px";
		this.table = document.createElement("TABLE"); this.content.appendChild(this.table);
		this.table.style.position = "absolute";
		this.table.style.left = "50px";
		this.table.style.top = "16px";
		this.table.style.border = "1px solid black";
		this.table.style.borderCollapse = "collapse";
		this.table.style.borderSpacing = "0px";
		this.table.style.tableLayout = "fixed";
		// create columns and rows
		this.columns = [];
		this.rows = [];
		this.cells = [];
		for (var i = 0; i < columns; ++i)
			this.addColumn();
		for (var i = 0; i < rows; ++i)
			this.addRow();
		var t=this;
		this.content.style.overflow = "auto";
		this.last_scroll_left = this.content.scrollLeft;
		this.last_scroll_top = this.content.scrollTop;
		this.content.onscroll = function(ev) {
			if (t.content.scrollLeft != t.last_scroll_left) {
				t.row_headers_container.style.left = t.content.scrollLeft+"px";
				t.corner.style.left = t.content.scrollLeft+"px";
				t.last_scroll_left = t.content.scrollLeft;
			}
			if (t.content.scrollTop != t.last_scroll_top) {
				t.column_headers_container.style.top = t.content.scrollTop+"px";
				t.corner.style.top = t.content.scrollTop+"px";
				t.last_scroll_top = t.content.scrollTop;
			}
		};
		this.layout();
		if (onready) onready(this);
	};
	
	this.layout = function() {
		this.content.style.width = this.container.clientWidth+"px";
		this.content.style.height = this.container.clientHeight+"px";
	};
	
	this.addColumn = function(index) {
		if (typeof index == 'undefined' || index >= this.columns.length) {
			this.columns.push(new ExcelSheetColumn(this, this.columns.length));
			var cells = [];
			for (var i = 0; i < this.rows.length; ++i)
				cells.push(new ExcelSheetCell(this, this.columns.length-1, i));
			this.cells.push(cells);
		} else {
			// TODO move data and insert column and change name of next columns
		}
		if (this.cursor == null && this.cells.length > 0 && this.cells[0].length > 0)
			this.cursor = new ExcelSheetCursor(this);
		this.layout();
	};
	
	this.addRow = function(index) {
		if (typeof index == 'undefined' || index >= this.rows.length) {
			this.rows.push(new ExcelSheetRow(this, this.rows.length));
			for (var i = 0; i < this.columns.length; ++i)
				this.cells[i].push(new ExcelSheetCell(this, i, this.rows.length-1));
		} else {
			// TODO move data and insert row and change name of next rows
		}
		if (this.cursor == null && this.cells.length > 0 && this.cells[0].length > 0)
			this.cursor = new ExcelSheetCursor(this);
		this.layout();
	};
	
	this._init();
	addLayoutEvent(this.container, function() { t.layout(); });
	
	listenEvent(window,'keydown',function(ev){
		var event = window.event ? window.event : e;
		var key = event.keyCode;
		var stop = false;
		if (event.shiftKey) {
			switch (key) {
			case 37: // left
				if (t.cursor) t.cursor.select_left();
				stop = true;
				break;
			case 38: // up
				if (t.cursor) t.cursor.select_up();
				stop = true;
				break;
			case 39: // right
				if (t.cursor) t.cursor.select_right();
				stop = true;
				break;
			case 40: // down
				if (t.cursor) t.cursor.select_down();
				stop = true;
				break;
			}
		} else {
			switch (key) {
			case 37: // left
				if (t.cursor) t.cursor.left();
				stop = true;
				break;
			case 38: // up
				if (t.cursor) t.cursor.up();
				stop = true;
				break;
			case 39: // right
				if (t.cursor) t.cursor.right();
				stop = true;
				break;
			case 40: // down
				if (t.cursor) t.cursor.down();
				stop = true;
				break;
			}
		}
		if (stop) {
			stopEventPropagation(ev);
			return false;
		}
	});
	listenEvent(window,'mouseup',function(ev) {
		if (t.cursor) t.cursor.mouse_select_start = null;
	});
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
	this.sheet = sheet;
	this.index = index;
	this.name = getExcelColumnName(index);
	this.width = 100;
	
	this.setWidth = function(w) {
		if (w < 10) w = 10;
		this.width = w;
		this.header.style.width = w+"px";
		for (var j = 0; j < sheet.cells[this.index].length; ++j)
			sheet.cells[this.index][j]._set_width(w);
		var x = 0;
		for (var i = 0; i <= this.index; ++i) x += sheet.columns[i].width+1;
		this.resizer.style.left = (x-4)+"px";
		for (var i = this.index+1; i < sheet.columns.length; ++i) {
			sheet.columns[i].header.style.left = x+"px";
			sheet.columns[i].resizer.style.left = (x+sheet.columns[i].width-4)+"px";
			x += sheet.columns[i].width+1;
		}
		if (sheet.cursor) sheet.cursor.refresh();
	};
	
	this._init = function() {
		this.header = document.createElement("DIV");
		this.header.style.borderBottom = "1px solid black";
		this.header.style.borderLeft = "1px solid black";
		this.header.style.backgroundColor = "#D0D0D0";
		this.header.style.textAlign = "center";
		this.header.style.verticalAlign = "middle";
		this.header.style.fontWeight = "bold";
		this.header.style.zIndex = 3;
		this.header.style.height = "16px";
		this.header.style.width = this.width+"px";
		this.header.style.top = "0px";
		var x = 0;
		for (var i = 0 ; i < index; ++i) x += sheet.columns[i].width+1;
		this.header.style.left = x+"px";
		this.header.style.position = "absolute";
		this.header.innerHTML = this.name;
		this.sheet.column_headers_container.appendChild(this.header);
		this.resizer = document.createElement("DIV");
		this.resizer.style.position = "absolute";
		this.resizer.style.height = "16px";
		this.resizer.style.width = "10px";
		this.resizer.style.top = "0px";
		this.resizer.style.left = (x+this.width-4)+"px";
		this.resizer.style.cursor = "col-resize";
		this.resizer.style.zIndex = 4;
		this.sheet.content.appendChild(this.resizer);
		var t=this;
		this.resize_pos = null;
		this.resizer.onmousedown = function(ev) {
			var e = getCompatibleMouseEvent(ev);
			t.resize_pos = e.x;
			stopEventPropagation(ev);
			return false;
		};
		listenEvent(window,'mousemove',function(ev){
			if (t.resize_pos == null) return;
			var e = getCompatibleMouseEvent(ev);
			var diff = e.x-t.resize_pos;
			t.setWidth(t.width+diff);
			t.resize_pos = e.x;
		});
		listenEvent(window,'mouseup',function(ev){
			t.resize_pos = null;
		});
		this.header.onclick = function(ev) {
			if (sheet.cursor)
				sheet.cursor.setRange(t.index, 0, t.index, sheet.rows.length-1);
		};
	};
	this._init();
}

function ExcelSheetRow(sheet, index) {
	this.sheet = sheet;
	this.index = index;
	this.name = ""+(index+1);
	this.height = 15;
	
	this.setHeight = function(h) {
		this.height = h;
		this.header.style.height = h+"px";
		for (var j = 0; j < sheet.cells.length; ++j)
			sheet.cells[j][this.index]._set_height(h);
		var y = 0;
		for (var i = 0; i <= this.index; ++i) y += sheet.rows[i].height+1;
		this.resizer.style.top = (y-3)+"px";
		for (var i = this.index+1; i < sheet.rows.length; ++i) {
			sheet.rows[i].header.style.top = y+"px";
			sheet.rows[i].resizer.style.top = y+"px";
			y += sheet.rows[i].height+1;
		}
		if (sheet.cursor) sheet.cursor.refresh();
	};
	
	this._init = function() {
		this.header = document.createElement("DIV");
		this.header.style.borderTop = "1px solid black";
		this.header.style.borderRight = "1px solid black";
		this.header.style.backgroundColor = "#D0D0D0";
		this.header.style.textAlign = "right";
		this.header.style.verticalAlign = "middle";
		this.header.style.fontWeight = "bold";
		this.header.style.zIndex = 3;
		this.header.style.width = "50px";
		this.header.style.height = this.height+"px";
		this.header.style.left = "0px";
		var y = 0;
		for (var i = 0 ; i < index; ++i) y += sheet.rows[i].height+1;
		this.header.style.top = y+"px";
		this.header.style.position = "absolute";
		this.header.innerHTML = this.name;
		this.sheet.row_headers_container.appendChild(this.header);
		this.resizer = document.createElement("DIV");
		this.resizer.style.position = "absolute";
		this.resizer.style.width = "50px";
		this.resizer.style.height = "8px";
		this.resizer.style.left = "0px";
		this.resizer.style.top = (y+this.height-3)+"px";
		this.resizer.style.cursor = "row-resize";
		this.resizer.style.zIndex = 4;
		this.sheet.content.appendChild(this.resizer);
		var t=this;
		this.resize_pos = null;
		this.resizer.onmousedown = function(ev) {
			var e = getCompatibleMouseEvent(ev);
			t.resize_pos = e.y;
			stopEventPropagation(ev);
			return false;
		};
		listenEvent(window,'mousemove',function(ev){
			if (t.resize_pos == null) return;
			var e = getCompatibleMouseEvent(ev);
			var diff = e.y-t.resize_pos;
			t.setHeight(t.height+diff);
			t.resize_pos = e.y;
		});
		listenEvent(window,'mouseup',function(ev){
			t.resize_pos = null;
		});
		this.header.onclick = function(ev) {
			if (sheet.cursor)
				sheet.cursor.setRange(0, t.index, sheet.columns.length-1, t.index);
		};
		this.tr = document.createElement("TR");
		sheet.table.appendChild(this.tr);
	};
	this._init();
}

function ExcelSheetCell(sheet, column, row) {
	this.sheet = sheet;
	this.column = column;
	this.row = row;
	
	this.setValue = function(value) {
		this.value.innerHTML = value;
	};
	this.setStyle = function(styles) {
		for (var name in styles)
			this.td.style[name] = styles[name];
	};

	this._set_width = function(w) {
		this.value.style.width = (w-1)+"px";
	};
	this._set_height = function(h) {
		this.value.style.height = (h-0)+"px";
	};
	
	this._init = function() {
		this.td = document.createElement("TD");
		this.td.style.border = "1px solid black";
		this.td.style.overflow = "hidden";
		sheet.rows[this.row].tr.appendChild(this.td);
		this.value = document.createElement("DIV");
		this._set_width(sheet.columns[this.column].width);
		this._set_height(sheet.rows[this.row].height);
		this.value.style.overflow = "hidden";
		this.value.style.paddingLeft = "1px";
		this.td.style.padding = "0px";
		this.td.appendChild(this.value);
		var t=this;
		this.td.onmousedown = function(ev) {
			if (sheet.cursor) {
				sheet.cursor.setRange(t.column, t.row, t.column, t.row);
				sheet.cursor.mouse_select_start = {column:t.column,row:t.row};
			}
			window.focus();
			stopEventPropagation(ev);
			return false;
		};
		this.td.onmousemove = function(ev) {
			if (sheet.cursor && sheet.cursor.mouse_select_start) {
				var col1 = sheet.cursor.mouse_select_start.column;
				var col2 = t.column;
				var row1 = sheet.cursor.mouse_select_start.row;
				var row2 = t.row;
				if (col2 < col1) { var c = col1; col1 = col2; col2 = c; }
				if (row2 < row1) { var r = row1; row1 = row2; row2 = r; }
				sheet.cursor.setRange(col1, row1, col2, row2);
			}
		};
	};
	this._init();
}

function ExcelSheetCursor(sheet) {
	this.row_start = 0;
	this.row_end = 0;
	this.col_start = 0;
	this.col_end = 0;
	this.select_x_dir = 0;
	this.select_y_dir = 0;
	this.mouse_select_start = null;
	
	this.up = function() { this.setRange(this.col_start, this.row_start-1, this.col_start, this.row_start-1); };
	this.down = function() { this.setRange(this.col_start, this.row_end+1, this.col_start, this.row_end+1); };
	this.left = function() { this.setRange(this.col_start-1, this.row_start, this.col_start-1, this.row_start); };
	this.right = function() { this.setRange(this.col_end+1, this.row_start, this.col_end+1, this.row_start); };
	this.setPosition = function(col,row) { this.setRange(col,row,col,row); };
	this.select_up = function() {
		if (this.select_y_dir == 0) this.select_y_dir = -1;
		if (this.select_y_dir == -1)
			this.setRange(this.col_start, this.row_start-1, this.col_end, this.row_end);
		else
			this.setRange(this.col_start, this.row_start, this.col_end, this.row_end-1);
	};
	this.select_down = function() {
		if (this.select_y_dir == 0) this.select_y_dir = 1;
		if (this.select_y_dir == -1)
			this.setRange(this.col_start, this.row_start+1, this.col_end, this.row_end);
		else
			this.setRange(this.col_start, this.row_start, this.col_end, this.row_end+1);
	};
	this.select_left = function() {
		if (this.select_x_dir == 0) this.select_x_dir = -1;
		if (this.select_x_dir == -1)
			this.setRange(this.col_start-1, this.row_start, this.col_end, this.row_end);
		else
			this.setRange(this.col_start, this.row_start, this.col_end-1, this.row_end);
	};
	this.select_right = function() {
		if (this.select_x_dir == 0) this.select_x_dir = 1;
		if (this.select_x_dir == -1)
			this.setRange(this.col_start+1, this.row_start, this.col_end, this.row_end);
		else
			this.setRange(this.col_start, this.row_start, this.col_end+1, this.row_end);
	};
	this.setRange = function(col_start, row_start, col_end, row_end) {
		if (row_start < 0) row_start = 0;
		if (col_start < 0) col_start = 0;
		if (row_start > sheet.rows.length-1) row_start = sheet.rows.length-1;
		if (col_start > sheet.columns.length-1) col_start = sheet.columns.length-1;
		if (row_end < row_start) row_end = row_start;
		if (col_end < col_start) col_end = col_start;
		if (row_end > sheet.rows.length-1) row_end = sheet.rows.length-1;
		if (col_end > sheet.columns.length-1) col_end = sheet.columns.length-1;
		if (row_start == row_end) this.select_y_dir = 0;
		if (col_start == col_end) this.select_x_dir = 0;
		this.row_start = row_start;
		this.row_end = row_end;
		this.col_start = col_start;
		this.col_end = col_end;
		this.refresh();
	};
	
	this._init = function() {
		this.div = document.createElement("DIV");
		this.div.style.zIndex = 2;
		this.div.style.position = "absolute";
		this.div.style.border = "2px solid #8080D0";
		this.div.style.pointerEvents = "none";
		sheet.content.appendChild(this.div);
		this.refresh();
		window.focus();
	};
	
	this.refresh = function() {
		var x = 50, y = 16;
		for (var i = 0; i < this.col_start; ++i)
			x += sheet.columns[i].width+1;
		for (var i = 0; i < this.row_start; ++i)
			y += sheet.rows[i].height+1;
		this.div.style.top = y+"px";
		this.div.style.left = x+"px";
		var w = 0, h = 0;
		for (var i = this.col_start; i <= this.col_end; ++i)
			w += sheet.columns[i].width+1;
		for (var i = this.row_start; i <= this.row_end; ++i)
			h += sheet.rows[i].height+1;
		w-=3; h-=3;
		this.div.style.width = w+"px";
		this.div.style.height = h+"px";
		if (this.col_start != this.col_end || this.row_start != this.row_end)
			this.div.style.backgroundColor = "rgba(192,192,255,0.33)";
		else
			this.div.style.backgroundColor = "";
	};
	
	this._init();
}