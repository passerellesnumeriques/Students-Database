function linked_exam_centers(container, all_centers, linked_ids, can_edit) {
	if (typeof container == 'string') container = document.getElementById(container);
	
	this.linked_ids = linked_ids;
	this.onapplicantsadded = new Custom_Event();
	this.onapplicantsremoved = new Custom_Event();
	
	this._init = function() {
		this._table = document.createElement("TABLE");
		this._section = new section("/static/selection/exam/exam_center_16.png", "Exam Centers Linked", this._table, false, false, 'soft');
		container.appendChild(this._section.element);
		
		if (can_edit) {
			var button_new = document.createElement("BUTTON");
			button_new.className = "action";
			button_new.innerHTML = "<img src='"+theme.icons_16.link+"'/> Link another center";
			this._section.addToolBottom(button_new);
			button_new.t = this;
			button_new.onclick = function() {
				var t = this.t;
				var button = this;
				require("context_menu.js", function() {
					var menu = new context_menu();
					for (var i = 0; i < all_centers.length; ++i) {
						if (t.linked_ids.contains(all_centers[i].id)) continue;
						menu.addIconItem(null, all_centers[i].name, function(ev,center_id) {
							t.linkExamCenter(center_id);
						}, all_centers[i].id);
					}
					if (menu.getItems().length == 0) {
						menu.addIconItem(theme.icons_16.info, "No more Exam Center available");
					}
					menu.showBelowElement(button);
				});
			};
		}
		
		this.refresh();
	};
	
	this.linkExamCenter = function(center_id) {
		this.linked_ids.push(center_id);
		this._addExamCenterRow(center_id,true);
		getWindowFromElement(this._table).pnapplication.dataUnsaved("InterviewCenterExamCenter");
		layout.changed(this._table);
	};
	
	this.setHostFromExamCenter = function(center_id) {
		var popup = window.parent.get_popup_window_from_frame(window);
		popup.freeze("Loading Host information from Exam Center...");
		service.json("selection","exam/get_host",{id:center_id},function(res) {
			popup.unfreeze();
			if (!res) return;
			window.center_location.setHostPartner(res);
		});
	};
	
	this.refresh = function() {
		this._table.removeAllChildren();
		for (var i = 0; i < this.linked_ids.length; ++i)
			this._addExamCenterRow(this.linked_ids[i]);
		layout.changed(this._table);
	};
	
	this._addExamCenterRow = function(center_id,is_new) {
		var ec = null;
		for (var i = 0; i < all_centers.length; ++i) if (all_centers[i].id == center_id) { ec = all_centers[i]; break; }
		var tr, td;
		this._table.appendChild(tr = document.createElement("TR"));
		tr.center_id = center_id;
		tr.appendChild(td = document.createElement("TD"));
		td.appendChild(document.createTextNode(ec.name));
		tr.center_applicants = document.createElement("SPAN");
		td.appendChild(tr.center_applicants);
		tr.appendChild(td = document.createElement("TD"));
		var button;
		if (can_edit) {
			button = document.createElement("BUTTON");
			button.className = "flat";
			button.innerHTML = "<img src='"+theme.icons_16.unlink+"'/>";
			button.title = "Unlink this Exam Center from the Interview Center";
			button.tr = tr;
			button.t = this;
			tr.unlink_button = button;
			button.disabled = 'disabled'; // will be enabled as soon as we get the list of applicants
			button.onclick = function() {
				var t=this.t;
				var tr=this.tr;
				var twin = window;
				confirm_dialog("If you unlink an Exam Center, all its associated applicants will be removed from the interview center.<br/>Are you sure you want to do this ?", function(yes) {
					if (!yes) return;
					if (tr.applicants_list)
						t.onapplicantsremoved.fire(tr.applicants_list);
					t._table.removeChild(tr);
					t.linked_ids.remove(center_id);
					twin.pnapplication.dataUnsaved("InterviewCenterExamCenter");
				});
			};
			td.appendChild(button);
		}
		
		button = document.createElement("BUTTON");
		button.className = "flat";
		button.innerHTML = "<img src='/static/selection/exam/exam_center_16.png'/>";
		button.title = "Open Exam Center profile";
		td.appendChild(button);
		button.onclick = function() {
			window.top.popup_frame("/static/selection/exam/exam_center_16.png","Exam Center","/dynamic/selection/page/exam/center_profile?id="+center_id+"&readonly=true",null,95,95);
		};

		if (can_edit) {
			button = document.createElement("BUTTON");
			button.className = "flat";
			button.innerHTML = "<img src='"+theme.build_icon("/static/contact/address_16.png", theme.icons_10._import)+"'/>";
			button.title = "Use the location and hosting partner of this Exam Center for this Interview Center";
			td.appendChild(button);
			button.t = this;
			button.onclick = function() {
				this.t.setHostFromExamCenter(center_id);
			};
		}
		
		this._loadApplicants(center_id, is_new);
	};
	
	this._loadApplicants = function(center_id, add_applicants) {
		var ec = null;
		for (var i = 0; i < all_centers.length; ++i) if (all_centers[i].id == center_id) { ec = all_centers[i]; break; }
		var popup = window.parent.get_popup_window_from_frame(window);
		if (add_applicants)
			popup.freeze("Loading applicants from Exam Center "+ec.name+"...");
		var t=this;
		var applicants_loaded = function() {
			// get TR for this Exam Center
			var tr = null;
			var rows = getTableRows(t._table);
			for (var i = 0; i < rows.length; ++i) if (rows[i].center_id == center_id) { tr = rows[i]; break; }
			if (tr && tr.unlink_button)
				tr.unlink_button.disabled = '';

			if (add_applicants) popup.unfreeze();
			if (!ec._applicants) return;
			if (add_applicants) t.onapplicantsadded.fire(ec._applicants);
			// update nb applicants in the table
			if (tr) {
				tr.applicants_list = ec._applicants;
				tr.center_applicants.innerHTML = " ("+ec._applicants.length+" passer"+(ec._applicants.length > 1 ? "s" : "")+")";
				layout.changed(t._table);
			}
		};
		if (ec._applicants) applicants_loaded();
		else service.json("selection", "applicant/get_applicants",{exam_center:center_id,excluded:false,exam_passer:true}, function(res) {
			ec._applicants = res;
			applicants_loaded();
		});
	};
	
	this._init();
}