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
window.download_init_speed = 128*1024; // start with steps of 128K
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