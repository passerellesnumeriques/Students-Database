/** Represents a 2D point
 * @param {Number} x horizontal position
 * @param {Number} y vertical position
 */
function Point2D(x,y) {
	this.x = x;
	this.y = y;

	/** 
	 * square of the distance between this point and the given point
	 * @param {Point2D} pt the other point
	 * @returns {Number} the square of the distance between this point and the given point
	 */
	this.getDistanceSquared = function(pt) {
		return (pt.x-this.x)*(pt.x-this.x)+(pt.y-this.y)*(pt.y-this.y);
	};
	/** 
	 * the distance between this point and the given point
	 * @param {Point2D} pt the other point
	 * @returns {Number} the distance between this point and the given point
	 */
	this.getDistance = function(pt) {
		return Math.sqrt(this.getDistanceSquared(pt));
	};
}

/**
 * Represent a 2D line
 * @constructor
 * @param {Point2D} pt1 the starting point of the line
 * @param {Point2D} pt2 the ending point of the line
 */
function Line2D(pt1,pt2) {
	this.pt1 = pt1;
	this.pt2 = pt2;

	/** Get the 3 components of the equation defining this line
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
	 * Get the distance squared between the given point and this line
	 * @param {Point2D} pt the point
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
	 * Get the distance between this line and the given point
	 * @param {Point2D} pt the point
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

/** Drawing functionalities */
drawing = {
	/** No connector */
	CONNECTOR_NONE: 0,
	/** Arrow connector */
	CONNECTOR_ARROW: 1,
	/** Circle connector */
	CONNECTOR_CIRCLE: 2,
		
	/** Draw a line on the given canvas between the 2 given points
	 * @param {Object} ctx the 2d context of the canvas to draw on
	 * @param {Point2D} from starting point
	 * @param {Point2D} to ending point
	 * @param {Number} from_type type of connector for the starting point (one of the CONNECTOR_* constants)
	 * @param {Number} to_type type of connector for the ending point (one of the CONNECTOR_* constants)
	 * @param {String} color CSS color to use
	 * @param {Number} width width of the line  
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
	 * @param {Number} type type of connector: CONNECTOR_NONE, or CONNECTOR_ARROW, or CONNECTOR_CIRCLE
	 * @param {String} color CSS color to use
	 * @param {Number} width width of the lines when drawing 
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
	 * @param {String} color CSS color
	 * @param {Number} width width of lines 
	 * @param {String} force 'horiz' to force connecting elements horizontally, or 'vert' to force vertical connection, or null to let decide 
	 */
	connectElements: function(from, to, from_type, to_type, color, width, force) {
		var canvas = document.createElement("CANVAS");
		canvas.style.position = "absolute";
		canvas.style.pointerEvents = "none";
		var parent = getAbsoluteParent(from);
		var parent2 = getAbsoluteParent(to);
		if (parent != parent2) {
			var path1 = [parent];
			var p = parent;
			while (p && p.nodeName != 'BODY') {
				p = getAbsoluteParent(p);
				if (p) path1.push(p);
			}
			var path2 = [parent2];
			var p = parent2;
			while (p && p.nodeName != 'BODY') {
				p = getAbsoluteParent(p);
				if (p) path2.push(p);
			}
			parent = null;
			for (var i = 0; i < path1.length; ++i)
				if (path2.indexOf(path1[i]) >= 0) { parent = path1[i]; break; }
			if (!parent) parent = getWindowFromElement(from).document.body;
		}
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
	}
		
};