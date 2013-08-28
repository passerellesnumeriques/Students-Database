function collapsable_section(element) {
	if (typeof element == 'string') element = document.getElementById(element);
	var t = this;
	t.path = get_script_path('collapsable_section.js');

	// get the header and content, based on css class
	for (var i = 0; i < element.childNodes.length; ++i)
		if (element.childNodes[i].className == 'collapsable_section_header')
			t.header = element.childNodes[i];
		else if (element.childNodes[i].className == 'collapsable_section_content')
			t.content = element.childNodes[i];
	// add the icon on the right corner of the header
	t.toggle_icon = document.createElement("IMG");
	t.toggle_icon.src = t.path+'collapse.gif';
	t.toggle_icon.style.cssFloat = 'right';
	t.toggle_icon.style.marginTop = ((t.header.offsetHeight-11)/2)+'px';
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
		fireLayoutEventFor(element.parentNode);
	};
}