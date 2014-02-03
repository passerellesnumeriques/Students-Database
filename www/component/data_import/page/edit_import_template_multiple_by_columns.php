<?php 
class page_edit_import_template_multiple_by_columns extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$id = $_GET["id"];
		if ($id == -1) {
			$root = $_GET["root"];
			$submodel = isset($_GET["submodel"]) ? $_GET["submodel"] : null;
		} else {
			// TODO
		}
		
		$fields = PNApplication::$instance->data_model->getAvailableFields($root);
		// sort by category
		$categories = array();
		foreach ($fields as $f) {
			$cat = $f[0]->getCategory();
			if (!isset($categories[$cat])) $categories[$cat] = array();
			array_push($categories[$cat], $f);
		}
		
		$this->require_javascript("section.js");
		$this->require_javascript("DataDisplay.js");
		$this->require_javascript("typed_field.js");
		$this->require_javascript("field_integer.js");
?>
<script type='text/javascript'>
var categories = [<?php
$first = true;
foreach ($categories as $cat_name=>$fields) {
	if ($first) $first = false; else echo ",";
	echo "{";
	echo "name:".json_encode($cat_name);
	echo ",fields:[";
	$first_field = true;
	foreach ($fields as $f) {
		if ($first_field) $first_field = false; else echo ",";
		echo "{";
		echo "data:".$f[0]->javascriptDataDisplay(null);
		echo ",path:new DataPath(".json_encode($f[1]->get_string()).")";
		echo "}";
	}
	echo "]";
	echo "}";
} 
?>];

var excel_win = window.parent.getIFrameWindow(window.parent.excel_frame);
var excel = excel_win.excel;

function createSelectColumn(container) {
	var select = document.createElement("SELECT");
	var o = document.createElement("OPTION");
	o.sheet = -1; o.col = -1;
	o.text = "Not available";
	select.add(o);
	for (var sheet_index = 0; sheet_index < excel.sheets.length; ++sheet_index) {
		var sheet = excel.sheets[sheet_index];
		for (var col_index = 0; col_index < sheet.columns.length; ++col_index) {
			o = document.createElement("OPTION");
			o.sheet = sheet_index;
			o.col = col_index;
			o.text = "Sheet "+sheet.name+", Column "+excel_win.getExcelColumnName(col_index);
			select.add(o);
		}
	}
	container.appendChild(select);
	var button = document.createElement("IMG");
	button.className = "button";
	button.src = "/static/excel/select_range.png";
	button.style.verticalAlign = "bottom";
	button.onclick = function() {
		var sheet_index = excel.getActiveSheetIndex();
		if (sheet_index < 0) return;
		var sheet = excel.sheets[sheet_index];
		var sel = sheet.getSelection();
		if (sel == null) return;
		var col = sel.start_col;
		for (var i = 0; i < select.options.length; ++i) {
			o = select.options[i];
			if (o.sheet != sheet_index) continue;
			if (o.col != col) continue;
			select.selectedIndex = i;
			break;
		} 
	};
	container.appendChild(button);
	return select;
}

function Field(container, field) {
	var select = createSelectColumn(container);
}

function updateHeader(sheet_index, data) {
	
}

function buildHeader() {
	var div = document.createElement("DIV");
	div.style.padding = "2px";
	div.style.margin = "2px 5px 2px 5px";
	div.style.border = "1px solid black";
	div.style.backgroundColor = "white";
	setBorderRadius(div, 3,3,3,3,3,3,3,3);
	div.innerHTML = "Number of rows containing columns titles:";
	var table = document.createElement("TABLE"); div.appendChild(table);
	for (var i = 0; i < excel.sheets.length; ++i) {
		var tr = document.createElement("TR"); table.appendChild(tr);
		var td = document.createElement("TD"); tr.appendChild(td);
		td.appendChild(document.createTextNode("Sheet "+excel.sheets[i].name));
		td = document.createElement("TD"); tr.appendChild(td);
		var tf = new field_integer(0,true,{min:0,max:excel.sheets[i].rows.length});
		tf.sheet_index = i;
		td.onchange.add_listener(function(tf){
			updateHeader(tf.sheet_index, tf.getCurrentData());
		});
		td.appendChild(tf.getHTMLElement());
	}
	document.body.appendChild(div);
}

function buildCategories() {
	for (var i = 0; i < categories.length; ++i) {
		var content = document.createElement("TABLE");
		var s = new section(null,categories[i].name,content,true);
		document.body.appendChild(s.element);
		s.element.style.margin = "2px 5px 2px 5px";
		for (var j = 0; j < categories[i].fields.length; ++j) {
			var field = categories[i].fields[j];
			var tr = document.createElement("TR"); content.appendChild(tr);
			var td = document.createElement("TD"); tr.appendChild(td);
			td.style.verticalAlign = "top";
			td.appendChild(document.createTextNode(field.data.name));
			td = document.createElement("TD"); tr.appendChild(td);
			new Field(td, field);
		}
	}
}
buildHeader();
buildCategories();
</script>
<?php
	} 		
}
?>