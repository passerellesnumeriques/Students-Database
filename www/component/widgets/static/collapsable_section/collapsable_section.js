function collapsable_section(element) {
	if (typeof element == 'string') element = document.getElementById(element);
	var t = this;
	t.path = get_script_path('collapsable_section.js');
	t.element = element;

	if (t.element) {
		// get the header and content, based on css class
		for (var i = 0; i < t.element.childNodes.length; ++i)
			if (t.element.childNodes[i].className == 'collapsable_section_header')
				t.header = t.element.childNodes[i];
			else if (t.element.childNodes[i].className == 'collapsable_section_content')
				t.content = t.element.childNodes[i];
	} else {
		t.element = document.createElement("DIV");
		t.element.className = "collapsable_section";
		t.element.style.display = "inline-block";
	}
	if (!t.header) {
		t.header = document.createElement("DIV");
		t.header.className = "collapsable_section_header";
		t.element.appendChild(t.header);
	}
	if (!t.content) {
		t.content = document.createElement("DIV");
		t.content.className = "collapsable_section_content";
		t.element.appendChild(t.content);
	}
	// add the icon on the right corner of the header
	t.toggle_icon = document.createElement("IMG");
	t.toggle_icon.src = t.path+'collapse.gif';
	t.toggle_icon.style.cssFloat = 'right';
	t.toggle_icon.style.marginTop = '2px';
	t.toggle_icon.style.marginRight = '2px';
	t.header.appendChild(t.toggle_icon);

	// show or hide the content when user clicks on the header
	t.header.onclick = function() { t.toggle(); return false; };
	
	t.visible = true;
	
	t.toggle = function() {
		if (t.visible) {
			t.content.style.visibility = "hidden";
			t.content.style.position = "absolute";
			t.content.style.top = "-10000px";
			t.toggle_icon.src = t.path+'expand.gif';
			t.visible = false;
		} else {
			t.content.style.visibility = "visible";
			t.content.style.position = "static";
			t.toggle_icon.src = t.path+'collapse.gif';
			t.visible = true;
		}
		fireLayoutEventFor(t.element.parentNode);
	};
}