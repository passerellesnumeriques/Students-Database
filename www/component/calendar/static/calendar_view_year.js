/**
 * View of one year
 * @param {CalendarView} view the view manager
 * @param {Element} container where to display
 */
function calendar_view_year(view, container) {

	/** The first day of the year to display */
	this.start_date = view.cursor_date;
	this.start_date.setMonth(0);
	this.start_date.setDate(1);
	/** The last day of the year to display */
	this.end_date = new Date(this.start_date.getTime());
	this.end_date.setFullYear(this.end_date.getFullYear());
	this.end_date.setDate(0);

	/** Returns a text to describe the current position of the view
	 * @param {Number} shorter indicates an index of how small we should try to make the text
	 * @returns {String} the text
	 */
	this.getPositionText = function(shorter) {
		switch (shorter) {
		case 0: // normal
			return ""+this.start_date.getFullYear();
		}
		return null;
	};
	
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