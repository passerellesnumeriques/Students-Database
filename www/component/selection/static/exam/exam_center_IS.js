function exam_center_IS(container, all_is, linked_is) {
	if (typeof container == 'string') container = document.getElementById(container);
	
	this.linked_ids = linked_is;
	this.onapplicantsadded = new Custom_Event();
	this.onapplicantsremoved = new Custom_Event();
	
	this._init = function() {
		this._table = document.createElement("TABLE");
		this._section = new section("/static/selection/IS/IS_16.png", "Information Sessions Linked", this._table, false, false, 'soft');
		container.appendChild(this._section.element);
		
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
					menu.addIconItem(null, all_is[i].name, function(is_id) {
						t.linked_ids.push(is_id);
						t._addISRow(is_id);
						t._loadApplicants(is_id);
						layout.invalidate(t._table);
					}, all_is[i].id);
				}
				menu.showBelowElement(button);
			});
		};
		
		this.refresh();
	};
	
	this.refresh = function() {
		this._table.innerHTML = "";
		for (var i = 0; i < this.linked_ids.length; ++i)
			this._addISRow(this.linked_ids[i]);
		layout.invalidate(this._table);
	};
	
	this._addISRow = function(is_id) {
		var is = null;
		for (var i = 0; i < all_is.length; ++i) if (all_is[i].id == is_id) { is = all_is[i]; break; }
		var tr, td;
		this._table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.appendChild(document.createTextNode(is.name));
	};
	
	this._loadApplicants = function(is_id) {
		var is = null;
		for (var i = 0; i < all_is.length; ++i) if (all_is[i].id == is_id) { is = all_is[i]; break; }
		var popup = window.parent.get_popup_window_from_frame(window);
		popup.freeze("Loading applicants from Information Session "+is.name+"...");
		var t=this;
		service.json("selection", "applicant/get_applicants",{information_session:is_id,excluded:false}, function(res) {
			popup.unfreeze();
			if (!res) return;
			t.onapplicantsadded.fire(res);
		});
	};
	
	this._init();
}