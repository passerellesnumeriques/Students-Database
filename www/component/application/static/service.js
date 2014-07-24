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
	 * @param {Function} progress_handler callback to be called to display a progress (parameters are current position and total amount)
	 */
	json: function(component, service_name, input, handler, foreground, progress_handler, onerror) {
		window.top._last_service_call = new Date().getTime();
		var data = "";
		if (input != null)
			data = service.generateInput(input);
		ajax.custom_post_parse_result("/dynamic/"+component+"/service/"+service_name, "text/json;charset=UTF-8", data, 
			function(result){
				if (result && result.warnings)
					for (var i = 0; i < result.warnings.length; ++i)
						window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_WARNING,result.warnings[i],[{action:"popup"},{action:"close"}],5000));
				handler(result ? result.result : null);
			},
			foreground,
			function(error){
				if (typeof error == 'string')
					window.top.status_manager.add_status(new window.top.StatusMessageError(null,error,10000));
				else for (var i = 0; i < error.length; ++i)
					window.top.status_manager.add_status(new window.top.StatusMessageError(null,error[i],10000));
				if (onerror) onerror(error, input);
			},
			progress_handler
		);
	},
	
	/**
	 * Call a service using XML output
	 * @param {String} component the component containing the service
	 * @param {String} service_name the name of the service to call
	 * @param {Object} input data to send to the service: an object, each attribute being a $_POST. If an attribute is a structure or array, it will be converted into a json string.
	 * @param {Function} handler callback that will receive the result, or null if an error occured
	 * @param {Boolean} foreground if true, the function will return only after completion of the ajax call, else it will return immediately.
	 * @param {Function} progress_handler callback to be called to display a progress (parameters are current position and total amount)
	 */
	xml: function(component, service_name, input, handler, foreground, progress_handler) {
		window.top._last_service_call = new Date().getTime();
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
			},
			progress_handler
		);
	},
	
	/**
	 * Call a service with JSON input, but which return a custom format which should not be analyzed automatically.
	 * @param {String} component the component containing the service
	 * @param {String} service_name the name of the service to call
	 * @param {Object} input data to send to the service: an object, each attribute being a $_POST. If an attribute is a structure or array, it will be converted into a json string.
	 * @param {Function} handler callback that will receive the raw result, or null if a network error occured
	 * @param {Boolean} foreground if true, the function will return only after completion of the ajax call, else it will return immediately.
	 * @param {Function} error_handler callback to be called when an error occured (the error message is given as parameter)
	 * @param {Function} progress_handler callback to be called to display a progress (parameters are current position and total amount)
	 * @param {String} override_response_mime_type if specified, the response will be interpreted as the given mime type
	 */
	customOutput: function(component, service_name, input, handler, foreground, error_handler, progress_handler, override_response_mime_type) {
		window.top._last_service_call = new Date().getTime();
		var data = null;
		if (input != null)
			data = service.generateInput(input);
		ajax.call(data ? "POST" : "GET", "/dynamic/"+component+"/service/"+service_name, data ? "text/json" : null, data, 
			function(error){
				if (error_handler)
					error_handler(error);
				else
					window.top.status_manager.add_status(new window.top.StatusMessageError(null,error,10000));
				handler(null);
			},
			function(xhr){
				handler(xhr.responseText);
			},
			foreground,
			progress_handler,
			override_response_mime_type
		);
	},
	
	/** Call a service with JSON input, and HTML output. The output will be loaded in the given container.
	 * The advantage of using this instead of customOutput is that this function automatically loads scripts if there are some in the HTML.
	 * @param {String} component the component containing the service
	 * @param {String} service_name the name of the service to call
	 * @param {Object} input data to send to the service: an object, each attribute being a $_POST. If an attribute is a structure or array, it will be converted into a json string.
	 * @param {Element|String} container where to put the HTML sent by the service
	 * @param {Function} ondone callback that will receive the raw result, or null if a network error occured
	 */
	html: function(component, service_name, input, container, ondone) {
		service.customOutput(component, service_name, input, function(html) {
			if (!html) html = "";
			if (typeof container == 'string') container = document.getElementById(container);
			// unload scripts
			if (container._attachedScripts)
				for (var i = 0; i < container._attachedScripts.length; ++i)
						container._attachedScripts[i].parentNode.removeChild(container._attachedScripts[i]);
			container.innerHTML = html;
			// take scripts and load them into the head
			container._attachedScripts = [];
			var scripts = container.getElementsByTagName("SCRIPT");
			var list = [];
			for (var i = 0; i < scripts.length; ++i) list.push(scripts[i]);
			for (var i = 0; i < list.length; ++i) list[i].parentNode.removeChild(list[i]);
			var head = document.getElementsByTagName("HEAD")[0];
			for (var i = 0; i < list.length; ++i) {
				var s = document.createElement("SCRIPT");
				s.type = "text/javascript";
				s.textContent = list[i].textContent;
				head.appendChild(s);
				container._attachedScripts.push(s);
			}
			layout.invalidate(container);
			if (ondone) ondone();
		});
	},

	/**
	 * Generate a JSON string from the given object.
	 * @param {Object} input the javascript object to convert into a JSON string
	 * @returns {String} the JSON representation of the given object
	 */
	generateInput: function(input) {
		var s = "";
		if (input == null) return "null";
		if (input instanceof Array || (typeof input == 'object' && getObjectClassName(input) == "Array")) {
			s += "[";
			for (var i = 0; i < input.length; ++i) {
				if (i>0) s += ",";
				s += service.generateInput(input[i]);
			}
			s += "]";
		} else if (input instanceof Date || (typeof input == 'object' && getObjectClassName(input) == "Date")) {
			s += input.getTime()/1000;
		} else if (typeof input == 'object') {
			s += "{";
			var first = true;
			for (var attr in input) {
				if (first) first = false; else s += ",";
				s += "\""+attr + "\":" + service.generateInput(input[attr]);
			}
			s += "}";
		} else if (typeof input == 'string')
			s += "\""+input.replace(/\\/g, "\\\\").replace(/"/g, "\\\"")+"\"";
		else
			s += input;
		return s;
	}
};

if (typeof window.top._last_service_call == 'undefined')
	/** {Number} timestamp of the last call to a service */
	window.top._last_service_call = 0;

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
/** Send the given object to the given URL into the given frame
 * @param {String} url the location where to send the data
 * @param {Object} data the data to send
 * @param {String|Element} frame the frame (or frame name) where the URL will be loaded
 */
function postFrame(url, data, frame) {
	var form = document.createElement("FORM");
	var i = document.createElement("INPUT");
	i.type = "hidden";
	i.name = "input";
	i.value = service.generateInput(data);
	form.appendChild(i);
	form.method = "POST";
	form.action = url;
	form.target = typeof frame == 'string' ? frame : frame.name;
	document.body.appendChild(form);
	form.submit();
	document.body.removeChild(form);
}
if (typeof ajax != 'undefined')
	ajax.http_response_handlers.push(function(xhr){
		if (xhr.status == 403) {
			try {
				var loc = window.top.frames['pn_application_frame'].location;
				var url = new URL(loc.href);
				if (url.path.startsWith("/dynamic/development/") || url.path.startsWith("/dynamic/test/"))
					return false;
			} catch (e) { return false; }
			if (window.top.pnapplication)
				window.top.pnapplication.onlogout.fire();
			window.top.location = "/";
			return false;
		}
		return true;
	});