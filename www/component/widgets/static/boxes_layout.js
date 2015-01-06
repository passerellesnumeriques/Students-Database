/**
 * Boxes layout layouts DIV element so that it will optimize the space used.
 * This is quite similar as having every DIV as inline-block, but the difference is boxes_layout will try to use remaining spaces so if the height of the DIV is different, we won't have big empty spaces
 * @param {Element} container the container to layout
 * @param {Number} space number of pixels between boxes
 */
function boxes_layout(container, space) {
	if (typeof container == 'string') container = document.getElementById(container);
	container.style.position = "relative";
	for (var i = 0; i < container.childNodes.length; ++i) {
		if (container.childNodes[i].nodeType != 1) continue;
		if (container.childNodes[i].nodeName == 'SCRIPT' || container.childNodes[i].nodeName == 'STYLE') continue;
		container.childNodes[i].style.position = "absolute";
		container.childNodes[i].style.margin = "0px";
		container.childNodes[i]._boxes_layout = true;
	}
	var doLayout = function() {
		var boxes = [];
		for (var i = 0; i < container.childNodes.length; ++i) {
			if (container.childNodes[i].nodeType != 1) continue;
			if (container.childNodes[i].nodeName == 'SCRIPT' || container.childNodes[i].nodeName == 'STYLE') continue;
			if (!container.childNodes[i]._boxes_layout) {
				container.childNodes[i].style.position = "absolute";
				container.childNodes[i].style.margin = "0px";
				container.childNodes[i]._boxes_layout = true;
			}
			boxes.push({
				element: container.childNodes[i],
				width: container.childNodes[i].offsetWidth,
				height: container.childNodes[i].offsetHeight
			});
		}
		if (boxes.length == 0) return;
		var total_width = container.clientWidth;
		var total_height = container.clientHeight;
		// first one is always the first one
		boxes[0].element.style.top = space+"px";
		boxes[0].element.style.left = space+"px";
		if (boxes.length == 1) return;
		var row_y = space;
		do {
			var row = [[boxes[0]]];
			var width = space+boxes[0].width+space;
			var height = boxes[0].height+space;
			boxes.splice(0,1);
			var boxes_not_layouted = [];
			while (boxes.length > 0) {
				var box = boxes[0];
				boxes.splice(0,1);
				if (width+box.width+space <= total_width) {
					// we continue to fill the width
					row.push([box]);
					width += box.width+space;
					if (box.height+space > height) height = box.height+space;
					continue;
				}
				// not space any more in width
				// 1 - try to include the new box somewhere
				var included = false;
				for (var x = 0; x < row.length; ++x) {
					var h = space;
					for (var y = 0; y < row[x].length; ++y) h += row[x][y].height + space;
					if (h + box.height+space <= height) {
						// we can include it here !
						var cur_w = 0;
						for (var y = 0; y < row[x].length; ++y) if (row[x][y].width > cur_w) cur_w = row[x][y].width;
						if (box.width > cur_w) {
							width += box.width - cur_w; // the new box increased the width of the column
							if (width > total_width) {
								// it makes the row width exceed the total width ! => remove the last column
								var col = row[row.length-1];
								row.splice(row.length-1,1);
								var w = 0;
								for (var y = col.length-1; y >= 0; --y) {
									if (col[y].width > w) w = col[y].width;
									boxes.splice(0,0,col[y]);
								}
								width -= w+space;
							}
						}
						row[x].push(box);
						included = true;
						break;
					}
				}
				if (included) continue;
				// 2- see if we can compress
				var ch = height;
				if (box.height + space > height) ch = box.height+space; // the new box is the highest, compress base on this new size
				var compressed = false;
				for (var x = 0; x < row.length; ++x) {
					var h = space;
					for (var y = 0; y < row[x].length; ++y) h += row[x][y].height + space;
					if (h < ch) {
						// this column is not the highest, let's see if we can find a box to complete
						var found = null;
						for (var x2 = x+1; x2 < row.length; ++x2) {
							for (var y = 0; y < row[x2].length; ++y)
								if (h+row[x2][y].height+space <= ch) {
									// found one!
									found = row[x2][y];
									row[x2].splice(y,1);
									if (row[x2].length == 0) {
										// no more boxes here, one column less!
										row.splice(x2,1);
										width -= found.width+space;
									}
									break;
								}
							if (found != null) break;
						}
						if (found) {
							var cur_w = 0;
							for (var y = 0; y < row[x].length; ++y) if (row[x][y].width > cur_w) cur_w = row[x][y].width;
							if (found.width > cur_w)
								width += found.width - cur_w; // the new box increased the width of the column
							row[x].push(found);
							compressed = true;
							break;
						}
					}
				}
				if (compressed) {
					// we manage to compress, let's retry
					boxes.splice(0,0,box);
					continue;
				}
				// cannot compress
				boxes_not_layouted.push(box);
			}
			// layout the row
			var lx = space;
			var h = 0;
			for (var x = 0; x < row.length; ++x) {
				var ly = row_y;
				var w = 0;
				for (var y = 0; y < row[x].length; ++y) {
					row[x][y].element.style.top = ly+'px';
					row[x][y].element.style.left = lx+'px';
					ly += row[x][y].height + space;
					if (row[x][y].width > w) w = row[x][y].width;
				}
				lx += w + space;
				if (ly > h) h = ly;
			}
			row_y += h;
			boxes = boxes_not_layouted;
		} while (boxes.length > 0);
	};
	layout.listenElementSizeChanged(container, doLayout);
	layout.listenInnerElementsChanged(container, doLayout);
	container.ondomremoved = function() {
		layout.unlistenElementSizeChanged(container, doLayout);
		layout.unlistenInnerElementsChanged(container, doLayout);
	};
}