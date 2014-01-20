/**
 * View agenda for calendars
 * @param {CalendarView} view view manager
 * @param {DOMNode} container where to display
 */
function calendar_view_agenda(view, container) {
	/** Starting date to display */ 
	this.start_date = view.cursor_date;
	/** Maximum date to display */
	this.end_date = new Date(this.start_date.getTime()+30*24*60*60*1000-1);
	/** List of rows of the agenda */
	this._rows = [];
	
	/** Goes 30 days before */
	this.back = function() {
		this.start_date = new Date(this.start_date.getTime()-30*24*60*60*1000);
		this.end_date = new Date(this.start_date.getTime()+30*24*60*60*1000-1);
		view.cursor_date = this.start_date;
		this._reloadTable();
		view.loadEvents();
	};
	/** Goes 30 days after */
	this.forward = function() {
		this.start_date = new Date(this.start_date.getTime()+30*24*60*60*1000);
		this.end_date = new Date(this.start_date.getTime()+30*24*60*60*1000-1);
		view.cursor_date = this.start_date;
		this._reloadTable();
		view.loadEvents();
	};
	
	/** Initialize the display */
	this._init = function() {
		this.table = document.createElement("TABLE");
		this.table.style.borderSpacing = 0;
		this.table.style.width = "100%";
		container.appendChild(this.table);
		this._reloadTable();
	};
	/** Build the content of the display */
	this._reloadTable = function() {
		while (this.table.childNodes.length > 0) this.table.removeChild(this.table.childNodes[0]);
		this._rows = [];
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
			this._rows.push(tr);
			date.setTime(date.getTime()+24*60*60*1000);
		}
	};
	
	/** Called by the CalendarView when a new event should be displayed.
	 * @param {Object} ev the event to display
	 */
	this.addEvent = function(ev) {
		for (var i = 0; i < this._rows.length; ++i) {
			if (ev.start.getTime() >= this._rows[i].date.getTime() && ev.start.getTime() < this._rows[i].date.getTime()+24*60*60*1000) {
				var row = this._rows[i];

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
	
	/** Called by the CalendarView when an event needs to be removed from the dislpay.
	 * @param {String} uid the uid of the event to remove
	 */
	this.removeEvent = function(uid) {
		for (var row_i = 0; row_i < this._rows.length; ++row_i) {
			var row = this._rows[row_i];
			for (var i = 0; i < row.table.childNodes.length; ++i) {
				var tr = row.table.childNodes[i];
				if (tr.event.uid == uid) {
					row.table.removeChild(tr);
					i--;
				}
			}
		}
	};

	/** Add a 0 if the number is only 1 digit
	 * @param {Number} n the number
	 */
	this._2digits = function(n) {
		var s = ""+n;
		while (s.length < 2) s = "0"+s;
		return s;
	};
	
	this._init();
	
}