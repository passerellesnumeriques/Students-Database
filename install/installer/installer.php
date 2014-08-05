<html>
<head>
<title>PN Students Management Software - Installation</title>
<script type='text/javascript' src='deploy_utils.js'></script>
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
		<div id='title'>
			<img src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAKT2lDQ1BQaG90b3Nob3AgSUNDIHByb2ZpbGUAAHjanVNnVFPpFj333vRCS4iAlEtvUhUIIFJCi4AUkSYqIQkQSoghodkVUcERRUUEG8igiAOOjoCMFVEsDIoK2AfkIaKOg6OIisr74Xuja9a89+bN/rXXPues852zzwfACAyWSDNRNYAMqUIeEeCDx8TG4eQuQIEKJHAAEAizZCFz/SMBAPh+PDwrIsAHvgABeNMLCADATZvAMByH/w/qQplcAYCEAcB0kThLCIAUAEB6jkKmAEBGAYCdmCZTAKAEAGDLY2LjAFAtAGAnf+bTAICd+Jl7AQBblCEVAaCRACATZYhEAGg7AKzPVopFAFgwABRmS8Q5ANgtADBJV2ZIALC3AMDOEAuyAAgMADBRiIUpAAR7AGDIIyN4AISZABRG8lc88SuuEOcqAAB4mbI8uSQ5RYFbCC1xB1dXLh4ozkkXKxQ2YQJhmkAuwnmZGTKBNA/g88wAAKCRFRHgg/P9eM4Ors7ONo62Dl8t6r8G/yJiYuP+5c+rcEAAAOF0ftH+LC+zGoA7BoBt/qIl7gRoXgugdfeLZrIPQLUAoOnaV/Nw+H48PEWhkLnZ2eXk5NhKxEJbYcpXff5nwl/AV/1s+X48/Pf14L7iJIEyXYFHBPjgwsz0TKUcz5IJhGLc5o9H/LcL//wd0yLESWK5WCoU41EScY5EmozzMqUiiUKSKcUl0v9k4t8s+wM+3zUAsGo+AXuRLahdYwP2SycQWHTA4vcAAPK7b8HUKAgDgGiD4c93/+8//UegJQCAZkmScQAAXkQkLlTKsz/HCAAARKCBKrBBG/TBGCzABhzBBdzBC/xgNoRCJMTCQhBCCmSAHHJgKayCQiiGzbAdKmAv1EAdNMBRaIaTcA4uwlW4Dj1wD/phCJ7BKLyBCQRByAgTYSHaiAFiilgjjggXmYX4IcFIBBKLJCDJiBRRIkuRNUgxUopUIFVIHfI9cgI5h1xGupE7yAAygvyGvEcxlIGyUT3UDLVDuag3GoRGogvQZHQxmo8WoJvQcrQaPYw2oefQq2gP2o8+Q8cwwOgYBzPEbDAuxsNCsTgsCZNjy7EirAyrxhqwVqwDu4n1Y8+xdwQSgUXACTYEd0IgYR5BSFhMWE7YSKggHCQ0EdoJNwkDhFHCJyKTqEu0JroR+cQYYjIxh1hILCPWEo8TLxB7iEPENyQSiUMyJ7mQAkmxpFTSEtJG0m5SI+ksqZs0SBojk8naZGuyBzmULCAryIXkneTD5DPkG+Qh8lsKnWJAcaT4U+IoUspqShnlEOU05QZlmDJBVaOaUt2ooVQRNY9aQq2htlKvUYeoEzR1mjnNgxZJS6WtopXTGmgXaPdpr+h0uhHdlR5Ol9BX0svpR+iX6AP0dwwNhhWDx4hnKBmbGAcYZxl3GK+YTKYZ04sZx1QwNzHrmOeZD5lvVVgqtip8FZHKCpVKlSaVGyovVKmqpqreqgtV81XLVI+pXlN9rkZVM1PjqQnUlqtVqp1Q61MbU2epO6iHqmeob1Q/pH5Z/YkGWcNMw09DpFGgsV/jvMYgC2MZs3gsIWsNq4Z1gTXEJrHN2Xx2KruY/R27iz2qqaE5QzNKM1ezUvOUZj8H45hx+Jx0TgnnKKeX836K3hTvKeIpG6Y0TLkxZVxrqpaXllirSKtRq0frvTau7aedpr1Fu1n7gQ5Bx0onXCdHZ4/OBZ3nU9lT3acKpxZNPTr1ri6qa6UbobtEd79up+6Ynr5egJ5Mb6feeb3n+hx9L/1U/W36p/VHDFgGswwkBtsMzhg8xTVxbzwdL8fb8VFDXcNAQ6VhlWGX4YSRudE8o9VGjUYPjGnGXOMk423GbcajJgYmISZLTepN7ppSTbmmKaY7TDtMx83MzaLN1pk1mz0x1zLnm+eb15vft2BaeFostqi2uGVJsuRaplnutrxuhVo5WaVYVVpds0atna0l1rutu6cRp7lOk06rntZnw7Dxtsm2qbcZsOXYBtuutm22fWFnYhdnt8Wuw+6TvZN9un2N/T0HDYfZDqsdWh1+c7RyFDpWOt6azpzuP33F9JbpL2dYzxDP2DPjthPLKcRpnVOb00dnF2e5c4PziIuJS4LLLpc+Lpsbxt3IveRKdPVxXeF60vWdm7Obwu2o26/uNu5p7ofcn8w0nymeWTNz0MPIQ+BR5dE/C5+VMGvfrH5PQ0+BZ7XnIy9jL5FXrdewt6V3qvdh7xc+9j5yn+M+4zw33jLeWV/MN8C3yLfLT8Nvnl+F30N/I/9k/3r/0QCngCUBZwOJgUGBWwL7+Hp8Ib+OPzrbZfay2e1BjKC5QRVBj4KtguXBrSFoyOyQrSH355jOkc5pDoVQfujW0Adh5mGLw34MJ4WHhVeGP45wiFga0TGXNXfR3ENz30T6RJZE3ptnMU85ry1KNSo+qi5qPNo3ujS6P8YuZlnM1VidWElsSxw5LiquNm5svt/87fOH4p3iC+N7F5gvyF1weaHOwvSFpxapLhIsOpZATIhOOJTwQRAqqBaMJfITdyWOCnnCHcJnIi/RNtGI2ENcKh5O8kgqTXqS7JG8NXkkxTOlLOW5hCepkLxMDUzdmzqeFpp2IG0yPTq9MYOSkZBxQqohTZO2Z+pn5mZ2y6xlhbL+xW6Lty8elQfJa7OQrAVZLQq2QqboVFoo1yoHsmdlV2a/zYnKOZarnivN7cyzytuQN5zvn//tEsIS4ZK2pYZLVy0dWOa9rGo5sjxxedsK4xUFK4ZWBqw8uIq2Km3VT6vtV5eufr0mek1rgV7ByoLBtQFr6wtVCuWFfevc1+1dT1gvWd+1YfqGnRs+FYmKrhTbF5cVf9go3HjlG4dvyr+Z3JS0qavEuWTPZtJm6ebeLZ5bDpaql+aXDm4N2dq0Dd9WtO319kXbL5fNKNu7g7ZDuaO/PLi8ZafJzs07P1SkVPRU+lQ27tLdtWHX+G7R7ht7vPY07NXbW7z3/T7JvttVAVVN1WbVZftJ+7P3P66Jqun4lvttXa1ObXHtxwPSA/0HIw6217nU1R3SPVRSj9Yr60cOxx++/p3vdy0NNg1VjZzG4iNwRHnk6fcJ3/ceDTradox7rOEH0x92HWcdL2pCmvKaRptTmvtbYlu6T8w+0dbq3nr8R9sfD5w0PFl5SvNUyWna6YLTk2fyz4ydlZ19fi753GDborZ752PO32oPb++6EHTh0kX/i+c7vDvOXPK4dPKy2+UTV7hXmq86X23qdOo8/pPTT8e7nLuarrlca7nuer21e2b36RueN87d9L158Rb/1tWeOT3dvfN6b/fF9/XfFt1+cif9zsu72Xcn7q28T7xf9EDtQdlD3YfVP1v+3Njv3H9qwHeg89HcR/cGhYPP/pH1jw9DBY+Zj8uGDYbrnjg+OTniP3L96fynQ89kzyaeF/6i/suuFxYvfvjV69fO0ZjRoZfyl5O/bXyl/erA6xmv28bCxh6+yXgzMV70VvvtwXfcdx3vo98PT+R8IH8o/2j5sfVT0Kf7kxmTk/8EA5jz/GMzLdsAAAAGYktHRAD/AP8A/6C9p5MAAAAJcEhZcwAALiMAAC4jAXilP3YAAAJ0SURBVDjLbZNPSBRxFMc/v5lxx8UZbWprU6h2vWkEaW5dSlr01EUkPHRIOtSl8hBFWRBBEt26VRAUeqqLmXYopKUCLQo7SG7/EVISdbNRZ3Tdnd1+Hdqt3a3v6cf7ve97vO97X0EOlmFiuw6WYW4GokAbUJ/7ngAGgWe268zlcwFECfkS0A2UUwIpJUBSCNFju87VPEctIN8EzgCqlFJIKclms2SyWYQQNDY1EQgEtMT8fKvfp2+wXeeRZZioa+k0lmFeBk7nOgkpJZuCQfY2N3O7r4+GXY3sj0apqqoSL0ZHAfb4fXradp0RzTLMauDsb64Ux7u6ONzZSbi2lvjEBOFwCE1V+DYzUzQRcNEyzDsK0AroeT2aIhHGXr9icGAAz/MACAQClJcXySIAPxBVgPZSwX5Kicy9dV0nFosRiUTIZDKlqW0KUFcY8TyPgx0dtLS2MD01ha7rPOi/j2EYf4oWYLtSGtE0jSfDw+yoq+dCdzeqqjI+Pg6AT9P+qaAA74uGE4Lk6ipLi4tk0yk+xd8yt5Li1PVe+p6+LOXHldyF/Z0/m+HLxw9YW0LUHDrJ0ekKDtx7ztDnWRLTX0sLDAnLMGuAScAnpRRb24+g79xHTTBA2vNYXUtTqcHYwF2SsX6ET8+vMQWENNt1ZtZVGNeEEOcBxNJ3NobCrAS3sTzymIWhXhJvRlHKfHlyfo1XbNeZE+vNSn44y1iGeUtKeayyrkF6a0mxMvkO4XkIrQyhqoUHJIAbtuucsAwTNZlO5c300K/rZenE7O7M0oKmoCA0DaEULSoF9Niucy7vIfEfO1cjaUEU2TmeEztmu85soZ1/AaCD/2R8+sBqAAAAAElFTkSuQmCC'/>
			Students Management Software - Installation
		</div>
		<div id='content'>
		</div>
	</div>
</div>
<script type='text/javascript'>
var content = document.getElementById('content');
function request(url,params,handler) {
	var xhr = new XMLHttpRequest();
	xhr.open("POST","bridge.php", true);
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
function getLatestVersion() {
	content.innerHTML = "Retrieving latest version...";
	getURLFile("bridge.php", "http://sourceforge.net/projects/studentsdatabase/files/latest.txt/download", function(error,res) {
		if (error) { content.innerHTML = "Error: "+res; return; }
		var version = res;
		content.innerHTML = "Latest version: "+version+"<br/>Downloading Students Management Software "+version+": ";
		var span_progress = document.createElement("SPAN");
		content.appendChild(span_progress);
		download("bridge.php", "http://sourceforge.net/projects/studentsdatabase/files/updates/Students_Management_Software_"+version+".zip/download", "Students_Management_Software_"+version+".zip", span_progress, function(error) {
			if (error) {
				content.innerHTML = "Latest version: "+version+"<br/>";
				content.innerHTML += "Error downloading: "+error+"<br/>";
				var retry_button = document.createElement("BUTTON");
				retry_button.innerHTML = "Retry";
				retry_button.onclick = function() { window.location.reload(); };
				content.appendChild(retry_button);
				return;
			}
			content.innerHTML = "Version "+version+" downloaded.<br/>Extracting files...";
			request("Students_Management_Software_"+version+".zip","&unzip=true",function(error,res) {
				if (error) { content.innerHTML += "<br/>Error: "+res; return; }
				window.location.href = "/";
			});
		});
	});
}
getLatestVersion();
</script>
</body>
</html>