if (typeof require != 'undefined') {
	require("vertical_layout.js");
	require("horizontal_layout.js");
	require("animation.js");
}
if (typeof theme != 'undefined')
	theme.css("frame_header.css");
function frame_header(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	container.style.width = "100%";
	container.style.height = "100%";
	var t=this;
	
	t.setIcon = function(url) {
		t.icon.src = url;
		fireLayoutEventFor(t.header);
	};

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
		t.header.className = "frame_header";
		t.header.appendChild(t.header_title = document.createElement("DIV"));
		t.header_title.className = "frame_header_title";
		t.icon = document.createElement('IMG');
		if (container.hasAttribute("icon")) {
			t.icon.src = container.getAttribute("icon");
			container.removeAttribute("icon");
		}
		t.icon.onload = function() { fireLayoutEventFor(t.header); };
		t.icon.style.verticalAlign = "middle";
		t.header_title.appendChild(t.icon);
		t.title = document.createElement("SPAN");
		if (container.hasAttribute("title")) {
			t.title.innerHTML = container.getAttribute("title");
			container.removeAttribute("title");
		}
		t.header_title.appendChild(t.title);
		
		// frame
		t.frame = document.createElement("IFRAME");
		t.frame.onload = function() { t.frame_load(); };
		if (container.hasAttribute("page")) {
			t.frame.src = container.getAttribute("page");
			container.removeAttribute("page");
		}
		t.frame.name = container.id+"_content";
		t.frame.id = container.id+"_content";
		t.frame.frameBorder = 0;
		t.frame.style.width = "100%";

		// menu
		t.header.appendChild(t.header_menu = document.createElement("DIV"));
		while (container.childNodes.length > 0)
			t.header_menu.appendChild(container.removeChild(container.childNodes[0]));
		
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
		require("horizontal_menu.js",function(){
			var div = document.createElement("DIV");
			div.className = "button";
			div.innerHTML = "<img src='"+theme.icons_16.more_menu+"'/> More";
			t.header_menu.appendChild(div);
			new horizontal_menu(t.header_menu, "middle");
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