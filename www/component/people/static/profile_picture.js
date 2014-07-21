if (typeof require != 'undefined')
	require("progress_bar.js");

function profile_picture(container, width, height, halign, valign) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	if (width || height) {
		if (!width) width = 0.75*height;
		if (!height) height = width/0.75;
	}
	t.width = width;
	t.height = height;

	this.picture_container = document.createElement("DIV");
	this.picture_container.style.display = "inline-block";
	this.picture_container.style.position = "relative";
	if (width) {
		this.picture_container.style.width = width+"px";
		this.picture_container.style.height = height + "px";
	}
	if (container) container.appendChild(this.picture_container);
	
	this.adjustPicture = function(recall) {
		if (!t.picture) return;
		var resize_ratio = 1;
		var h = t.picture.naturalHeight;
		var w = t.picture.naturalWidth;
		if (w == 0 || h == 0) {
			if (!recall) recall = 0;
			if (recall >= 10) return;
			setTimeout(function() {
				t.adjustPicture(recall+1);
			},1+recall*10);
		}
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
		if (t.width) {
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
		}
		if (!t.picture.parentNode) {
			t.picture_container.appendChild(t.picture);
		}
	};
	
	var img = document.createElement("IMG");
	img.src = '/static/news/loading.gif'; // TODO put it outside of news
	if (width) {
		img.style.position = "absolute";
		img.style.top = Math.floor(height/2-8)+'px';
		img.style.left = Math.floor(width/2-8)+'px';
	}
	t.picture_container.appendChild(img);
	
	this.loadPeopleID = function(people_id, onloaded) {
		service.json("people", "picture", {people:people_id}, function(res) {
			if (!res) {
				img.src = theme.icons_16.error;
				t.picture = null;
				if (onloaded) onloaded();
				return;
			}
			if (typeof res.storage_id != 'undefined')
				t.loadPeopleStorage(people_id, res.storage_id, res.revision, onloaded);
			else {
				t.picture = document.createElement("IMG");
				t.picture.onload = function() {
					t.adjustPicture();
					if (img.parentNode)
						t.picture_container.removeChild(img);
					if (onloaded) onloaded();
				};
				t.picture.onerror = function() {
					img.src = theme.icons_16.error;
					t.picture = null;
					if (onloaded) onloaded();
				};
				t.picture.src = "/static/people/default_"+(res.sex == 'F' ? "female" : "male")+".jpg";
			}
		});
	};
	this.loadUser = function(domain, username, onloaded) {
		service.json("user_management", "people_from_user", {domain:domain,username:username}, function(res) {
			if (!res) {
				img.src = theme.icons_16.error;
				t.picture = null;
				if (onloaded) onloaded();
				return;
			}
			t.loadPeopleID(res.people_id, onloaded);
		});
	};
	this.loadPeopleObject = function(people, onloaded) {
		if (!people.picture_id)
			this.loadPeopleID(people.id, onloaded);
		else
			this.loadPeopleStorage(people.id, people.picture_id,people.picture_revision,onloaded);
	};
	this.loadPeopleStorage = function(people_id,picture_id,revision,onloaded) {
		if (!picture_id)
			this.loadPeopleID(people_id, onloaded);
		else {
			window.top.datamodel.addCellChangeListener(window, "People", "picture", people_id, function(value) {
				t.loadPeopleID(people_id);
			});
			window.top.datamodel.addCellChangeListener(window, "Storage", "revision", revision, function(value) {
				t.revision = value;
				t.reload();
			});
			this._load(picture_id,revision,onloaded);
		}
	};
	
	this.storage_id = null;
	this.revision = null;
	this._load = function(storage_id,revision,onloaded) {
		this.storage_id = storage_id;
		this.revision = revision;
		if (typeof window.btoa == 'undefined') {
			t.picture = document.createElement("IMG");
			t.picture.onload = function() {
				t.adjustPicture();
				if (img.parentNode)
					t.picture_container.removeChild(img);
				if (onloaded) onloaded();
			};
			t.picture.onerror = function() {
				img.src = theme.icons_16.error;
				t.picture = null;
				if (onloaded) onloaded();
			};
			t.picture.src = "/dynamic/storage/service/get?id="+storage_id+"&revision="+revision;
		} else {
			var progress = 0;
			var total = 0;
			require("progress_bar.js", function() {
				if (progress == -1) return;
				var w = Math.floor(t.width*0.8);
				var h = w > 50 ? 12 : 5;
				t.progress = new progress_bar(w, h);
				if (t.width) {
					t.progress.element.style.position = "absolute";
					t.progress.element.style.top = Math.floor(t.height/2-h/2)+'px';
					t.progress.element.style.left = Math.floor(t.width/2-w/2)+'px';
				}
				if (total != 0) t.progress.setTotal(total);
				t.progress.setPosition(progress);
			});
			service.customOutput("storage", "get?id="+storage_id+"&revision="+revision, null, 
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
						t.picture_container.removeChild(img);
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
					if (!img.parentNode)
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
	};
	
	this.setSize = function(width, height) {
		if (width || height) {
			if (!width) width = 0.75*height;
			if (!height) height = width/0.75;
		}
		this.width = width;
		this.height = height;
		if (width) {
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
		}
		this.adjustPicture();
	};

	this.reload = function() {
		if (!t.picture) return;
		if (!t.storage_id) return;
		var p = new Image();
		p.onload = function() {
			if (t.picture.parentNode) t.picture_container.removeChild(t.picture);
			t.picture = p;
			t.adjustPicture();
		};
		p.src = "/dynamic/storage/service/get?id="+t.storage_id+"&revision="+t.revision+"&ts="+new Date().getTime();
	};
}

function addDataListPeoplePictureSupport(list) {
	list.addPictureSupport("People",function(container,people_id,width,height) {
		while (container.childNodes.length > 0) container.removeChild(container.childNodes[0]);
		require("profile_picture.js",function() {
			new profile_picture(container,width,height,"center","middle").loadPeopleID(people_id);
		});
	},function(handler)  {
		require("profile_picture.js");
		var people_ids = [];
		for (var i = 0; i < list.grid.getNbRows(); ++i) {
			var row = list.grid.getRow(i);
			if (typeof row.row_id == 'undefined') continue;
			people_ids.push(list.getTableKeyForRow("People",i));
		}
		service.json("people","get_peoples",{ids:people_ids},function(peoples) {
			var pics = [];
			for (var i = 0; i < peoples.length; ++i) {
				var pic = {people:peoples[i]};
				pic.picture_provider = function(container,width,height,onloaded) {
					this.pic = new profile_picture(container,width,height,"center","bottom");
					this.pic.loadPeopleObject(this.people,onloaded);
					return this.pic;
				};
				pic.name_provider = function() {
					return this.people.first_name+"<br/>"+this.people.last_name;
				};
				pic.onclick_title = "Click to see profile of "+pic.people.first_name+" "+pic.people.last_name;
				pic.onclick = function(ev,pic) {
					window.top.require("popup_window.js", function() {
						var p = new window.top.popup_window("Profile", null, "");
						p.setContentFrame("/dynamic/people/page/profile?people="+pic.people.id);
						p.showPercent(95,95);
					});
				};
				pics.push(pic);
			}
			handler(pics);
		});
	});
}

function addDataListImportPicturesButton(list) {
	var import_pictures;
	import_pictures = document.createElement("BUTTON");
	import_pictures.className = "flat";
	import_pictures.disabled = "disabled";
	import_pictures.innerHTML = "<img src='/static/images_tool/people_picture.png'/> Import Pictures";
	require("images_tool.js",function() {
		var tool = new images_tool();
		tool.usePopup(true, function() {
			var pictures = [];
			for (var i = 0; i < tool.getPictures().length; ++i) pictures.push(tool.getPictures()[i]);
			var nb = 0;
			for (var i = 0; i < pictures.length; ++i)
				if (tool.getTool("people").getPeople(pictures[i]))
					nb++;
			if (nb == 0) return;
			tool.popup.freeze_progress("Saving pictures...", nb, function(span_message, progress_bar) {
				var next = function(index) {
					if (index == pictures.length) {
						if (tool.getPictures().length > 0) {
							tool.popup.unfreeze();
							return;
						}
						tool.popup.close();
						list.reloadData();
						return;
					}
					var people = tool.getTool("people").getPeople(pictures[index]);
					if (!people) {
						next(index+1);
						return;
					}
					span_message.removeAllChildren();
					span_message.appendChild(document.createTextNode("Saving picture for "+people.first_name+" "+people.last_name));
					var data = pictures[index].getResultData();
					service.json("people", "save_picture", {id:people.id,picture:data}, function(res) {
						if (res)
							tool.removePicture(pictures[index]);
						progress_bar.addAmount(1);
						next(index+1);
					});
				};
				next(0);
			});
		});
		tool.useUpload();
		tool.useFaceDetection();
		tool.addTool("crop",function() {
			tool.setToolValue("crop", null, {aspect_ratio:0.75}, true);
		});
		tool.addTool("scale", function() {
			tool.setToolValue("scale", null, {max_width:300,max_height:300}, false);
		});
		tool.addTool("people", function() {});
		tool.init(function() {
			import_pictures.disabled = "";
			import_pictures.onclick = function(ev) {
				tool.reset();
				var people_ids = [];
				for (var i = 0; i < list.grid.getNbRows(); ++i) {
					var row = list.grid.getRow(i);
					if (typeof row.row_id == 'undefined') continue;
					people_ids.push(list.getTableKeyForRow("People",row.row_id));
				}
				if (people_ids.length == 0) {
					alert("Nobody in the list");
					return;
				}
				service.json("people","get_peoples",{ids:people_ids},function(peoples) {
					tool.setToolValue("people", null, peoples, false);
				});
				tool.launchUpload(ev, true);
			};
		});
	});
	list.addHeader(import_pictures);
}