if (typeof require != 'undefined')
	require("custom_search.js");

function search_student(container,section) {
	require("custom_search.js", function() {
		new custom_search(container, 3, "Search a student", function(input, ondone) {
			var redirected = false;
			var check = function() {
				waitForFrame('application_frame', function(app_win) {
					var app_frame = findFrame('application_frame');
					var url = new URL(app_frame.src);
					if (url.path != '/dynamic/students_groups/page/tree_frame') {
						redirected = true;
						app_win.location.href = '/dynamic/students_groups/page/tree_frame?section='+section+'#/dynamic/students/page/list';
						app_frame.src = '/dynamic/students_groups/page/tree_frame?section='+section+'#/dynamic/students/page/list';
						setTimeout(function(){check();},50);
						return;
					}
					url = new URL(app_win.location.href);
					if (url.path != '/dynamic/students_groups/page/tree_frame') {
						if (!redirected) {
							redirected = true;
							app_win.location.href = '/dynamic/students_groups/page/tree_frame?section='+section+'#/dynamic/students/page/list';
							app_frame.src = '/dynamic/students_groups/page/tree_frame?section='+section+'#/dynamic/students/page/list';
							setTimeout(function(){check();},50);
							return;
						}
						setTimeout(function(){check();},50);
						return;
					}
					waitFrameReady(app_win, function(app_win) {
						return app_win.curriculum_root;
					}, function(app_win) {
						var tag = app_win.getSelectedNodeTag();
						if (tag != 'all_students') {
							app_win.selectNodeByTag('all_students');
						}
						if (app_win.location.hash != '#/dynamic/students/page/list') {
							app_win.location.hash = '#/dynamic/students/page/list';
							triggerEvent(app_win, 'hashchange');
							setTimeout(function(){check();},50);
							return;
						}
						var list_frame = findFrame('students_groups_tree_frame');
						if (list_frame) {
							var list_win = getIFrameWindow(list_frame);
							if (list_win && list_win.location) {
								var list_url = new URL(list_win.location.href);
								if (list_url.path == '/dynamic/students/page/list') {
									waitFrameReady(list_win, function(list_win) { return list_win.students_list; }, function(list_win) {
										var list = list_win.students_list;
										list.resetFilters();
										list.addFilter({
											category: "Personal Information",
											name: "First Name",
											data: {type:'contains',value:input},
											or: {
												category: "Personal Information",
												name: "Last Name",
												data: {type:'contains',value:input}
											}
										});
										list.reloadData(ondone);
									},10000,function() {
										// failed
										ondone();
									});
									check = null;
									return;
								}
							}
						}
						setTimeout(function(){check();},50);
					},10000,function() {
						// failed
						ondone();
						check = null;
					});
					return;
				},10000,function() {
					// failed
					ondone();
					check = null;
				});
			};
			check();
		});
	});
}