if (typeof require != 'undefined') {
	require("wizard.js");
	require("upload.js");
}

function import_coordinates(country, country_data, division_index) {
	require("wizard.js", function() {
		var wiz = new wizard();
		wiz.addPage(new ImportChoicePage(wiz));
		wiz.launch();
	});
}

function ImportChoicePage(wiz) {
	this.icon = theme.icons_32._import;
	this.title = "Import Geographic Coordinates";
	this.content = document.createElement("DIV");
	this.validate = function() {
		return null;
	};
	
	var ul = document.createElement("UL");
	this.content.appendChild(ul);
	var li = document.createElement("LI");
	ul.appendChild(li);
	var link = document.createElement("A");
	link.href = '#';
	link.className = "black_link";
	link.innerHTML = "Import from KML (Google Earth Format)";
	link.onclick = function(ev) {
		require("upload.js", function() {
			var up = new upload("/dynamic/geography/service/import_kml", false, true);
			up.openDialog(ev);
		});
		return false;
	};
	li.appendChild(link);
}