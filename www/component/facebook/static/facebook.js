if (!window.top.facebook) {
	/** Facebook connection and functionalities */
	window.top.facebook = {
		/** Indicates if the Facebook API is already loaded */
		api_loaded: false,
		/** Status of the connection with Facebook: 0=not connected, 1=pending, 2=not authorized, 3=ok */
		connection_status: 1,
		/** List of listeners called when the connection_status is updated */
		_connection_status_listeners: [],
		/** Change connection_status and called the listeners
		 * @param {Number} status new status
		 */
		setConnectionStatus: function(status) {
			this.connection_status = status;
			for (var i = 0; i < this._connection_status_listeners.length; ++i)
				this._connection_status_listeners[i]();
		},
		/** Add a listener, to be called each time the connection_status is updated */
		addConnectionStatusListener: function(listener) {
			this._connection_status_listeners.push(listener);
		},
		/** Call the given listener when we are connected to Facebook, or immediately if already connected
		 * @param {Function} listener the function to be called
		 */
		onconnect: function(listener) {
			if (this.connection_status == 3)
				listener();
			else
				this.addConnectionStatusListener(function() {
					if (window.top.facebook.connection_status == 3) listener();
				});
		}
	};
	/** Function called by the Facebook API when it is loaded */
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
	    		window.top.facebook.setConnectionStatus(3);
	    	} else if (response.status == 'not_athorized') {
	    		window.top.facebook.setConnectionStatus(2);
	    	} else {
	    		window.top.facebook.setConnectionStatus(0);
	    	}
	    });
	};
	/** Start loading the Facebook API. Automatically called when this script is loaded. */
	window.top.loadFacebookAPI = function() {
		window.top.add_javascript("http://connect.facebook.net/en_US/all.js");
		window.top.setTimeout(function(){
			if (window.top.facebook.api_loaded) return;
			window.top.remove_javascript("http://connect.facebook.net/en_US/all.js");
			window.top.loadFacebookAPI();
		},30000);
	};
	//window.top.loadFacebookAPI();
}