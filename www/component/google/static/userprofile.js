function google_userprofile(onready) {
	if (window.top.google.data && window.top.google.data.userprofile) {
		if (onready) onready(window.top.google.data.userprofile);
		return;
	}
		
	window.top.add_javascript("/static/google/google.js",function() {
		window.top.google.need_connection(function(){
			window.top.gapi.client.load('oauth2','v1',function(){
				var req = window.top.gapi.client.oauth2.userinfo.get();
				req.execute(function(resp){
					if (!window.top.google.data) window.top.google.data = {};
					window.top.google.data.userprofile = resp;
					if (onready) onready(resp);
				});
			});
		});
	});
}