/**
 * View upcoming events of calendars
 * @param {CalendarView} view view manager
 * @param {Element} container where to display
 */
function calendar_view_upcoming(view, container) {
	/** Starting date to display */ 
	this.start_date = new Date();
	this.start_date.setHours(0,0,0,0);
	/** Maximum date to display */
	this.end_date = new Date(this.start_date.getTime()+view.zoom*24*60*60*1000-1);
	/** List of rows of the agenda */
	this._rows = [];
	
	/** returns an empty string 
	 * @returns {String} empty string
	 */
	this.getPositionText = function() { return ""; };
	
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
				td.innerHTML = _2digits(ev.start.getHours())+":"+_2digits(ev.start.getMinutes())+" - "+_2digits(ev.end.getHours())+":"+_2digits(ev.end.getMinutes());
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

	this._init();
	
}