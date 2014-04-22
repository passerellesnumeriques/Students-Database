/**
 * Set an element height in order to have real 100% behaviour
 * @param {HTMLElement} element
 */
function fill_height_layout(element){
	var t = this;
	element = typeof element == "string" ? document.getElementById(element) : element;
	t.layout = function(){
		var parent = element.parentNode;
		var s = getComputedStyleSizes(parent);
		var h = parent.clientHeight - parseInt(s.paddingTop) - parseInt(s.paddingBottom);
		if(h <= 0)
			return;
		setHeight(element,h);
	};
	t.layout();
	layout.addHandler(element.parentNode,t.layout);
}