if (!window.top.facebook) {
	window.top.facebook = {
		api_loaded: false,
		connection_status: 1, // 0=not connected, 1=pending, 2=not authorized, 3=ok
		_connection_status_listeners: [],
		set_connection_status: function(status) {
			this.connection_status = status;
			for (var i = 0; i < this._connection_status_listeners.length; ++i)
				this._connection_status_listeners[i]();
		},
		add_connection_status_listener: function(listener) {
			this._connection_status_listeners.push(listener);
		},
		onconnect: function(listener) {
			if (this.connection_status == 3)
				listener();
			else
				this.add_connection_status_listener(function() {
					if (window.top.facebook.connection_status == 3) listener();
				});
		}
	};
	window.top.fbAsyncInit = function() {
		window.top.facebook.api_loaded = true;
	    window.top.FB.init({
	      appId      : '316910509803',                        // App ID from the app dashboard
	      status     : true                                 // Check Facebook Login status
	    });
	    FB.getLoginStatus(function(response) {
	    	if (response.status == 'connected') {
	    		window.top.facebook.user_id = response.authResponse.userID;
	    		window.top.facebook.access_token = response.authResponse.accessToken;
	    		window.top.facebook.set_connection_status(3);
	    	} else if (response.status == 'not_athorized') {
	    		window.top.facebook.set_connection_status(2);
	    	} else {
	    		window.top.facebook.set_connection_status(0);
	    	}
	    });
	};
	window.top.load_facebook_api = function() {
		window.top.add_javascript("http://connect.facebook.net/en_US/all.js");
		window.top.setTimeout(function(){
			if (window.top.facebook.api_loaded) return;
			window.top.remove_javascript("http://connect.facebook.net/en_US/all.js");
			window.top.load_facebook_api();
		},30000);
	};
	// TODO window.top.load_facebook_api();
}