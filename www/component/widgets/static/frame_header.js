if (typeof require != 'undefined') {
	require("vertical_layout.js");
	require("horizontal_layout.js");
	require("animation.js");
}
function frame_header(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	container.style.width = "100%";
	container.style.height = "100%";
	var t=this;
	
	t.setTitle = function(title) {
		if (typeof title == 'string')
			t.title.innerHTML = title;
		else {
			t.title.innerHTML = "";
			t.title.appendChild(title);
		}
		fireLayoutEventFor(t.header);
	};
	
	t._init = function() {
		// header
		t.header = document.createElement("DIV");
		t.header.className = "page_header";
		t.header.appendChild(t.header_title = document.createElement("DIV"));
		t.header_title.className = "page_header_title";
		t.icon = document.createElement('IMG');
		t.icon.src = container.getAttribute("icon");
		t.icon.onload = function() { fireLayoutEventFor(t.header); };
		t.icon.style.verticalAlign = "middle";
		t.header_title.appendChild(t.icon);
		t.title = document.createElement("SPAN");
		t.title.innerHTML = container.getAttribute("title");
		t.header_title.appendChild(t.title);
		container.removeAttribute("icon");
		container.removeAttribute("title");
		
		// frame
		t.frame = document.createElement("IFRAME");
		t.frame.onload = function() { t.frame_load(); };
		t.frame.src = container.getAttribute("page");
		t.frame.name = container.id+"_content";
		t.frame.id = container.id+"_content";
		t.frame.frameBorder = 0;
		t.frame.style.width = "100%";
		container.removeAttribute("page");

		// menu
		t.header.appendChild(t.header_menu = document.createElement("DIV"));
		var table = document.createElement("TABLE");
		table.style.margin = "0px"; table.style.padding = "0px"; table.style.width = "100%"; table.style.height = "100%";
		var tr = document.createElement("TR");
		var td = document.createElement("TD");
		td.style.verticalAlign = "middle";
		var menu_container = document.createElement("DIV");
		td.appendChild(menu_container);
		tr.appendChild(td);
		table.appendChild(tr);
		t.header_menu.appendChild(table);
		while (container.childNodes.length > 0)
			menu_container.appendChild(container.removeChild(container.childNodes[0]));
		
		// set layout
		container.appendChild(t.header);
		container.appendChild(t.frame);
		t.header.setAttribute("layout", "35");
		t.frame.setAttribute("layout", "fill");
		require("vertical_layout.js",function(){
			new vertical_layout(container);
		});
		t.header_title.setAttribute("layout", "fixed");
		t.header_menu.setAttribute("layout", "fill");
		require("horizontal_layout.js",function(){
			new horizontal_layout(t.header);
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