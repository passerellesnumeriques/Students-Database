function debug_status(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	t.icon = document.createElement("IMG");
	t.icon.src = "/static/development/debug.png";
	t.icon.style.verticalAlign = 'bottom';
	t.icon.style.cursor = "pointer";
	container.appendChild(t.icon);
	
	t.icon.onclick = function() {
		require(["popup_window.js","tabs.js"]);
		service.json("development","get_debug_info",{},function(result){
			require(["popup_window.js","tabs.js"],function(){
				var content = document.createElement("DIV");
				content.style.width = "1000px";
				content.style.height = "800px";
				var tabs_control = new tabs(content, false);

				// sql queries
				var tab = document.createElement("DIV");
				var table = document.createElement("TABLE");
				table.style.border = '1px solid black';
				table.style.borderCollapse = 'collapse';
				var tr, td;
				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TH"));
				td.style.border = '1px solid black';
				td.innerHTML = "URL";
				tr.appendChild(td = document.createElement("TH"));
				td.style.border = '1px solid black';
				td.innerHTML = "DataBase Query";
				tr.appendChild(td = document.createElement("TH"));
				td.style.border = '1px solid black';
				td.innerHTML = "Time";
				for (var i = 0; i < result.requests.length; ++i) {
					var req = result.requests[i];
					table.appendChild(tr = document.createElement("TR"));
					tr.appendChild(td = document.createElement("TD"));
					td.style.border = '1px solid black';
					var rowspan = req.sql_queries.length;
					if (rowspan == 0) rowspan = 1;
					td.rowSpan = rowspan;
					td.style.verticalAlign = "top";
					td.innerHTML = req.url;
					for (var j = 0; j < req.sql_queries.length; ++j) {
						if (j > 0)
							table.appendChild(tr = document.createElement("TR"));
						tr.appendChild(td = document.createElement("TD"));
						td.style.border = '1px solid black';
						var sql = req.sql_queries[j];
						var img = document.createElement("IMG");
						img.src = sql[1] == 0 ? theme.icons_16.ok : theme.icons_16.error;
						img.style.verticalAlign = 'bottom';
						img.style.paddingRight = '5px';
						td.appendChild(img);
						td.appendChild(document.createTextNode(sql[0]));
						if (sql[1] != 0) {
							var div = document.createElement("DIV");
							div.style.color = 'red';
							div.appendChild(document.createTextNode("Error #"+sql[1]+": "+sql[2]));
							td.appendChild(div);
						}
						tr.appendChild(td = document.createElement("TD"));
						td.style.border = '1px solid black';
						td.innerHTML = sql[3]+"s.";
						if (sql[3] > 1) td.style.color = "#FF0000";
						else if (sql[3] > 0.5) td.style.color = "#B00000";
						else if (sql[3] > 0.1) td.style.color = "#700000";
					}
				}
				tab.appendChild(table);
				tabs_control.addTab("DataBase",null,tab);
				
				// locks
				tab = document.createElement("DIV");
				tab.style.width = "100%";
				tab.style.height = "100%";
				tab.style.overflow = "auto";
				tabs_control.addTab("Locks", null, tab);
				
				var popup = new popup_window("Debug Information", "/static/development/debug.png", content);
				popup.show();
			});
		});
	};
}