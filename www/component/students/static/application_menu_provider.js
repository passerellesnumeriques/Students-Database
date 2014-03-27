if (typeof window.populating_menu != 'undefined' && window.populating_menu) {// to avoid execution when loading application
	resetAllMenus();
	addMenuItem(theme.icons_16.dashboard, "Dashboard", "Overview and updates about students", "/dynamic/students/page/tree", "/dynamic/students/page/");
	addMenuItem("/static/training/training_16.png", "Training", "Curriculum, grades", "/dynamic/training/page/tree", "/dynamic/training/page/");
	addMenuItem("/static/education/education_16.png", "Education", "Discipline, health...", "/dynamic/education/page/tree", "/dynamic/education/page/");
	addMenuItem("/static/finance/finance_16.png", "Finance", "Finance", "/dynamic/finance/page/tree", "/dynamic/finance/page/");
	require("autocomplete.js",function() {
		var container = document.createElement("DIV");
		container.style.display = "inline-block";
		container.style.marginBottom = "5px";
		container.style.verticalAlign = "bottom";
		var ac = new autocomplete(container, 3, 'Search a student', function(name, handler) {
			service.json("students","search_student_by_name", {name:name}, function(res) {
				if (!res) { handler([]); return; }
				var items = [];
				for (var i = 0; i < res.length; ++i) {
					var item = new autocomplete_item(res[i].people_id, res[i].first_name+' '+res[i].last_name, res[i].first_name+' '+res[i].last_name+" (Batch "+res[i].batch_name+")");
					items.push(item); 
				}
				handler(items);
			});
		}, function(item) {
			document.getElementById('students_page').src = "/dynamic/people/page/profile?people="+item.value;
		}, 250);
		setBorderRadius(ac.input,8,8,8,8,8,8,8,8);
		setBoxShadow(ac.input,-1,2,2,0,'#D8D8F0',true);
		ac.input.style.background = "#ffffff url('"+theme.icons_16.search+"') no-repeat 3px 1px";
		ac.input.style.padding = "2px 4px 2px 23px";
		ac.input.style.margin = "2px";
		addRightControl(container);
	});
}