function debug_status(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	t.icon = document.createElement("IMG");
	t.icon.src = "/static/development/debug.png";
	t.icon.style.verticalAlign = 'bottom';
	t.icon.style.cursor = "pointer";
	container.appendChild(t.icon);
	
	t.icon.onclick = function() {
		require("popup_window.js");
		service.json("development","get_debug_info",{},function(result){
			require("popup_window.js",function(){
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
				td.innerHTML = "DataBase Queries";
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
					}
				}
				var popup = new popup_window("Debug Information", "/static/development/debug.png", table);
				popup.show();
			});
		});
	};
}