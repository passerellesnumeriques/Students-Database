if (!window.top.geography) {
	var wt = window.top;
	wt.geography = {
		_countries: null,
		_countries_ongoing: false,
		_countries_listeners: [],
		_countries_data: [],
		_countries_data_listeners: [],
		getCountries: function(onready) {
			if (wt.geography._countries_ongoing) {
				wt.geography._countries_listeners.push(onready);
				return;
			}
			if (wt.geography._countries == null) {
				wt.geography._countries_ongoing = true;
				wt.geography._countries_listeners.push(onready);
				service.json("geography", "get_countries_list", {}, function(result) {
					if (!result) return;
					wt.geography._countries = result;
					wt.geography._countries_ongoing = false;
					for (var i = 0; i < wt.geography._countries_listeners.length; ++i)
						wt.geography._countries_listeners[i](result);
				});
				return;
			}
			onready(wt.geography._countries);
		},
		getCountry: function(country_id, onready) {
			this.getCountries(function(countries) {
				for (var i = 0; i < countries.length; ++i)
					if (countries[i].country_id == country_id) { onready(countries[i]); return; }
			});
		},
		getCountryName: function(country_id, onready) {
			this.getCountry(country_id, function(country) {
				onready(country.country_name);
			});
		},
		getCountryData: function(country_id, onready) {
			for (var i = 0; i < this._countries_data.length; ++i)
				if (this._countries_data[i].id == country_id) {
					onready(this._countries_data[i].data);
					return;
				}
			for (var i = 0; i < this._countries_data_listeners.length; ++i)
				if (this._countries_data_listeners[i].id == country_id) {
					this._countries_data_listeners[i].listeners.push(onready);
					return;
				}
			this._countries_data_listeners.push({id:country_id,listeners:[onready]});
			var t=this;
			service.json("geography", "get_country_data", {country_id:country_id}, function(result) {
				if (!result) return;
				t._countries_data.push({id:country_id,data:result});
				for (var i = 0; i < t._countries_data_listeners.length; ++i) {
					if (t._countries_data_listeners[i].id == country_id) {
						for (var j = 0; j < t._countries_data_listeners[i].listeners.length; ++j)
							t._countries_data_listeners[i].listeners[j](result);
						t._countries_data_listeners.splice(i,1);
						return;
					}
				}
			});
		}
	};
}