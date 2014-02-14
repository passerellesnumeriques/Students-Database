<?php 
function custom_data_list(&$page, $root_table, $sub_model, $available_fields, $filters) {

	// init query with root table
	$q = SQLQuery::create();
	$t = DataModel::get()->getTable($root_table);
	$alias = $q->generateTableAlias();
	$table_name = $t->getSQLName(null);
	$q->select(array($table_name=>$alias));
	
	// build the request
	$data_aliases = array();
	$remaining_filters = array_merge($filters);
	foreach ($available_fields as $f) {
		$data_filters = array();
		for ($i = 0; $i < count($remaining_filters); $i++) {
			$filter = $remaining_filters[$i];
			if ($filter["category"] <> $f[0]->handler->category) continue;
			if ($filter["name"] <> $f[0]->getDisplayName()) continue;
			array_splice($remaining_filters, $i, 1);
			$i--;
			$fil = array();
			array_push($fil, $filter["data"]);
			while (isset($filter["or"])) {
				$filter = $filter["or"];
				array_push($fil, $filter["data"]);
			}
			array_push($data_filters, $fil);
		}
		array_push($data_aliases, $f[0]->buildSQL($q, $f[1], $data_filters));
	}
	
	$res = $q->execute();
	for ($i = 0; $i < count($available_fields); $i++) {
		$data = $available_fields[$i][0];
		$path = $available_fields[$i][1];
		$data->performSubRequests($q, $res, $data_aliases[$i], $path, null);
	}
	
	$page->add_javascript("/static/data_model/DataDisplay.js");
	$page->add_javascript("/static/widgets/typed_field/typed_field.js");
	foreach ($available_fields as $f) {
		$tf = $f[0]->getTypedField($sub_model);
		$page->require_javascript($tf[0].".js");
	}
?>
<script type='text/javascript'>
var custom_data_list = {
	fields_from_request: [<?php
	$first = true;
	foreach ($available_fields as $f) {
		if ($first) $first = false; else echo ",";
		echo $f[0]->javascriptDataDisplay($sub_model);
	} 
	?>],
	selected_fields_from_request: [],
	data_from_request: [<?php 
	$first = true;
	foreach ($res as $row) {
		if ($first) $first = false; else echo ",";
		echo "[";
		$f = true;
		for ($i = 0; $i < count($available_fields); $i++) {
			$a = $data_aliases[$i];
			$data = $available_fields[$i][0];
			$path = $available_fields[$i][1];
			if ($f) $f = false; else echo ",";
			echo "{v:";
			echo json_encode($row[$a["data"]]);
			if (isset($row[$a["key"]]))
				echo ",k:".json_encode($row[$a["key"]]);
			else {
				echo ",k:null";
				//PNApplication::error("Missing key '".$a["key"]."' for data '".$data->getDisplayName()."' in table '".$data->handler->table->getName()."'");
			} 
			echo "}";
		}
		echo "]";
	}
	?>],
	
	container: null,
	table: null,
	thead: null,
	tbody: null,
	init: function(container) {
		if (typeof container == 'string') container = document.getElementById(container);
		this.container = container;
		container.appendChild(this.table = document.createElement("TABLE"));
		this.table.appendChild(this.thead = document.createElement("THEAD"));
		this.table.appendChild(this.tbody = document.createElement("TBODY"));
		// create a row for headers
		this.thead.appendChild(document.createElement("TR"));
		// create a row for each data
		for (var i = 0; i < this.data_from_request.length; ++i)
			this.tbody.appendChild(document.createElement("TR"));

		this.table.style.backgroundColor = "#FFFFFF";
	},
	select_field: function(field_category, field_name, show, customize_header) {
		var index;
		for (index = 0; index < this.fields_from_request.length; ++index)
			if (this.fields_from_request[index].category == field_category && this.fields_from_request[index].name == field_name) break;
		if (index == this.fields_from_request.length) {
			alert("Unknown field: "+field_category+"."+field_name);
			return;
		}
		var col_index;
		for (col_index = 0; col_index < this.selected_fields_from_request.length; ++col_index)
			if (this.selected_fields_from_request[col_index] == index) break;
		if (show) {
			if (col_index < this.selected_fields_from_request.length) return; // already there
			// add the column header
			var th = document.createElement("TH");
			th.appendChild(document.createTextNode(field_name));
			th.rowSpan = this.thead.childNodes.length;
			th.style.verticalAlign = "bottom";
			var tr = this.thead.childNodes[0];
			if (tr.childNodes.length <= this.selected_fields_from_request.length)
				tr.appendChild(th);
			else
				tr.insertBefore(th, tr.childNodes[this.selected_fields_from_request.length]);
			// add the cell for each
			var data_display = this.fields_from_request[index];
			for (var i = 0; i < this.data_from_request.length; ++i) {
				var data = this.data_from_request[i][index];
				var tr = this.tbody.childNodes[i];
				var td = document.createElement("TD");
				var f = new window[data_display.field_classname](data.v, false, data_display.field_config);
				td.appendChild(f.getHTMLElement());
				if (tr.childNodes.length <= this.selected_fields_from_request.length)
					tr.appendChild(td);
				else
					tr.insertBefore(td, tr.childNodes[this.selected_fields_from_request.length]);
			}
			// add to list
			this.selected_fields_from_request.push(index);
			if (customize_header)
				customize_header(th);
		} else {
			if (col_index == this.selected_fields_from_request.length) return; // not there
			// remove column header
			var tr = this.thead.childNodes[0];
			tr.removeChild(tr.childNodes[col_index]);
			// remove cell for each data
			for (var i = 0; i < this.data_from_request.length; ++i) {
				var tr = this.tbody.childNodes[i];
				tr.removeChild(tr.childNodes[col_index]);
			}
			// remove from list
			this.selected_fields_from_request.splice(col_index, 1);
		}
	},

	columns: [],

	_count_columns: function(cols) {
		var count = 0;
		for (var i = 0; i < cols.length; ++i)
			if (cols[i].sub_columns.length == 0)
				count++;
			else
				count += this._count_columns(cols[i].sub_columns);
		return count;
	},
	addColumn: function(col_id, col_header, content_provider, before_col_id, customize_header) {
		var col = {id: col_id, content_provider: content_provider, sub_columns: [], parent_column: null};
		col.th = document.createElement("TH");
		if (typeof col_header == 'string') col.th.innerHTML = col_header;
		else col.th.appendChild(col_header);
		col.th.rowSpan = this.thead.childNodes.length == 0 ? 1 : this.thead.childNodes.length;
		col.th.style.verticalAlign = "bottom";
		if (customize_header) customize_header(col.th);
		var before_col = null, before_col_i = -1;
		var col_index = this.selected_fields_from_request.length; // after data from request
		for (var i = 0; i < this.columns.length; ++i) {
			if (this.columns[i].id == before_col_id) { before_col = this.columns[i]; before_col_i = i; break; }
			if (this.columns[i].sub_columns.length == 0)
				col_index++;
			else 
				col_index += this._count_columns(this.columns[i].sub_columns);
		}
		if (!before_col) {
			this.thead.childNodes[0].appendChild(col.th);
			this.columns.push(col);
		} else {
			this.thead.childNodes[0].insertBefore(col.th, before_col.th);
			this.columns.splice(before_col_i,0,col);
		}
		for (var i = 0; i < this.tbody.childNodes.length; ++i) {
			var tr = this.tbody.childNodes[i];
			var td = document.createElement("TD");
			if (col_index >= tr.childNodes.length)
				tr.appendChild(td);
			else
				tr.insertBefore(td, tr.childNodes[col_index]);
			content_provider(td, i);
		}
	},
	_find_column: function(id) {
		return this._find_column_(this.columns, id);
	},
	_find_column_: function(cols, id) {
		for (var i = 0; i < cols.length; ++i) {
			if (cols[i].id == id) return cols[i];
			var col = this._find_column_(cols[i].sub_columns, id);
			if (col != null) return col;
		}
		return null;
	},
	_find_next_at: function(top_index, level) {
		var l = 0;
		if (top_index >= this.columns.length) return null;
		var c = this.columns[top_index];
		while (l != level) {
			if (c.sub_columns.length == 0) break;
			c = c.sub_columns[0];
			l++;
		}
		if (l != level) {
			if (top_index == this.columns.length-1) return null;
			return this._find_next_at(top_index+1, level);
		}
		return c;
	},
	_find_column_index_in: function(col, node) {
		var index = 0;
		for (var i = 0; i < node.sub_columns.length; ++i) {
			if (node.sub_columns[i] == col) return index;
			var j = this._find_column_index_in(col, node.sub_columns[i]);
			if (j != -1)
				return index+j;
			index += this._count_columns([node.sub_columns[i]]);
		}
		return -1;
	},
	_column_index: function(col) {
		var index = this.selected_fields_from_request.length;
		var p = col;
		while (p.parent_column != null) p = p.parent_column;
		for (var i = 0; i < this.columns.length; ++i) {
			if (this.columns[i] == p) break;
			index += this._count_columns([this.columns[i]]);
		}
		if (p != col)
			index += this._find_column_index_in(col, p);
		return index;
	},
	_count_sublevels: function(col) {
		if (col.sub_columns.length == 0) return 0;
		var max = 0;
		for (var i = 0; i < col.sub_columns.length; ++i) {
			var nb = this._count_sublevels(col.sub_columns[i]);
			if (nb > max) max = nb;
		}
		return max+1;
	},
	_update_rowspan: function(col, total_rows) {
		var nb = this._count_sublevels(col);
		col.th.rowSpan = total_rows-nb;
		for (var i = 0; i < col.sub_columns.length; ++i)
			this._update_rowspan(col.sub_columns[i], total_rows-col.th.rowSpan);
	},
	addSubColumn: function(parent_col_id, col_id, col_header, content_provider, before_col_id, customize_header) {
		var parent = this._find_column(parent_col_id);
		if (parent == null) {
			alert("Invalid parent column id: "+parent_col_id);
			return;
		}
		var level = 0;
		var p = parent;
		var top_level = parent;
		while (p != null) {
			level++;
			p = p.parent_column;
			if (p != null) top_level = p;
		}

		var col = {id: col_id, content_provider: content_provider, sub_columns: [], parent_column: parent};
		col.th = document.createElement("TH");
		if (typeof col_header == 'string') col.th.innerHTML = col_header;
		else col.th.appendChild(col_header);
		col.th.style.verticalAlign = "bottom";
		if (customize_header) customize_header(col.th);
		
		if (this.thead.childNodes.length == level) {
			// new level
			var tr = document.createElement("TR");
			this.thead.appendChild(tr);
			for (var i = 0; i < this.thead.childNodes[0].childNodes.length; ++i)
				if (this.thead.childNodes[0].childNodes[i] != top_level.th)
					this.thead.childNodes[0].childNodes[i].rowSpan = this.thead.childNodes[0].childNodes[i].rowSpan+1;
			col.th.rowSpan = 1;
			tr.appendChild(col.th);
			parent.sub_columns.push(col);
			// remove the cells corresponding to the parent
			var cell_index = this._column_index(parent);
			for (var i = 0; i < this.tbody.childNodes.length; ++i)
				this.tbody.childNodes[i].removeChild(this.tbody.childNodes[i].childNodes[cell_index]);
		} else {
			if (parent.sub_columns.length == 0) {
				// first of this level in the parent
				var before_th = null;
				if (before_col_id) {
					for (var i = 0; i < parent.sub_columns.length; ++i)
						if (parent.sub_columns[i].id == before_col_id) { before_th = parent.sub_columns[i].th; break; }
				}
				if (before_th == null) {
					// find next at this level
					var index = this.columns.indexOf(top_level);
					var next = this._find_next_at(index+1, level);
					if (next != null) before_th = next.th;
				}
				if (before_th == null)
					this.thead.childNodes[level].appendChild(col.th);
				else
					this.thead.childNodes[level].insertBefore(col.th, before_th);
				parent.sub_columns.push(col);
				this._update_rowspan(top_level, this.thead.childNodes.length);
				// remove the cells corresponding to the parent
				var cell_index = this._column_index(parent);
				for (var i = 0; i < this.tbody.childNodes.length; ++i)
					this.tbody.childNodes[i].removeChild(this.tbody.childNodes[i].childNodes[cell_index]);
			} else {
				var before_th = null;
				if (before_col_id) {
					for (var i = 0; i < parent.sub_columns.length; ++i)
						if (parent.sub_columns[i].id == before_col_id) { 
							before_th = parent.sub_columns[i].th; 
							parent.sub_columns.splice(i,0,col);
							break; 
						}
				}
				if (before_th == null) {
					before_th = parent.sub_columns[parent.sub_columns.length-1].th.nextSibling;
					parent.sub_columns.push(col);
}
				this.thead.childNodes[level].insertBefore(col.th, before_th);
			}
		}
		parent.th.colSpan = parent.sub_columns.length;
		
		var col_index = this.selected_fields_from_request.length; // after data from request
		for (var i = 0; i < this.columns.length; ++i) {
			if (this.columns[i].id == top_level.id) break;
			if (this.columns[i].sub_columns.length == 0)
				col_index++;
			else 
				col_index += this._count_columns(this.columns[i].sub_columns);
		}
		col_index += this._find_column_index_in(col, top_level);

		for (var i = 0; i < this.tbody.childNodes.length; ++i) {
			var tr = this.tbody.childNodes[i];
			var td = document.createElement("TD");
			if (col_index >= tr.childNodes.length)
				tr.appendChild(td);
			else
				tr.insertBefore(td, tr.childNodes[col_index]);
			content_provider(td, i);
		}
	},
	resetColumns: function() {
		for (var i = 0; i < this.selected_fields_from_request.length; ++i)
			this.thead.childNodes[0].childNodes[i].rowSpan = 1;
		while (this.thead.childNodes[0].childNodes.length > this.selected_fields_from_request.length)
			this.thead.childNodes[0].removeChild(this.thead.childNodes[0].childNodes[this.selected_fields_from_request.length]);
		while (this.thead.childNodes.length > 1)
			this.thead.removeChild(this.thead.childNodes[1]);
		for (var i = 0; i < this.tbody.childNodes.length; ++i)
			while (this.tbody.childNodes[i].childNodes.length > this.selected_fields_from_request.length)
				this.tbody.childNodes[i].removeChild(this.tbody.childNodes[i].childNodes[this.selected_fields_from_request.length]);
		this.columns = [];
	},
	removeColumn: function(id) {
		var col = this._find_column(id);
		// TODO
	},
	getDataIndex: function(table, key) {
		// find a field in the table
		var field_index = -1;
		for (var i = 0; i < this.fields_from_request.length; ++i)
			if (this.fields_from_request[i].table == table) { field_index = i; break; }
		if (field_index < 0) return -1;
		// find a row with that key
		for (var i = 0; i < this.data_from_request.length; ++i)
			if (this.data_from_request[i][field_index].k == key)
				return i;
		return -1;
	},
	getData: function(row_index, category, name) {
		// find the field index
		var field_index = -1;
		for (var i = 0; i < this.fields_from_request.length; ++i)
			if (this.fields_from_request[i].category == category && this.fields_from_request[i].name == name) { field_index = i; break; }
		if (field_index < 0) return null;
		return this.data_from_request[row_index][field_index].v;
	}
};
</script>
<?php 
	return array(
		"data"=>$res,
		"data_aliases"=>$data_aliases,
		"query"=>$q,
	);
}
?>