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
			e.style.verticalAlign = "top";
			if (layout == 'fill')
				e.style.width = "";
		}
		// get size of container
		var size = getComputedStyleSizes(t.container);
		var w = t.container.clientWidth - parseInt(size.paddingRight) - parseInt(size.paddingLeft);
		var h = t.container.clientHeight - parseInt(size.paddingTop) - parseInt(size.paddingBottom);
		// set size of non-fill elements
		var nb_to_fill = 0;
		var used = 0;
		for (var i = 0; i < t.container.childNodes.length; ++i) {
			var e = t.container.childNodes[i];
			var layout;
			if (e.getAttribute('layout')) layout = e.getAttribute('layout'); else layout = 'fixed';
			if (!keep_height) setHeight(e, h);
			if (layout == 'fill')
				nb_to_fill++;
			else if (!isNaN(parseInt(layout))) {
				var ww = parseInt(layout);
				if (!e._fixed_size_set || e._fixed_size_set != ww) {
					setWidth(e, ww);
					e._fixed_size_set = ww;
				}
				used += ww;
			} else {
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
		// ditribute remaining size
		for (var i = 0; i < t.container.childNodes.length; ++i) {
			var e = t.container.childNodes[i];
			if (e.getAttribute('layout') == 'fill') {
				var ww = Math.floor((w-used)/nb_to_fill--);
				setWidth(e, ww);
				used += ww;
			}
		}
	};
	
	t.layout();
	layout.addHandler(t.container, t.layout);
	layout.invalidate(t.container.parentNode);
}