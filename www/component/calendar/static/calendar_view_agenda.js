function calendar_view_agenda(view, container) {

	this.start_date = view.cursor_date;
	this.end_date = new Date(this.start_date.getTime()+30*24*60*60*1000-1);
	this.rows = [];
	
	this.back = function() {
		this.start_date = new Date(this.start_date.getTime()-30*24*60*60*1000);
		this.end_date = new Date(this.start_date.getTime()+30*24*60*60*1000-1);
		view.cursor_date = this.start_date;
		this._reload_table();
		view.load_events();
	};
	this.forward = function() {
		this.start_date = new Date(this.start_date.getTime()+30*24*60*60*1000);
		this.end_date = new Date(this.start_date.getTime()+30*24*60*60*1000-1);
		view.cursor_date = this.start_date;
		this._reload_table();
		view.load_events();
	};
	
	this._init = function() {
		this.table = document.createElement("TABLE");
		this.table.style.borderSpacing = 0;
		this.table.style.width = "100%";
		container.appendChild(this.table);
		this._reload_table();
	};
	this._reload_table = function() {
		while (this.table.childNodes.length > 0) this.table.removeChild(this.table.childNodes[0]);
		this.rows = [];
		var date = new Date(this.start_date.getTime());
		while (date.getTime() <= this.end_date.getTime()) {
			var tr = document.createElement("TR"); this.table.appendChild(tr);
			var td = document.createElement("TD"); tr.appendChild(td);
			td.style.borderBottom = "1px solid #A0A0A0";
			td.innerHTML = date.toDateString();
			var td = document.createElement("TD"); tr.appendChild(td);
			td.style.borderBottom = "1px solid #A0A0A0";
			tr.date = new Date(date.getTime());
			var table = document.createElement("TABLE");
			td.appendChild(table);
			table.appendChild(tr.table = document.createElement("TBODY"));
			this.rows.push(tr);
			date.setTime(date.getTime()+24*60*60*1000);
		}
	};
	
	this.add_event = function(ev) {
		for (var i = 0; i < this.rows.length; ++i) {
			if (ev.start.getTime() >= this.rows[i].date.getTime() && ev.start.getTime() < this.rows[i].date.getTime()+24*60*60*1000) {
				var row = this.rows[i];

				var tr = document.createElement("TR");
				var td = document.createElement("TD"); tr.appendChild(td);
				td.innerHTML = this._2digits(ev.start.getHours())+":"+this._2digits(ev.start.getMinutes())+" - "+this._2digits(ev.end.getHours())+":"+this._2digits(ev.end.getMinutes());
				td = document.createElement("TD"); tr.appendChild(td);
				td.innerHTML = ev.title;
				tr.event = ev;
				
				var index;
				for (index = 0; index < row.table.childNodes.length; ++index)
					if (row.table.childNodes[index].event.start.getTime() > ev.start.getTime()) break;
				
				if (index >= row.table.childNodes.length)
					row.table.appendChild(tr);
				else
					row.table.insertBefore(tr, row.table.childNodes[index]);
				
				break;
			}
		}
	};
	
	this.remove_event = function(uid) {
		for (var row_i = 0; row_i < this.rows.length; ++row_i) {
			var row = this.rows[row_i];
			for (var i = 0; i < row.table.childNodes.length; ++i) {
				var tr = row.table.childNodes[i];
				if (tr.event.uid == uid) {
					row.table.removeChild(tr);
					i--;
				}
			}
		}
	};

	this._2digits = function(n) {
		var s = ""+n;
		while (s.length < 2) s = "0"+s;
		return s;
	};
	
	this._init();
	
}