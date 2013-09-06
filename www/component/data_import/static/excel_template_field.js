function excel_template_field(table, column, display_name) {
	this.container = document.getElementById("excel_template_field__"+table+"__"+column);
	this.name = document.createElement("SPAN");
	this.name.style.cursor = "pointer";
	this.name.title = "Import this data";
	this.name.innerHTML = display_name;
	this.container.appendChild(this.name);
	this.imported = false;
	
	var t=this;
	this.name.onclick = function() {
		if (!window.excel) return;
		if (!t.imported) {
			t.range = document.createElement("INPUT");
			t.range.type = 'text';
			t.container.appendChild(t.range);
			//t.range.value = window.excel.getSelectedRangeString();
		};
	};
}