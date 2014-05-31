if (typeof require != 'undefined')
	require("custom_search.js");

function search_student(container) {
	require("custom_search.js", function() {
		new custom_search(container, 3, "Search a student", function(input, ondone) {
			var check = function() {
				waitForFrame('application_frame', function(app_win) {
					var app_frame = findFrame('application_frame');
					var url = new URL(app_frame.src);
					if (url.path != '/dynamic/curriculum/page/tree_frame') {
						app_win.location.href = '/dynamic/curriculum/page/tree_frame#/dynamic/students/page/list';
						app_frame.src = '/dynamic/curriculum/page/tree_frame#/dynamic/students/page/list';
						setTimeout(function(){check();},50);
						return;
					}
					url = new URL(app_win.location.href);
					if (url.path != '/dynamic/curriculum/page/tree_frame') {
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
							app_win.onhashchange();
							setTimeout(function(){check();},50);
							return;
						}
						var list_frame = findFrame('curriculum_tree_frame');
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
									});
									return;
								}
							}
						}
						setTimeout(function(){check();},50);
					});
					return;
				});
			};
			check();
		});
	});
}