function horizontal_scrolling(container, bgcolor, color, arrow_width) {
	if (typeof container == 'string') container = document.getElementById(container);
	if (!arrow_width) arrow_width = 7;
	var t=this;

	this.element = document.createElement("DIV");
	while (container.childNodes.length > 0) this.element.appendChild(container.childNodes[0]);
	
	container.style.display = "flex";
	container.style.flexDirection = "row";
	container.style.position = "relative";
	container.style.overflow = "hidden";
	this.scroll_left = document.createElement("DIV");
	this.scroll_left.style.display = "none";
	this.scroll_left.style.flex = "none";
	this.scroll_left.style.width = arrow_width+"px";
	this.scroll_left.style.height = "100%";
	this.scroll_left.style.paddingLeft = "1px";
	this.scroll_left.style.paddingRight = "1px";
	this.scroll_left.style.cursor = "pointer";
	this.scroll_left.style.zIndex = "1";
	this.scroll_left.style.backgroundColor = bgcolor;
	this.scroll_left.style.position = "absolute";
	this.scroll_left.style.left = "0px";
	container.appendChild(this.scroll_left);
	this.element.style.flex = "1 1 auto";
	this.element.style.zIndex = 0;
	this.element.style.whiteSpace = "nowrap";
	container.appendChild(this.element);
	this.scroll_right = document.createElement("DIV");
	this.scroll_right.style.display = "none";
	this.scroll_right.style.flex = "none";
	this.scroll_right.style.width = arrow_width+"px";
	this.scroll_right.style.height = "100%";
	this.scroll_right.style.paddingLeft = "1px";
	this.scroll_right.style.paddingRight = "1px";
	this.scroll_right.style.cursor = "pointer";
	this.scroll_right.style.zIndex = 1;
	this.scroll_right.style.backgroundColor = bgcolor;
	this.scroll_right.style.position = "absolute";
	this.scroll_right.style.right = "0px";
	container.appendChild(this.scroll_right);
	
	this.arrow_left = document.createElement("DIV");
	this.arrow_left.style.borderRight = arrow_width+"px solid "+color;
	this.scroll_left.appendChild(this.arrow_left);

	this.arrow_right = document.createElement("DIV");
	this.arrow_right.style.borderLeft = arrow_width+"px solid "+color;
	this.scroll_right.appendChild(this.arrow_right);
	
	this._scrolling_way = 0;
	this._scrolling_interval = null;
	this.startScrolling = function() {
		this._scrolling_interval = setInterval(function() { t.doScrolling(); }, 10);
	};
	this.stopScrolling = function() {
		if (!this._scrolling_interval) return;
		clearInterval(this._scrolling_interval);
		this._scrolling_interval = null;
	};
	this.doScrolling = function() {
		var scroll = t._scrolling_way;
		if (t._scrolling_way < 0 && container.scrollLeft + scroll < 0)
			scroll = -container.scrollLeft;
		else if (t._scrolling_way > 0 && container.scrollLeft + scroll + t.element.clientWidth > t.element.scrollWidth)
			scroll = t.element.scrollWidth - container.scrollTop - t.element.clientWidth;
		container.scrollLeft += scroll;
		t.scroll_left.style.left = (container.scrollLeft)+"px";
		t.scroll_right.style.right = (-container.scrollLeft)+"px";
		t.layout();
	};

	this.scroll_right.onmousedown = function() {
		t._scrolling_way = 4;
		t.startScrolling();
	};
	this.scroll_right.onmouseup = function() {
		t.stopScrolling();
	};
	this.scroll_right.onmouseout = function() {
		t.stopScrolling();
	};
	
	this.scroll_left.onmousedown = function() {
		t._scrolling_way = -4;
		t.startScrolling();
	};
	this.scroll_left.onmouseup = function() {
		t.stopScrolling();
	};
	this.scroll_left.onmouseout = function() {
		t.stopScrolling();
	};
	
	this.layout = function() {
		if (this.element.scrollWidth > this.element.clientWidth) {
			// scrolling
			if (container.scrollLeft == 0)
				this.scroll_left.style.display = "none";
			else
				this.scroll_left.style.display = "";
			if (container.scrollLeft >= this.element.scrollWidth-this.element.clientWidth)
				this.scroll_right.style.display = "none";
			else
				this.scroll_right.style.display = "";
			
			var height = this.element.clientHeight;
			if (height > 50) {
				var diff = height-50;
				height = 50;
				this.arrow_left.style.marginTop = Math.floor(diff/2)+"px";
				this.arrow_left.style.marginBottom = Math.floor(diff/2)+"px";
				this.arrow_right.style.marginTop = Math.floor(diff/2)+"px";
				this.arrow_right.style.marginBottom = Math.floor(diff/2)+"px";
			} else {
				this.arrow_left.style.marginTop = "";
				this.arrow_left.style.marginRight = "";
				this.arrow_right.style.marginBottom = "";
				this.arrow_right.style.marginBottom = "";
			}
			this.arrow_left.style.borderTop = Math.floor(height/2)+"px solid transparent";
			this.arrow_left.style.borderBottom = Math.floor(height/2)+"px solid transparent";
			this.arrow_right.style.borderTop = Math.floor(height/2)+"px solid transparent";
			this.arrow_right.style.borderBottom = Math.floor(height/2)+"px solid transparent";
		} else {
			// no scrolling
			this.scroll_left.style.display = "none";
			this.scroll_right.style.display = "none";
		}
	};
	this.layout();
	
	layout.addHandler(container, function() { t.layout(); });
}