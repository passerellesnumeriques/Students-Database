function assign_is(button, applicants_ids, onchanged) {
	var lock = lock_screen(null, "Loading Information Sessions...");
	service.json("selection","is/list",{},function(list) {
		if (!list) { unlock_screen(lock); return; }
		require("context_menu.js",function() {
			unlock_screen(lock);
			var menu = new context_menu();
			menu.addIconItem(null, "Do not assign to any Information Session", function() {
				var lock = lock_screen(null, "Unassigning applicant"+(applicants_ids.length > 1 ? "s":"")+" from Information Session...");
				service.json("selection", "applicant/assign_is", {applicants:applicants_ids,information_session:null}, function(res) {
					if (!res) { unlock_screen(lock); return; }
					unlock_screen(lock);
					if (onchanged) onchanged();
				});
			});
			for (var i = 0; i < list.length; ++i) {
				menu.addIconItem(null, list[i].name, function(ev,id) {
					var lock = lock_screen(null, "Assigning applicant"+(applicants_ids.length > 1 ? "s":"")+" to Information Session...");
					var confirm_data = null;
					var call_service = function() {
						service.json("selection", "applicant/assign_is", {applicants:applicants_ids,information_session:id,confirm_data:confirm_data}, function(res) {
							if (!res) { unlock_screen(lock); return; }
							if (res && !res.confirm_message) {
								unlock_screen(lock);
								if (onchanged) onchanged();
								return;
							}
							// need confirmation
							confirm_data = res.confirm_data;
							confirm_dialog(res.confirm_message, function(yes) {
								if (!yes) { unlock_screen(lock); return; }
								call_service();
							});
						});
					};
					call_service();
				}, list[i].id);
			}
			menu.showBelowElement(button);
		});
	});
}