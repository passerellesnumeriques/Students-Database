function pictures_list_thumbnail(container, people, width, height) {
	var t=this;
	this.width = width;
	this.height = height;
	this.people = people;
	
	theme.css("picture_thumbnail.css");
	this.div = document.createElement("DIV"); container.appendChild(this.div);
	this.div.className = "picture_thumbnail";
	this.div.style.display = "inline-block";
	this.picture_container = document.createElement("DIV"); this.div.appendChild(this.picture_container);
	this.picture_container.style.width = (this.width)+'px';
	this.picture_container.style.height = (this.height)+'px';
	this.picture_container.className = "picture_thumbnail_picture_container";
	this.picture = document.createElement("IMG"); this.picture_container.appendChild(this.picture);
	this.picture.style.width = this.width+'px';
	this.picture.style.height = this.height+'px';
	this.picture_container.style.position = "relative";
	this.picture.style.position = "absolute";
	this.picture.style.bottom = "0px";
	this.picture.className = "picture_thumbnail_picture";
	this.picture.onload = function() {
		this._loaded = true;
		var resize_ratio = 1;
		var h = this.naturalHeight;
		var w = this.naturalWidth;
		if (h > t.height) {
			resize_ratio = t.height/h;
		}
		if (w > t.width) {
			var r = t.width/w;
			if (r < resize_ratio) resize_ratio = r;
		}
		if (resize_ratio != 1) {
			nw = Math.floor(w*resize_ratio);
			nh = Math.floor(h*resize_ratio);
			this.style.width = nw+'px';
			this.style.height = nh+'px';
		}
		this.style.left = Math.floor(t.width/2-this.offsetWidth/2)+'px';
	};
	this.picture.src = "/dynamic/people/service/picture?people="+people.id;
	this.name_container = document.createElement("DIV"); this.div.appendChild(this.name_container);
	this.name_container.className = "picture_thumbnail_name";
	this.name_container.appendChild(document.createTextNode(people.first_name));
	this.name_container.appendChild(document.createElement("BR"));
	this.name_container.appendChild(document.createTextNode(people.last_name));
	
	this.setSize = function(width, height) {
		this.width = width;
		this.height = height;
		this.picture_container.style.width = (this.width)+'px';
		this.picture_container.style.height = (this.height)+'px';
		if (this.picture._loaded) {
			var resize_ratio = 1;
			var h = this.picture.naturalHeight;
			var w = this.picture.naturalWidth;
			if (h > t.height) {
				resize_ratio = t.height/h;
			}
			if (w > t.width) {
				var r = t.width/w;
				if (r < resize_ratio) resize_ratio = r;
			}
			if (resize_ratio != 1) {
				nw = Math.floor(w*resize_ratio);
				nh = Math.floor(h*resize_ratio);
				this.picture.style.width = nw+'px';
				this.picture.style.height = nh+'px';
			}
		}
	};
	
	this.reload = function() {
		this.picture.src = "/dynamic/people/service/picture?people="+people.id+"&ts="+new Date().getTime();
	};
}