if (typeof add_javascript != 'undefined')
	add_javascript(get_script_path("calendar_view_day.js")+"day_column_layout.js");
function calendar_view_day(view, container) {

	this.start_date = view.cursor_date;
	this.end_date = new Date(this.start_date.getTime()+24*60*60*1000-1);
	this.zoom_supported = true;
	this.events = [];
	var t=this;
	
	this.back = function() {
		this.start_date = new Date(this.start_date.getTime()-24*60*60*1000);
		this.end_date = new Date(this.start_date.getTime()+24*60*60*1000-1);
		view.cursor_date = this.start_date;
		this.day_title.innerHTML = this.start_date.toDateString();
		if (this.day_column)
			this.day_column.remove_events();
		this.events = [];
		view.load_events();
	};
	this.forward = function() {
		this.start_date = new Date(this.start_date.getTime()+24*60*60*1000);
		this.end_date = new Date(this.start_date.getTime()+24*60*60*1000-1);
		view.cursor_date = this.start_date;
		this.day_title.innerHTML = this.start_date.toDateString();
		if (this.day_column)
			this.day_column.remove_events();
		this.events = [];
		view.load_events();
	};
	
	this._init = function() {
		this.header = document.createElement("DIV");
		this.header.setAttribute("layout", "20");
		this.header.style.borderBottom = "1px solid black";
		this.content = document.createElement("DIV");
		this.content.setAttribute("layout", "fill");
		this.content.style.overflow = "auto";
		container.appendChild(this.header);
		container.appendChild(this.content);
		require("vertical_layout.js", function() { new vertical_layout(container); t._layout(); });
		
		this.corner = document.createElement("DIV");
		this.corner.setAttribute("layout", "50");
		this.day_title = document.createElement("DIV");
		this.day_title.setAttribute("layout", "fill");
		this.day_title.style.borderLeft = "1px solid black";
		this.day_title.style.textAlign = "center";
		this.day_title.innerHTML = this.start_date.toDateString();
		this.header.appendChild(this.corner);
		this.header.appendChild(this.day_title);
		require("horizontal_layout.js", function() { new horizontal_layout(t.header); t._layout(); });
		
		this.content.style.position = "relative";
		this.time_title = document.createElement("DIV");
		this.time_title.style.position = "absolute";
		this.time_title.style.width = "50px";
		this.time_title.style.left = "0px";
		this.time_title.style.top = "0px";
		this.time_title.style.borderRight = "1px solid black";
		this.content.appendChild(this.time_title);
		this.day_content = document.createElement("DIV");
		this.day_content.style.position = "absolute";
		this.day_content.style.left = "51px";
		this.day_content.style.top = "0px";
		this.content.appendChild(this.day_content);
		
		this._create_time_scale();
		add_javascript(get_script_path("calendar_view_day.js")+"day_column_layout.js",function(){
			t.day_column = new DayColumnLayout();
			t._layout();
		});
	};
	
	this._time_lines = [];
	this._create_time_scale = function() {
		while (this.time_title.childNodes.length > 0)
			this.time_title.removeChild(this.time_title.childNodes[0]);
		for (var i = 0; i < this._time_lines.length; ++i) this.content.removeChild(this._time_lines[i]);
		this._time_lines = [];
		
		var time = view.zoom;
		while (time < 24*60) {
			var y = Math.floor(time*20/view.zoom);
			var line = document.createElement("DIV");
			line.style.borderTop = "1px dotted #808080";
			line.style.height = "1px";
			line.style.position = "absolute";
			line.style.left = "51px";
			line.style.top = y+"px";
			this.content.appendChild(line);
			this._time_lines.push(line);
			var d = document.createElement("DIV");
			var date = new Date();
			date.setHours(0, time, 0, 0);
			d.innerHTML = this._2digits(date.getHours())+":"+this._2digits(date.getMinutes());
			d.style.top = (y-8)+"px";
			d.style.position = "absolute";
			d.style.width = "50px";
			d.style.textAlign = "right";
			d.style.left = "0px";
			this.time_title.appendChild(d);
			time += view.zoom;
		}
		var y = Math.floor(24*60*20/view.zoom);
		this.time_title.style.height = y+"px";
		this.day_content.style.height = y+"px";
	};
	this._2digits = function(n) {
		var s = ""+n;
		while (s.length < 2) s = "0"+s;
		return s;
	};
	this._layout = function() {
		if (!this.day_content) return;
		var w = container.clientWidth-51;
		w -= (this.content.offsetWidth-this.content.clientWidth);
		this.day_content.style.width = w+"px";
		for (var i = 0; i < this._time_lines.length; ++i)
			this._time_lines[i].style.width = w+"px";
		if (this.day_column)
			this.day_column.layout(this.events, this.day_content, 0, w, 0, view.zoom, 20);
	};
	
	this.add_event = function(ev) {
		this.events.push(ev);
		this._layout();
	};
	this.remove_event = function(uid) {
		for (var i = 0; i < this.events.length; ++i)
			if (this.events[i].uid == uid) {
				this.events.splice(i, 1);
				i--;
			}
		this._layout();
	};
	
	this._init();
	addLayoutEvent(container, function() { t._layout(); });
}