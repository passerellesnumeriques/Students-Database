if (typeof theme != 'undefined') theme.css("statusui.css");
/**
 * Implementation of UI to display status message on the top of the window
 * @param {StatusManager} manager manager of status messages
 * @param {Number} margin pixels at the top
 */
function StatusUI_Top(manager, margin) {
	manager.status_ui = this;
	if (!margin) margin = 0;
	/** DIV which will contain the messages */
	this.container = document.createElement("DIV");
	this.container.style.position = "fixed";
	this.container.style.zIndex = 1000;
	this.container.style.top = margin+"px";
	this.container.style.visibility = "hidden";
	this.container.style.textAlign = "center";
	this.container.style.left = margin+'px';
	this.container.style.right = margin+'px';
	document.body.appendChild(this.container);
	
	/**
	 * Update the display with the given list of status messages
	 * @param {Array} list list of StatusMessage
	 */
	this.update = function(list) {
		for (var i = 0; i < list.length; ++i) {
			var c = this.getStatusControl(list[i].id);
			if (c == null)
				this.createControl(list[i]);
			else if (c.fading) {
				c.fading = false;
				if (c.anim1)
					animation.stop(c.anim1);
				c.anim1 = null;
				if (c.anim2)
					animation.stop(c.anim2);
				c.anim2 = null;
				c.style.visibility = "visible";
				setOpacity(c, 100);
				c.style.height = "";
				c = this.getStatusControlBR(list[i].id);
				c.fading = false;
				if (c.anim1)
					animation.stop(c.anim1);
				c.anim1 = null;
				if (c.anim2)
					animation.stop(c.anim2);
				c.anim2 = null;
				c.style.visibility = "visible";
				setOpacity(c, 100);
				c.style.height = "";
			}
		}
		for (var i = 0; i < this.container.childNodes.length; ++i) {
			var found = false;
			for (var j = 0; j < list.length; ++j)
				if (this.container.childNodes[i].name == list[j].id || this.container.childNodes[i].name == 'br_'+list[j].id) { found = true; break; }
			if (!found) {
				if (this.container.childNodes[i].fading) continue;
				if (typeof animation != 'undefined') {
					this.container.childNodes[i].fading = true;
					this.container.childNodes[i].id = generateID();
					this.container.childNodes[i].anim1 = 
						animation.fadeOut(this.container.childNodes[i], 400, function(e){
							e.anim1 = null;
							var p = e.parentNode;
							if (p == null) return;
							try {
								p.removeChild(e);
								p.style.left = (getWindowWidth()/2-p.scrollWidth/2)+'px';
							} catch (ex) {}
						});
					setTimeout("_statusUITopFade('"+this.container.childNodes[i].id+"');", 150);
				} else {
					this.container.removeChild(this.container.childNodes[i]);
					i--;
				}
			}
		}
		if (this.container.childNodes.length == 0)
			this.container.style.visibility = 'hidden';
		else {
			this.container.style.visibility = "visible";
			//this.container.style.left = (getWindowWidth()/2-this.container.scrollWidth/2)+'px';
		}
	};
	/** Get the element representing the message
	 * @param {String} id identifier of the message
	 * @returns {Element} the element
	 */
	this.getStatusControl = function(id) {
		for (var i = 0; i < this.container.childNodes.length; ++i) {
			if (this.container.childNodes[i].name == id)
				return this.container.childNodes[i];
		}
		return null;
	};
	/** Get the separator below the given message
	 * @param {String} id identifier of the message
	 * @returns {Element} separator
	 */
	this.getStatusControlBR = function(id) {
		for (var i = 0; i < this.container.childNodes.length; ++i) {
			if (this.container.childNodes[i].name == 'br_'+id)
				return this.container.childNodes[i];
		}
		return null;
	};
	/** Update the display of the given status
	 * @param {StatusMessage} status status
	 */
	this.updateStatus = function(status) {
		var c = this.getStatusControl(status.id);
		if (c == null) return;
		this.updateStatusControl(c, status);
		this.container.style.left = (getWindowWidth()/2-this.container.scrollWidth/2)+'px';
	},
	/** Create the display for the given status
	 * @param {StatusMessage} status the status
	 */
	this.createControl = function(status) {
		var c = document.createElement("DIV");
		c.className = "status_item";
		c.style.display = "inline-block";
		c.name = status.id;
		c.style.overflow = "hidden";
		this.container.appendChild(c);
		this.updateStatusControl(c, status);

		c = document.createElement("DIV");
		c.name = 'br_'+status.id;
		c.innerHTML = "<table height='5px' border=0 style='empty-cells:show'><tr><td></td></tr></table>";
		c.style.height = "2px";
		this.container.appendChild(c);
		if (status.timeout) {
			setTimeout(function(){manager.removeStatus(status.id);}, status.timeout);
		}
	};
	/** Update the display of the given status
	 * @param {Element} c the element of the status
	 * @param {StatusMessage} status the status
	 */
	this.updateStatusControl = function(c, status) {
		var t=this;
		c.removeAllChildren();
		c.style.backgroundColor = 
			status.type == Status_TYPE_INFO ? "#FFFF80" :
			status.type == Status_TYPE_ERROR || status.type == Status_TYPE_ERROR_NOICON ? "#FF8080" :
			status.type == Status_TYPE_WARNING ? "#FFC040" :
			status.type == Status_TYPE_PROCESSING ? "#FFFF80" :
			status.type == Status_TYPE_OK ? "#C0E0C0" :
			"#808080";
		var icon = null;
		if (!status.no_icon)
			switch (status.type) {
			case Status_TYPE_ERROR_NOICON: icon = null; break;
			case Status_TYPE_ERROR: icon = theme.icons_16.error; break;
			case Status_TYPE_WARNING: icon = theme.icons_16.warning; break;
			case Status_TYPE_PROCESSING: icon = theme.icons_16.loading; break;
			case Status_TYPE_OK: icon = theme.icons_16.ok; break;
			default:
			case Status_TYPE_INFO: icon = theme.icons_16.info; break;
			}
		if (icon) {
			var img = document.createElement("IMG");
			img.src = icon;
			img.style.verticalAlign = "top";
			img.marginBottom = "2px";
			img.marginRight = "2px";
			img.onload = function() {
				t.container.style.left = (getWindowWidth()/2-t.container.scrollWidth/2)+'px';				
			};
			c.appendChild(img);
		}
		var div = document.createElement("DIV");
		div.style.display = "inline-block";
		if (typeof status.message == 'string')
			div.innerHTML = status.message;
		else
			div.appendChild(status.message);
		c.appendChild(div);
		if (div.offsetWidth > getWindowWidth()*80/100) {
			div.style.width = (getWindowWidth()*80/100)+"px";
			div.style.overflowX = "auto";
		}
		if (div.offsetHeight > 100) {
			div.style.height = "100px";
			div.style.overflowY = "auto";
		}
		if (status.actions != null)
			for (var i = 0; i < status.actions.length; ++i) {
				var a = status.actions[i];
				if (a.action == "close") {
					var img = document.createElement("IMG");
					img.src = theme.icons_10.close;
					img.hspace=1;
					img.style.verticalAlign="top";
					setOpacity(img,50);
					img.style.cursor = "pointer";
					img.onmouseover = function() { setOpacity(this,100); };
					img.onmouseout = function() { setOpacity(this,50); };
					img.onclick = function() { manager.removeStatus(status.id); };
					img.onload = function() {
						t.container.style.left = (getWindowWidth()/2-t.container.scrollWidth/2)+'px';				
					};
					c.appendChild(document.createTextNode(" "));
					c.appendChild(img);
				} else if (a.action == "popup") {
					var img = document.createElement("IMG");
					img.src = theme.icons_10.popup;
					img.hspace=1;
					img.style.verticalAlign="top";
					setOpacity(img,50);
					img.style.cursor = "pointer";
					img.onmouseover = function() { setOpacity(this,100); };
					img.onmouseout = function() { setOpacity(this,50); };
					img.onclick = function() {
						require(["popup_window.js","layout.js"],function() {
							var p = new popup_window("Error", theme.icons_16.error, "<div>"+status.message+"</div>");
							p.show();
							manager.removeStatus(status.id);
						});
					};
					img.onload = function() {
						t.container.style.left = (getWindowWidth()/2-t.container.scrollWidth/2)+'px';				
					};
					c.appendChild(document.createTextNode(" "));
					c.appendChild(img);
				} else {
					var link = document.createElement("A");
					link.href = "#";
					link.onclick = function() { a.action(status); return false; };
					link.innerHTML = a.text;
					c.appendChild(document.createTextNode(" "));
					c.appendChild(link);
				}
			}
	};
}

/**
 * Create an animation to make the given status disappear
 * @param {String} id identifier of the status
 * @no_doc
 */
function _statusUITopFade(id) {
	var e = document.getElementById(id);
	if (e == null) return;
	if (!e.fading) return;
	e.anim2 = 
		animation.create(e, e.offsetHeight-2, 0, 250, function(value,elem){
			elem.style.height = Math.floor(value)+'px';
			if (value == 0) elem.anim2 = null;
		});
}
