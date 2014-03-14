function pictures_list_thumbnail(container, people) {
	var t=this;
	this.width = 200;
	this.height = 250;
	
	this.div = document.createElement("DIV"); container.appendChild(this.div);
	this.div.style.display = "inline-block";
	this.picture_container = document.createElement("DIV"); this.div.appendChild(this.picture_container);
	this.picture_container.style.width = (this.width)+'px';
	this.picture_container.style.height = (this.height)+'px';
	this.picture = document.createElement("IMG"); this.picture_container.appendChild(this.picture);
	this.picture.style.width = this.width+'px';
	this.picture.style.height = this.height+'px';
	this.picture_container.style.position = "relative";
	this.picture.style.position = "absolute";
	this.picture.style.bottom = "0px";
	this.picture.onload = function() {
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
	};
	this.picture.src = "/dynamic/people/service/picture?people="+people.id;
	this.name_container = document.createElement("DIV"); this.div.appendChild(this.name_container);
	this.name_container.style.textAlign = "center";
	this.name_container.appendChild(document.createTextNode(people.first_name));
	this.name_container.appendChild(document.createElement("BR"));
	this.name_container.appendChild(document.createTextNode(people.last_name));
}