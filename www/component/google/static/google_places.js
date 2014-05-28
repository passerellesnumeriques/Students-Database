function loadGooglePlaces(callback) {
	if (!window.top.initGooglePlaces) {
		window.top.initGooglePlaces = function() {
			var tmp = document.createElement("CANVAS");
			window.top.google_map = new window.top.google.maps.Map(tmp);
			callback();
		};
		window.top.addJavascript("http://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places&sensor=false&callback=initGooglePlaces");
	} else
		callback();
}

function getGooglePlaces(text, callback) {
	loadGooglePlaces(function() {
		var places = new window.top.google.maps.places.PlacesService(window.top.google_map);
		var request = {
			query: text
		};
		places.textSearch(request, callback);
	});
}
