function google_status(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	t.icon = document.createElement("IMG");
	t.icon.src = "/static/application/icon.php?main=/static/google/google.png&where=right_bottom&small="+theme.icons_10.no_connection;
	t.icon.style.verticalAlign = "bottom";
	t.icon.style.cursor = "pointer";
	t.icon.onclick = function() { t.show_menu(); };
	container.appendChild(t.icon);
	
	t.update_icon = function() {
		var url = "/static/application/icon.php?main=/static/google/google.png&where=right_bottom&small=";
		switch (window.top.google.connection_status) {
		case 0: url += theme.icons_10.no_connection; break;
		case 1: url += theme.icons_10.online; break;
		case -1: url += theme.icons_10.offline; break;
		}
		t.icon.src = url;
		if (window.top.google.connection_status == 1) {
			add_javascript("/static/google/userprofile.js",function(){
				google_userprofile(function(profile){
					t.profile = profile;
					t.icon.title = "Google Account: "+profile.name;
				});
			});
		}
	};
	
	window.top.add_javascript("/static/google/google.js",function() {
		window.top.google.connection_listeners.push(function(){t.update_icon();});
		t.update_icon();
	});
	
	t.show_menu = function() {
		if (t.menu) { t.menu.hide(); return; }
		require("context_menu.js",function() {
			t.menu = new context_menu();
			t.menu.onclose = function() { t.menu = null; };
			if (window.top.google.connection_status == -1) {
				t.menu.addIconItem("/static/google/connect.gif","Connect to Google", function() {
					t.menu.hide();
					window.top.google.ask_connection();
				});
			} else if (window.top.google.connection_status == 0 || !t.profile) {
				t.menu.addIconItem(theme.icons_16.loading, "Connection to Google pending...", function() {
					t.menu.hide();
				});
			} else {
				t.menu.addTitleItem("/static/google/google.png", t.profile.name);
			}
			t.menu.showAboveElement(t.icon);
		});
	};
}