function exam_center_sessions(container, sessions, applicants, center_rooms) {
	if (typeof container == 'string') container = document.getElementById(container);
	
	this.sessions = sessions;
	this.applicants = applicants;
	this.center_rooms = center_rooms;
	
	this.newSession = function() {
		// TODO
	};
	
	this._init = function() {
		// TODO
	};
	
	this._init();
}