function who_section(container) {
	var t=this;
	if (typeof container == 'string') container = document.getElementById(container);
	
	require("section.js");
	
	require("section.js", function() {
		t._content = document.createElement("DIV");
		t.section = new section("/static/selection/common_centers/who_black.png","Who ?",t._content,false,false,"soft");
		container.appendChild(t.section.element);
	});
}