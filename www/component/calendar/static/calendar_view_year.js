function calendar_view_year(view, container) {

	this.start_date = view.cursor_date;
	this.end_date = new Date(this.start_date.getTime()+24*60*60*1000-1);

	this.add_event = function(ev) {
		
	};
	
	this.remove_event = function(uid) {
		
	};
	
}