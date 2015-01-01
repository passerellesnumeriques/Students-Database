window.Status_TYPE_INFO = 0;
window.Status_TYPE_ERROR = 1;
window.Status_TYPE_WARNING = 2;
window.Status_TYPE_PROCESSING = 3;
window.Status_TYPE_OK = 4;
window.Status_TYPE_ERROR_NOICON = 5;

/** Represents a status message
 * @param {Number} type one of the Status_TYPE_xxx constant
 * @param {String} message message
 * @param {Array} actions list of possible actions
 * @param {Number} timeout if specified, the message will automatically disappear after this amount of milliseconds
 */
function StatusMessage(type,message,actions,timeout) {
	this.id = generateID();
	this.type = type;
	this.message = message;
	this.actions = actions;
	this.timeout = timeout;
}
/** Status message for an error, including an exception stack trace if given
 * @param {Exception} err the error, or null if no exception
 * @param {String} message error message
 * @param {Number} timeout if given, the message will automatically disappear after this timeout specified in milliseconds
 */
function StatusMessageError(err, message, timeout) {
	this.id = generateID();
	this.type = Status_TYPE_ERROR;
	this.timeout = timeout;
	this.message = (message != null ? " "+message : "");
	if (err != null)
		this.message += ": "+err.message;
	this.actions = [];
	if (timeout) this.actions.push({action:"popup"});
	this.actions.push({action:"close"});
	if (err != null) {
		if (err.stack)
			this.stack = err.stack;
		else if(err.stacktrace)
			this.stack = err.stacktrace;
		else {
			var s = "";
		    var currentFunction = arguments.callee.caller;
		    while (currentFunction) {
		      var fn = currentFunction.toString();
		      var fname = fn.substring(0, fn.indexOf('{'));;
		      s += fname+"\r\n";
		      currentFunction = currentFunction.caller;
		    }
		    this.stack = s;
		}
		if (this.stack != null) {
			this.actions.splice(0,0,{text:"Show stack trace",action:"show_stack_trace"});
		}
	}
}

/**
 * Manages a list of status, and coordinate with the given UI implementation
 */
function StatusManager() {
	/** List of status messages */
	this.status = [];
	/** {Object} implementation of the UI to display status */
	this.status_ui = null;
	
	/** Add a status to be displayed
	 * @param {StatusMessage} status status
	 * @returns {StatusMessage} the given status
	 */
	this.addStatus= function(status) {
		this.status.push(status);
		this.status_ui.update(this.status);
		return status;
	};
	/** Remove the given status
	 * @param {StatusMessage} status the status to remove
	 */
	this.removeStatus= function(status) {
		if (typeof status == 'string' || typeof status == 'number') status = this.getStatus(status);
		if (status == null) return;
		for (var i = 0; i < this.status.length; ++i)
			if (this.status[i] == status) {
				this.status.splice(i,1);
				break;
			}
		this.status_ui.update(this.status);
	};
	/** Indicates a status has been changed and its display needs to be updated
	 * @param {StatusMessage} status the status to update
	 */
	this.updateStatus= function(status) {
		this.status_ui.updateStatus(status);
	};
	/** Retrieve a status using its id
	 * @param {String} id identifier
	 * @returns {StatusMessage} the status
	 */
	this.getStatus= function(id) {
		for (var i = 0; i < this.status.length; ++i)
			if (this.status[i].id == id)
				return this.status[i];
		return null;
	};
}