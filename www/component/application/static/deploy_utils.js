/**
 * Download a file
 * @param {String} backend_url URL to send the requests
 * @param {String} file_url URL of the file to download
 * @param {String} target_file filename to save the downloaded file
 * @param {Element} progress_container where to put progress indications
 * @param {Function} end_handler called when the download is done with an error message, or null if it succeed
 * @param {String} mirror_id ID of a mirror to use, or null if no specific mirror
 * @param {String} mirror_name name of the mirror to use if specified in mirror_id
 */
function download(backend_url, file_url, target_file, progress_container, end_handler, mirror_id, mirror_name) {
	progress_container.innerHTML = "Starting download...";
	getURLFileSize(backend_url, file_url, function(error,res) {
		if (error) { end_handler(res); return; }
		if (res.accept_ranges != "bytes") { end_handler("Server does not accept partial download: "+res.accept_ranges); return; }
		var final_url = file_url;
		if (res.final_url) final_url = res.final_url;
		var progress_text = document.createElement("SPAN");
		progress_container.innerHTML = "";
		progress_container.appendChild(progress_text);
		progress_text.innerHTML = "0% ("+(res.size/(1024*1024)).toFixed(2)+"M)";
		if (mirror_id) {
			var mirror_text = document.createElement("SPAN");
			mirror_text.innerHTML = " using mirror: "+mirror_name;
			progress_container.appendChild(mirror_text);
		} else {
			var i = final_url.indexOf('//');
			if (i > 0) {
				var s = final_url.substr(i+2);
				var j = s.indexOf('/');
				if (j > 0) {
					s = s.substring(0,j);
					var mirror_text = document.createElement("SPAN");
					mirror_text.innerHTML = " using <i>"+s+"</i>";
					progress_container.appendChild(mirror_text);			
				}
			}
		}
		var new_mirror_id = null;
		var new_mirror_name = null;
		downloading(backend_url, final_url, res.size, target_file, res.mirrors_provider, mirror_id, function(pos,total) {
			progress_text.innerHTML = ""+Math.floor(pos*100/total)+"% ("+(pos/(1024*1024)).toFixed(2)+"M/"+(total/(1024*1024)).toFixed(2)+"M)";
			if (new_mirror_id != null) {
				return function() {
					download(backend_url, file_url, target_file, progress_container, end_handler, new_mirror_id, new_mirror_name);
				};
			}
		}, end_handler);
		if (res.mirrors_provider) {
			var span = document.createElement("SPAN");
			span.innerHTML = "Too slow ?";
			span.style.marginLeft = "15px";
			span.style.marginRight = "5px";
			progress_container.appendChild(span);
			var link = document.createElement("A");
			link.href = "#";
			link.innerHTML = "Pick another mirror";
			link.onclick = function() {
				var div = document.createElement("DIV");
				div.style.position = "fixed";
				div.style.top = "0px";
				div.style.left = "0px";
				div.style.border = "1px solid black";
				div.style.backgroundColor = "white";
				div.style.padding = "5px";
				div.innerHTML = "Loading mirrors list...";
				document.body.appendChild(div);
				getMirrorsList(backend_url, res.mirrors_provider, function(list) {
					if (!list) {
						div.innerHTML = "Sorry, we are unable to retrieve the list of available mirrors. ";
						var a = document.createElement("A");
						a.href = "#";
						a.innerHTML = "Close";
						div.appendChild(a);
						a.onclick = function() {
							document.body.removeChild(div);
							return false;
						};
					}
					div.innerHTML = "";
					for (var id in list) {
						var a = document.createElement("A");
						a.href = "#";
						a.innerHTML = list[id];
						a._id = id;
						div.appendChild(a);
						div.appendChild(document.createElement("BR"));
						a.onclick = function() {
							new_mirror_id = this._id;
							new_mirror_name = this.innerHTML;
							document.body.removeChild(div);
							return false;
						};
					}
					var a = document.createElement("A");
					a.href = "#";
					a.innerHTML = "Cancel";
					div.appendChild(a);
					a.onclick = function() {
						document.body.removeChild(div);
						return false;
					};
				});
				return false;
			};
			progress_container.appendChild(link);
		}
	});
}
/**
 * Download a file, but do not save it, instead it will be given as parameter of the handler
 * @param {String} backend_url URL where to send the request
 * @param {String} download_url URL of the file to download
 * @param {Function} handler takes 2 parameters: a boolean set to true in case of error, and a string containing either an error message or the content of the downloaded file
 */
function getURLFile(backend_url, download_url, handler) {
	var xhr = new XMLHttpRequest();
	xhr.open("POST",backend_url, true);
	xhr.onreadystatechange = function() {
	    if (this.readyState != 4) return;
		if (this.statusText == "Error")
			handler(true,xhr.responseText);
		else
			handler(false,xhr.responseText);
	};
	xhr.setRequestHeader('Content-type', "application/x-www-form-urlencoded");
	var data = "url="+encodeURIComponent(download_url);
	xhr.send(data);
}
/**
 * Get the size of the file to download
 * @param {String} backend_url URL where to send to request
 * @param {String} download_url URL of the file we want to know the size
 * @param {Function} handler takes 2 parameters: a boolean set to true in case of error, and either an error message, or an object containing information about the file 
 */
function getURLFileSize(backend_url, download_url, handler) {
	var xhr = new XMLHttpRequest();
	xhr.open("POST",backend_url, true);
	xhr.onreadystatechange = function() {
	    if (this.readyState != 4) return;
		if (this.statusText == "Error")
			handler(true,xhr.responseText);
		else if (xhr.responseText.length == 0)
				handler(true, "Unable to get size of download ("+download_url+")");
		else {
			var res = null;
			try { res = eval('('+xhr.responseText+')'); }
			catch (e) {}
			if (!res || res == -1)
				handler(true, "Unable to get size of download ("+download_url+"): "+xhr.responseText);
			else
				handler(false, res);
		}
	};
	xhr.setRequestHeader('Content-type', "application/x-www-form-urlencoded");
	var data = "url="+encodeURIComponent(download_url)+"&getsize=true";
	xhr.send(data);
}
/**
 * Get a list of mirrors
 * @param {String} backend_url URL where to send the request
 * @param {Object} provider_info information about the provider, given by the function getURLFileSize
 * @param {Function} handler takes 1 parameter which is null if we cannot get a list of mirrors, or the list of mirrors
 */
function getMirrorsList(backend_url, provider_info, handler) {
	var xhr = new XMLHttpRequest();
	xhr.open("POST",backend_url, true);
	xhr.onreadystatechange = function() {
	    if (this.readyState != 4) return;
		if (this.statusText == "Error")
			handler(null);
		else if (xhr.responseText.length == 0)
				handler(null);
		else {
			var res = null;
			try { res = eval('('+xhr.responseText+')'); }
			catch (e) {}
			handler(res);
		}
	};
	xhr.setRequestHeader('Content-type', "application/x-www-form-urlencoded");
	var data = "provider_info="+encodeURIComponent(JSON.stringify(provider_info))+"&getmirrors=true";
	xhr.send(data);	
}
/** Control the initial speed of the download, to adjust in case the connection is very slow and avoid timeout */
window.download_init_speed = 128*1024; // start with steps of 128K
/**
 * Download a file, step by step, calling progress_handler at each step, and end_handler at the end
 * @param {String} backend_url URL where to send the request
 * @param {String} download_url URL of the file to download
 * @param {Number} size the total size of the file to download (previously retrieved through getURLFileSize)
 * @param {String} file filename where to save the downloaded file
 * @param {Object} mirrors_provider_info information about mirrors provider, if any
 * @param {String} mirror_id the mirror to use, or null if no specific mirror
 * @param {Function} progress_handler called step by step to display progress information
 * @param {Function} end_handler called when the download is done
 */
function downloading(backend_url, download_url, size, file, mirrors_provider_info, mirror_id, progress_handler, end_handler) {
	var speed = window.download_init_speed;
	var next = function(from) {
		var start_time = new Date().getTime();
		var end = from + Math.floor(speed);
		if (end >= size) end = size-1;
		downloadRange(backend_url,download_url,from,end,file,mirrors_provider_info,mirror_id,function(error,content) {
			if (error) {
				if (content.indexOf("(#28)") > 0) {
					window.download_init_speed = Math.floor(window.download_init_speed/2);
					if (window.download_init_speed < 32768) window.download_init_speed = 32768;
				}
				end_handler(content);
				return;
			}
			var stop = progress_handler(end+1,size);
			if (end >= size-1) { end_handler(null); return; }
			if (content.substring(0,6) == "cache:") {
				var size_already_downloaded = parseInt(content.substring(6));
				if (!isNaN(size_already_downloaded)) {
					end = size_already_downloaded-1;
					stop = progress_handler(end+1,size);
					if (end >= size-1) { end_handler(null); return; }
				}
			} else {
				var end_time = new Date().getTime();
				if (end_time-start_time < 2000) speed *= 10;
				else if (end_time-start_time < 4000) speed *= 5.5;
				else if (end_time-start_time < 6000) speed *= 3.5;
				else if (end_time-start_time < 8000) speed *= 2.5;
				else if (end_time-start_time < 15000) speed *= 1.3;
				else if (end_time-start_time > 30000) speed *= 0.85;
				if (speed > 2.5*1024*1024) speed = 2.5*1024*1024;
				if (speed < 32768) speed = 32768;
				else if (speed >= 160*1024) window.download_init_speed = 128*1024;
			}
			if (stop) { stop(); return; }
			next(end+1);
		});
	};
	next(0);

}
/**
 * Download a part of a file
 * @param {String} backend_url URL where to send the request
 * @param {String} download_url URL of the file to download
 * @param {Number} from start position of the part to download (in bytes)
 * @param {Number} to end position of the part to download (in bytes)
 * @param {String} target_file filename where to save the result
 * @param {Object} mirrors_provider_info information about the mirrors provider, if any
 * @param {String} mirror_id mirror to use, or null if no specific mirror
 * @param {Function} handler called when the requested part is downloaded
 */
function downloadRange(backend_url, download_url, from, to, target_file, mirrors_provider_info, mirror_id, handler) {
	var xhr = new XMLHttpRequest();
	xhr.open("POST",backend_url, true);
	xhr.onreadystatechange = function() {
	    if (this.readyState != 4) return;
		if (this.statusText == "Error")
			handler(true,xhr.responseText);
		else
			handler(false,xhr.responseText);
	};
	xhr.setRequestHeader('Content-type', "application/x-www-form-urlencoded");
	var data = "url="+encodeURIComponent(download_url)+"&range_from="+from+"&range_to="+to+"&target="+encodeURIComponent(target_file);
	if (mirror_id) data += "&mirror_id="+encodeURIComponent(mirror_id)+"&mirrors_provider="+encodeURIComponent(JSON.stringify(mirrors_provider_info));
	xhr.send(data);
}