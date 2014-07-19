function google_status(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	var w=window;
	
	t.icon = document.createElement("IMG");
	t.icon.src = "/static/application/icon.php?main=/static/google/google.png&where=right_bottom&small="+theme.icons_10.no_connection;
	t.icon.style.verticalAlign = "bottom";
	t.icon.style.cursor = "pointer";
	t.icon.onclick = function() { t.show_menu(); };
	container.appendChild(t.icon);
	
	t.update_icon = function() {
		if (!w.theme) return; // window closed
		var url = "/static/application/icon.php?main=/static/google/google.png&where=right_bottom&small=";
		switch (w.top.google.connection_status) {
		case 0: url += w.theme.icons_10.no_connection; break;
		case 1: url += w.theme.icons_10.online; break;
		case -1: url += w.theme.icons_10.offline; break;
		}
		t.icon.src = url;
		if (w.top.google.connection_status == 1) {
			w.top.addJavascript("/static/google/userprofile.js",function(){
				w.top.google_userprofile(function(profile){
					t.profile = profile;
					t.icon.title = "Google Account: "+profile.name;
				});
			});
		}
	};
	
	window.top.addJavascript("/static/google/google.js",function() {
		window.top.google.connection_event.add_listener(function(){t.update_icon();});
		t.update_icon();
	});
	
	t.show_menu = function() {
		if (t.menu) { t.menu.hide(); return; }
		require("context_menu.js",function() {
			t.menu = new context_menu();
			t.menu.onclose = function() { t.menu = null; };
			if (window.top.google.connection_status == -1) {
				if (window.top.google.connected_pn_email == null) {
					t.menu.addIconItem(null, "Connect your PN Google account", function() {
						if (t.menu) t.menu.hide();
						window.top.google.connectAccount();
					});
				} else {
					t.menu.addIconItem(null,"Login to Google", function() {
						if (t.menu) t.menu.hide();
						window.top.google.connect(window.top.google.connected_pn_email, true);
					});
				}
			} else if (window.top.google.connection_status == 0 || !t.profile) {
				t.menu.addIconItem(theme.icons_16.loading, "Connection to Google in progress...", function() {
					if (t.menu)
						t.menu.hide();
				});
			} else {
				t.menu.addTitleItem("/static/google/google.png", "Connected ("+t.profile.name+")");
			}
			t.menu.showAboveElement(t.icon);
		});
	};
}