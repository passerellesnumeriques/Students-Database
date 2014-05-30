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
			query: text+", "+window.top.default_country_name,
			language: "en",
			sensor: false
		};
		places.textSearch(request, function(results,status) {
			if (status == "OK") {
				var list = [];
				for (var i = 0; i < results.length; ++i) {
					if (!results[i].formatted_address.toLowerCase().trim().endsWith(", "+window.top.default_country_name.toLowerCase())) {
						continue;
					}
					list.push(results[i]);
				}
				callback(list, null);
			} else if (status == "ZERO_RESULTS")
				callback([], null);
			else
				callback(null, status);
		});
	});
}

function getGooglePlaceDetails(reference, callback) {
	var places = new window.top.google.maps.places.PlacesService(window.top.google_map);
	var request = {
		reference: reference,
		sensor: false,
		language: "en"
	};
	places.getDetails(request, function(place,status) {
		if (status == "OK") callback(place, null);
		else callback(null, status);
	});
}
