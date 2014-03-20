if (typeof require != 'undefined')
	require("progress_bar.js");

function profile_picture(container, people_id, domain, username, width, height, halign, valign, onloaded) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	if (!width) width = 0.75*height;
	if (!height) height = width/0.75;
	t.width = width;
	t.height = height;

	var component = people_id ? "people" : "user_people";
	var service_name = people_id ? "picture?people="+people_id : "user_picture?domain="+domain+"&username="+username;
	
	this.picture_container = document.createElement("DIV");
	this.picture_container.style.position = "relative";
	this.picture_container.style.width = width+"px";
	this.picture_container.style.height = height + "px";
	if (container) container.appendChild(this.picture_container);
	
	this.adjustPicture = function() {
		if (!t.picture) return;
		var resize_ratio = 1;
		var h = t.picture.naturalHeight;
		var w = t.picture.naturalWidth;
		if (h > t.height) {
			resize_ratio = t.height/h;
		}
		if (w > t.width) {
			var r = t.width/w;
			if (r < resize_ratio) resize_ratio = r;
		}
		w = Math.floor(w*resize_ratio);
		h = Math.floor(h*resize_ratio);
		t.picture.style.width = w+'px';
		t.picture.style.height = h+'px';
		switch (halign) {
		case "left": t.picture.style.left = "0px"; break;
		case "right": t.picture.style.right = "0px"; break;
		default: t.picture.style.left = Math.floor(t.picture_container.clientWidth/2-w/2)+'px';
		}
		switch (valign) {
		case "top": t.picture.style.top = "0px"; break;
		case "bottom": t.picture.style.bottom = "0px"; break;
		default: t.picture.style.top = Math.floor(t.picture_container.clientHeight/2-h/2)+'px';
		}
		t.picture.style.position = "absolute";
		if (!t.picture.parentNode) {
			t.picture_container.appendChild(t.picture);
		}
	};
	
	var img = document.createElement("IMG");
	img.src = theme.icons_16.loading;
	img.style.position = "absolute";
	img.style.top = Math.floor(height/2-8)+'px';
	img.style.left = Math.floor(width/2-8)+'px';
	t.picture_container.appendChild(img);
	if (typeof window.btoa == 'undefined') {
		t.picture = document.createElement("IMG");
		t.picture.onload = function() {
			t.adjustPicture();
			if (onloaded) onloaded();
		};
		t.picture.onerror = function() {
			img.src = theme.icons_16.error;
			t.picture = null;
			if (onloaded) onloaded();
		};
		t.picture.src = "/dynamic/"+component+"/service/"+service_name;
	} else {
		var progress = 0;
		var total = 0;
		require("progress_bar.js", function() {
			if (progress == -1) return;
			var w = Math.floor(t.width*0.8);
			var h = w > 50 ? 12 : 5;
			t.progress = new progress_bar(w, h);
			t.progress.element.style.position = "absolute";
			t.progress.element.style.top = Math.floor(t.height/2-h/2)+'px';
			t.progress.element.style.left = Math.floor(t.width/2-w/2)+'px';
			if (total != 0) t.progress.setTotal(total);
			t.progress.setPosition(progress);
		});
		service.customOutput(component, service_name, null, 
			function(bin) {
				if (!bin) return;
				progress = -1;
				var len = bin.length;
				var binary = '';
				for (var i = 0; i < len; i+=1)
					binary += String.fromCharCode(bin.charCodeAt(i) & 0xff);
				t.picture = new Image();
				t.picture.src = 'data:image/jpeg;base64,' + btoa(binary);
				if (t.progress) {
					if (t.progress.element.parentNode)
						t.picture_container.removeChild(t.progress.element);
					t.progress = null;
				}
				if (img.parentNode)
					t.picture_container.appendChild(img);
				t.adjustPicture();
				if (onloaded) onloaded();
			}, 
			false, 
			function(error) {
				progress = -1;
				if (t.progress) {
					if (t.progress.element.parentNode)
						t.picture_container.removeChild(t.progress.element);
					t.progress = null;
				}
				img.src = theme.icons_16.error;
				if (img.parentNode)
					t.picture_container.appendChild(img);
				if (onloaded) onloaded();
			}, function(loaded, tot) {
				if (t.progress) {
					if (!t.progress.element.parentNode) {
						t.picture_container.removeChild(img);
						t.picture_container.appendChild(t.progress.element);
					}
					if (t.progress.total == 0) t.progress.setTotal(tot);
					t.progress.setPosition(loaded);
				} else {
					progress = loaded;
					total = tot;
				}
			},
			"text/plain; charset=x-user-defined"
		);
	}
	
	this.setSize = function(width, height) {
		if (!width) width = 0.75*height;
		if (!height) height = width/0.75;
		this.width = width;
		this.height = height;
		this.picture_container.style.width = (this.width)+'px';
		this.picture_container.style.height = (this.height)+'px';
		if (t.progress) {
			var w = Math.floor(t.width*0.8);
			var h = w > 50 ? 12 : 5;
			t.progress.setSize(w,h);
			t.progress.element.style.top = Math.floor(t.height/2-h/2)+'px';
			t.progress.element.style.left = Math.floor(t.width/2-w/2)+'px';
		}
		img.style.top = Math.floor(height/2-8)+'px';
		img.style.left = Math.floor(width/2-8)+'px';
		if (t._change)
			t._change.style.left = Math.floor(t.picture_container.offsetWidth/2-t._change.offsetWidth/2)+"px";
		this.adjustPicture();
	};

	this.reload = function() {
		if (!t.picture) return;
		var p = new Image();
		p.onload = function() {
			if (t.picture.parentNode) t.picture_container.removeChild(t.picture);
			t.picture = p;
			t.adjustPicture();
		};
		p.src = "/dynamic/"+component+"/service/"+service_name+"&ts="+new Date().getTime();
	};
}