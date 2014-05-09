/**
 * View of one month
 * @param {CalendarView} view the view manager
 * @param {DOMNode} container where to display
 */
function calendar_view_month(view, container) {

	/** {Date} The first day of the month to display */
	this.start_date = view.cursor_date;
	if (this.start_date.getDate() != 1) this.start_date.setDate(1);
	/** {Date} The last day of the month to display */
	this.end_date = new Date(this.start_date.getTime());
	this.end_date.setMonth(this.end_date.getMonth()+1);
	this.end_date.setDate(this.end_date.getDate()-1);

	/** Returns a text to describe the current position of the view
	 * @param {Number} shorter indicates an index of how small we should try to make the text
	 * @returns {String} the text
	 */
	this.getPositionText = function(shorter) {
		switch (shorter) {
		case 0: // normal
			return getMonthName(this.start_date.getMonth()+1);
		case 1: // short name
			return getMonthShortName(this.start_date.getMonth()+1);
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
	
	container.appendChild(document.createTextNode("The month view is not yet implemented"));
}