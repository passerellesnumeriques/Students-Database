/** Application status, indicating online/offline
 * @param {Element} container where to put the icon of the status
 */
function app_status(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	var w=window;

	/** {Element} online green icon */
	t.icon_online = document.createElement("IMG");
	t.icon_online.src = theme.icons_10.online;
	t.icon_online.style.visibility = 'visible';
	t.icon_online.style.position = "absolute";
	t.icon_online.style.top = "3px";
	t.icon_online.style.left = "3px";
	/** {Element} offline red icon */
	t.icon_offline = document.createElement("IMG");
	t.icon_offline.src = theme.icons_10.offline;
	t.icon_offline.style.visibility = 'hidden';
	t.icon_offline.style.position = "absolute";
	t.icon_offline.style.top = "3px";
	t.icon_offline.style.left = "3px";
	/** {Element} semi-online orange icon */
	t.icon_semionline = document.createElement("IMG");
	t.icon_semionline.src = theme.icons_10.semi_online;
	t.icon_semionline.style.visibility = 'hidden';
	t.icon_semionline.style.position = "absolute";
	t.icon_semionline.style.top = "3px";
	t.icon_semionline.style.left = "3px";
	container.appendChild(t.icon_online);
	container.appendChild(t.icon_offline);
	container.appendChild(t.icon_semionline);
	container.style.height = "16px";
	container.style.width = "16px";
	
	/** {Boolean} indicates if the app was offline during last check */
	t._last_offline = false;
	/** Update the icon */
	t.updateIcon = function() {
		if (!w.theme) return; // window closed
		t.icon_online.style.visibility = 'hidden';
		t.icon_offline.style.visibility = 'hidden';
		t.icon_semionline.style.visibility = 'hidden';
		if (w.top.ping_time == -2) {
			t._last_offline = true;
			t.icon_offline.style.visibility = 'visible';
			window.top.status_manager.addStatus(new window.top.StatusMessage(window.top.Status_TYPE_ERROR_NOICON,"Sorry, we lost the connection to the server...",[],5000));
		} else {
			if (t._last_offline) {
				t._last_offline = false;
				window.top.status_manager.addStatus(new window.top.StatusMessage(window.top.Status_TYPE_INFO,"We're back! Connection to the server restored.",[],5000));
			}
			if (w.top.ping_time == -1)
				t.icon_online.style.visibility = 'visible';
			else if (w.top.ping_time < 5000)
				t.icon_online.style.visibility = 'visible';
			else
				t.icon_semionline.style.visibility = 'visible';
		}
	};

	window.top.ping_event.addListener(function(res) {
		t.updateIcon();
	});
}