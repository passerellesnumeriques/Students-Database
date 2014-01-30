function hscroll_bar() {
	var t=this;
	this.position = 0;
	this.max = 0;
	this.inc_page = 1;
	this.dec_page = 1;
	this.onscroll = null;
	
	this.getMaximum = function() { return this.max; };
	this.setMaximum = function(max) {
		if (max < 0) max = 0;
		this.max = max;
		if (this.position > max) this.position = max;
		this._layout();
	};
	this.getPosition = function() { return this.position; };
	this.setPosition = function(pos) {
		if (pos < 0) pos = 0;
		if (pos > this.max) pos = this.max;
		this.position = pos;
		if (this.onscroll) this.onscroll(this);
	};
	this.setIncrementPage = function(amount) { this.inc_page = amount; };
	this.setDecrementPage = function(amount) { this.dec_page = amount; };
	
	this._init = function() {
		this.element = document.createElement("DIV");
		this.element.style.height = "10px";
		this.element.style.borderTop = "1px solid black";
		var url = get_script_path("hscroll_bar.js");
		var img;
		img = document.createElement("IMG");
		img.src = url+"left.gif";
		img.style.verticalAlign = "bottom";
		img.style.position = "absolute";
		img.style.left = "0px";
		img.style.top = "0px";
		img.style.borderLeft = "1px solid #808080";
		img.style.borderRight = "1px solid #808080";
		img.style.paddingLeft = "2px";
		img.onclick = function() {
			t.setPosition(t.position-t.dec_page);
		};
		this.element.appendChild(img);
		img = document.createElement("IMG");
		img.src = url+"right.gif";
		img.style.verticalAlign = "bottom";
		img.style.position = "absolute";
		img.style.right = "0px";
		img.style.top = "0px";
		img.style.borderLeft = "1px solid #808080";
		img.style.borderRight = "1px solid #808080";
		img.style.paddingRight = "2px";
		img.onclick = function() {
			t.setPosition(t.position+t.inc_page);
		};
		this.element.appendChild(img);
		this.bar = document.createElement("DIV");
		this.bar.style.position = "absolute";
		this.bar.style.top = "0px";
		this.bar.style.height = "10px";
		this.bar.style.backgroundColor = "#A0A0A0";
		this.bar.onmousedown = function(ev) { t._start_move(ev); return false; };
		this.element.appendChild(this.bar);
		this._layout();
	};
	
	this._layout = function() {
		var w = this.element.clientWidth - 14*2;
		var sw = w;
		var x = 0;
		if (this.max > 0) {
			sw = Math.floor(w/this.max);
			if (sw < 10) sw = 10;
			x = this.position / this.max;
		}
		x = (w-sw)*x;
		if (x+sw > w) x = w-sw;
		x += 14;
		this.bar.style.left = Math.floor(x)+"px";
		this.bar.style.width = sw+"px";
	};
	
	this._move_x = null;
	this._move_x_precise = 0;
	this._start_move = function(ev) {
		var e = getCompatibleMouseEvent(ev);
		this._move_x = e.x;
		stopEventPropagation(ev);
	};
	
	this._init();
	addLayoutEvent(this.element, function() { t._layout(); });
	
	listenEvent(window, 'mousemove', function(ev) {
		if (t._move_x == null) return;
		var e = getCompatibleMouseEvent(ev);
		var diff = e.x-t._move_x;
		var w = t.element.clientWidth - 14*2;
		var sw = t.bar.offsetWidth;
		var pixel = t.max/(w-sw);
		var move = diff*pixel;
		move += t._move_x_precise;
		if (t.position+move < 0) move = -t.position;
		if (t.position+move > t.max) move = t.max-t.position;
		var move2 = Math.floor(move);
		t._move_x = e.x;
		t._move_x_precise = move-move2;
		t.setPosition(t.position+move2);
		t._layout();
	});
	listenEvent(window, 'mouseup', function(ev) {
		t._move_x = null;
		t._move_x_precise = 0;
	});
}