/**
 * Section displaying the list of information session linked to an exam center
 * @param {Element|String} container where to create the screen
 * @param {Array} all_is list of all existing information sessions
 * @param {Array} already_linked_ids list of information sessions which are already linked to another exam center
 * @param {Array} linked_is list of information sessions linked to this exam center
 * @param {Boolean} can_edit indicates if the user can modify something
 */
function exam_center_is(container, all_is, already_linked_ids, linked_is, can_edit) {
	if (typeof container == 'string') container = document.getElementById(container);
	
	/** List of linked information sessions */
	this.linked_ids = [];
	for (var i = 0; i < linked_is.length; ++i) this.linked_ids.push(parseInt(linked_is[i]));
	/** List of information sessions already linked to another exam center */
	this.already_linked_ids = [];
	for (var i = 0; i < already_linked_ids.length; ++i) this.already_linked_ids.push(parseInt(already_linked_ids[i]));
	/** Event fired when applicants are assigned to the exam center due to a new linked information session */
	this.onapplicantsadded = new Custom_Event();
	/** Event fired when applicants are unassigned from the exam center due to a unlinked information session */
	this.onapplicantsremoved = new Custom_Event();
	
	/** Creation of the screen */
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
						if (t.already_linked_ids.contains(all_is[i].id)) {
							var item = menu.addIconItem(null, all_is[i].name+" (already linked to another center)");
							addClassName(item, "disabled");
						} else
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
	/** Link a new information session
	 * @param {Number} is_id information session id
	 */
	this.linkIS = function(is_id) {
		this.linked_ids.push(is_id);
		this._addISRow(is_id,true);
		getWindowFromElement(this._table).pnapplication.dataUnsaved("ExamCenterInformationSession");
		layout.changed(this._table);
	};
	/** Set hosting partner of the exam center from the given information session
	 * @param {Number} is_id information session id
	 */
	this.setHostFromIS = function(is_id) {
		var popup = window.parent.getPopupFromFrame(window);
		popup.freeze("Loading Host information from Information Session...");
		service.json("selection","is/get_host",{id:is_id},function(res) {
			popup.unfreeze();
			if (!res) return;
			window.center_location.setHostPartner(res);
		});
	};
	/** Refresh the screen */
	this.refresh = function() {
		this._table.removeAllChildren();
		for (var i = 0; i < this.linked_ids.length; ++i)
			this._addISRow(this.linked_ids[i]);
		layout.changed(this._table);
	};
	/** Display a row with an information session
	 * @param {Number} is_id information session id
	 * @param {Boolean} is_new indicates if this is a newly linked IS, so that we need to load the list of applicants
	 */
	this._addISRow = function(is_id,is_new) {
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
				confirmDialog("If you unlink an Information Session, all its associated applicants will be removed from the exam center planning.<br/>Are you sure you want to do this ?", function(yes) {
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
			window.top.popupFrame("/static/selection/is/is_16.png","Information Session","/dynamic/selection/page/is/profile?id="+is_id+"&readonly=true",null,95,95);
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
		
		this._loadApplicants(is_id, is_new);
	};
	/** Load applicants from an information session
	 * @param {Number} is_id information session id
	 * @param {Boolean} add_applicants indicates if the loaded applicants should be assigned to the center
	 */
	this._loadApplicants = function(is_id, add_applicants) {
		var is = null;
		for (var i = 0; i < all_is.length; ++i) if (all_is[i].id == is_id) { is = all_is[i]; break; }
		var popup = window.parent.getPopupFromFrame(window);
		if (add_applicants)
			popup.freeze("Loading applicants from Information Session "+is.name+"...");
		var t=this;
		var applicants_loaded = function() {
			// get TR for this IS
			var tr = null;
			var rows = getTableRows(t._table);
			for (var i = 0; i < rows.length; ++i) if (rows[i].is_id == is_id) { tr = rows[i]; break; }
			if (tr && tr.unlink_button)
				tr.unlink_button.disabled = '';

			if (add_applicants) popup.unfreeze();
			if (!is._applicants) return;
			if (add_applicants) t.onapplicantsadded.fire(is._applicants);
			// update nb applicants in the table
			if (tr) {
				tr.applicants_list = is._applicants;
				tr.is_applicants.innerHTML = " ("+is._applicants.length+" applicant"+(is._applicants.length > 1 ? "s" : "")+")";
				layout.changed(t._table);
			}
		};
		if (is._applicants) applicants_loaded();
		else service.json("selection", "applicant/get_applicants",{information_session:is_id,excluded:false}, function(res) {
			is._applicants = res;
			applicants_loaded();
		});
	};
	
	this._init();
}