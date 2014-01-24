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
		connect: function() {
			window.top.google.connection_status = 0;
			window.top.google.connection_event.fire();
			window.top.google._connecting_time = new Date().getTime();
			window.top.gapi.auth.authorize(
				{
					client_id:window.top.google._client_id,
					scope:window.top.google._scopes,
					immediate:true
				},function(auth_result){
					if (auth_result && !auth_result.error) {
						window.top.google.connection_status = 1;
						window.top.google.connection_event.fire();
						setTimeout(window.top.google.connect, (parseInt(auth_result.expires_in)-30)*1000);
						return;
					}
					window.top.google.connection_status = -1;
					window.top.google.connection_event.fire();
					setTimeout(window.top.google.connect, 60000);
				}
			);
		},
		ask_connection: function() {
			var wt = window.top;
			wt.google.connection_status = 0;
			wt.google.connection_event.fire();
			wt.google._connecting_time = new Date().getTime();
			wt.gapi.auth.authorize(
				{
					client_id:window.top.google._client_id,
					scope:window.top.google._scopes,
					immediate:false
				},function(auth_result){
					if (auth_result && !auth_result.error) {
						wt.google.connection_status = 1;
						wt.google.connection_event.fire();
						setTimeout(wt.google.connect, (parseInt(auth_result.expires_in)-30)*1000);
						return;
					}
					wt.google.connection_status = -1;
					wt.google.connection_event.fire();
				}
			);
		}
	};
	window.top.google_api_loaded = function(){
		window.top.google.api_loaded = true;
		window.top.gapi.client.setApiKey("AIzaSyBy-4f3HsbxvXJ6sULM87k35JrsGSGs3q8");
		window.top.gapi.auth.init();
		window.top.setInterval(function(){
			if (window.top.google.connection_status == 0 && window.top.google._connecting_time < new Date().getTime()-30000) {
				window.top.google.connection_status = -1;
				window.top.google.connection_event.fire();
				setTimeout(window.top.google.connect, 60000);
			}
		},10000);
		window.top.google.connect();
		require("userprofile.js",function() {
			google_userprofile();
		});
	};
	window.top.load_google_api = function() {
		window.top.add_javascript("https://apis.google.com/js/client.js?onload=google_api_loaded");
		window.top.setTimeout(function(){
			if (window.top.google.api_loaded) return;
			window.top.remove_javascript("https://apis.google.com/js/client.js?onload=google_api_loaded");
			window.top.load_google_api();
		},30000);
	};
	window.top.load_google_api();
}
if (typeof require != 'undefined') {
	// declares google as a calendar provider
	require([["calendar.js","google_calendar.js"]]);
}