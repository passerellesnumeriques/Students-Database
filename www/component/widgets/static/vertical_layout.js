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
	t.container.overflowY = "hidden";
	
	t.removeLayout = function() {
		t.container.widget = null;
		layout.removeHandler(t.container, t.layout);
	};
	
	t.layout = function() {
		// get the container size
		var h = t.container.clientHeight;
		// reset
		for (var i = 0; i < t.container.childNodes.length; ++i) {
			var e = t.container.childNodes[i];
			if (e.nodeType != 1) continue;
			e.style.display = "block";
			var layout;
			if (e.getAttribute('layout')) layout = e.getAttribute('layout'); else layout = 'fixed';
			if (layout == 'fill')
				e.style.height = "0px";
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
			else if (!isNaN(parseInt(layout))) {
				var hh = parseInt(layout);
				if (!e._fixed_size_set || e._fixed_size_set != hh) {
					setHeight(e, hh);
					e._fixed_size_set = hh;
				}
				used += hh;
			} else {
				used += getHeight(e);
			}
		}
		for (var i = 0; i < t.container.childNodes.length; ++i) {
			var e = t.container.childNodes[i];
			if (e.nodeType != 1) continue;
			if (e.getAttribute('layout') == 'fill') {
				var hh = Math.floor((h-used)/nb_to_fill--);
				setHeight(e, hh);
			}
		}
	};

	t.layout();
	layout.addHandler(t.container, t.layout);
}