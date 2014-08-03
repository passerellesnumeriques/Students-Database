function loadGooglePlaces(callback) {
	window.top.require("google_maps.js", function() {
		window.top.loadGoogleMaps(function() {
			var tmp = document.createElement("CANVAS");
			window.top.google_map_for_places = new window.top.google.maps.Map(tmp);
			callback();
		});
	});
}

function getGooglePlaces(text, callback) {
	loadGooglePlaces(function() {
		var places = new window.top.google.maps.places.PlacesService(window.top.google_map_for_places);
		var request = {
			query: text,//+", "+window.top.default_country_name,
			components: "country:"+window.top.default_country_code.toUpperCase(),
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
	var places = new window.top.google.maps.places.PlacesService(window.top.google_map_for_places);
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
