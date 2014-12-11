function pictures_section(section, pictures, max_width, max_height, can_edit, component, add_picture_service, add_picture_data, remove_picture_service, remove_picture_data) {
	this.pictures = pictures;
	this.createPicture = function(picture) {
		var div = document.createElement("DIV");
		div.style.display = "inline-block";
		div.style.position = "relative";
		div.style.width = max_width+"px";
		div.style.height = max_height+"px";
		div.style.border = "1px solid black";
		setBorderRadius(div,3,3,3,3,3,3,3,3);
		div.style.margin = "1px 2px";
		var img = document.createElement("IMG");
		img.style.width = max_width+"px";
		img.style.height = max_height+"px";
		img.style.position = "absolute";
		img.onload = function() {
			var w = img.naturalWidth;
			var h = img.naturalHeight;
			var ratio = 1;
			if (w > max_width) ratio = max_width/w;
			if (h > max_height && max_height/h < ratio) ratio = max_height/h;
			w = Math.floor(w*ratio);
			h = Math.floor(h*ratio);
			img.style.width = w+"px";
			img.style.height = h+"px";
			img.style.left = Math.floor((max_width-w)/2)+"px";
			img.style.top = Math.floor((max_height-h)/2)+"px";
		};
		img.src = "/dynamic/storage/service/get?id="+picture.id+"&revision="+picture.revision;
		div.appendChild(img);
		section.content.appendChild(div);
		div.style.cursor = "pointer";
		var t=this;
		div.onclick = function() {
			t.slideShow(t.pictures.indexOf(picture));
		};
		if (can_edit) {
			var remove = document.createElement("BUTTON");
			remove.className = "flat small_icon absolute";
			remove.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
			remove.title = "Remove this picture";
			remove.style.position = "absolute";
			remove.style.right = "-6px";
			remove.style.top = "-6px";
			div.appendChild(remove);
			remove.onclick = function(ev) {
				var data = remove_picture_data || {};
				data.id = picture.id;
				service.json(remove_picture_service ? component : "storage", remove_picture_service ? remove_picture_service : "remove", data, function(res) {
					if (res) {
						t.pictures.removeUnique(picture);
						section.content.removeChild(div);
					}
				});
				stopEventPropagation(ev);
				return false;
			};
		}
	};
	this.slideShow = function(index) {
		var container = document.createElement("DIV");
		container.style.position = "fixed";
		container.style.top = "0px";
		container.style.left = "0px";
		container.style.bottom = "0px";
		container.style.right = "0px";
		container.style.width = "100%";
		container.style.height = "100%";
		container.style.display = "flex";
		container.style.flexDirection = "column";
		container.style.alignItems = "stretch";
		container.style.zIndex = 5000;
		var hidder = document.createElement("DIV");
		hidder.style.backgroundColor = "black";
		hidder.style.flex = "1 1 auto";
		setOpacity(hidder, 0.9);
		container.appendChild(hidder);
		var footer = document.createElement("DIV");
		footer.style.height = "40px";
		footer.style.flex = "none";
		footer.style.backgroundColor = "black";
		container.appendChild(footer);
		var container2 = document.createElement("DIV");
		container2.style.position = "fixed";
		container2.style.top = "0px";
		container2.style.left = "0px";
		container2.style.bottom = "0px";
		container2.style.right = "0px";
		container2.style.width = "100%";
		container2.style.height = "100%";
		container2.style.display = "flex";
		container2.style.flexDirection = "column";
		container2.style.alignItems = "center";
		container2.style.zIndex = 5001;
		var picture_container = document.createElement("DIV");
		picture_container.style.backgroundColor = "black";
		picture_container.style.flex = "1 1 auto";
		picture_container.style.position = "relative";
		picture_container.style.width = "1px";
		container2.appendChild(picture_container);
		var nav = document.createElement("DIV");
		container2.appendChild(nav);
		nav.style.display = "flex";
		nav.style.flexDirection = "row";
		nav.style.alignItems = "center";
		var back = document.createElement("IMG");
		back.style.width = "40px";
		back.style.height = "40px";
		back.src = "/static/storage/slideshow_backward.png";
		back.style.cursor = "pointer";
		setOpacity(back,0.7);
		back.onmouseover = function() { setOpacity(this,1); };
		back.onmouseout = function() { setOpacity(this,0.7); };
		nav.appendChild(back);
		var count = document.createElement("DIV");
		count.style.color = "white";
		count.style.fontSize = "12pt";
		count.innerHTML = (index+1)+" / "+this.pictures.length;
		nav.appendChild(count);
		var forward = document.createElement("IMG");
		forward.style.width = "40px";
		forward.style.height = "40px";
		forward.src = "/static/storage/slideshow_forward.png";
		forward.style.cursor = "pointer";
		setOpacity(forward,0.7);
		forward.onmouseover = function() { setOpacity(this,1); };
		forward.onmouseout = function() { setOpacity(this,0.7); };
		nav.appendChild(forward);
		var t=this;
		var imgs = [];
		var selected = index;
		var showPicture = function(img, not_fade) {
			var w = img.naturalWidth;
			var h = img.naturalHeight;
			var max_width = window.top.getWindowWidth();
			var max_height = window.top.getWindowHeight()-40;
			var ratio = 1;
			if (w > max_width) ratio = max_width/w;
			if (h > max_height && max_height/h < ratio) ratio = max_height/h;
			w = Math.floor(w*ratio);
			h = Math.floor(h*ratio);
			img.style.width = w+"px";
			img.style.height = h+"px";
			img.style.left = "0px";
			img.style.top = Math.floor((max_height-h)/2)+"px";
			if (!not_fade) animation.fadeIn(img, 200);
			animation.create(picture_container, picture_container.offsetWidth, w, 100, function(w) { picture_container.style.width = w+"px"; });
		};
		var hidePicture = function(img) {
			animation.fadeOut(img, 200);
		};
		for (var i = 0; i < this.pictures.length; ++i) {
			var img = document.createElement("IMG");
			img.style.width = "1px";
			img.style.height = "1px";
			img.style.position = "absolute";
			setOpacity(img,0);
			img._index = i;
			img.onload = function() {
				this._loaded = true;
				if (selected == this._index) showPicture(this);
			};
			imgs.push(img);
			img.src = "/dynamic/storage/service/get?id="+this.pictures[i].id+"&revision="+this.pictures[i].revision;
			picture_container.appendChild(img);
		}
		var goTo = function(index) {
			hidePicture(imgs[selected]);
			selected = index;
			if (imgs[selected]._loaded) showPicture(imgs[selected]);
			count.innerHTML = (index+1)+" / "+t.pictures.length;
		};
		back.onclick = function(ev) {
			if (selected == 0) goTo(t.pictures.length-1);
			else goTo(selected-1);
			stopEventPropagation(ev);
			return false;
		};
		forward.onclick = function(ev) {
			if (selected == t.pictures.length-1) goTo(0);
			else goTo(selected+1);
			stopEventPropagation(ev);
			return false;
		};
		setOpacity(container,0);
		setOpacity(container2,0);
		window.top.document.body.appendChild(container);
		window.top.document.body.appendChild(container2);
		animation.fadeIn(container, 300);
		animation.fadeIn(container2, 300);
		var listener = function() {
			if (imgs[selected]._loaded) showPicture(imgs[selected],true);
		};
		window.top.listenEvent(window.top,'resize',listener);
		var close = function() {
			container.onclick = null;
			container2.onclick = null;
			animation.fadeOut(container, 200, function() { window.top.document.body.removeChild(container); });
			animation.fadeOut(container2, 200, function() { window.top.document.body.removeChild(container2); });
			window.top.unlistenEvent(window.top,'resize',listener);
		};
		container.onclick = close;
		container2.onclick = close;
	};
	for (var i = 0; i < pictures.length; ++i) this.createPicture(pictures[i]);
	if (can_edit) {
		var add_button = document.createElement("BUTTON");
		add_button.innerHTML = "<img src='/static/storage/import_image_16.png'/> Upload pictures";
		add_button.className = "action";
		section.addToolBottom(add_button);
		var t=this;
		add_button.onclick = function(ev) {
			var upl = createUploadTempFile(true, 10);
			upl.addUploadPopup('/static/storage/import_image_16.png',"Uploading pictures");
			upl.ondonefile = function(file, output, errors, warnings) {
				if (output && output.id) {
					var data = objectCopy(add_picture_data, 10);
					data.id = output.id;
					data.revision = output.revision;
					service.json(component, add_picture_service, data, function(res) {
						if (res) {
							t.pictures.push(output);
							t.createPicture(output);
						}
					}); 
				}
			};
			upl.openDialog(ev, "image/*");
		};
		add_button.disabled = "disabled";
		require("upload.js", function() {
			add_button.disabled = "";
		});
	}
}
