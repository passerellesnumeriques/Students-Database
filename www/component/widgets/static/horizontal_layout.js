/** Create a horizontal layout: children will fill the height, and be positioned horizontally according to the layout info of each.
 * Each child can contain a <i>layout</i> attribute, having one of the following value:<ul style='margin:0px'>
 * <li><code>fill</code>: the element will take as much space as possible</li>
 * <li><code>fixed</code>: the element should ne be resized</li>
 * <li>an integer value: the element must have the given width (in pixel)</li>
 * </ul>
 * @constructor
 * @param container the container (either the HTMLElement or its id)
 */
function horizontal_layout(container, keep_height, valign) {
	var t = this;
	t.container = container;
	if (typeof t.container == 'string') t.container = document.getElementById(t.container);
	t.container.widget = this;
	//t.container.style.overflow = "hidden"; // if we do this, it does not resize correctly 
	
	t.removeLayout = function() {
		t.container.widget = null;
		layout.removeHandler(t.container, t.layout);
	};
	
	t.layout = function() {
		// reset
		for (var i = 0; i < t.container.childNodes.length; ++i) {
			var e = t.container.childNodes[i];
			if (e.nodeType != 1) {
				t.container.removeChild(e);
				i--;
				continue;
			}
			var layout;
			if (e.getAttribute('layout')) layout = e.getAttribute('layout'); else layout = 'fixed';
			e.style.height = "";
			e.style.display = "inline-block";
			e.style.marginTop = "";
			if (layout == 'fill')
				e.style.width = "";
		}
		var w = t.container.clientWidth;
		var h = t.container.clientHeight;
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
				e.style.display = 'inline-block';
				e.style.width = "";
				if (!keep_height) 
					setHeight(e, h);
				else if (valign)
					switch (valign) {
					case "middle": e.style.marginTop = Math.floor(h/2-getHeight(e)/2)+'px'; break;
					case "bottom": e.style.marginTop = (h-getHeight(e))+'px'; break;
					}
				used += getWidth(e);
			}
		}
		var x = 0;
		for (var i = 0; i < t.container.childNodes.length; ++i) {
			var e = t.container.childNodes[i];
			if (e.nodeType != 1) continue;
			var layout;
			if (e.getAttribute('layout')) layout = e.getAttribute('layout'); else layout = 'fixed';
			e.style.display = 'inline-block';
			e.style.verticalAlign = "top";
			if (!keep_height) setHeight(e, h);
			if (layout == 'fill') {
				var ww = Math.floor((w-used)/nb_to_fill--);
				setWidth(e, ww);
				x += ww;
				used += ww;
			} else if (!isNaN(parseInt(layout))) {
				var ww = parseInt(layout);
				setWidth(e, ww);
				x += ww;
			} else {
				x += getWidth(e);
			}
		}
	};
	
	t.layout();
	layout.addHandler(t.container, t.layout);
	layout.invalidate(t.container.parentNode);
}