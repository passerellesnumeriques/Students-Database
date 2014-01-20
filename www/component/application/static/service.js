/**
 * Functionalities to call services using AJAX requests.
 */
service = {
	/**
	 * Call a service using JSON
	 * @param {String} component the component containing the service
	 * @param {String} service_name the name of the service to call
	 * @param {Object} input data to send to the service: an object, each attribute being a $_POST. If an attribute is a structure or array, it will be converted into a json string.
	 * @param {Function} handler callback that will receive the result, or null if an error occured
	 * @param {Boolean} foreground if true, the function will return only after completion of the ajax call, else it will return immediately.
	 */
	json: function(component, service_name, input, handler, foreground) {
		var data = "";
		if (input != null)
			data = service.generateInput(input);
		ajax.custom_post_parse_result("/dynamic/"+component+"/service/"+service_name, "text/json;charset=UTF-8", data, 
			function(result){
				handler(result ? result.result : null);
			},
			foreground,
			function(error){
				window.top.status_manager.add_status(new window.top.StatusMessageError(null,error,10000));
			}
		);
	},
	
	/**
	 * Call a service using XML output
	 * @param {String} component the component containing the service
	 * @param {String} service_name the name of the service to call
	 * @param {Object} input data to send to the service: an object, each attribute being a $_POST. If an attribute is a structure or array, it will be converted into a json string.
	 * @param {Function} handler callback that will receive the result, or null if an error occured
	 * @param {Boolean} foreground if true, the function will return only after completion of the ajax call, else it will return immediately.
	 */
	xml: function(component, service_name, input, handler, foreground) {
		var data = "";
		if (input != null) {
			if (typeof input == 'string') data = input;
			else for (var name in input) {
				if (data.length > 0) data += "&";
				data += encodeURIComponent(name)+"=";
				if (typeof input[name] == 'string')
					data += encodeURIComponent(input[name]);
				else
					data += encodeURIComponent(service.generateInput(input[name]));
			}
		}
		ajax.post_parse_result("/dynamic/"+component+"/service/"+service_name, data, 
			function(xml){
				handler(xml);
			},
			foreground,
			function(error){
				window.top.status_manager.add_status(new window.top.StatusMessageError(null,error,10000));
			}
		);
	},
	
	/**
	 * Call a service with JSON input, but which return a custom format which should not be analyzed automatically.
	 * @param {String} component the component containing the service
	 * @param {String} service_name the name of the service to call
	 * @param {Object} input data to send to the service: an object, each attribute being a $_POST. If an attribute is a structure or array, it will be converted into a json string.
	 * @param {Function} handler callback that will receive the raw result, or null if a network error occured
	 * @param {Boolean} foreground if true, the function will return only after completion of the ajax call, else it will return immediately.
	 */
	customOutput: function(component, service_name, input, handler, foreground) {
		var data = "";
		if (input != null)
			data = service.generateInput(input);
		ajax.call("POST", "/dynamic/"+component+"/service/"+service_name, "text/json", data, 
			function(error){
				window.top.status_manager.add_status(new window.top.StatusMessageError(null,error,10000));
				handler(null);
			},
			function(xhr){
				handler(xhr.responseText);
			},
			foreground
		);
	},

	/**
	 * Generate a JSON string from the given object.
	 * @param {Object} input the javascript object to convert into a JSON string
	 * @returns {String} the JSON representation of the given object
	 */
	generateInput: function(input) {
		var s = "";
		if (input == null) return "null";
		if (input instanceof Array || (typeof input == 'object' && input.constructor.name == "Array")) {
			s += "[";
			for (var i = 0; i < input.length; ++i) {
				if (i>0) s += ",";
				s += service.generateInput(input[i]);
			}
			s += "]";
		} else if (input instanceof Date) {
			s += input.getTime();
		} else if (typeof input == 'object') {
			s += "{";
			var first = true;
			for (var attr in input) {
				if (first) first = false; else s += ",";
				s += "\""+attr + "\":" + service.generateInput(input[attr]);
			}
			s += "}";
		} else if (typeof input == 'string')
			s += "\""+input.replace(/"/g, "\\\"")+"\"";
		else
			s += input;
		return s;
	}
};
/**
 * Send the given object to the given URL using POST method.
 * @param {String} url the location where to send the data
 * @param {Object} data the data to send
 * @param {window} win the window used to send the data, which will be used to display the resulting page
 */
function postData(url, data, win) {
	if (!win) win = window;
	var form = win.document.createElement("FORM");
	var i = win.document.createElement("INPUT");
	i.type = "hidden";
	i.name = "input";
	i.value = service.generateInput(data);
	form.appendChild(i);
	form.method = "POST";
	form.action = url;
	win.document.body.appendChild(form);
	form.submit();
}
if (typeof ajax != 'undefined')
	ajax.http_response_handlers.push(function(xhr){
		if (xhr.status == 403) {
			window.top.location = "/";
			return false;
		}
		return true;
	});