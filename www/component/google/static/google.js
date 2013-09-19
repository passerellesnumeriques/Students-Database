if (!window.top.google) {
	window.top.google = {
		connection_status: 0,
		connection_listeners: [],
		need_connection: function(on_connected) {
			if (window.top.google.connection_status == 1)
				on_connected();
			else {
				var listener = function(){
					if (window.top.google.connection_status == 1) {
						window.top.google.connection_listeners.remove(listener);
						on_connected();
					}
				};
				window.top.google.connection_listeners.push(listener);
			}
		},
		_client_id: "459333498575-p8k0toe6hpcjfe29k83ah77adnocqah4.apps.googleusercontent.com",
		_scopes: ["https://www.googleapis.com/auth/userinfo.profile","https://www.googleapis.com/auth/calendar"],
		connect: function() {
			window.top.google.connection_status = 0;
			for (var i = 0; i < window.top.google.connection_listeners.length; ++i)
				window.top.google.connection_listeners[i]();
			window.top.gapi.auth.authorize(
				{
					client_id:window.top.google._client_id,
					scope:window.top.google._scopes,
					immediate:true
				},function(auth_result){
					if (auth_result && !auth_result.error) {
						window.top.google.connection_status = 1;
						for (var i = 0; i < window.top.google.connection_listeners.length; ++i)
							window.top.google.connection_listeners[i]();
						setTimeout(window.top.google.connect, (parseInt(auth_result.expires_in)-30)*1000);
						return;
					}
					window.top.google.connection_status = -1;
					for (var i = 0; i < window.top.google.connection_listeners.length; ++i)
						window.top.google.connection_listeners[i]();
					setTimeout(window.top.google.connect, 30000);
				}
			);
		},
		ask_connection: function() {
			window.top.google.connection_status = 0;
			for (var i = 0; i < window.top.google.connection_listeners.length; ++i)
				window.top.google.connection_listeners[i]();
			window.top.gapi.auth.authorize(
				{
					client_id:window.top.google._client_id,
					scope:window.top.google._scopes,
					immediate:false
				},function(auth_result){
					if (auth_result && !auth_result.error) {
						window.top.google.connection_status = 1;
						for (var i = 0; i < window.top.google.connection_listeners.length; ++i)
							window.top.google.connection_listeners[i]();
						setTimeout(window.top.google.connect, (parseInt(auth_result.expires_in)-30)*1000);
						return;
					}
					window.top.google.connection_status = -1;
					for (var i = 0; i < window.top.google.connection_listeners.length; ++i)
						window.top.google.connection_listeners[i]();
				}
			);
		}
	};
	window.top.google_api_loaded = function(){
		window.top.gapi.client.setApiKey("AIzaSyBy-4f3HsbxvXJ6sULM87k35JrsGSGs3q8");
		window.top.gapi.auth.init();
		window.top.google.connect();
	};
	window.top.add_javascript("https://apis.google.com/js/client.js?onload=google_api_loaded");
}
