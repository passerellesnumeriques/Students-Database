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
	container.widget = this;
	
	t.layout = function() {
		// reset
		for (var i = 0; i < t.container.childNodes.length; ++i) {
			var e = t.container.childNodes[i];
			if (e.nodeType != 1) continue;
			var layout;
			if (e.getAttribute('layout')) layout = e.getAttribute('layout'); else layout = 'fixed';
			if (layout == 'fill')
				e.style.height = "";
		}
		var w = t.container.offsetWidth;
		var h = t.container.offsetHeight;
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
		}
	};
	
	t.layout();
	addLayoutEvent(t.container, function(){t.layout();});
}