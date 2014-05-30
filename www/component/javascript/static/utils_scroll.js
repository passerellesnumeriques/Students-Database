/**
 * Scroll up the given element
 * @param element
 * @param {Number} scroll
 */
function scrollUp(element, scroll) {
	// try to set scrollTop
	var s = element.scrollTop;
	element.scrollTop = s - scroll;
	if (element.scrollTop != s) return; // it changed, so it worked
	// TODO
}
/**
 * Scroll down the given element
 * @param element
 * @param {Number} scroll
 */
function scrollDown(element, scroll) {
	scrollUp(element, -scroll);
}
/**
 * Scroll left the given element
 * @param element
 * @param {Number} scroll
 */
function scrollLeft(element, scroll) {
	// try to set scrollTop
	var s = element.scrollLeft;
	element.scrollLeft = s - scroll;
	if (element.scrollLeft != s) return; // it changed, so it worked
	// TODO
}
/**
 * Scroll right the given element
 * @param element
 * @param {Number} scroll
 */
function scrollRight(element, scroll) {
	scrollLeft(element, -scroll);
}

/**
 * Return the first parent of the given element, being scrollable 
 * @param {Element} element the HTML element
 * @returns {Element} the scrollable container
 */
function getScrollableContainer(element) {
	var parent = element.parentNode;
	do {
		if (parent == document.body) return parent;
		if (parent.scrollHeight != parent.clientHeight) return parent;
		if (parent.scrollWidth != parent.clientWidth) return parent;
		parent = parent.parentNode;
	} while (parent != null);
	return document.body;
}

/**
 * Scroll all necessary scrollable elements to make the given element visible in the screen.
 * @param element
 */
function scrollToSee(element) {
	var parent = getScrollableContainer(element);
	var x1 = absoluteLeft(element, parent);
	var y1 = absoluteTop(element, parent);
	var x2 = x1+element.offsetWidth;
	var y2 = y1+element.offsetHeight;
	if (y1 < parent.scrollTop) {
		// the element is before, we need to scroll up
		scrollUp(parent, parent.scrollTop-y1);
	} else if (y2 > parent.scrollTop+parent.clientHeight) {
		// the element is after, we need to scroll down
		scrollDown(parent, y2-(parent.scrollTop+parent.clientHeight));
	}
	if (x1 < parent.scrollLeft) {
		// the element is before, we need to scroll left
		scrollLeft(parent, parent.scrollLeft-x1);
	} else if (x2 > parent.scrollLeft+parent.clientWidth) {
		// the element is after, we need to scroll down
		scrollRight(parent, x2-(parent.scrollLeft+parent.clientWidth));
	}
	// TODO same with parent, which may not be visible...
/*
				var x = absoluteLeft(cell, container);
				if (x < container.scrollLeft)
					container.scrollLeft = x;
				else if (container.scrollLeft+container.clientWidth < x+cell.offsetWidth)
					container.scrollLeft = x+cell.offsetWidth-container.clientWidth;
				var y = absoluteTop(cell, container);
				if (y < container.scrollTop)
					container.scrollTop = y;
				else if (container.scrollTop+container.clientHeight < y+cell.offsetHeight)
					container.scrollTop = y+cell.offsetHeight-container.clientHeight;
 */	
}

function scrollTo(element) {
	var parent = getScrollableContainer(element);
	var x1 = absoluteLeft(element, parent);
	var y1 = absoluteTop(element, parent);
	var x2 = x1+element.offsetWidth;
	var y2 = y1+element.offsetHeight;
	if (y1 < parent.scrollTop) {
		// the element is before, we need to scroll up
		scrollUp(parent, parent.scrollTop-y1);
	} else if (y2 > parent.scrollTop+parent.clientHeight) {
		// the element is after, we need to scroll down
		scrollDown(parent, y2-(parent.scrollTop+parent.clientHeight));
	} else {
		scrollDown(parent, -(y1-(parent.scrollTop+parent.clientHeight)));
	}
	if (x1 < parent.scrollLeft) {
		// the element is before, we need to scroll left
		scrollLeft(parent, parent.scrollLeft-x1);
	} else if (x2 > parent.scrollLeft+parent.clientWidth) {
		// the element is after, we need to scroll down
		scrollRight(parent, x2-(parent.scrollLeft+parent.clientWidth));
	} else {
		scrollRight(parent, -(x1-(parent.scrollLeft+parent.clientWidth)));
	}
	// TODO same with parent, which may not be visible...
}
