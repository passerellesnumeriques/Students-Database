/**
 * Displays a facebook icon, indicating if the user is connected to its Facebook account
 * @param {DOMNode} container where to put the icon
 */
function facebook_status(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	var w=window;
	
	/** icon element */
	t.icon = document.createElement("IMG");
	t.icon.style.verticalAlign = "bottom";
	t.icon.style.cursor = "pointer";
	t.icon.onclick = function() { t.showMenu(); };
	container.appendChild(t.icon);
	
	/** updates the icon based on current connection status */
	t.updateIcon = function() {
		if (!w.theme) return; // window closed
		var url = "/static/application/icon.php?main=/static/facebook/facebook.png&where=right_bottom&small=";
		switch (w.top.facebook.connection_status) {
		case 0: url += w.theme.icons_10.offline; break;
		case 1: url += w.theme.icons_10.no_connection; break;
		case 2: url += w.theme.icons_10.warning; break;
		case 3: url += w.theme.icons_10.online; break;
		}
		t.icon.src = url;
		if (window.top.facebook.connection_status == 3 && !t.me)
			window.top.FB.api("/me",function (response) { 
				t.me = response;
				t.icon.title = "Facebook Account: "+response.name;
			});
	};
	
	window.top.add_javascript("/static/facebook/facebook.js",function() {
		window.top.facebook.addConnectionStatusListener(function(){t.updateIcon();});
		t.updateIcon();
	});
	
	/** Displays a menu to the user when it clicks on the icon */
	t.showMenu = function() {
/*		if (t.menu) { t.menu.hide(); return; }
		require("context_menu.js",function() {
			t.menu = new context_menu();
			t.menu.onclose = function() { t.menu = null; };
			if (window.top.google.connection_status == -1) {
				t.menu.addIconItem("/static/google/connect.gif","Connect to Google", function() {
					if (t.menu) t.menu.hide();
					window.top.google.ask_connection();
				});
			} else if (window.top.google.connection_status == 0 || !t.profile) {
				t.menu.addIconItem(theme.icons_16.loading, "Connection to Google pending...", function() {
					if (t.menu)
						t.menu.hide();
				});
			} else {
				t.menu.addTitleItem("/static/google/google.png", t.profile.name);
			}
			t.menu.showAboveElement(t.icon);
		});*/
	};
}