if (typeof theme != 'undefined')
	theme.css("progress_bar.css");

/**
 * Display a progress bar.
 * @param {Number} width width of the progress bar in pixels
 * @param {Number} height height of the progress bar in pixels
 * @param {String|null} css specific CSS class to use, or null for default one
 */
function progress_bar(width, height, css) {
	
	this.width = width;
	this.height = height;
	/** Amount of work */
	this.total = 0;
	/** Current work done */
	this.position = 0;
	/** DIV containing the progress bar */
	this.element = document.createElement("DIV");
	this.element.className = "progress_bar"+(css ? " "+css : "");
	this.element.style.width = width+"px";
	this.element.style.height = height+"px";
	this.element.style.overflow = "hidden";
	this.element.style.position = "relative";
	/** DIV representing the progression */
	this.progress = document.createElement("DIV");
	this.progress.style.position = "absolute";
	this.progress.style.top = "0px";
	this.progress.style.left = "0px";
	this.progress.style.width = "0px";
	this.progress.style.height = height+"px";
	this.element.appendChild(this.progress);
	
	/** Set the size of the progress bar
	 * @param {Number} width width of the progress bar in pixels
	 * @param {Number} height height of the progress bar in pixels
	 */
	this.setSize = function(width,height) {
		this.width = width;
		this.height = height;
		this.element.style.width = width+"px";
		this.element.style.height = height+"px";
		this.progress.style.width = (this.total > 0 ? Math.floor(this.position*this.width/this.total) : 0)+"px";
		this.progress.style.height = height+"px";
	};
	
	/** Set the total amount of work
	 * @param {Number} total amount of work
	 */
	this.setTotal = function(total) {
		this.total = total;
		this.setPosition(0);
	};
	/** Set the position: current work done
	 * @param {Number} pos position
	 */
	this.setPosition = function(pos) {
		if (pos > this.total) pos = this.total;
		this.position = pos;
		this.progress.style.width = Math.floor(this.position*this.width/this.total)+"px";
	};
	/** Add an amount of work done (increase the position of the given amount)
	 * @param {Number} amount amount to add
	 */
	this.addAmount = function(amount) {
		this.setPosition(this.position + amount);
	};
	/** Work is completed, and the progress bar will be replaced by a green mark and text 'Done' */
	this.done = function() {
		this.element.className += " progress_done";
		this.element.style.color = "#00A000";
		this.element.innerHTML = "<img src='"+(this.height >= 16 ? theme.icons_16.ok : theme.icons_10.ok)+"' style='vertical-align:bottom'/> Done.";
	};
	/** Work has been interrupted due to an error, and the progress bar will be replaced by an error icon with text 'Error' */
	this.error = function() {
		this.element.className += " progress_done";
		this.element.style.color = "#A00000";
		this.element.innerHTML = "<img src='"+(this.height >= 16 ? theme.icons_16.error : theme.icons_10.error)+"' style='vertical-align:bottom'/> Error.";
	};
	
}
