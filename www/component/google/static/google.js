if (!window.top.google) {
	window.top.google = {
		connection_status: -1, // -1 = not connected, 0 = connection pending, 1 = connected
		connection_event: new Custom_Event(),
		connected_pn_email: null,
		_connecting_time: 0,
		_api_loaded_event: new Custom_Event(),

		_client_id: window.top.google_local_config.client_id,
		_api_key: window.top.google_local_config.api_key,
		_scopes: "https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/calendar",

		need_connection: function(on_connected) {
			if (window.top.google.connection_status == 1)
				on_connected();
			else {
				var listener = function(){
					if (window.top.google.connection_status == 1) {
						window.top.google.connection_event.remove_listener(listener);
						on_connected();
					}
				};
				window.top.google.connection_event.add_listener(listener);
			}
		},
		
		/* remove the very annoying popup frame of Google saying Welcome Back... */
		_remove_annoying_popup: function() {
			if (typeof window.top.Element.prototype._insertBefore_google == 'undefined') {
				window.top.Element.prototype._insertBefore_google = window.top.Element.prototype.insertBefore;
				window.top.Element.prototype.insertBefore = function(e,b) {
					if (e.nodeName == "IFRAME" && e.src.indexOf('widget/oauthflow/toast') > 0) {
						e.src = "about:blank";
						this._insertBefore(e,b);
						window.top.Element.prototype.insertBefore = window.top.Element.prototype._insertBefore_google;
						window.top.Element.prototype._insertBefore_google = 'undefined';
						return;
					}
					this._insertBefore_google(e,b);
				};
			}
		},
		
		connect: function(pn_email, force_login) {
			if (window.top.google.connection_status == 0) return;
			if (!window.top.google.api_loaded) {
				window.top.google._api_loaded_event.add_listener(function() { window.top.google.connect(pn_email); });
				return;
			}
			window.top.google.connected_pn_email = pn_email;
			window.top.google.connection_status = 0;
			window.top.google.connection_event.fire();
			var received = false;
			setTimeout(function() {
				if (received) return;
				window.top.google.connection_error = "not logged";
				window.top.google.connection_status = -1;
				window.top.google.connection_event.fire();
			}, 30000);
			window.top.gapi.auth.authorize({
				client_id:window.top.google._client_id,
				scope:window.top.google._scopes,
				hd:"passerellesnumeriques.org",
				immediate: !force_login,
				login_hint: pn_email
			},function(auth_result){
				received = true;
				if (auth_result && !auth_result.error) {
					window.top.google.connection_status = 1;
					window.top.google.connection_event.fire();
					setTimeout(function() { window.top.google.connect(window.top.connected_pn_email); }, (parseInt(auth_result.expires_in)-30)*1000);
					return;
				}
				window.top.google.connection_error = "authentication failed";
				window.top.google.connection_status = -1;
				window.top.google.connection_event.fire();
				setTimeout(function() {
					window.top.google._connecting_time = new Date().getTime();
					window.top.google.connect(pn_email);
				}, 60000);
			});
		},
		try_connect_now: function() {
			if (window.top.google.connection_status != -1) return;
			if (window.top.google.connected_pn_email == null) return;
			window.top.google._connecting_time = new Date().getTime();
			window.top.google.connect(window.top.connected_pn_email);
		},
		
		connectAccount: function(onconnected) {
			if (!window.top.google.api_loaded) {
				var locker = lock_screen(null, "Connecting to Google...");
				window.top.google._api_loaded_event.add_listener(function() { unlock_screen(locker); window.top.connectAccount(); });
				return;
			}
			input_dialog("/static/google/google.png","Connect to your Google Account","Please enter your PN email address","",200,function(value){
				value = value.trim();
				if (value.length == 0) return "Please enter an email address";
				var i = value.indexOf('@');
				if (i < 0) return "Please enter a valid email address";
				var username = value.substring(0,i);
				var domain = value.substring(i+1);
				i = username.indexOf('.');
				if (i < 0 || i == username.length-1) return "Please enter a valid email address";
				if (domain.length < 25) return "Please enter a valid PN email address";
				if (domain != "passerellesnumeriques.org" && domain.substring(domain.length-26) != ".passerellesnumeriques.org") return "Please enter a valid PN email address";
				return null;
			}, function(value) {
				if (!value) return;
				window.top.gapi.auth.authorize({
					client_id:window.top.google._client_id,
					scope:window.top.google._scopes,
					hd:"passerellesnumeriques.org",
					immediate: false,
					login_hint: value
				},function(auth_result){
					if (auth_result && !auth_result.error) {
						window.top.google.connected_pn_email = value;
						window.top.google.connection_status = 1;
						window.top.google.connection_event.fire();
						setTimeout(function() { window.top.google.connect(window.top.connected_pn_email); }, (parseInt(auth_result.expires_in)-30)*1000);
						service.json("google","set_google_account",{pn_email:value,auth_token:window.top.gapi.auth.getToken()["access_token"]},function(res){
							if (onconnected) onconnected();
						});
						return;
					}
					window.top.google.connection_error = "authentication failed";
					window.top.google.connection_status = -1;
					window.top.google.connection_event.fire();
				});			
			});
		}
	};
	window.top.google_api_loaded = function(){
		window.top.google.api_loaded = true;
		window.top.gapi.client.setApiKey(window.top.google._api_key);
		window.top.google._api_loaded_event.fire();
		window.top.google._api_loaded_event = null;
	};
	window.top.load_google_api = function() {
		window.top.google._connecting_time = new Date().getTime();
		window.top.addJavascript("https://apis.google.com/js/client.js?onload=google_api_loaded");
		window.top.setTimeout(function(){
			if (window.top.google.api_loaded) return;
			window.top.remove_javascript("https://apis.google.com/js/client.js?onload=google_api_loaded");
			window.top.google.connection_error = "cannot load google api";
			window.top.google.connection_status = -1;
			window.top.google.connection_event.fire();
			window.top.load_google_api();
		},30000);
	};
	window.top.load_google_api();
}
if (typeof require != 'undefined') {
	// declares google as a calendar provider
	require([["calendar.js","google_calendar.js"]]);
}