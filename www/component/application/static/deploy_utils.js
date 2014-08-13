function download(backend_url, file_url, target_file, progress_container, end_handler) {
	progress_container.innerHTML = "Starting download...";
	getURLFileSize(backend_url, file_url, function(error,size,accept_ranges,url) {
		if (error) { end_handler(size); return; }
		if (accept_ranges != "bytes") { end_handler("Server does not accept partial download: "+accept_ranges); return; }
		if (url) file_url = url;
		progress_container.innerHTML = "0% ("+(size/(1024*1024)).toFixed(2)+"M)";
		downloading(backend_url, file_url, size, target_file, function(pos,total) {
			progress_container.innerHTML = ""+Math.floor(pos*100/total)+"% ("+(pos/(1024*1024)).toFixed(2)+"M/"+(total/(1024*1024)).toFixed(2)+"M)";
		}, end_handler);
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
			var i = xhr.responseText.indexOf('/');
			var accept_ranges = false;
			var url = null;
			var size;
			if (i < 0)
				size = parseInt(xhr.responseText);
			else {
				size = parseInt(xhr.responseText.substring(0,i));
				accept_ranges = xhr.responseText.substring(i+1);
				i = accept_ranges.indexOf('/');
				if (i > 0) {
					url = accept_ranges.substr(i+1);
					accept_ranges = accept_ranges.substr(0,i);
				}
			}
			if (isNaN(size))
				handler(true, "Unable to get size of download ("+download_url+"): "+xhr.responseText);
			else
				handler(false,size,accept_ranges,url);
		}
	};
	xhr.setRequestHeader('Content-type', "application/x-www-form-urlencoded");
	var data = "url="+encodeURIComponent(download_url)+"&getsize=true";
	xhr.send(data);
}
window.download_init_speed = 128*1024; // start with steps of 128K
function downloading(backend_url, download_url, size, file, progress_handler, end_handler) {
	var speed = window.download_init_speed;
	var next = function(from) {
		var start_time = new Date().getTime();
		var end = from + Math.floor(speed);
		if (end >= size) end = size-1;
		downloadRange(backend_url,download_url,from,end,file,function(error,content) {
			if (error) {
				if (content.indexOf("(#28)") > 0) {
					window.download_init_speed = Math.floor(window.download_init_speed/2);
					if (window.download_init_speed < 32768) window.download_init_speed = 32768;
				}
				end_handler(content);
				return;
			}
			progress_handler(end+1,size);
			if (end >= size-1) { end_handler(null); return; }
			if (content != "cache") {
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
			} else
				speed *= 1.2;
			next(end+1);
		});
	};
	next(0);

}
function downloadRange(backend_url, download_url, from, to, target_file, handler) {
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
	xhr.send(data);
}