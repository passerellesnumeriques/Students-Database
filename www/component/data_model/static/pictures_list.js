function pictures_list(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;

	this.width = 200; 
	this.height = 240;
	this.pictures = [];
	
	this.setSize = function(width, height) {
		this.width = width;
		this.height = height;
		for (var i = 0; i < this.pictures.length; ++i)
			this.pictures[i].setSize(width, height);
		this.adjustSizes();
	};
	
	this.adjustSizes = function() {
		for (var i = 0; i < this.pictures.length; ++i) {
			this.pictures[i].element.style.width = "";
			this.pictures[i].element.style.height = "";
		}
		var max_width = 0;
		var max_height = 0;
		for (var i = 0; i < this.pictures.length; ++i) {
			var w = getWidth(this.pictures[i].element);
			if (w > max_width)
				max_width = w;
			var h = getHeight(this.pictures[i].element);
			if (h > max_height)
				max_height = h;
		}
		for (var i = 0; i < this.pictures.length; ++i) {
			setWidth(this.pictures[i].element, max_width);
			setHeight(this.pictures[i].element, max_height);
			this.pictures[i].adjustPicture();
		}
	};
	
	this.setPictures = function(pictures) {
		while (container.childNodes.length > 0) container.removeChild(container.childNodes[0]);
		this.pictures = [];
		var ready_count = pictures.length;
		var ready = function() {
			if (--ready_count == 0 || (ready_count%10)==0)
				t.adjustSizes();
		};
		for (var i = 0; i < pictures.length; ++i) {
			var pic = new pictures_list_thumbnail(container, pictures[i], t.width, t.height, ready);
			if (pictures[i].onclick) {
				if (pictures[i].onclick_title)
					pic.element.title = pictures[i].onclick_title;
				pic.element.style.cursor = "pointer";
				pic.element._pic = pictures[i];
				pic.element.onclick = function(ev) {
					this._pic.onclick(ev, this._pic);
				};
			}
			t.pictures.push(pic);
		}
	};
	
	this._init = function() {
		while (container.childNodes.length > 0) container.removeChild(container.childNodes[0]);
		this.pictures = [];
	};
	this._init();
}

function pictures_list_thumbnail(container, picture, width, height, onloaded) {
	var t=this;
	this.width = width;
	this.height = height;
	
	theme.css("picture_thumbnail.css");
	this.element = document.createElement("DIV"); container.appendChild(this.element);
	this.element.className = "picture_thumbnail";
	this.element.style.display = "inline-block";
	this.picture_container = document.createElement("DIV"); this.element.appendChild(this.picture_container);
	this.picture_container.className = "picture_thumbnail_picture_container";
	this.name_container = document.createElement("DIV"); this.element.appendChild(this.name_container);
	this.name_container.className = "picture_thumbnail_name";
	this.name_container.style.whiteSpace = "nowrap";
	this.name_container.innerHTML = picture.name_provider();
	
	this.setSize = function(width, height) {
		this.width = width;
		this.height = height;
		this.picture_container.style.height = "";
		if (t.picture) t.picture.setSize(width, height);
	};
	
	this.adjustPicture = function() {
		this.picture_container.style.height = "";
		var h = this.element.clientHeight;
		h -= this.name_container.offsetHeight;
		setHeight(this.picture_container, h);
		if (t.picture) {
			var w = this.picture_container.clientWidth;
			h = this.picture_container.clientHeight;
			t.picture.picture_container.style.width = w+"px";
			t.picture.picture_container.style.height = h+"px";
			t.picture.adjustPicture();
		}
	};
	
	this.reload = function() {
		if (t.picture) t.picture.reload();
	};
	
	this.picture = picture.picture_provider(this.picture_container, width, height, onloaded);
}