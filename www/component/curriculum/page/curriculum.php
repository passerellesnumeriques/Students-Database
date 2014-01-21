<?php 
class page_curriculum extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		if (isset($_GET["batch"])) {
			$periods = SQLQuery::create()->select("AcademicPeriod")->where("batch",$_GET["batch"])->order_by("AcademicPeriod","start_date")->execute();
			$all_periods = $periods;
		} else {
			$periods = SQLQuery::create()->select("AcademicPeriod")->where("id",$_GET["period"])->execute();
			$all_periods = SQLQuery::create()->select("AcademicPeriod")->where("batch",$periods[0]["batch"])->order_by("AcademicPeriod","start_date")->execute();
		}
		
		$this->add_javascript("/static/widgets/tree/tree.js");
		?>
		<div id='curriculum_tree' style='cursor:default'></div>
		<script type='text/javascript'>
		var edit = <?php if (PNApplication::$instance->user_management->has_right("edit_curriculum")) echo "true"; else echo "false"; ?>;
		var periods = [<?php 
		$first = true;
		foreach ($periods as $period) {
			if ($first) $first = false; else echo ",";
			$period_spe = SQLQuery::create()->select("AcademicPeriodSpecialization")->where_value("AcademicPeriodSpecialization","period",$period["id"])->join("AcademicPeriodSpecialization","Specialization",array("specialization"=>"id"))->execute();
			echo "{";
			echo "id:".$period["id"];
			echo ",name:".json_encode($period["name"]);
			echo ",next_periods:[";
			$period_found = false;
			$first_next = true;
			foreach ($all_periods as $p) {
				if (!$period_found) {
					if ($p["id"] == $period["id"]) $period_found = true;
					continue;
				}
				if ($first_next) $first_next = false; else echo ",";
				echo "{id:".$p["id"].",name:".json_encode($p["name"])."}";
			}
			echo "]";
			echo ",specializations:[";
			$first_spe = true;
			foreach ($period_spe as $s) {
				if ($first_spe) $first_spe = false; else echo ",";
				echo "{";
				echo "id:".$s["specialization"];
				echo ",name:".json_encode($s["name"]);
				echo ",subjects:[";
				$subjects = SQLQuery::create()->select("CurriculumSubject")->where_value("CurriculumSubject", "period", $period["id"])->where_value("CurriculumSubject", "specialization", $s["specialization"])->execute();
				$first_subject = true;
				foreach ($subjects as $subject) {
					if ($first_subject) $first_subject = false; else echo ",";
					echo "{";
					echo "id:".$subject["id"];
					echo ",name:".json_encode($subject["name"]);
					echo ",code:".json_encode($subject["code"]);
					echo ",category:".$subject["category"];
					echo "}";
				}
				echo "]";
				echo "}";
			}
			echo "]";
			echo ",subjects:[";
			if (count($period_spe) == 0) {
				$subjects = SQLQuery::create()->select("CurriculumSubject")->where_value("CurriculumSubject", "period", $period["id"])->execute();
				$first_subject = true;
				foreach ($subjects as $subject) {
					if ($first_subject) $first_subject = false; else echo ",";
					echo "{";
					echo "id:".$subject["id"];
					echo ",name:".json_encode($subject["name"]);
					echo ",code:".json_encode($subject["code"]);
					echo ",category:".$subject["category"];
					echo "}";
				}
			}
			echo "]";
			echo "}";
		}
		?>];
		var categories = [<?php
		$categories = SQLQuery::create()->select("CurriculumSubjectCategory")->execute();
		$first = true;
		foreach ($categories as $cat) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$cat["id"];
			echo ",name:".json_encode($cat["name"]);
			echo "}";
		} 
		?>];
		var specializations = [<?php
		$spe = SQLQuery::create()->select("Specialization")->execute();
		$first = true;
		foreach ($spe as $s) {
			if ($first) $first = false; else echo ",";
			echo "{id:".$s["id"].",name:".json_encode($s["name"])."}";
		} 
		?>];

		var t = new tree('curriculum_tree');
		t.addColumn(new TreeColumn(""));

		function build_element(icon, text, oncontextmenu) {
			var div = document.createElement("DIV");
			div.style.display = "inline-block";
			if (icon) {
				var img = document.createElement("IMG");
				img.src = icon;
				img.style.paddingRight = "3px";
				img.style.verticalAlign = 'bottom';
				div.appendChild(img);
			}
			div.appendChild(document.createTextNode(text));
			if (oncontextmenu)
				div.oncontextmenu = function(ev) {
					var elem = this;
					require("context_menu.js",function() {
						var menu = new context_menu();
						oncontextmenu(elem, menu);
						if (menu.getItems().length > 0)
							menu.showBelowElement(elem);
					});
					stopEventPropagation(ev);
					return false;
				};
				return div;
		}
		function build_period(period) {
			var element = build_element(null, period.name, function(elem,menu) {
				if (edit) {
					menu.addIconItem(theme.build_icon("/static/curriculum/curriculum_16.png", theme.icons_10.add,"right_bottom"), "Add Specialization", function() { new_specialization(period) });
					menu.addSeparator();
					menu.addIconItem(theme.build_icon("/static/curriculum/subjects_16.png", theme.icons_10.add,"right_bottom"), "Add Subject Category", new_category);
				}
			});
			var item = new TreeItem(element, true);
			item.period = period;
			element.item = item;
			period.item = item;
			t.addItem(item);
			if (period.specializations.length == 0) {
				for (var i = 0; i < categories.length; ++i)
					build_category(categories[i], period);
			} else {
				for (var i = 0; i < period.specializations.length; ++i)
					build_specialization(period.specializations[i], period);
			}
		}
		function build_specialization(spe, period) {
			var element = build_element("/static/curriculum/curriculum_16.png", spe.name, function(elem,menu) {
				// TODO
			});
			var item = new TreeItem(element, true);
			item.specialization = spe;
			item.period = period;
			element.item = item;
			spe.item = item;
			period.item.addItem(item);
			for (var i = 0; i < categories.length; ++i)
				build_category(categories[i], period, spe);
		}
		function build_category(cat, period, spe) {
			var element = build_element("/static/curriculum/subjects_16.png", cat.name, function(elem,menu) {
				if (edit) {
					menu.addIconItem(theme.build_icon("/static/curriculum/subject_16.png", theme.icons_10.add, "right_bottom"), "Add Subject", function() { new_subject(period, cat, spe); });
					//menu.addSeparator();
					//menu.addIconItem(theme.build_icon("/static/curriculum/subjects_16.png", theme.icons_10.remove,"right_bottom"), "Remove Category "+cat.name, function() { remove_category(cat); });
				}
			});
			var item = new TreeItem(element, true);
			item.category = cat;
			item.period = period;
			item.spe = spe;
			element.item = item;
			if (!spe) {
				period.item.addItem(item);
				if (!period.categories_items) period.categories_items = [];
				period.categories_items.push({category:cat.id,item:item});
				for (var i = 0; i < period.subjects.length; ++i)
					if (period.subjects[i].category == cat.id)
						build_subject(period.subjects[i], item);
			} else {
				spe.item.addItem(item);
				if (!spe.categories_items) spe.categories_items = [];
				spe.categories_items.push({category:cat.id,item:item});
				for (var i = 0; i < spe.subjects.length; ++i)
					if (spe.subjects[i].category == cat.id)
						build_subject(spe.subjects[i], item);
			}
		}
		function build_subject(subject, cat_item) {
			var element = build_element("/static/curriculum/subject_16.png", subject.code+" - "+subject.name);
			var item = new TreeItem(element);
			item.subject = subject;
			element.item = item;
			subject.item = item;
			cat_item.addItem(item);
		}
		for (var i = 0; i < periods.length; ++i)
			build_period(periods[i]);

		function new_specialization(period) {
			require("popup_window.js",function() {
				var content = document.createElement("DIV");
				var selection = -1;
				for (var i = 0; i < specializations.length; ++i) {
					var found = false;
					for (var j = 0; j < period.specializations.length; ++j)
						if (period.specializations[j].id == specializations[i].id) { found = true; break; }
					if (found) continue;
					var radio = document.createElement("INPUT");
					radio.type = 'radio';
					radio.name = 'specialization';
					radio.value = specializations[i].id;
					radio.onchange = function() { if (this.checked) selection = this.value; }
					content.appendChild(radio);
					content.appendChild(document.createTextNode(specializations[i].name));
					content.appendChild(document.createElement("BR"));
				}
				var radio = document.createElement("INPUT");
				radio.type = 'radio';
				radio.name = 'specialization';
				radio.value = 0;
				radio.onchange = function() { if (this.checked) selection = this.value; }
				content.appendChild(radio);
				content.appendChild(document.createTextNode("New specialization: "));
				var input = document.createElement("INPUT");
				input.type = 'text';
				input.maxLength = 100;
				content.appendChild(input);
				var div = document.createElement("DIV"); content.appendChild(div);
				div.style.padding = "3px";
				div.appendChild(document.createTextNode("Add this specialization from period "+period.name+" to period "));
				var select_to_period = document.createElement("SELECT");
				var o = document.createElement("OPTION");
				o.value = period.id;
				o.text = period.name;
				select_to_period.add(o);
				for (var i = 0; i < period.next_periods.length; ++i) {
					o = document.createElement("OPTION");
					o.value = period.next_periods[i].id;
					o.text = period.next_periods[i].name;
					select_to_period.add(o);
				}
				div.appendChild(select_to_period);
				
				var p = new popup_window("Add Specialization",null,content);
				p.addOkCancelButtons(function(){
					if (selection == -1) {
						alert("You didn't select anything");
						return;
					}
					var add_spe = function(spe) {
						var periods_to_add = [period];
						for (var i = 0; i < period.next_periods.length; ++i) {
							periods_to_add.push(period.next_periods[i]);
							if (period.next_periods[i].id == select_to_period.value) break;
						}
						var add_spe_to_period = function(period_index) {
							p.freeze("Add specialization "+spe.name+" to period "+periods_to_add[period_index].name+"...");
							service.json("curriculum","add_period_specialization",{period:periods_to_add[period_index].id,specialization:spe.id},function(res){
								if (!res) { p.unfreeze(); return; }
								// if we have the period on the screen, update it
								period = null;
								for (var i = 0; i < periods.length; ++i)
									if (periods[i].id == periods_to_add[period_index].id) { period = periods[i]; break; }
								if (period != null) {
									var s = {id:spe.id,name:spe.name};
									s.subjects = [];
									if (period.subjects) {
										// all subjects have been moved
										s.subjects = period.subjects;
										period.subjects = null;
										for (var i = 0; i < s.subjects.length; ++i)
											s.subjects[i].item.parent_item.removeItem(s.subjects[i].item);
									}
									if (period.categories_items){
										for (var i = 0; i < period.categories_items.length; ++i)
											period.categories_items[i].item.parent_item.removeItem(period.categories_items[i].item);
										period.categories_items = null;
									}
									period.specializations.push(s);
									build_specialization(s, period);
								}
								if (window.parent.specialization_added_to_period)
									window.parent.specialization_added_to_period(periods_to_add[period_index].id, spe.id);
								p.unfreeze();
								if (period_index == periods_to_add.length-1)
									p.close();
								else
									add_spe_to_period(period_index+1);
							});
						};
						add_spe_to_period(0);
					};
					if (selection == 0) {
						if (input.value.length == 0) {
							alert("Please enter a name");
							return;
						}
						for (var i = 0; i < specializations.length; ++i)
							if (specializations[i].name.toLowerCase().trim() == input.value.toLowerCase().trim()) {
								alert("A specialization already exists with this name");
								return;
							}
						p.freeze("Create specialization "+input.value.trim()+"...");
						service.json("data_model","save_entity",{table:"Specialization",field_name:input.value.trim()},function(res) {
							p.unfreeze();
							if (!res || !res.key) return;
							var spe = {id:res.key,name:input.value.trim()};
							specializations.push(spe);
							if (window.parent.specialization_added)
								window.parent.specialization_added(spe.id, spe.name);
							add_spe(spe);
						});
						return;
					}
					var spe;
					for (var i = 0; i < specializations.length; ++i)
						if (specializations[i].id == selection) { spe = specializations[i]; break; }
					add_spe(spe);
				});
				p.show();
			});
		}
		function new_category() {
			input_dialog(
				theme.build_icon("/static/curriculum/subjects_16.png",theme.icons_10.add,"right_bottom"),
				"New Subject Category",
				"Category name",
				"",
				100,
				function(name) {
					if (name.length == 0) return "Please enter a name";
					for (var i = 0; i < categories.length; ++i)
					if (categories[i].name.toLowerCase().trim() == name.toLowerCase().trim())
						return "This category already exists";
					return null;
				},function(name) {
					if (!name) return;
					var lock = lock_screen(null, "Creation of the category "+name);
					service.json("data_model","save_entity",{table:"CurriculumSubjectCategory",field_name:name},function(res) {
						unlock_screen(lock);
						if (res && res.key) {
							var cat = {id:res.key,name:name};
							categories.push(cat);
							for (var i = 0; i < periods.length; ++i)
								build_category(cat, periods[i]);
						}
					});
				}
			);
		}
		function remove_category(cat) {
			// TODO
			/*var lock = lock_screen(null, "Removing category "+cat.name);
			service.json("data_model","remove_row",{table:"CurriculumSubjectCategory",row_key:cat.id},function(res) {
				unlock_screen(lock);
				if (!res) return;
				// TODO remove from tree
			});*/
		}
		function new_subject(period, category, spe) {
			require("popup_window.js",function() {
				var p = new popup_window("New Subject", theme.build_icon("/static/curriculum/subject_16.png", theme.icons_10.add, "right_bottom"), "");
				var frame = p.setContentFrame("/dynamic/curriculum/page/new_subject?period="+period.id+"&category="+category.id+(spe ? "&specialization="+spe.id : ""));
				p.addOkCancelButtons(function(){
					var w = getIFrameWindow(frame);
					if (!w.validate()) return;
					var code_name = w.get_code_and_name();
					if (code_name == null) return;
					p.close();
					var lock = lock_screen(null, "Creation of the subject "+code_name.name);
					service.json("data_model","save_entity",{
						table: "CurriculumSubject",
						field_period: period.id,
						field_category: category.id,
						field_specialization: spe ? spe.id : null,
						field_code: code_name.code,
						field_name: code_name.name
					},function(res){
						unlock_screen(lock);
						if (res && res.key) {
							var subject = code_name;
							subject.id = res.key;
							subject.category = category.id;
							var cat_item;
							if (spe) {
								spe.subjects.push(subject);
								for (var i = 0; i < spe.categories_items.length; ++i)
									if (spe.categories_items[i].category == category.id) {
										cat_item = spe.categories_items[i].item;
										break;
									}
								} else {
								period.subjects.push(subject);
								for (var i = 0; i < period.categories_items.length; ++i)
									if (period.categories_items[i].category == category.id) {
										cat_item = period.categories_items[i].item;
										break;
									}
							}
							build_subject(subject, cat_item);
						}
					});
				});
				p.show(); 
			});
		}
		</script>
		<?php 
	}
	
}
?>