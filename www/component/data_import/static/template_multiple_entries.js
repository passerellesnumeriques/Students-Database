function template_multiple_entries(container, excel, fields, onready) {
	container.removeAllChildren();
	var t=this;

	this._createSelectColumn = function(container, found) {
		var select = document.createElement("SELECT");
		var o;
		o = document.createElement("OPTION");
		o.text = "";
		select.add(o);
		var sel = 0;
		for (var i = 0; i < excel.sheets.length; ++i) {
			var sheet = excel.sheets[i];
			for (var j = 0; j < sheet.columns.length; ++j) {
				var colname = excel.getExcelColumnName(j);
				o = document.createElement("OPTION");
				o._value = [i,j];
				o.text = "Sheet "+sheet.name+" / Column "+colname;
				if (found && found[0] == i && found[1] == j) sel = select.options.length;
				select.add(o);
			}
		}
		select.selectedIndex = sel;
		container.appendChild(select);
		return select;
	};
	
	this._createSingleField = function(field) {
		var tr = document.createElement("TR");
		t.table.appendChild(tr);
		var td = document.createElement("TD");
		tr.appendChild(td);
		td.appendChild(document.createTextNode(field.data.name));
		td = document.createElement("TD");
		tr.appendChild(td);
		var select = this._createSelectColumn(td, field._found);
		var layer = null;
		select.onchange = function() {
			if (select.selectedIndex == 0) {
				if (layer) {
					layer.sheet.__columns_layers.remove(layer);
					layer.sheet.removeLayer(layer);
					layer = null;
				}
			} else {
				var val = select.options[select.selectedIndex]._value;
				var sheet = excel.sheets[val[0]];
				if (layer) {
					if (layer.sheet != sheet) {
						layer.sheet.__columns_layers.remove(layer);
						layer.sheet.removeLayer(layer);
						layer = null;
					} else {
						layer.setRange(val[1],layer.row_start,val[1],layer.row_end);
					}
				}
				if (!layer) {
					layer = sheet.addLayer(val[1],sheet.__header_rows,val[1],sheet.rows.length-1,0,255,0, field.data.name);
					if (!sheet.__columns_layers) sheet.__columns_layers = [];
					sheet.__columns_layers.push(layer);
				}
			}
		};
		if (select.selectedIndex > 0) select.onchange();
	};
	this._createMultipleField = function(field) {
		// TODO
	};
	
	this._createHeaderRowsField = function() {
		var div = document.createElement("DIV");
		div.innerHTML = "How many rows contain title/header ? ";
		container.appendChild(div);
		require([["typed_field.js","field_integer.js"]], function() {
			var f = new field_integer(excel.getActiveSheet().__header_rows,true,{min:0,can_be_null:false});
			div.appendChild(f.getHTMLElement());
			var update_layer = function(nb_rows) {
				var sheet = excel.getActiveSheet();
				if (nb_rows == 0) {
					if (sheet.__header_layer) {
						sheet.removeLayer(sheet.__header_layer);
						sheet.__header_layer = null;
					}
				} else {
					if (!sheet.__header_layer)
						sheet.__header_layer = sheet.addLayer(0, 0, sheet.columns.length-1, nb_rows-1, 255,100,0, "Titles / Headers");
					else
						sheet.__header_layer.setRange(0, 0, sheet.columns.length-1, nb_rows-1);
				}
				if (sheet.__columns_layers)
					for (var i = 0; i < sheet.__columns_layer.length; ++i)
						sheet.__columns_layer[i].setRange(sheet.__columns_layer[i].col_start, nb_rows, sheet.__columns_layer[i].col_start, sheet.__columns_layer[i].row_end);
			};
			excel.onactivesheetchanged.add_listener(function() {
				var nb_rows = excel.getActiveSheet().__header_rows;
				f.setData(nb_rows);
				update_layer(nb_rows);
			});
			f.onchange.add_listener(function() {
				var nb_rows = f.hasError() ? 0 : f.getCurrentData();
				excel.getActiveSheet().__header_rows = nb_rows;
				update_layer(nb_rows);
			});
			update_layer();
		});
	};
	
	this._buildCategory = function(cat, list) {
		var title = document.createElement("DIV");
		title.className = "page_section_title3";
		title.appendChild(document.createTextNode(cat));
		var tr = document.createElement("TR"); t.table.appendChild(tr);
		var td = document.createElement("TD"); tr.appendChild(td);
		td.colSpan = 2;
		td.appendChild(title);
		for (var i = 0; i < list.length; ++i)
			if (window[list[i].data.field_classname].prototype.isMultiple())
				this._createMultipleField(list[i]);
			else
				this._createSingleField(list[i]);
	};
	
	this._init = function() {
		require("typed_field.js");
		var cats = [];
		var js = [];
		for (var i = 0; i < fields.length; ++i) {
			if (!cats.contains(fields[i].data.category))
				cats.push(fields[i].data.category);
			if (!js.contains(fields[i].data.field_classname+".js"))
				js.push(fields[i].data.field_classname+".js");
		}
		// try to detect columns
		for (var i = 0; i < excel.sheets.length; ++i) {
			excel.sheets[i].__header_rows = 0;
			if (excel.sheets[i].rows.length == 0) continue;
			var found = false;
			for (var j = 0; j < excel.sheets[i].columns.length; ++j) {
				var value = excel.sheets[i].getCell(j, 0).getValue();
				for (var k = 0; k < fields.length; ++k)
					if (fields[k].data.name.isSame(value)) {
						if (typeof fields[k]._found == 'undefined')
							fields[k]._found = [i,j];
						else
							fields[k]._found = null;
						found = true;
					}
			}
			if (found)
				excel.sheets[i].__header_rows = 1;
		}
		this._createHeaderRowsField();
		require([["typed_field.js",js]],function() {
			t.table = document.createElement("TABLE");
			container.appendChild(t.table);
			for (var i = 0; i < cats.length; ++i) {
				var list = [];
				for (var j = 0; j < fields.length; ++j)
					if (fields[j].data.category == cats[i]) list.push(fields[j]);
				t._buildCategory(cats[i], list);
			}
			onready(this);
		});
	};
	this._init();
}