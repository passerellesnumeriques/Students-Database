/**
 * @class service
 */
service = {
	/**
	 * Call a service using JSON
	 * @param component the component containing the service
	 * @param service_name the name of the service to call
	 * @param input data to send to the service: an object, each attribute being a $_POST. If an attribute is a structure or array, it will be converted into a json string.
	 * @param handler callback that will receive the result, or null if an error occured
	 * @param foreground if true, the function will return only after completion of the ajax call, else it will return immediately.
	 */
	json: function(component, service_name, input, handler, foreground) {
		var data = "";
		if (input != null) {
			if (typeof input == 'string') data = input;
			else for (var name in input) {
				if (data.length > 0) data += "&";
				data += encodeURIComponent(name)+"="+encodeURIComponent(service._generate_input(input[name]));
			}
		}
		ajax.post_parse_result("/dynamic/"+component+"/service/"+service_name, data, 
			function(result){
				handler(result);
			},
			foreground,
			function(error){
				window.top.status_manager.add_status(new window.top.StatusMessageError(null,error));
			}
		);
	},
	
	_generate_input: function(input) {
		var s = "";
		if (input instanceof Array) {
			s += "[";
			for (var i = 0; i < input.length; ++i) {
				if (i>0) s += ",";
				s += service._generate_input(input[i]);
			}
			s += "]";
		} else if (typeof input == 'object') {
			s += "{";
			var first = true;
			for (var attr in input) {
				if (first) first = false; else s += ",";
				s += attr + ":" + service._generate_input(input[attr]);
			}
			s += "}";
		} else
			s += input;
		return s;
	}
};