function download(backend_url, file_url, target_file, progress_container, end_handler) {
	progress_container.innerHTML = "Starting download...";
	getURLFileSize(backend_url, file_url, function(error,size) {
		if (error) { end_handler(size); return; }
		var start_time = new Date().getTime();
		progress_container.innerHTML = "0% ("+(size/(1024*1024)).toFixed(2)+"M)";
		downloading(backend_url, file_url, size, target_file, function(pos,total) {
			var s = "";
			if (pos > 0) {
				var now = new Date().getTime();
				var rem = (total-pos)*(now-start_time)/pos;
				rem = Math.floor(rem/1000);
				if (rem >= 120) { s = Math.floor(rem/60)+" mintues "; rem -= Math.floor(rem/60)*60; }
				else if (rem >= 60) { s = "1 minute "; rem -= 60; }
				if (rem > 0 || s == "") {
					s += ""+rem+" second";
					if (rem > 1) s += "s";
				}
				s = ", remaining: "+s;
			}
			progress_container.innerHTML = ""+Math.floor(pos*100/total)+"% ("+(pos/(1024*1024)).toFixed(2)+"M/"+(total/(1024*1024)).toFixed(2)+"M)"+s;
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
			var size = parseInt(xhr.responseText);
			if (isNaN(size))
				handler(true, "Unable to get size of download ("+download_url+"): "+xhr.responseText);
			else
				handler(false,size);
		}
	};
	xhr.setRequestHeader('Content-type', "application/x-www-form-urlencoded");
	var data = "url="+encodeURIComponent(download_url)+"&getsize=true";
	xhr.send(data);
}
function downloading(backend_url, download_url, size, file, progress_handler, end_handler) {
	var speed = 256*1024; // start with steps of 256K
	var next = function(from) {
		var start_time = new Date().getTime();
		var end = from + Math.floor(speed);
		if (end >= size) end = size-1;
		downloadRange(backend_url,download_url,from,end,file,function(error,content) {
			if (error) { end_handler(content); return; }
			progress_handler(end+1,size);
			if (end >= size-1) { end_handler(null); return; }
			var end_time = new Date().getTime();
			if (end_time-start_time < 2000) speed *= 10;
			else if (end_time-start_time < 4000) speed *= 5.5;
			else if (end_time-start_time < 6000) speed *= 3.5;
			else if (end_time-start_time < 8000) speed *= 2.5;
			else if (end_time-start_time < 15000) speed *= 1.3;
			else if (end_time-start_time > 25000) speed *= 0.85;
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