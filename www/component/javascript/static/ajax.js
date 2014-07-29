/**
 * @namespace
 */
ajax = {
	_interceptors: [],
	/** An interceptor can modify an URL: before every AJAX call, the interceptor will be provided with the URL, and must return an URL (the same or a modified one)
	 * @param {function} interceptor taking an URL as paramter, and must return an URL
	 */
	addInterceptor: function(interceptor) {
		ajax._interceptors.push(interceptor);
	},
	http_response_handlers: [],
	/**
	 * Call all interceptors for the given URL, and return the final one.
	 * @param {string|URL} url
	 * @returns {string|URL}
	 */
	process_url: function(url) {
		var u;
		if (typeof url == 'string') u = new URL(url); else u = url;
		for (var i = 0; i < ajax._interceptors.length; ++i)
			ajax._interceptors[i](u);
		if (typeof url == 'string') return u.toString();
		return u;
	},
	/**
	 * Perform an AJAX request
	 * @param {string} method GET or POST
	 * @param {string|URL} url
	 * @param {string|null} content_type MIME type of the data to send to the server
	 * @param {string|null} content_data the body of the request
	 * @param {Function} error_handler in case of error, this function is called with the error message as parameter
	 * @param {Function} success_handler on success, this function is called with the XMLHttpRequest object as parameter
	 * @param {Boolean} foreground if true this function will block until the AJAX call is done, else this function return immediately and let the AJAX call be done in background
	 * @param {Function} progress_handler callback to be called to display a progress (parameters are current position and total amount)
	 * @param {String} override_response_mime_type if specified, the response will be interpreted as the given mime type
	 */
	call: function(method, url, content_type, content_data, error_handler, success_handler, foreground, progress_handler, override_response_mime_type) {
		if (typeof url == 'string')
			url = new URL(url);
		url = ajax.process_url(url);
		var xhr;
		try { xhr = new XMLHttpRequest(); }
		catch (e) {
			// everything seems to be unloaded as we cannot create new AJAX request
			if (window == window.top) {
				window.top.console.error("AJAX call cancelled because everything is unloaded: "+url);
			} else {
				// try on top
				window.top.ajax.call(method, url, content_type, content_data, error_handler, success_handler, foreground, progress_handler, override_response_mime_type);
			}
			return;
		}
		if (override_response_mime_type && typeof xhr.overrideMimeType != 'undefined')
			xhr.overrideMimeType(override_response_mime_type);
		var aborted = false;
		var timeouted = false;
		try { xhr.open(method, url.toString(), !foreground); }
		catch (e) {
			// error opening the AJAX request
			if (window == window.top) {
				log_exception(e, "while creating AJAX request to "+url.toString()); 
			} else {
				// try on top
				window.top.ajax.call(method, url, content_type, content_data, error_handler, success_handler, foreground, progress_handler, override_response_mime_type);
			}
			return;
		}
		xhr.onabort = function() { aborted = true; };
		xhr.ontimeout = function() { timeouted = true; };
		if (content_type != null)
			xhr.setRequestHeader('Content-type', content_type);
		var sent = function() {
	        if (xhr.status != 200) {
	        	var continu = true;
	        	for (var i = 0; i < ajax.http_response_handlers.length; ++i)
	        		continu &= ajax.http_response_handlers[i](xhr);
	        	if (continu) {
	        		if (xhr.status == 0)
        				return;
	        		error_handler("Error "+xhr.status+": "+xhr.statusText);
	        	}
	        	return; 
	        }
	        success_handler(xhr);
		};
		xhr.onreadystatechange = function() {
	        if (this.readyState != 4) return;
	        sent();
	    };
	    if (progress_handler) {
	    	xhr.onprogress = function(ev) {
	    		progress_handler(ev.loaded, ev.total);
	    	};
	    }
	    try {
	    	xhr.send(content_data);
	    } catch (e) {
	    	log_exception(e, "Sending AJAX request to "+url);
	    	error_handler(e);
	    }
	},
	/**
	 * Perform a POST call to the server
	 * @param {String|URL} url
	 * @param {String|object} data if an object is given, every attribute will be sent as string to the server 
	 * @param {Function} error_handler called in case of error, with the error message as parameter
	 * @param {Function} success_handler called on success, with the XMLHttpRequest as parameter
	 * @param {Boolean} foreground same as for the function call
	 * @param {Function} progress_handler callback to be called to display a progress (parameters are current position and total amount)
	 */
	post: function(url, data, error_handler, success_handler, foreground, progress_handler) {
	    if (typeof data == 'object') {
	    	var s = "";
	    	for (var name in data) {
	    		if (s.length > 0) s += "&";
	    		s += encodeURIComponent(name);
	    		s += "=";
	    		s += encodeURIComponent(data[name]);
	    	}
	    	data = s;
	    }
		ajax.call("POST", url, "application/x-www-form-urlencoded", data, error_handler, success_handler, foreground, progress_handler);
	},
	/**
	 * Perform a POST call, then on success it will analyze the result
	 * @param {String|URL} url
	 * @param {String|Object} data if an object is given, every attribute will be sent as string to the server 
	 * @param {Function} handler callback to call when the AJAX is done (null is given in case of error, else the parsed output)
	 * @param {Boolean} foreground same as for the function call
	 * @param {Function} error_handler callback to be called in case of error (error message given as parameter)
	 * @param {Function} progress_handler callback to be called to display a progress (parameters are current position and total amount)
	 */
	post_parse_result: function(url, data, handler, foreground, error_handler, progress_handler) {
		var eh = function(error) {
			if (error_handler)
				error_handler(error);
			else
				error_dialog(error);
			handler(null);
		};
		ajax.post(url, data, eh, function(xhr) {
			var ct = xhr.getResponseHeader("Content-Type");
			if (ct) {
				var i = ct.indexOf(';');
				if (i > 0) ct = ct.substring(0, i);
			}
			if (ct == "text/xml" || (!ct && xhr.responseXML)) {
				// XML
		        if (xhr.responseXML && xhr.responseXML.childNodes.length > 0) {
		            if (xhr.responseXML.childNodes[0].nodeName == "ok") {
		            	handler(xhr.responseXML.childNodes[0]);
		            	return;
		            }
	                if (xhr.responseXML.childNodes[0].nodeName == "error")
	                	eh(xhr.responseXML.childNodes[0].getAttribute("message"));
	                else
	                	eh(xhr.responseText);
		        } else
		        	eh(xhr.responseText);
		        handler(null);
			} else if (ct == "text/json") {
				// JSON
				if (xhr.responseText.length == 0) {
					eh("Empty response from the server:<ul><li>Request URL: "+url+"</li><li>Request Data: "+data+"</li></ul>");
					return;
				}
				var output;
		        try {
		        	output = eval("("+xhr.responseText+")");
		        } catch (e) {
		        	eh("Invalid json output: "+url+"<br/>Error: "+e+"<br/>Output:<br/>"+xhr.responseText);
		        	return;
		        }
	        	if (output.errors) {
	        		if (output.errors.length == 1)
	        			eh(output.errors[0]);
	        		else
	        			eh(output.errors);
	        		return;
	        	}
	        	if (typeof output.result == 'undefined') {
	        		eh("Error: No result from JSON service");
	        		return;
	        	}
	        	handler(output);
			} else {
				// considered as free text...
				handler(xhr.responseText);
			}
		}, foreground, progress_handler);
	},
	
	/**
	 * Perform a POST call, with custom data type, then on success it will analyze the result
	 * @param {String|URL} url
	 * @param {String} data_type mime type of the input
	 * @param {String|Object} data if an object is given, every attribute will be sent as string to the server 
	 * @param {Function} handler callback to call when the AJAX is done (null is given in case of error, else the parsed output)
	 * @param {Boolean} foreground same as for the function call
	 * @param {Function} error_handler callback to be called in case of error (error message given as parameter)
	 * @param {Function} progress_handler callback to be called to display a progress (parameters are current position and total amount)
	 */
	custom_post_parse_result: function(url, data_type, data, handler, foreground, error_handler, progress_handler) {
		var eh = function(error) {
			if (error_handler)
				error_handler(error);
			else
				error_dialog(error);
			handler(null);
		};
		ajax.call("POST", url, data_type, data, eh, function(xhr) {
			var ct = xhr.getResponseHeader("Content-Type");
			if (ct) {
				var i = ct.indexOf(';');
				if (i > 0) ct = ct.substring(0, i);
			}
			if (ct == "text/xml" || (!ct && xhr.responseXML)) {
				// XML
		        if (xhr.responseXML && xhr.responseXML.childNodes.length > 0) {
		            if (xhr.responseXML.childNodes[0].nodeName == "ok") {
		            	handler(xhr.responseXML.childNodes[0]);
		            	return;
		            }
	                if (xhr.responseXML.childNodes[0].nodeName == "error")
	                	eh(xhr.responseXML.childNodes[0].getAttribute("message"));
	                else
	                	eh(xhr.responseText);
		        } else
		        	eh(xhr.responseText);
		        handler(null);
			} else if (ct == "text/json") {
				// JSON
				if (xhr.responseText.length == 0) {
					eh("Empty response from the server:<ul><li>Request URL: "+url+"</li><li>Request Data: "+data+"</li></ul>");
					return;
				}
				var output;
		        try {
		        	output = eval("("+xhr.responseText+")");
		        } catch (e) {
		        	eh("Invalid json output:<br/>Error: "+e+"<br/>Output:<br/>"+xhr.responseText);
		        	return;
		        }
	        	if (output.errors) {
	        		if (output.errors.length == 1)
	        			eh(output.errors[0]);
	        		else 
	        			eh(output.errors);
	        		return;
	        	}
	        	if (typeof output.result == 'undefined') {
	        		eh("Error: No result from JSON service");
	        		return;
	        	}
	        	handler(output);
			} else {
				// considered as free text...
				handler(xhr.responseText);
			}
		}, foreground, progress_handler);
	}
};