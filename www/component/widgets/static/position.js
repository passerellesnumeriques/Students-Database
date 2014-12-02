function positionBelowElement(to_position, from_element, min_width_is_from,ondone) {
	to_position.style.position = "fixed";
	to_position.style.top = "0px";
	to_position.style.left = "0px";
	to_position.style.width = "";
	to_position.style.height = "";
	var win = getWindowFromElement(from_element);
	var x,y,w,h;
	var pos = win.getFixedPosition(from_element);
	x = pos.x;
	y = pos.y;
	w = to_position.offsetWidth;
	h = to_position.offsetHeight;
	if (min_width_is_from && w < from_element.offsetWidth) {
		setWidth(to_position, w = from_element.offsetWidth, []);
	}
	if (y+from_element.offsetHeight+h > window.top.getWindowHeight()) {
		// not enough space below
		var space_below = window.top.getWindowHeight()-(y+from_element.offsetHeight);
		var space_above = y;
		if (space_above > space_below) {
			y = y-h;
			if (y < 0) {
				// not enough space: scroll bar
				y = 0;
				to_position.style.overflowY = 'scroll';
				to_position.style.height = space_above+"px";
			}
		} else {
			// not enough space: scroll bar
			y = y+from_element.offsetHeight;
			to_position.style.overflowY = 'scroll';
			to_position.style.height = space_below+"px";
		}
	} else {
		// by default, show it below
		y = y+from_element.offsetHeight;
	}
	if (x+w > window.top.getWindowWidth()) {
		if (x+from_element.offsetWidth < window.top.getWindowWidth()-5)
			x = window.top.getWindowWidth()-w-5;
		else
			x = window.top.getWindowWidth()-w;
	}
	to_position.style.top = y+"px";
	to_position.style.left = x+"px";
	if (ondone) ondone(x,y);
}
function positionAboveElement(to_position, from_element, min_width_is_from, ondone) {
	to_position.style.position = "fixed";
	to_position.style.top = "0px";
	to_position.style.width = "0px";
	to_position.style.width = "";
	to_position.style.height = "";
	var win = getWindowFromElement(from_element);
	var x,y,w,h;
	var pos = win.getFixedPosition(from_element);
	x = pos.x;
	y = pos.y;
	w = to_position.offsetWidth;
	h = to_position.offsetHeight;
	if (min_width_is_from && w < from_element.offsetWidth) {
		setWidth(to_position, w = from_element.offsetWidth, []);
	}
	if (y-h < 0) {
		// not enough space above
		var space_below = window.top.getWindowHeight()-(y+from_element.offsetHeight);
		var space_above = y;
		if (space_below > space_above) {
			y = y+from_element.offsetHeight;
			if (y+h > window.top.getWindowHeight()) {
				// not enough space: scroll bar
				y = 0;
				to_position.style.overflowY = 'scroll';
				to_position.style.height = space_above+"px";
			}
		} else {
			// not enough space: scroll bar
			y = 0;
			to_position.style.overflowY = 'scroll';
			to_position.style.height = space_below+"px";
		}
	} else {
		// by default, show it above
		y = y-h;
	}
	if (x+w > window.top.getWindowWidth()) {
		if (x+from_element.offsetWidth < window.top.getWindowWidth()-5)
			x = window.top.getWindowWidth()-w-5;
		else
			x = window.top.getWindowWidth()-w;
	}
	to_position.style.top = y+"px";
	to_position.style.left = x+"px";
	if (ondone) ondone(x,y);
}
function positionAtRightOfElement(to_position, from_element, ondone) {
	to_position.style.position = "fixed";
	to_position.style.top = "0px";
	to_position.style.left = "0px";
	to_position.style.width = "";
	to_position.style.height = "";
	var win = getWindowFromElement(from_element);
	var x,y,w,h;
	var pos = win.getFixedPosition(from_element);
	x = pos.x;
	y = pos.y;
	w = to_position.offsetWidth;
	h = to_position.offsetHeight;
	if (y+h > window.top.getWindowHeight()) {
		// not enough space below
		var space_below = window.top.getWindowHeight()-(y);
		var space_above = y;
		if (space_above > space_below) {
			y = y-h;
			if (y < 0) {
				// not enough space: scroll bar
				y = 0;
				to_position.style.overflowY = 'scroll';
				to_position.style.height = space_above+"px";
			}
		} else {
			// not enough space: scroll bar
			to_position.style.overflowY = 'scroll';
			to_position.style.height = space_below+"px";
		}
	}
	if (x+from_element.offsetWidth+w > window.top.getWindowWidth()) {
		// not enough space at right
		var space_right = window.top.getWindowWidth()-(x+from_element.offsetWidth);
		var space_left = x;
		if (space_left > space_right) {
			x = x-w;
			if (x < 0) {
				// not enough space: scroll bar
				x = 0;
				to_position.style.overflowX = 'scroll';
				to_position.style.width = space_left+"px";
			}
		} else {
			// not enough space: scroll bar
			to_position.style.overflowX = 'scroll';
			to_position.style.width = space_right+"px";
		}
	} else {
		// by default, show it at right
		x = x+from_element.offsetWidth;
	}
	to_position.style.top = y+"px";
	to_position.style.left = x+"px";
	if (ondone) ondone(x,y);
}