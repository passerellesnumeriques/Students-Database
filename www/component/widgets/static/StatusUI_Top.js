theme.css("statusui.css");
function StatusUI_Top(manager, margin) {
	manager.status_ui = this;
	if (!margin) margin = 0;
	this.container = document.createElement("DIV");
	this.container.style.position = "fixed";
	this.container.style.zIndex = 1000;
	this.container.style.top = margin+"px";
	this.container.style.visibility = "hidden";
	this.container.style.textAlign = "center";
	document.body.appendChild(this.container);
	
	this.update = function(list) {
		for (var i = 0; i < list.length; ++i) {
			var c = this.get_status_control(list[i].id);
			if (c == null)
				this.create_control(list[i]);
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
				c = this.get_status_control_br(list[i].id);
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
							var p = e.parentNode;
							if (p == null) return;
							try {
								p.removeChild(e);
								p.style.left = (getWindowWidth()/2-p.scrollWidth/2)+'px';
							} catch (ex) {}
						});
					setTimeout("_status_ui_top_fade('"+this.container.childNodes[i].id+"');", 150);
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
			this.container.style.left = (getWindowWidth()/2-this.container.scrollWidth/2)+'px';
		}
	};
	this.get_status_control = function(id) {
		for (var i = 0; i < this.container.childNodes.length; ++i) {
			if (this.container.childNodes[i].name == id)
				return this.container.childNodes[i];
		}
		return null;
	};
	this.get_status_control_br = function(id) {
		for (var i = 0; i < this.container.childNodes.length; ++i) {
			if (this.container.childNodes[i].name == 'br_'+id)
				return this.container.childNodes[i];
		}
		return null;
	};
	this.update_status = function(status) {
		var c = this.get_status_control(status.id);
		if (c == null) return;
		this.update_status_control(c, status);
		this.container.style.left = (getWindowWidth()/2-this.container.scrollWidth/2)+'px';
	},
	this.create_control = function(status) {
		var c = document.createElement("DIV");
		c.className = "status_item";
		c.style.display = "inline-block";
		c.name = status.id;
		c.style.overflow = "hidden";
		this.container.appendChild(c);
		this.update_status_control(c, status);

		c = document.createElement("DIV");
		c.name = 'br_'+status.id;
		c.innerHTML = "<table height='5px' border=0 style='empty-cells:show'><tr><td></td></tr></table>";
		c.style.height = "2px";
		this.container.appendChild(c);
		if (status.timeout) {
			setTimeout(function(){manager.remove_status(status.id);}, status.timeout);
		}
	};
	this.update_status_control = function(c, status) {
		var t=this;
		c.removeAllChildren();
		c.style.backgroundColor = 
			status.type == Status_TYPE_INFO ? "#FFFF80" :
			status.type == Status_TYPE_ERROR ? "#FF8080" :
			status.type == Status_TYPE_WARNING ? "#FFC040" :
			status.type == Status_TYPE_PROCESSING ? "#FFFF80" :
			status.type == Status_TYPE_OK ? "#C0E0C0" :
			"#808080";
		var icon = null;
		if (!status.no_icon)
			switch (status.type) {
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
		div.innerHTML = status.message;
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
					img.onclick = function() { manager.remove_status(status.id); };
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
							manager.remove_status(status.id);
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

function _status_ui_top_fade(id) {
	var e = document.getElementById(id);
	if (e == null) return;
	if (!e.fading) return;
	e.anim2 = 
		animation.create(e, e.offsetHeight-2, 0, 250, function(value,elem){
			elem.style.height = Math.floor(value)+'px';
		});
}
