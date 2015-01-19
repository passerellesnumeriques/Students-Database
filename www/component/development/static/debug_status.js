function debug_status(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	t.icon = document.createElement("IMG");
	t.icon.src = "/static/development/debug.png";
	t.icon.style.verticalAlign = 'bottom';
	t.icon.style.cursor = "pointer";
	container.appendChild(t.icon);
	
	t.showDebugPopup = function() {
		window.top.require(["popup_window.js","tabs.js"]);
		service.json("development","get_debug_info",{},function(result){
			window.top.require(["popup_window.js","tabs.js"],function(){
				var content = document.createElement("DIV");
				//content.style.width = (getWindowWidth()-50)+"px";
				//content.style.height = (getWindowHeight()-50)+"px";
				var tabs_control = new window.top.tabs(content, false);
				var tab, table, tr, td;
				
				// requests
				tab = document.createElement("DIV");
				tab.style.width = "100%";
				tab.style.height = "100%";
				//tab.style.overflow = "auto";
				table = document.createElement("TABLE");
				table.style.border = '1px solid black';
				table.style.borderCollapse = 'collapse';
				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TH"));
				td.style.border = '1px solid black';
				td.innerHTML = "URL";
				tr.appendChild(td = document.createElement("TH"));
				td.style.border = '1px solid black';
				td.innerHTML = "Details";
				tr.appendChild(td = document.createElement("TH"));
				td.style.border = '1px solid black';
				td.innerHTML = "Total Time";
				tr.appendChild(td = document.createElement("TH"));
				td.style.border = '1px solid black';
				td.innerHTML = "Session";
				tr.appendChild(td = document.createElement("TH"));
				td.style.border = '1px solid black';
				td.innerHTML = "Process";
				for (var i = 0; i < result.requests.length; ++i) {
					var req = result.requests[i];
					table.appendChild(tr = document.createElement("TR"));
					tr.appendChild(td = document.createElement("TD"));
					td.style.border = '1px solid black';
					td.style.verticalAlign = "top";
					td.style.whiteSpace = "nowrap";
					td.innerHTML = req ? req.url : "<i>empty</i>";
					tr.appendChild(td = document.createElement("TD"));
					td.style.border = '1px solid black';
					td.innerHTML = req ? req.sql_queries.length+" database request(s)" : "";
					td.style.whiteSpace = "nowrap";
					tr.appendChild(td = document.createElement("TD"));
					td.style.border = '1px solid black';
					td.style.whiteSpace = "nowrap";
					if (req && req.end_time > 0) {
						var time = req.end_time-req.start_time;
						td.innerHTML = time.toFixed(4)+"s.";
						if (time > 1) td.style.color = "#FF0000";
						else if (time > 0.5) td.style.color = "#B00000";
						else if (time > 0.1) td.style.color = "#700000";
					}
					tr.appendChild(td = document.createElement("TD"));
					td.style.border = '1px solid black';
					td.style.whiteSpace = "nowrap";
					if (req && req.session_load_time >= 0)
						td.innerHTML = parseFloat(req.session_load_time).toFixed(4);
					tr.appendChild(td = document.createElement("TD"));
					td.style.border = '1px solid black';
					td.style.whiteSpace = "nowrap";
					if (req && req.process_time >= 0)
						td.innerHTML = parseFloat(req.process_time).toFixed(4);
				}
				tab.appendChild(table);
				tabs_control.addTab("Requests",null,tab);
				

				// sql queries
				tab = document.createElement("DIV");
				tab.style.width = "100%";
				tab.style.height = "100%";
				//tab.style.overflow = "auto";
				table = document.createElement("TABLE");
				table.style.border = '1px solid black';
				table.style.borderCollapse = 'collapse';
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
					td.style.borderTop = '2px solid black';
					td.style.whiteSpace = "nowrap";
					var rowspan = req ? req.sql_queries.length : 0;
					if (rowspan == 0) rowspan = 1;
					td.rowSpan = rowspan;
					td.style.verticalAlign = "top";
					td.style.whiteSpace = "nowrap";
					td.innerHTML = req ? req.url : "<i>No info</i>";
					if (req)
					for (var j = 0; j < req.sql_queries.length; ++j) {
						if (j > 0)
							table.appendChild(tr = document.createElement("TR"));
						tr.appendChild(td = document.createElement("TD"));
						td.style.border = '1px solid black';
						if (j == 0) td.style.borderTop = '2px solid black';
						td.style.whiteSpace = "nowrap";
						td.innerHTML = (j+1)+". ";
						var sql = req.sql_queries[j];
						var img = document.createElement("IMG");
						img.src = sql[1] == 0 ? theme.icons_16.ok : theme.icons_16.error;
						img.style.verticalAlign = 'bottom';
						img.style.paddingRight = '5px';
						td.appendChild(img);
						td.appendChild(document.createTextNode(sql[0]));
						img = document.createElement("IMG");
						img.src = theme.icons_10.arrow_down_context_menu;
						img.className = "button_verysoft";
						img.style.verticalAlign = "middle";
						img.style.padding = "0px";
						img.trace = sql[4];
						img.onclick = function() {
							var t=this;
							require("context_menu.js", function() {
								var menu = new context_menu();
								for (var i = 0; i < t.trace.length; ++i) {
									var div = document.createElement("DIV");
									div.appendChild(document.createTextNode(t.trace[i][0]+":"+t.trace[i][1]));
									menu.addItem(div);
								}
								menu.showBelowElement(t);
							});
						};
						td.appendChild(img);
						if (sql[1] != 0) {
							var div = document.createElement("DIV");
							div.style.color = 'red';
							div.appendChild(document.createTextNode("Error #"+sql[1]+": "+sql[2]));
							td.appendChild(div);
						}
						tr.appendChild(td = document.createElement("TD"));
						td.style.border = '1px solid black';
						if (j == 0) td.style.borderTop = '2px solid black';
						td.style.whiteSpace = "nowrap";
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
				//tab.style.overflow = "auto";
				table = document.createElement("TABLE");
				table.style.border = '1px solid black';
				table.style.borderCollapse = 'collapse';
				table.appendChild(tr = document.createElement("TR"));
				tr.appendChild(td = document.createElement("TH"));
				td.style.border = '1px solid black';
				td.innerHTML = "Table";
				tr.appendChild(td = document.createElement("TH"));
				td.style.border = '1px solid black';
				td.innerHTML = "Column";
				tr.appendChild(td = document.createElement("TH"));
				td.style.border = '1px solid black';
				td.innerHTML = "Row";
				tr.appendChild(td = document.createElement("TH"));
				td.style.border = '1px solid black';
				td.innerHTML = "Check";
				var js_locks = [];
				for (var i = 0; i < window.top.databaselock._locks.length; ++i) js_locks.push(window.top.databaselock._locks[i].id);
				for (var i = 0; i < result.locks.length; ++i) {
					table.appendChild(tr = document.createElement("TR"));
					tr.appendChild(td = document.createElement("TD"));
					td.style.border = '1px solid black';
					td.innerHTML = result.locks[i].table;
					tr.appendChild(td = document.createElement("TD"));
					td.style.border = '1px solid black';
					td.innerHTML = result.locks[i].column;
					tr.appendChild(td = document.createElement("TD"));
					td.style.border = '1px solid black';
					td.innerHTML = result.locks[i].row_key;
					tr.appendChild(td = document.createElement("TD"));
					td.style.border = '1px solid black';
					var found = false;
					for (var j = 0; j < js_locks.length; ++j) {
						if (js_locks[j] == result.locks[i].id) {
							found = true;
							js_locks.splice(j,1);
							break;
						}
					}
					td.innerHTML = found ? "OK" : "NOT REGISTERED";
				}
				for (var i = 0; i < js_locks.length; ++i) {
					table.appendChild(tr = document.createElement("TR"));
					tr.appendChild(td = document.createElement("TD"));
					td.style.border = '1px solid black';
					td.colSpan = 4;
					td.innerHTML = "Lock ID "+js_locks[i]+" is not in database !";
				}
				tab.appendChild(table);
				tabs_control.addTab("Locks", null, tab);
				
				// requests
				tab = document.createElement("DIV");
				tab.style.width = "100%";
				tab.style.height = "100%";
				tab.style.padding = "10px";
				var cb_profiling = document.createElement("INPUT");
				cb_profiling.type = 'checkbox';
				cb_profiling.checked = result.cfg.xdebug_profiling ? 'checked' : '';
				tab.appendChild(cb_profiling);
				tab.appendChild(document.createTextNode("Enable XDebug profiling"));
				tab.appendChild(document.createElement("BR"));
				var save_cfg = document.createElement("BUTTON");
				save_cfg.innerHTML = "Save";
				save_cfg.onclick = function() {
					if (cb_profiling.checked)
						setCookie("XDEBUG_PROFILE","1",60,"/dynamic/");
					else
						removeCookie("XDEBUG_PROFILE","/dynamic/");
				};
				tab.appendChild(save_cfg);
				tab.appendChild(document.createElement("BR"));
				var phpinfo = document.createElement("A");
				phpinfo.innerHTML = "php info";
				phpinfo.href = '#';
				phpinfo.onclick = function() {
					window.top.popupFrame(null,"PHP Info","/dynamic/development/page/phpinfo");
					return false;
				};
				tab.appendChild(phpinfo);
				tabs_control.addTab("Configuration", null, tab);

				
				var popup = new window.top.popup_window("Debug Information", "/static/development/debug.png", content);
				popup.show();
			});
		});
	};
	
	t.icon.onclick = function() { t.showDebugPopup(); };
}