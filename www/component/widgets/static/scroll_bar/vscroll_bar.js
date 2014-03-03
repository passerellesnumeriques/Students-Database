function vscroll_bar() {
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
		this.element.style.width = "10px";
		this.element.style.borderLeft = "1px solid black";
		var url = get_script_path("vscroll_bar.js");
		var img;
		img = document.createElement("IMG");
		img.src = url+"up.gif";
		img.style.verticalAlign = "bottom";
		img.style.position = "absolute";
		img.style.left = "0px";
		img.style.top = "0px";
		img.style.borderTop = "1px solid #808080";
		img.style.borderBottom = "1px solid #808080";
		img.style.paddingTop = "2px";
		img.onclick = function() {
			t.setPosition(t.position-t.dec_page);
		};
		this.element.appendChild(img);
		img = document.createElement("IMG");
		img.src = url+"down.gif";
		img.style.verticalAlign = "bottom";
		img.style.position = "absolute";
		img.style.bottom = "0px";
		img.style.left = "0px";
		img.style.borderTop = "1px solid #808080";
		img.style.borderBottom = "1px solid #808080";
		img.style.paddingBottom = "2px";
		img.onclick = function() {
			t.setPosition(t.position+t.inc_page);
		};
		this.element.appendChild(img);
		this.bar = document.createElement("DIV");
		this.bar.style.position = "absolute";
		this.bar.style.top = "0px";
		this.bar.style.width = "10px";
		this.bar.style.backgroundColor = "#A0A0A0";
		this.bar.onmousedown = function(ev) { t._start_move(ev); return false; };
		this.element.appendChild(this.bar);
		this._layout();
	};
	
	this._layout = function() {
		var h = this.element.clientHeight - 14*2;
		var sh = h;
		var y = 0;
		if (this.max > 0) {
			sh = Math.floor(h/this.max);
			if (sh < 10) sh = 10;
			y = this.position / this.max;
		}
		y = (h-sh)*y;
		if (y+sh > h) y = h-sh;
		y += 14;
		this.bar.style.top = Math.floor(y)+"px";
		this.bar.style.height = sh+"px";
	};
	
	this._move_y = null;
	this._move_y_precise = 0;
	this._start_move = function(ev) {
		var e = getCompatibleMouseEvent(ev);
		this._move_y = e.y;
		stopEventPropagation(ev);
	};
	
	this._init();
	layout.addHandler(this.element, function() { t._layout(); });
	
	listenEvent(window, 'mousemove', function(ev) {
		if (t._move_y == null) return;
		var e = getCompatibleMouseEvent(ev);
		var diff = e.y-t._move_y;
		var h = t.element.clientHeight - 14*2;
		var sh = t.bar.offsetHeight;
		var pixel = t.max/(h-sh);
		var move = diff*pixel;
		move += t._move_y_precise;
		if (t.position+move < 0) move = -t.position;
		if (t.position+move > t.max) move = t.max-t.position;
		var move2 = Math.floor(move);
		t._move_y = e.y;
		t._move_y_precise = move-move2;
		t.setPosition(t.position+move2);
		t._layout();
	});
	listenEvent(window, 'mouseup', function(ev) {
		t._move_y = null;
		t._move_y_precise = 0;
	});
}