/** Represents a 2D point
 * @constructor
 */
function Point2D(x,y) {
	this.x = x;
	this.y = y;

	/** 
	 * @method Point2D#getDistanceSquared
	 * @param {Point2D} pt
	 * @returns {Number} the square of the distance between this point and the given point
	 */
	this.getDistanceSquared = function(pt) {
		return (pt.x-this.x)*(pt.x-this.x)+(pt.y-this.y)*(pt.y-this.y);
	};
	/** 
	 * @method Point2D#getDistance
	 * @param {Point2D} pt
	 * @returns {Number} the the distance between this point and the given point
	 */
	this.getDistance = function(pt) {
		return Math.sqrt(this.getDistanceSquared(pt));
	};
}

/**
 * Represent a 2D line
 * @constructor
 * @param {Point2D} pt1
 * @param {Point2D} pt2
 */
function Line2D(pt1,pt2) {
	this.pt1 = pt1;
	this.pt2 = pt2;

	/** @method Line2D#getEquation
	 * @returns {Array} the 3 components of the equation defining this line
	 */
	this.getEquation = function() {
		var equation = [0,0,0];
		if (this.pt1.x == this.pt2.x) {
			if (this.pt1.y == this.pt2.y)
				return equation;
			equation[0]=1;
			equation[1]=0;
			equation[2]=this.pt1.x;
			return equation;
		}
		
		equation[0]=(this.pt1.y-this.pt2.y)/(this.pt2.x-this.pt1.x);
		equation[1]=1.0;
		equation[2]=this.pt2.y+equation[0]*this.pt2.x;
		return equation;
	};
	/**
	 * @method Line2D#getDistanceSquared
	 * @param {Point2D} pt
	 * @returns {Number} the square of the distance between this line and the given point
	 */
	this.getDistanceSquared = function(pt) {
		  var l2 = this.pt1.getDistanceSquared(this.pt2);
		  if (l2 == 0) return pt.getDistanceSquared(this.pt1);
		  var t = ((pt.x - this.pt1.x) * (this.pt2.x - this.pt1.x) + (pt.y - this.pt1.y) * (this.pt2.y - this.pt1.y)) / l2;
		  if (t < 0) return pt.getDistanceSquared(this.pt1);
		  if (t > 1) return pt.getDistanceSquared(this.pt2);
		  return pt.getDistanceSquared(new Point2D(this.pt1.x + t * (this.pt2.x - this.pt1.x), this.pt1.y + t * (this.pt2.y - this.pt1.y)));
	};
	/**
	 * @method Line2D#getDistance
	 * @param {Point2D} pt
	 * @returns {Number} the distance between this line and the given point
	 */
	this.getDistance = function(pt) {
		//var eq = this.getEquation();
		//var dist = eq[0]*pt.x + eq[1]*pt.y + eq[2];
		//if (dist < 0) dist = -dist;
		//dist = dist / Math.sqrt(eq[0]*eq[0] + eq[1]*eq[1]);
		//return dist;
		return Math.sqrt(this.getDistanceSquared(pt));
	};
}

/** Drawing functionalities
 * @namespace
 */
drawing = {
	CONNECTOR_NONE: 0,
	CONNECTOR_ARROW: 1,
	CONNECTOR_CIRCLE: 2,
		
	/** Draw a line on the given canvas between the 2 given points
	 * @param {Object} ctx the 2d context of the canvas to draw on
	 * @param {Point2D} from starting point
	 * @param {Point2D} to ending point
	 * @param {Number} from_type type of connector for the starting point (one of the CONNECTOR_* constants)
	 * @param {Number} to_type type of connector for the ending point (one of the CONNECTOR_* constants)
	 * @param {String} style CSS style of the connector (accepted values are the ones of <a href='http://www.w3schools.com/tags/canvas_strokestyle.asp'>strokeStyle</a>) 
	 */
	connect: function(ctx, from, to, from_type, to_type, color, width) {
		ctx.strokeStyle = color;
		ctx.lineWidth = width;
		ctx.beginPath();
		ctx.moveTo(from.x, from.y);
		ctx.lineTo(to.x, to.y);
		ctx.stroke();
		drawing.drawConnector(ctx, from, to, from_type, color, width);
		drawing.drawConnector(ctx, to, from, to_type, color, width);
	},
	/** Draw a connector point on the given canvas
	 * @param {Object} ctx the 2d context of the canvas to draw on
	 * @param {Point2D} pt position of the connector
	 * @param {Point2D} origin where the connection comes from to be able to orientate the drawing correctly
	 * @param {String} style CSS style of the connector (accepted values are the ones of <a href='http://www.w3schools.com/tags/canvas_strokestyle.asp'>strokeStyle</a>) 
	 */
	drawConnector: function(ctx, pt, origin, type, color, width) {
		switch (type) {
		case drawing.CONNECTOR_ARROW:
			ctx.strokeStyle = color;
			ctx.lineWidth = width;
			var headlen = 10;   // length of head in pixels
		    var angle = Math.atan2(pt.y-origin.y,pt.x-origin.x);
		    ctx.beginPath();
		    ctx.moveTo(pt.x, pt.y);
		    ctx.lineTo(pt.x-headlen*Math.cos(angle-Math.PI/6),pt.y-headlen*Math.sin(angle-Math.PI/6));
		    ctx.stroke();
		    ctx.beginPath();
		    ctx.moveTo(pt.x, pt.y);
		    ctx.lineTo(pt.x-headlen*Math.cos(angle+Math.PI/6),pt.y-headlen*Math.sin(angle+Math.PI/6));
		    ctx.stroke();
			break;
		case drawing.CONNECTOR_CIRCLE:
			ctx.strokeStyle = color;
			ctx.lineWidth = width;
			ctx.fillStyle = color;
			ctx.beginPath();
			ctx.arc(pt.x,pt.y,1,0,2*Math.PI);
			ctx.stroke();
			break;
		}
	},

	/** Draw a line connecting the 2 given elements
	 * @param {Element} from origin
	 * @param {Element} to destination
	 * @param {Number} from_type type of connector for the starting point (one of the CONNECTOR_* constants)
	 * @param {Number} to_type type of connector for the ending point (one of the CONNECTOR_* constants)
	 * @param {String} style CSS style of the connector (accepted values are the ones of <a href='http://www.w3schools.com/tags/canvas_strokestyle.asp'>strokeStyle</a>) 
	 * @param {String} force 'horiz' to force connecting elements horizontally, or 'vert' to force vertical connection, or null to let decide 
	 */
	connectElements: function(from, to, from_type, to_type, color, width, force) {
		var canvas = document.createElement("CANVAS");
		canvas.style.position = "absolute";
		canvas.style.pointerEvents = "none";
		var parent = getAbsoluteParent(from);
		parent.appendChild(canvas);
		var update = function() {
			var from_x = absoluteLeft(from, parent);
			var from_y = absoluteTop(from, parent);
			var to_x = absoluteLeft(to, parent);
			var to_y = absoluteTop(to, parent);
			
			var start, end;
			if (force == 'horiz') {
				if (from_x < to_x) {
					start = new Point2D(from_x+from.offsetWidth, Math.floor(from_y+from.offsetHeight/2));
					end = new Point2D(to_x-1, Math.floor(to_y+to.offsetHeight/2));
				} else {
					start = new Point2D(from_x-1, Math.floor(from_y+from.offsetHeight/2));
					end = new Point2D(to_x+to.offsetWidth, Math.floor(to_y+to.offsetHeight/2));
				}
			} else if (force == 'vert') {
				if (from_y < to_y) {
					start = new Point2D(Math.floor(from_x+from.offsetWidth/2), from_y+from.offsetHeight);
					end = new Point2D(Math.floor(to_x+to.offsetWidth/2), to_y-1);
				} else {
					start = new Point2D(Math.floor(from_x+from.offsetWidth/2), from_y-1);
					end = new Point2D(Math.floor(to_x+to.offsetWidth/2), to_y+to.offsetHeight);
				}
			} else if (force == "horiz_straight") {
				if (from_x < to_x) {
					start = new Point2D(from_x+from.offsetWidth, Math.floor(from_y+from.offsetHeight/2));
					end = new Point2D(to_x-1, start.y);
				} else {
					start = new Point2D(from_x-1, Math.floor(from_y+from.offsetHeight/2));
					end = new Point2D(to_x+to.offsetWidth, start.y);
				}
			} else {
				// if there is a space vertically, we will connect using bottom/top edges
				if (from_y < to_y && from_y+from.offsetHeight < to_y) {
					start = new Point2D(Math.floor(from_x+from.offsetWidth/2), from_y+from.offsetHeight);
					end = new Point2D(Math.floor(to_x+to.offsetWidth/2), to_y-1);
				} else if (to_y < from_y && to_y+to.offsetHeight < from_y) {
					start = new Point2D(Math.floor(from_x+from.offsetWidth/2), from_y-1);
					end = new Point2D(Math.floor(to_x+to.offsetWidth/2), to_y+to.offsetHeight);
				} else if (from_x < to_x) {
					start = new Point2D(from_x+from.offsetWidth, Math.floor(from_y+from.offsetHeight/2));
					end = new Point2D(to_x-1, Math.floor(to_y+to.offsetHeight/2));
				} else {
					start = new Point2D(from_x-1, Math.floor(from_y+from.offsetHeight/2));
					end = new Point2D(to_x+to.offsetWidth, Math.floor(to_y+to.offsetHeight/2));
				}
			}
			
			var x1 = Math.min(start.x, end.x);
			var y1 = Math.min(start.y, end.y);
			var x2 = Math.max(start.x, end.x);
			var y2 = Math.max(start.y, end.y);
			
			// add 5 pixels margin
			x1 -= 5;
			y1 -= 5;
			x2 += 5;
			y2 += 5;
			
			canvas.style.top = y1+"px";
			canvas.style.left = x1+"px";
			canvas.style.width = (x2-x1+1)+"px";
			canvas.style.height = (y2-y1+1)+"px";
			canvas.width = (x2-x1+1);
			canvas.height = (y2-y1+1);
			
			start.x = start.x-x1;
			start.y = start.y-y1;
			end.x = end.x-x1;
			end.y = end.y-y1;
			
			var ctx = canvas.getContext("2d");
			drawing.connect(ctx, start, end, from_type, to_type, color, width);
		};
		update();
		var refresh_timeout = null;
		var refresher = function() {
			if (refresh_timeout) return;
			refresh_timeout = setTimeout(function() {
				update();
				refresh_timeout = null;
			},1);
		};
		layout.listenElementSizeChanged(parent,refresher);
		layout.listenInnerElementsChanged(parent,refresher);
		return canvas;
	},
		
		
	/**
	 * Draw a line between two HTML elements: from the middle of the right edge of e1, to the middle of the left edge of e2.
	 * It does not use HTML 5 canvas, but only a table, so it is compatible with any browser including old ones. 
	 * @param e1
	 * @param e2
	 */
	horizontal_connector: function(e1,e2) {
		if (typeof e1 == 'string') e1 = document.getElementById(e1);
		if (typeof e2 == 'string') e2 = document.getElementById(e2);
		var parent = getAbsoluteParent(e1);
		var conn = document.createElement("CANVAS");
		conn.style.position = 'absolute';
		parent.appendChild(conn);
		var refresh = function() {
			var x1 = absoluteLeft(e1, parent)+e1.offsetWidth;
			var y1 = absoluteTop(e1, parent)+e1.offsetHeight/2;
			var x2 = absoluteLeft(e2, parent);
			var y2 = absoluteTop(e2, parent)+e2.offsetHeight/2;
			var start_y = y1, end_y = y2;
			if (y1 > y2) { var y = y1; y1 = y2; y2 = y; }
			conn.style.top = y1+"px";
			conn.style.left = x1+"px";
			conn.style.width = (x2-x1+1)+"px";
			conn.style.height = (y2-y1+1)+"px";
			conn.width = (x2-x1+1);
			conn.height = (y2-y1+1);
			var ctx = conn.getContext("2d");
			ctx.beginPath();
			if (start_y < end_y) {
				ctx.moveTo(0,0);
				ctx.lineTo(x2-x1,y2-y1);
			} else if (start_y == end_y) {
				ctx.moveTo(0,0);
				ctx.lineTo(x2-x1,0);
			} else {
				ctx.moveTo(0,y2-y1);
				ctx.lineTo(x2-x1,0);
			}
			ctx.stroke();
		};
		refresh();
		var refresh_timeout = null;
		var refresher = function() {
			if (refresh_timeout) return;
			refresh_timeout = setTimeout(function() {
				update();
				refresh_timeout = null;
			},1);
		};
		layout.listenElementSizeChanged(parent,refresher);
		layout.listenInnerElementsChanged(parent,refresher);
	}
//	horizontal_connector: function(e1,e2) {
//		if (typeof e1 == 'string') e1 = document.getElementById(e1);
//		if (typeof e2 == 'string') e2 = document.getElementById(e2);
//		var parent = getAbsoluteParent(e1);
//		var conn = document.createElement("DIV");
//		var table = document.createElement("TABLE"); conn.appendChild(table);
//		table.style.borderCollapse = 'collapse';
//		table.style.borderSpacing = "0px";
//		table.appendChild(table = document.createElement("TBODY"));
//		conn.style.position = 'absolute';
//		parent.appendChild(conn);
//		var refresh = function() {
//			var x1 = absoluteLeft(e1, parent)+e1.offsetWidth;
//			var y1 = absoluteTop(e1, parent)+e1.offsetHeight/2;
//			var x2 = absoluteLeft(e2, parent);
//			var y2 = absoluteTop(e2, parent)+e2.offsetHeight/2;
//			var start_y = y1, end_y = y2;
//			if (y1 > y2) { var y = y1; y1 = y2; y2 = y; }
//			conn.style.top = y1+"px";
//			conn.style.left = x1+"px";
//			conn.style.width = (x2-x1+1)+"px";
//			conn.style.height = (y2-y1+1)+"px";
//			while (table.childNodes.length > 0) table.removeChild(table.childNodes[0]);
//			var line = new Line2D(new Point2D(x1,start_y),new Point2D(x2,end_y));
//			for (var y = 0; y < y2-y1+1; y++) {
//				var tr = document.createElement("TR"); table.appendChild(tr);
//				tr.style.height = "1px";
//				for (var x = 0; x < x2-x1+1; x++) {
//					var td = document.createElement("TD"); tr.appendChild(td);
//					td.style.width = "1px";
//					var dist = line.getDistance(new Point2D(x1+x,y1+y));
//					td.style.padding = "0px";
//					td.data = dist;
//					var col = 256;
//					if (dist < 0.25)
//						col = 0;
//					else if (dist < 1)
//						col = Math.floor(255*(dist));
//					if (col < 256)
//						td.style.backgroundColor = "rgb("+col+","+col+","+col+")";
//				}
//			}
//		};
//		refresh();
//		var refresh_timeout = null;
//		layout.addHandler(parent,function() {
//			if (refresh_timeout) return;
//			refresh_timeout = setTimeout(function() {
//				refresh();
//				refresh_timeout = null;
//			},1);
//		});
//	}
};