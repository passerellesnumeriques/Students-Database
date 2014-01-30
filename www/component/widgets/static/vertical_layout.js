/** Create a vertical layout: children will fill the width, and be positioned vertically according to the layout info of each.
 * Each child can contain a <i>layout</i> attribute, having one of the following value:<ul style='margin:0px'>
 * <li><code>fill</code>: the element will take as much space as possible</li>
 * <li><code>fixed</code>: the element should ne be resized</li>
 * <li>an integer value: the element must have the given height (in pixel)</li>
 * </ul>
 * @constructor
 * @param container the container (either the HTMLElement or its id)
 */
function vertical_layout(container) {
	var t = this;
	t.container = container;
	if (typeof t.container == 'string') t.container = document.getElementById(t.container);
	t.container.widget = this;
	t.container.style.overflow = "hidden";
	
	t.removeLayout = function() {
		t.container.widget = null;
		removeLayoutEvent(t.container, t.layout);
	};
	
	t.layout = function() {
		// remove all to get the container size
		for (var i = 0; i < t.container.childNodes.length; ++i) {
			var e = t.container.childNodes[i];
			if (e.nodeType != 1) continue;
			e._save_scrollTop = e.scrollTop;
			e._save_scrollLeft = e.scrollLeft;
			e.style.position = 'fixed';
		}		
		var w = t.container.clientWidth;
		var h = t.container.clientHeight;
		for (var i = 0; i < t.container.childNodes.length; ++i) {
			var e = t.container.childNodes[i];
			if (e.nodeType != 1) continue;
			if (e.hasAttribute("force_position"))
				e.style.position = e.getAttribute("force_position");
			else
				e.style.position = 'static';
		}		
		// reset
		for (var i = 0; i < t.container.childNodes.length; ++i) {
			var e = t.container.childNodes[i];
			if (e.nodeType != 1) continue;
			var layout;
			if (e.getAttribute('layout')) layout = e.getAttribute('layout'); else layout = 'fixed';
			if (layout == 'fill')
				e.style.height = "";
		}
		var nb_to_fill = 0;
		var used = 0;
		for (var i = 0; i < t.container.childNodes.length; ++i) {
			var e = t.container.childNodes[i];
			if (e.nodeType != 1) continue;
			var layout;
			if (e.getAttribute('layout')) layout = e.getAttribute('layout'); else layout = 'fixed';
			if (layout == 'fill')
				nb_to_fill++;
			else if (!isNaN(parseInt(layout)))
				used += parseInt(layout);
			else {
				e.style.display = "block";
				setWidth(e, w);
				e.style.height = "";
				used += getHeight(e);
			}
		}
		var y = 0;
		for (var i = 0; i < t.container.childNodes.length; ++i) {
			var e = t.container.childNodes[i];
			if (e.nodeType != 1) continue;
			var layout;
			if (e.getAttribute('layout')) layout = e.getAttribute('layout'); else layout = 'fixed';
			e.style.display = "block";
			setWidth(e, w);
			if (layout == 'fill') {
				var hh = Math.floor((h-used)/nb_to_fill--);
				setHeight(e, hh);
				y += hh;
			} else if (!isNaN(parseInt(layout))) {
				var hh = parseInt(layout);
				setHeight(e, hh);
				y += hh;
			} else {
				y += getHeight(e);
			}
			e.scrollTop = e._save_scrollTop;
			e.scrollLeft = e._save_scrollLeft;
		}
	};
	
	t.layout();
	addLayoutEvent(t.container, t.layout);
}