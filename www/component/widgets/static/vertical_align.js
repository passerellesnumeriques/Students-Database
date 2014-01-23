function vertical_align(container, align) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	for (var i = 0; i < container.childNodes.length; ++i) {
		var e = container.childNodes[i];
		var s = getComputedStyleSizes(e);
		e.originalMargin = s.marginTop;
		if (e.nodeName == "IMG")
			listenEvent(e,'load',function(){t.layout();});
	}

	t.layout = function() {
		var h = container.clientHeight;
		for (var i = 0; i < container.childNodes.length; ++i) {
			var e = container.childNodes[i];
			if (e.nodeType != 1) continue;
			if (typeof e.originalMargin == 'undefined') {
				var s = getComputedStyleSizes(e);
				e.originalMargin = s.marginTop;
			}
			e.style.marginTop = e.originalMargin;
			if (align == "top") continue;
			if (align == "bottom") {
				e.style.marginTop = (h-getHeight(e))+"px";
			} else if (align == "middle") {
				e.style.marginTop = ((h-getHeight(e))/2)+"px";
			}
		}
	};
	
	t.layout();
	addLayoutEvent(container, function(){t.layout();});	
}