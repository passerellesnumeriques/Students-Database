if (!window.top.google) {
	window.top.google = {
		connection_status: 0,
		connection_event: new Custom_Event(),
		_connecting_time: 0,
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
		_client_id: "459333498575-p8k0toe6hpcjfe29k83ah77adnocqah4.apps.googleusercontent.com",
		_scopes: "https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/calendar",
		//_scopes: "https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/plus.me https://www.googleapis.com/auth/calendar",
		_connect: function() {
			if (window.top.google.connection_status == 0) return;
			window.top.google.connection_status = 0;
			window.top.google.connection_event.fire();
			window.top.gapi.auth.authorize(
				{
					client_id:window.top.google._client_id,
					scope:window.top.google._scopes,
					immediate:true
				},function(auth_result){
					if (auth_result && !auth_result.error) {
						window.top.google.connection_status = 1;
						window.top.google.connection_event.fire();
						var google_id_set = false;
						var listener = function() {
							if (!google_id_set)
								service.json("google","set_google_id",{auth_token:window.top.gapi.auth.getToken()["access_token"]},function(res){
									if (res) google_id_set = true;
								});
							window.top.pnapplication.onlogin.remove_listener(listener);
						};
						if (window.top.pnapplication.logged_in)
							listener();
						else
							window.top.pnapplication.onlogin.add_listener(listener);
						setTimeout(window.top.google._connect, (parseInt(auth_result.expires_in)-30)*1000);
						return;
					}
					window.top.google.connection_error = "authentication failed";
					window.top.google.connection_status = -1;
					window.top.google.connection_event.fire();
					setTimeout(function() {
						window.top.google._connecting_time = new Date().getTime();
						window.top.google._connect();
					}, 60000);
				}
			);
		},
		try_connect_now: function() {
			if (window.top.google.connection_status != -1) return;
			window.top.google._connecting_time = new Date().getTime();
			window.top.google._connect();
		}
	};
	window.top.google_api_loaded = function(){
		window.top.google.api_loaded = true;
		window.top.google._connecting_time = new Date().getTime();
		window.top.google.connection_status = 0;
		window.top.google.connection_event.fire();
		window.top.gapi.client.setApiKey("AIzaSyBy-4f3HsbxvXJ6sULM87k35JrsGSGs3q8");
		window.top.gapi.auth.init();
		window.top.setInterval(function(){
			if (window.top.google.connection_status != 0) return;
			if (window.top.google._connecting_time < new Date().getTime()-30000) {
				window.top.google.connection_error = "too long to connect";
				window.top.google.connection_status = -1;
				window.top.google.connection_event.fire();
				setTimeout(function() {
					window.top.google._connecting_time = new Date().getTime();
					window.top.google._connect();
				}, 60000);
				return;
			}
			// try again
			window.top.google._connect();
		},10000);
		window.top.google.connection_status = -1;
		window.top.google._connect();
		require("userprofile.js",function() {
			google_userprofile();
		});
	};
	window.top.load_google_api = function() {
		window.top.google._connecting_time = new Date().getTime();
		window.top.add_javascript("https://apis.google.com/js/client.js?onload=google_api_loaded");
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