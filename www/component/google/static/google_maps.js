function loadGoogleMaps(callback) {
	if (!window.top.googleMapsLoaded) {
		if (!window.top.initGoogleMaps) {
			window.top.initGoogleMapsEvent = new Custom_Event();
			window.top.initGoogleMapsEvent.add_listener(callback);
			window.top.googleMapsLoaded = false;
			window.top.initGoogleMaps = function() {
				window.top.googleMapsLoaded = true;
				window.top.initGoogleMapsEvent.fire();
			};
			window.top.addJavascript("http://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&sensor=false&callback=initGoogleMaps&key=AIzaSyBy-4f3HsbxvXJ6sULM87k35JrsGSGs3q8");
		} else
			window.top.initGoogleMapsEvent.add_listener(callback);
	} else
		callback();
}
