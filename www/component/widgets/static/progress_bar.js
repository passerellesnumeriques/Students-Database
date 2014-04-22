if (typeof theme != 'undefined')
	theme.css("progress_bar.css");

function progress_bar(width, height, css) {
	
	this.width = width;
	this.height = height;
	this.total = 0;
	this.position = 0;
	this.element = document.createElement("DIV");
	this.element.className = "progress_bar"+(css ? " "+css : "");
	this.element.style.width = width+"px";
	this.element.style.height = height+"px";
	this.element.style.overflow = "hidden";
	this.element.style.position = "relative";
	this.progress = document.createElement("DIV");
	this.progress.style.position = "absolute";
	this.progress.style.top = "0px";
	this.progress.style.left = "0px";
	this.progress.style.width = "0px";
	this.progress.style.height = height+"px";
	this.element.appendChild(this.progress);
	
	this.setSize = function(width,height) {
		this.width = width;
		this.height = height;
		this.element.style.width = width+"px";
		this.element.style.height = height+"px";
		this.progress.style.width = (this.total > 0 ? Math.floor(this.position*this.width/this.total) : 0)+"px";
		this.progress.style.height = height+"px";
	};
	
	this.setTotal = function(total) {
		this.total = total;
		this.setPosition(0);
	};
	this.setPosition = function(pos) {
		if (pos > this.total) pos = this.total;
		this.position = pos;
		this.progress.style.width = Math.floor(this.position*this.width/this.total)+"px";
	};
	this.addAmount = function(amount) {
		this.setPosition(this.position + amount);
	};
	this.done = function() {
		this.element.className += " progress_done";
		this.element.style.color = "#00A000";
		this.element.innerHTML = "<img src='"+(this.height >= 16 ? theme.icons_16.ok : theme.icons_10.ok)+"' style='vertical-align:bottom'/> Done.";
	};
	
	this.error = function() {
		this.element.className += " progress_done";
		this.element.style.color = "#A00000";
		this.element.innerHTML = "<img src='"+(this.height >= 16 ? theme.icons_16.error : theme.icons_10.error)+"' style='vertical-align:bottom'/> Error.";
	};
	
}
