if (typeof require != 'undefined')
	require("profile_picture.js");

function pictures_list_thumbnail(container, people, width, height, onloaded) {
	var t=this;
	this.width = width;
	this.height = height;
	this.people = people;
	
	theme.css("picture_thumbnail.css");
	this.element = document.createElement("DIV"); container.appendChild(this.element);
	this.element.className = "picture_thumbnail";
	this.element.style.display = "inline-block";
	this.picture_container = document.createElement("DIV"); this.element.appendChild(this.picture_container);
	this.picture_container.className = "picture_thumbnail_picture_container";
	var ready_count = 2;
	var ready = function() {
		if (--ready_count == 0) onloaded();
	};
	require("profile_picture.js", function() {
		t.picture = new profile_picture(t.picture_container, people.id, null, null, t.width, t.height, "center", "bottom", false, ready);
	});
	this.name_container = document.createElement("DIV"); this.element.appendChild(this.name_container);
	this.name_container.className = "picture_thumbnail_name";
	this.name_container.style.whiteSpace = "nowrap";
	this.name_container.appendChild(document.createTextNode(people.first_name));
	this.name_container.appendChild(document.createElement("BR"));
	this.name_container.appendChild(document.createTextNode(people.last_name));
	
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
	ready();
}