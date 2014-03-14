/**
 * Set an element height in order to have real 100% behaviour
 * @param {HTMLElement} element
 */
function fill_height_layout(element){
	var t = this;
	element = typeof element == "string" ? document.getElementById(element) : element;
	t.layout = function(){
		var parent = element.parentNode;
		var h = parent.clientHeight - parent.style.paddingTop - parent.style.paddingBottom;
		if(h <= 0)
			return;
		setHeight(element,h);
	};
	t.layout();
	layout.addHandler(element,t.layout);
}