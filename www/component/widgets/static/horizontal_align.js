function horizontal_align(container, align) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	
	for (var i = 0; i < container.childNodes.length; ++i) {
		var e = container.childNodes[i];
		var s = getComputedStyleSizes(e);
		e.originalMargin = s.marginLeft;
		if (e.nodeName == "IMG")
			listenEvent(e,'load',function(){t.layout();});
	}

	t.layout = function() {
		var h = container.clientWidth;
		for (var i = 0; i < container.childNodes.length; ++i) {
			var e = container.childNodes[i];
			if (e.nodeType != 1) continue;
			if (typeof e.originalMargin == 'undefined') {
				var s = getComputedStyleSizes(e);
				e.originalMargin = s.marginLeft;
			}
			e.style.marginLeft = e.originalMargin;
			if (align == "left") continue;
			if (align == "right") {
				e.style.marginLeft = (h-getWidth(e))+"px";
			} else if (align == "middle") {
				e.style.marginLeft = ((h-getWidth(e))/2)+"px";
			}
		}
	};
	
	t.layout();
	layout.addHandler(container, function(){t.layout();});	
}