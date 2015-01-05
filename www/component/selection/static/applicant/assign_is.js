/**
 * Show a menu to select on which information session to assign a list of applicants
 * @param {Element} button button below which to display the menu
 * @param {Array} applicants_ids list of applicants to assign
 * @param {Function} onchanged called when assignment has been done
 */
function assign_is(button, applicants_ids, onchanged) {
	var lock = lockScreen(null, "Loading Information Sessions...");
	service.json("selection","is/list",{},function(list) {
		if (!list) { unlockScreen(lock); return; }
		require("context_menu.js",function() {
			unlockScreen(lock);
			var menu = new context_menu();
			menu.addIconItem(null, "Do not assign to any Information Session", function() {
				var lock = lockScreen(null, "Unassigning applicant"+(applicants_ids.length > 1 ? "s":"")+" from Information Session...");
				service.json("selection", "applicant/assign_is", {applicants:applicants_ids,information_session:null}, function(res) {
					if (!res) { unlockScreen(lock); return; }
					unlockScreen(lock);
					if (onchanged) onchanged();
				});
			});
			for (var i = 0; i < list.length; ++i) {
				menu.addIconItem(null, list[i].name, function(ev,id) {
					var lock = lockScreen(null, "Assigning applicant"+(applicants_ids.length > 1 ? "s":"")+" to Information Session...");
					var confirm_data = null;
					var call_service = function() {
						service.json("selection", "applicant/assign_is", {applicants:applicants_ids,information_session:id,confirm_data:confirm_data}, function(res) {
							if (!res) { unlockScreen(lock); return; }
							if (res && !res.confirm_message) {
								unlockScreen(lock);
								if (onchanged) onchanged();
								return;
							}
							// need confirmation
							confirm_data = res.confirm_data;
							confirmDialog(res.confirm_message, function(yes) {
								if (!yes) { unlockScreen(lock); return; }
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