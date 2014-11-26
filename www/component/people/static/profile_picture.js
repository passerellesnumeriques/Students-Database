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
		if (!t || !t.picture) return;
		if (!t.picture.parentNode) {
			t.picture_container.appendChild(t.picture);
		}
		var resize_ratio = 1;
		var h = t.picture.naturalHeight;
		var w = t.picture.naturalWidth;
		var p = container.parentNode;
		while (p && p.nodeName != "BODY") p = p.parentNode;
		if (!p) {
			if (!recall) recall = 0;
			if (recall < 1000) {
				setTimeout(function() {
					if (t && t.adjustPicture) t.adjustPicture(recall+1);
				},10+recall);
				return;
			}
		}
		if (w == 0 || h == 0) {
			if (!recall) recall = 0;
			if (recall >= 10) return;
			setTimeout(function() {
				if (t) t.adjustPicture(recall+1);
			},1+recall*10);
			return;
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
		if (t.width >= 75 && t.height >= 75) {
			t.controls.style.display = "flex";
		} else {
			t.controls.style.display = "none";
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
	
	this._datamodel_cell_listener = null;
	this._datamodel_storage_cell_listener = null;
	this.setNoPicture = function(people_id, sex, onloaded) {
		var prev = t.picture;
		t.picture = document.createElement("IMG");
		t.picture.onload = function() {
			if (!t) return;
			if (prev && prev.parentNode) prev.parentNode.removeChild(prev);
			t.adjustPicture();
			if (img.parentNode)
				t.picture_container.removeChild(img);
			if (onloaded) onloaded();
		};
		t.picture.onerror = function() {
			if (!t) return;
			if (prev && prev.parentNode) prev.parentNode.removeChild(prev);
			if (!img.parentNode) t.picture_container.appendChild(img);
			img.src = theme.icons_16.error;
			t.picture = null;
			if (onloaded) onloaded();
		};

		t.picture.src = "/static/people/default_"+(sex == 'F' ? "female" : "male")+".jpg";
		
		if (this._datamodel_cell_listener)
			window.top.datamodel.removeCellChangeListener(this._datamodel_cell_listener);
		window.top.datamodel.addCellChangeListener(window, "People", "picture", people_id, this._datamodel_cell_listener = function(value) {
			t.loadPeopleID(people_id);
		});
	};
	
	this.loadPeopleID = function(people_id, onloaded) {
		service.json("people", "picture", {people:people_id}, function(res) {
			if (!t) return;
			if (!res) {
				if (t.picture.parentNode) t.picture.parentNode.removeChild(t.picture);
				if (!img.parentNode) t.picture_container.appendChild(img);
				img.src = theme.icons_16.error;
				t.picture = null;
				if (onloaded) onloaded();
				return;
			}
			if (typeof res.storage_id != 'undefined')
				t.loadPeopleStorage(people_id, res.storage_id, res.revision, onloaded);
			else
				t.setNoPicture(people_id, res.sex, onloaded);
		});
	};
	this.loadUser = function(domain, username, onloaded) {
		service.json("user_management", "people_from_user", {domain:domain,username:username}, function(res) {
			if (!t) return;
			if (!res) {
				if (t.picture.parentNode) t.picture.parentNode.removeChild(t.picture);
				if (!img.parentNode) t.picture_container.appendChild(img);
				img.src = theme.icons_16.error;
				t.picture = null;
				if (onloaded) onloaded();
				return;
			}
			t.loadPeopleID(res.people_id, onloaded);
		});
	};
	this.loadPeopleObject = function(people, onloaded) {
		if (typeof people.picture_id == 'undefined')
			this.loadPeopleID(people.id, onloaded);
		else if (people.picture_id == null)
			this.setNoPicture(people.id, people.sex, onloaded);
		else
			this.loadPeopleStorage(people.id, people.picture_id,people.picture_revision,onloaded);
	};
	this.loadPeopleStorage = function(people_id,picture_id,revision,onloaded) {
		if (!picture_id)
			this.loadPeopleID(people_id, onloaded);
		else {
			if (this._datamodel_cell_listener)
				window.top.datamodel.removeCellChangeListener(this._datamodel_cell_listener);
			window.top.datamodel.addCellChangeListener(window, "People", "picture", people_id, this._datamodel_cell_listener = function(value) {
				t.loadPeopleID(people_id);
			});
			if (this._datamodel_storage_cell_listener)
				window.top.datamodel.removeCellChangeListener(this._datamodel_storage_cell_listener);
			window.top.datamodel.addCellChangeListener(window, "Storage", "revision", revision, this._datamodel_storage_cell_listener = function(value) {
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
			var prev = t.picture;
			t.picture = document.createElement("IMG");
			t.picture.onload = function() {
				if (prev && prev.parentNode) prev.parentNode.removeChild(prev);
				t.adjustPicture();
				if (img.parentNode)
					t.picture_container.removeChild(img);
				if (onloaded) onloaded();
			};
			t.picture.onerror = function() {
				if (prev && prev.parentNode) prev.parentNode.removeChild(prev);
				if (!img.parentNode) t.picture_container.appendChild(img);
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
					if (!t) return;
					if (!bin) return;
					progress = -1;
					var len = bin.length;
					var binary = '';
					for (var i = 0; i < len; i+=1)
						binary += String.fromCharCode(bin.charCodeAt(i) & 0xff);
					var prev = t.picture;
					t.picture = new Image();
					t.picture.src = 'data:image/jpeg;base64,' + btoa(binary);
					if (t.progress) {
						if (t.progress.element.parentNode)
							t.picture_container.removeChild(t.progress.element);
						t.progress = null;
					}
					if (img.parentNode)
						t.picture_container.removeChild(img);
					if (prev && prev.parentNode) prev.parentNode.removeChild(prev);
					t.adjustPicture();
					if (onloaded) onloaded();
				}, 
				false, 
				function(error) {
					if (!t) return;
					progress = -1;
					if (t.progress) {
						if (t.progress.element.parentNode)
							t.picture_container.removeChild(t.progress.element);
						t.progress = null;
					}
					img.src = theme.icons_16.error;
					if (!img.parentNode)
						t.picture_container.appendChild(img);
					if (t.picture && t.picture.parentNode) t.picture.parentNode.removeChild(t.picture);
					if (onloaded) onloaded();
				}, function(loaded, tot) {
					if (!t) return;
					if (t.progress) {
						if (!t.progress.element.parentNode) {
							if (img.parentNode)
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
	
	this.controls = document.createElement("DIV");
	this.controls.style.flexDirection = "column";
	this.controls.style.position = "absolute";
	this.controls.style.top = "2px";
	this.controls.style.right = "2px";
	this.controls.style.zIndex = "1";
	this.controls.style.display = "none";
	this.controls.style.visibility = "hidden";
	this.controls.onclick = function(ev) { stopEventPropagation(ev); };
	setOpacity(this.controls,0);
	this.picture_container.appendChild(this.controls);
	require("animation.js",function() {
		animation.appearsOnOver(t.picture_container, t.controls);
	});
	
	this.addTool = function(icon,tooltip_text,handler) {
		var button = document.createElement("BUTTON");
		button.className = "flat icon";
		button.style.flex = "none";
		button.innerHTML = "<img src='"+icon+"' style='background-color:rgba(255,255,255,0.5);'/>";
		if (tooltip_text) tooltip(button,tooltip_text);
		button.onclick = handler;
		this.controls.appendChild(button);
	};
	this.addTool("/static/people/enlarge_picture.png", "Enlarge", function() {
		var div = document.createElement("DIV");
		div.style.zIndex = "2";
		var img = document.createElement("IMG");
		img.src = t.picture.src;
		div.appendChild(img);
		
		var close = document.createElement("IMG");
		close.src = "/static/people/close_enlarge.png";
		close.style.backgroundColor = "white";
		setBorderRadius(close,8,8,8,8,8,8,8,8);
		close.style.position = "absolute";
		close.style.right = "-10px";
		close.style.top = "-10px";
		close.style.cursor = "pointer";
		div.appendChild(close);
		close.onclick = function() {
			animation.fadeOut(div,500,function() {
				document.body.removeChild(div);
			});
		};
		
		var resize_ratio = 1;
		var h = t.picture.naturalHeight;
		var w = t.picture.naturalWidth;
		var wh = getWindowHeight()-20;
		var ww = getWindowWidth()-20;
		if (h > wh) resize_ratio = wh/h;
		if (w > ww) {
			var r = ww/w;
			if (r < resize_ratio) resize_ratio = r;
		}
		w = Math.floor(w*resize_ratio);
		h = Math.floor(h*resize_ratio);
		var ow = t.picture_container.offsetWidth;
		var oh = t.picture_container.offsetHeight;
		div.style.position = "fixed";
		var pos = getFixedPosition(t.picture,true);
		setOpacity(div,0);
		div.style.visibility = 'hidden';
		document.body.appendChild(div);
		
		img.style.width = ow+"px";
		img.style.height = oh+"px";
		div.style.left = pos.x+"px";
		div.style.top = pos.y+"px";
		animation.fadeIn(div,500);
		animation.create(div,pos.x,getWindowWidth()/2-w/2,500,function(value,element){ element.style.left = Math.floor(value)+"px"; });
		animation.create(div,pos.y,getWindowHeight()/2-h/2,500,function(value,element){ element.style.top = Math.floor(value)+"px"; });
		animation.create(img,ow,w,500,function(value,element){ element.style.width = Math.floor(value)+"px"; });
		animation.create(img,oh,h,500,function(value,element){ element.style.height = Math.floor(value)+"px"; });
	});
	
	this.picture_container.ondomremoved(function() {
		t.picture = null;
		t.controls = null;
		t.picture_container = null;
		t.progress = null;
		img = null;
		if (t._datamodel_cell_listener)
			window.top.datamodel.removeCellChangeListener(t._datamodel_cell_listener);
		t._datamodel_cell_listener = null;
		if (t._datamodel_storage_cell_listener)
			window.top.datamodel.removeCellChangeListener(t._datamodel_storage_cell_listener);
		t._datamodel_storage_cell_listener = null;
		t = null;
	});
}

function addDataListPeoplePictureSupport(list) {
	list.addPictureSupport("People",function(container,people_id,width,height) {
		container.removeAllChildren();
		container.picture = new profile_picture(container,width,height,"center","middle");
		container.picture.loadPeopleID(people_id);
		container.ondomremoved(function() {
			container.picture = null;
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
	var tool = null;
	require("images_tool.js",function() {
		if (typeof tool == 'undefined') return;
		tool = new images_tool();
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
	list.header.ondomremoved(function() {
		if (tool)
			tool.cleanup();
		tool = undefined;
	});
}