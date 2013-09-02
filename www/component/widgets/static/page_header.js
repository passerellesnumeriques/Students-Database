if (typeof require != 'undefined') {
	require("vertical_layout.js");
	require("horizontal_layout.js");
	require("animation.js");
}
function page_header(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	container.style.width = "100%";
	container.style.height = "35px";
	container.className = "page_header";
	var t=this;
	t._init = function() {
		// menu
		var menu_container = document.createElement("DIV");
		while (container.childNodes.length > 0)
			menu_container.appendChild(container.removeChild(container.childNodes[0]));
		
		// header
		var header_title;
		container.appendChild(header_title = document.createElement("DIV"));
		header_title.className = "page_header_title";
		var icon = document.createElement('IMG');
		icon.src = container.getAttribute("icon");
		icon.onload = function() { fireLayoutEventFor(container); };
		icon.style.verticalAlign = "middle";
		header_title.appendChild(icon);
		var title = document.createElement("SPAN");
		title.innerHTML = container.getAttribute("title");
		header_title.appendChild(title);
		container.removeAttribute("icon");
		container.removeAttribute("title");
		
		container.appendChild(menu_container);
		
		// set layout
		header_title.setAttribute("layout", "fixed");
		menu_container.setAttribute("layout", "fill");
		require("horizontal_layout.js",function(){
			new horizontal_layout(container);
		});
	};
	
	t._init();
}