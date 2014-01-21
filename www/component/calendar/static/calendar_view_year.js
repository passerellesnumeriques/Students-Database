/**
 * View of one year
 * @param {CalendarView} view the view manager
 * @param {DOMNode} container where to display
 */
function calendar_view_year(view, container) {

	/** The first day of the year to display */
	this.start_date = view.cursor_date;
	/** The last day of the year to display */
	this.end_date = new Date(this.start_date.getTime()+24*60*60*1000-1);

	/** Called by the CalendarView when a new event should be displayed.
	 * @param {Object} ev the event to display
	 */
	this.addEvent = function(ev) {
		
	};
	
	/** Called by the CalendarView when an event needs to be removed from the dislpay.
	 * @param {String} uid the uid of the event to remove
	 */
	this.removeEvent = function(uid) {
		
	};
	
	container.appendChild(document.createTextNode("The year view is not yet implemented"));
	
}