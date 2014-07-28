function picture(container,url,max_width,max_height,title) {
	if (typeof container == 'string') container = document.getElementById(container);
	var img = document.createElement("IMG");
	img.src = url+"&max_width="+max_width+"&max_height="+max_height;
	img.style.cursor = "pointer";
	img.onclick = function() {
		require("popup_window.js",function() {
			var i = document.createElement("IMG");
			var p = new popup_window(title,null,i);
			i.src = url;
			p.show();
			i.onload = function() { layout.invalidate(container); };
		});
	};
	container.appendChild(img);
}