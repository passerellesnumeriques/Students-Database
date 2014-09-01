function exam_center_is(container, all_is, linked_is, can_edit) {
	if (typeof container == 'string') container = document.getElementById(container);
	
	this.linked_ids = linked_is;
	this.onapplicantsadded = new Custom_Event();
	this.onapplicantsremoved = new Custom_Event();
	
	this._init = function() {
		this._table = document.createElement("TABLE");
		this._section = new section("/static/selection/is/is_16.png", "Information Sessions Linked", this._table, false, false, 'soft');
		container.appendChild(this._section.element);
		
		if (can_edit) {
			var button_new = document.createElement("BUTTON");
			button_new.className = "action";
			button_new.innerHTML = "<img src='"+theme.icons_16.link+"'/> Link another session";
			this._section.addToolBottom(button_new);
			button_new.t = this;
			button_new.onclick = function() {
				var t = this.t;
				var button = this;
				require("context_menu.js", function() {
					var menu = new context_menu();
					for (var i = 0; i < all_is.length; ++i) {
						if (t.linked_ids.contains(all_is[i].id)) continue;
						menu.addIconItem(null, all_is[i].name, function(ev,is_id) {
							t.linkIS(is_id);
						}, all_is[i].id);
					}
					if (menu.getItems().length == 0) {
						menu.addIconItem(theme.icons_16.info, "No more Information Session available");
					}
					menu.showBelowElement(button);
				});
			};
		}
		
		this.refresh();
	};
	
	this.linkIS = function(is_id) {
		this.linked_ids.push(is_id);
		this._addISRow(is_id);
		this._loadApplicants(is_id);
		getWindowFromElement(this._table).pnapplication.dataUnsaved("ExamCenterInformationSession");
		layout.invalidate(this._table);
	};
	
	this.setHostFromIS = function(is_id) {
		var popup = window.parent.get_popup_window_from_frame(window);
		popup.freeze("Loading Host information from Information Session...");
		service.json("selection","is/get_host",{id:is_id},function(res) {
			popup.unfreeze();
			if (!res) return;
			window.center_location.setHostPartner(res);
		});
	};
	
	this.refresh = function() {
		this._table.removeAllChildren();
		for (var i = 0; i < this.linked_ids.length; ++i)
			this._addISRow(this.linked_ids[i]);
		layout.invalidate(this._table);
	};
	
	this._addISRow = function(is_id) {
		var is = null;
		for (var i = 0; i < all_is.length; ++i) if (all_is[i].id == is_id) { is = all_is[i]; break; }
		var tr, td;
		this._table.appendChild(tr = document.createElement("TR"));
		tr.is_id = is_id;
		tr.appendChild(td = document.createElement("TD"));
		td.appendChild(document.createTextNode(is.name));
		tr.is_applicants = document.createElement("SPAN");
		td.appendChild(tr.is_applicants);
		tr.appendChild(td = document.createElement("TD"));
		var button;
		if (can_edit) {
			button = document.createElement("BUTTON");
			button.className = "flat";
			button.innerHTML = "<img src='"+theme.icons_16.unlink+"'/>";
			button.title = "Unlink this Information Session from the Exam Center";
			button.tr = tr;
			button.t = this;
			tr.unlink_button = button;
			button.disabled = 'disabled'; // will be enabled as soon as we get the list of applicants
			button.onclick = function() {
				var t=this.t;
				var tr=this.tr;
				var twin = window;
				confirm_dialog("If you unlink an Information Session, all its associated applicants will be removed from the exam center planning.<br/>Are you sure you want to do this ?", function(yes) {
					if (!yes) return;
					if (tr.applicants_list)
						t.onapplicantsremoved.fire(tr.applicants_list);
					t._table.removeChild(tr);
					t.linked_ids.remove(is_id);
					twin.pnapplication.dataUnsaved("ExamCenterInformationSession");
				});
			};
			td.appendChild(button);
		}
		
		button = document.createElement("BUTTON");
		button.className = "flat";
		button.innerHTML = "<img src='/static/selection/is/is_16.png'/>";
		button.title = "Open Information Session profile";
		td.appendChild(button);
		button.onclick = function() {
			window.top.popup_frame("/static/selection/is/is_16.png","Information Session","/dynamic/selection/page/is/profile?id="+is_id+"&readonly=true",null,95,95);
		};

		if (can_edit) {
			button = document.createElement("BUTTON");
			button.className = "flat";
			button.innerHTML = "<img src='"+theme.build_icon("/static/contact/address_16.png", theme.icons_10._import)+"'/>";
			button.title = "Use the location and hosting partner of this Information Session for this Exam Center";
			td.appendChild(button);
			button.t = this;
			button.onclick = function() {
				this.t.setHostFromIS(is_id);
			};
		}
	};
	
	this._loadApplicants = function(is_id) {
		var is = null;
		for (var i = 0; i < all_is.length; ++i) if (all_is[i].id == is_id) { is = all_is[i]; break; }
		var popup = window.parent.get_popup_window_from_frame(window);
		popup.freeze("Loading applicants from Information Session "+is.name+"...");
		var t=this;
		service.json("selection", "applicant/get_applicants",{information_session:is_id,excluded:false}, function(res) {
			// get TR for this IS
			var tr = null;
			var rows = getTableRows(t._table);
			for (var i = 0; i < rows.length; ++i) if (rows[i].is_id == is_id) { tr = rows[i]; break; }
			if (tr)
				tr.unlink_button.disabled = '';

			popup.unfreeze();
			if (!res) return;
			t.onapplicantsadded.fire(res);
			// update nb applicants in the table
			if (tr) {
				tr.applicants_list = res;
				tr.is_applicants.innerHTML = " ("+res.length+" applicant"+(res.length > 1 ? "s" : "")+")";
				layout.invalidate(t._table);
			}
		});
	};
	
	this._init();
}