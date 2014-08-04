<html>
<head>
<style type='text/css'>
html, body {
	width: 100%;
	height: 100%;
	margin: 0px;
	padding: 0px;
}
html, body, table {
	font-family: Verdana;
}
#container {
	width: 100%;
	height: 100%;
	display: flex;
	flex-direction: column;
	justify-content: center;
	align-items: center;
	background-color: #D0D0D0;
}
#box {
	border: 1px solid #22bbea;
	border-radius: 5px;
	box-shadow: 5px 5px 5px 0px #808080;
}
#title {
	border-bottom: 1px solid #22bbea;
	font-weight: bold;
	text-align: center;
	font-size: 14pt;
	background-color: #22bbea;
	border-top-left-radius: 5px;
	border-top-right-radius: 5px;
	padding: 3px;
	color: white;
	text-shadow: #40a0c0 0.1em 0.1em 0.1em;
}
#content {
	padding: 10px;
	font-size: 10pt;
	background-color: white;
}
#box>#content:last-child {
	border-bottom-left-radius: 5px;
	border-bottom-right-radius: 5px;
}
#footer {
	border-top: 1px solid #22bbea;
	border-bottom-left-radius: 5px;
	border-bottom-right-radius: 5px;
	padding: 5px;
	background-color: white;
}
button {
    padding: 1px 3px 1px 3px;
    border-radius: 7px;
    background: linear-gradient(to bottom, #3498db, #2980b9);
    color: white;
    font-size: 9pt;
    font-weight: bold;
    text-transform: uppercase;
    border: 1px solid rgba(0,0,0,0);
	text-align: center;
    box-shadow: 1px 1px 1px rgba(170,170,170,0);
    display: inline-block;
    font-family: Arial;
    white-space: nowrap;
    cursor: pointer;
    margin: 1px;
    outline: 1px solid rgba(0,0,0,0);
}
button:hover {
    border: 1px solid rgba(255,255,255,0.4);
    box-shadow: 1px 1px 1px rgba(170,170,170,0.6);
    background: linear-gradient(to bottom, #44a8eb, #3990c9);
}
button:active {
    box-shadow: 0px 0px 0px rgba(170,170,170,0);
    position: relative;
    top: 1px;
    left: 1px;
}
button:focus {
	outline: 1px dotted #404080;
}
</style>
</head>
<body>
<div id='container'>
	<div id='box'>
		<div id='title'>Students Management Software - Installation</div>
		<div id='content'>
		</div>
	</div>
</div>
<script type='text/javascript'>
var content = document.getElementById('content');
function request(url,params,handler) {
	var xhr = new XMLHttpRequest();
	xhr.open("POST","downloader.php", true);
	xhr.onreadystatechange = function() {
	    if (this.readyState != 4) return;
		if (this.statusText == "Error")
			handler(true,xhr.responseText);
		else
			handler(false,xhr.responseText);
	};
	xhr.setRequestHeader('Content-type', "application/x-www-form-urlencoded");
	var data = "url="+encodeURIComponent(url)+params;
	xhr.send(data);
}
function download(url,handler) {
	request(url,"",handler);
}
function getSize(url,handler) {
	request(url,"&getsize=true",handler);
}
function downloadRange(url, file, from, to, handler) {
	request(url,"&range_from="+from+"&range_to="+to+"&target="+encodeURIComponent(file),handler);
}
function downloading(url, file, size, progress_handler, end_handler) {
	var next = function(from) {
		var end = from + 128*1024;
		if (end >= size) end = size-1;
		downloadRange(url,file,from,end,function(error,content) {
			if (error) { end_handler(content); return; }
			progress_handler(end+1,size);
			if (end >= size-1) { end_handler(null); return; }
			next(end+1);
		});
	};
	next(0);
}
function sizeString(size) {
	if (size < 1024) return ""+size;
	if (size < 1024*1024) return (size/1024).toFixed(1)+"K";
	return (size/(1024*1024)).toFixed(2)+"M";
}
function getLatestVersion() {
	content.innerHTML = "Retrieving latest version...";
	download("http://sourceforge.net/projects/studentsdatabase/files/latest.txt/download", function(error,res) {
		if (error) { content.innerHTML = "Error: "+res; return; }
		var version = res;
		content.innerHTML = "Latest version: "+version+"<br/>Starting download...";
		getSize("http://sourceforge.net/projects/studentsdatabase/files/updates/Students_Management_Software_"+version+".zip/download",function(error,res) {
			if (error) {
				content.innerHTML = "Latest version: "+version+"<br/>";
				content.innerHTML += "Error while retrieving file information: "+res;
				return;
			}
			var size = parseInt(res);
			content.innerHTML = "Downloading version "+version+": 0% (0/"+sizeString(size)+")";
			downloading("http://sourceforge.net/projects/studentsdatabase/files/updates/Students_Management_Software_"+version+".zip/download","Students_Management_Software_"+version+".zip",size,function(pos,total) {
				var pc = Math.floor(pos*100/total);
				content.innerHTML = "Downloading version "+version+": "+pc+"% ("+sizeString(pos)+"/"+sizeString(total)+")";
			},function(error) {
				if (error) { content.innerHTML = "Error downloading: "+error; return; }
				content.innerHTML = "Version "+version+" downloaded.<br/>Extracting files...";
				request("Students_Management_Software_"+version+".zip","&unzip=true",function(error,res) {
					if (error) { content.innerHTML += "<br/>Error: "+res; return; }
					window.location.href = "/";
				});
			});
		});
	});
}
getLatestVersion();
</script>
</body>
</html>