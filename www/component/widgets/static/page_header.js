if (typeof require != 'undefined') {
	require("vertical_layout.js");
	require("horizontal_layout.js");
	require("animation.js");
}
function page_header(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	container.style.width = "100%";
	container.style.height = "100%";
	container.className = "page_header";
	var t=this;
	t._init = function() {
		var header, header_title, header_menu, icon, title, frame;
		// header
		header = document.createElement("DIV");
		header.appendChild(header_title = document.createElement("DIV"));
		icon = document.createElement('IMG');
		icon.src = container.getAttribute("icon");
		icon.onload = function() { fireLayoutEventFor(header); };
		icon.style.verticalAlign = "middle";
		header_title.appendChild(icon);
		title = document.createElement("SPAN");
		title.innerHTML = container.getAttribute("title");
		header_title.appendChild(title);
		container.removeAttribute("icon");
		container.removeAttribute("title");
		
		// frame
		t.frame = frame = document.createElement("IFRAME");
		frame.onload = function() { t.frame_load(); };
		frame.src = container.getAttribute("page");
		frame.name = container.id+"_content";
		frame.frameBorder = 0;
		frame.style.width = "100%";
		container.removeAttribute("page");

		// menu
		header.appendChild(header_menu = document.createElement("DIV"));
		var table = document.createElement("TABLE");
		table.style.margin = "0px"; table.style.padding = "0px"; table.style.width = "100%"; table.style.height = "100%";
		var tr = document.createElement("TR");
		var td = document.createElement("TD");
		td.style.verticalAlign = "middle";
		var menu_container = document.createElement("DIV");
		td.appendChild(menu_container);
		tr.appendChild(td);
		table.appendChild(tr);
		header_menu.appendChild(table);
		while (container.childNodes.length > 0)
			menu_container.appendChild(container.removeChild(container.childNodes[0]));
		
		// set layout
		container.appendChild(header);
		container.appendChild(frame);
		header.setAttribute("layout", "35");
		frame.setAttribute("layout", "fill");
		require("vertical_layout.js",function(){
			new vertical_layout(container);
		});
		header_title.setAttribute("layout", "fixed");
		header_menu.setAttribute("layout", "fill");
		require("horizontal_layout.js",function(){
			new horizontal_layout(header);
		});
	};
	t.hider = null;
	t.frame_unload = function() {
		if (t.hider != null) return;
		var div = document.createElement("DIV");
		div.style.position = 'fixed';
		div.style.top = absoluteTop(t.frame)+"px";
		div.style.left = absoluteLeft(t.frame)+"px";
		div.style.width = t.frame.offsetWidth+"px";
		div.style.height = t.frame.offsetHeight+"px";
		div.style.backgroundColor = '#808080';
		div.style.zIndex = 25;
		if (typeof animation != 'undefined')
			div.anim = animation.fadeIn(div, 500, function(){}, 0, 50);
		else
			setOpacity(div, 50);
		document.body.appendChild(div);
		t.hider = div;
	};
	t.frame_load = function() {
		listenEvent(getIFrameWindow(t.frame),'unload',function() { t.frame_unload(); });
		if (t.hider == null) return;
		var div = t.hider;
		t.hider = null;
		if (typeof animation != 'undefined') {
			if (div.anim) animation.stop(div.anim);
			animation.fadeOut(div, 300, function(){document.body.removeChild(div);}, 50, 0);
		} else
			document.body.removeChild(div);
	};	
	
	t._init();
}