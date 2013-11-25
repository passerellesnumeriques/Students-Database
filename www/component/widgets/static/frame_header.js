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
	t.frame_unload = function() {
		if (typeof animation != 'undefined') {
			if (t.frame.anim) animation.stop(t.frame.anim);
			t.frame.anim = animation.fadeOut(t.frame, 300, function(){t.frame.style.visibility = 'hidden';});
		} else
			t.frame.style.visibility = 'hidden';
	};
	t.frame_load = function() {
		listenEvent(getIFrameWindow(t.frame),'unload',function() { t.frame_unload(); });
		if (typeof animation != 'undefined') {
			if (t.frame.anim) animation.stop(t.frame.anim);
			t.frame.anim = animation.fadeIn(t.frame, 300, function(){});
			t.frame.style.visibility = 'visible';
		} else
			t.frame.style.visibility = 'visible';
	};	
	
	t._init();
}