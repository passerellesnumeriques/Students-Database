<?php 
class page_batches_classes extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		$this->onload("window.loading_lock = lock_screen(null,'Loading...');");
		$this->add_javascript("/static/widgets/vertical_layout.js");
		$this->add_javascript("/static/widgets/page_header.js");
		$this->add_javascript("/static/widgets/splitter_vertical/splitter_vertical.js");
		$this->add_javascript("/static/widgets/tree/tree.js");
		$this->add_javascript("/static/widgets/tabs.js");
		$this->add_javascript("/static/data_model/data_list.js");
		$this->add_javascript("/static/widgets/page_header.js");
		$this->onload("new splitter_vertical('training_education_split',0.25);");
		$this->onload("new vertical_layout('students_right');");
		$this->onload("new page_header('tree_header', true);");
		$this->onload("new vertical_layout('tree_container');");
		
		require_once("component/data_model/page/table_datadisplay_edit.inc");
		table_datadisplay_edit($this, "StudentBatch", null, null, "create_new_batch_table");
		table_datadisplay_edit($this, "AcademicPeriod", null, null, "create_academic_period_table");
		?>
		<div style='width:100%;height:100%'>
			<div id='training_education_split' style='width:100%;height:100%'>
				<div id='tree_container'>
					<div id='tree_header' icon='/static/curriculum/batch_16.png' title='Batches & Classes'>
						<?php if (PNApplication::$instance->user_management->has_right("manage_batches")) { ?>
						<div class='button' onclick="create_new_batch();"><img src='/static/application/icon.php?main=/static/curriculum/batch_16.png&small=<?php echo theme::$icons_10["add"];?>&where=right_bottom'/> New batch</div>
						<?php } ?>
					</div>
					<div id='students_tree' style='overflow:auto;cursor:default;background-color:white' layout='fill'>
					</div>
				</div>
				<div id='students_right'>
					<div id='students_right_header'>
					</div>
					<div id='students_tabs' layout="fill">
					</div>
				</div>
			</div>
		</div>
		<script type='text/javascript'>
		var things_to_be_ready = 1;
		function something_ready() {
			if (--things_to_be_ready > 0) return;
			unlock_screen(window.loading_lock);
		}
		
		var manage_batches = <?php echo PNApplication::$instance->user_management->has_right("manage_batches") ? "true" : "false"; ?>;
		var batches = [<?php
		$batches = SQLQuery::create()->select("StudentBatch")->orderBy("StudentBatch","start_date", false)->execute();
		$first_batch = true;
		foreach ($batches as $batch) {
			if ($first_batch) $first_batch = false; else echo ",";
			echo "{";
			echo "id:".$batch["id"];
			echo ",name:".json_encode($batch["name"]);
			echo ",start_date:".json_encode($batch["start_date"]);
			echo ",end_date:".json_encode($batch["end_date"]);
			echo ",periods:[";
			$periods = SQLQuery::create()->select("AcademicPeriod")->whereValue("AcademicPeriod", "batch", $batch["id"])->orderBy("AcademicPeriod", "start_date", true)->execute();
			$first_period = true;
			foreach ($periods as $period) {
				if ($first_period) $first_period = false; else echo ",";
				echo "{";
				echo "id:".$period["id"];
				echo ",name:".json_encode($period["name"]);
				echo ",start_date:".json_encode($period["start_date"]);
				echo ",end_date:".json_encode($period["end_date"]);
				echo ",specializations:[";
				$spe = SQLQuery::create()->select("AcademicPeriodSpecialization")->whereValue("AcademicPeriodSpecialization","period",$period["id"])->join("AcademicPeriodSpecialization","Specialization",array("specialization"=>"id"))->execute();
				$first_spe = true;
				foreach ($spe as $s) {
					if ($first_spe) $first_spe = false; else echo ",";
					echo "{";
					echo "id:".$s["id"];
					echo ",name:".json_encode($s["name"]);
					echo ",classes:[";
					$classes = SQLQuery::create()->select("AcademicClass")->whereValue("AcademicClass", "period", $period["id"])->whereValue("AcademicClass", "specialization", $s["id"])->execute();
					$first_class = true;
					foreach ($classes as $class) {
						if ($first_class) $first_class = false; else echo ",";
						echo "{";
						echo "id:".$class["id"];
						echo ",name:".json_encode($class["name"]);
						echo "}";
					}
					echo "]";
					echo "}";
				}
				echo "]";
				if (count($spe) == 0) {
					echo ",classes:[";
					$classes = SQLQuery::create()->select("AcademicClass")->whereValue("AcademicClass", "period", $period["id"])->whereNull("AcademicClass", "specialization")->execute();
					$first_class = true;
					foreach ($classes as $class) {
						if ($first_class) $first_class = false; else echo ",";
						echo "{";
						echo "id:".$class["id"];
						echo ",name:".json_encode($class["name"]);
						echo "}";
					}
					echo "]";
				}
				echo "}";
			}
			echo "]";
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
				
		// Right - Header
		var header = new page_header('students_right_header',true);
		header.setTitle("All Students");

		// Tree
		var students_tree = new tree('students_tree');
		students_tree.addColumn(new TreeColumn(""));
		function build_item_element(icon, name, text, cell, onclick, oncontextmenu) {
			var element = document.createElement("SPAN");
			if (icon) {
				var img = document.createElement("IMG");
				img.src = icon;
				img.style.verticalAlign = "bottom";
				img.style.paddingRight = "3px";
				element.appendChild(img);
			}
			if (name) {
				var span = document.createElement("SPAN");
				span.appendChild(document.createTextNode(name));
				span.style.paddingRight = "5px";
				element.appendChild(span);
			}
			var span = document.createElement("SPAN");
			span.style.fontWeight = "bold";
			var node = document.createTextNode(text);
			span.appendChild(node);
			if (cell) {
				window.top.datamodel.addCellChangeListener(window, cell.table, cell.column, cell.row_key, function(value) {
					node.nodeValue = value;
				});
			}
			element.appendChild(span);
			element.onmouseover = function() { this.style.textDecoration = "underline"; };
			element.onmouseout = function() { this.style.textDecoration = "none"; };
			element.onclick = onclick;
			if (oncontextmenu) {
				element.oncontextmenu = function(ev) {
					var elem = this;
					require("context_menu.js",function() {
						var menu = new context_menu();
						oncontextmenu(elem, menu);
						if (menu.getItems().length > 0)
							menu.showBelowElement(elem);
					});
					if (ev)
						stopEventPropagation(ev);
					return false;
				};
				var icon = document.createElement("IMG");
				icon.src = theme.icons_10.arrow_down_context_menu;
				icon.className = "button";
				icon.style.padding = "0px";
				icon.style.verticalAlign = "bottom";
				icon.style.marginLeft = "2px";
				icon.onclick = function(ev) { element.oncontextmenu(ev); return false; };
				element.appendChild(icon);
			}
			return element;
		};
		function init_students_tree() {
			var all_students = new TreeItem(build_item_element(null, null, "All Students", null, select_all_students), true);
			students_tree.addItem(all_students);
			var current_batches = new TreeItem(build_item_element(null, null, "Current Students", null, select_current_students), true);
			all_students.addItem(current_batches);
			var alumni = new TreeItem(build_item_element(null, null, "Alumni", null, select_alumni));
			all_students.addItem(alumni);
			for (var batch_i = 0; batch_i < batches.length; ++batch_i) {
				var batch = batches[batch_i];
				var parent_item;
				if (parseSQLDate(batch.end_date).getTime() < new Date().getTime())
					parent_item = alumni;
				else
					parent_item = current_batches;
				build_batch_tree(batch, parent_item, parent_item == current_batches);
			}			
		};
		function build_batch_tree(batch, parent_item, expand) {
			var batch_element = build_item_element(
				"/static/curriculum/batch_16.png",
				"Batch",
				batch.name,
				{table:"StudentBatch",column:"name",row_key:batch.id},
				function() { select_batch(this.batch); },
				function(elem, menu) {
					if (manage_batches) {
						menu.addIconItem(theme.build_icon("/static/curriculum/academic_16.png",theme.icons_10.add,"right_bottom"), "Add Academic Period", function() { new_academic_period(elem.batch); });
						menu.addSeparator();
						menu.addIconItem(theme.icons_16.remove, "Remove Batch "+elem.batch.name, function() { remove_batch(elem.batch); });
					}
				}						
			);
			batch_element.batch = batch;
			var batch_item = new TreeItem(batch_element, expand);
			batch.item = batch_item;
			batch.element = batch_element;
			parent_item.addItem(batch_item);
			
			for (var period_i = 0; period_i < batch.periods.length; period_i++) {
				var period = batch.periods[period_i];
				build_period_tree(batch, period, batch_item);
			}
		}
		function build_period_tree(batch, period) {
			var period_element = build_item_element(
				theme.build_icon("/static/curriculum/hat.png", "/static/curriculum/calendar_10.gif", "right_bottom"),
				"Period",
				period.name,
				{table:"AcademicPeriod",column:"name",row_key:period.id},
				function() { select_period(this.batch, this.period); },
				function(elem, menu) {
					if (manage_batches) {
						menu.addIconItem(theme.build_icon("/static/curriculum/curriculum_16.png",theme.icons_10.add,"right_bottom"), "Add Specialization", function() { new_specialization(elem.period); });
						if (period.specializations.length == 0)
							menu.addIconItem(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add,"right_bottom"), "Add Class", function() { new_class(elem.period); });
						menu.addSeparator();
						menu.addIconItem(theme.icons_16.remove, "Remove Period "+period.name, function() { remove_period(elem.period); });
					}
				}
			);
			var start = parseSQLDate(period.start_date).getTime();
			var end = parseSQLDate(period.end_date).getTime();
			var now = new Date().getTime();
			period_element.style.color = end < now ? "#4040A0" : start > now ? "#A04040" : "#40A040";
			period_element.batch = batch;
			period_element.period = period;
			var period_item = new TreeItem(period_element, end > now && start < now);
			period.item = period_item;
			period.element = period_element;
			batch.item.addItem(period_item);
			
			for (var spe_i = 0; spe_i < period.specializations.length; ++spe_i) {
				var spe = period.specializations[spe_i];
				build_spe_tree(period, spe, end > now && start < now);
			}
			if (period.classes)
				for (var i = 0; i < period.classes.length; ++i)
					build_class_tree(period, null, period.classes[i]);
		}
		function build_spe_tree(period, spe, expand) {
			var spe_element = build_item_element(
				"/static/curriculum/curriculum_16.png",
				"Specialization",
				spe.name,
				{table:"Specialization",column:"name",row_key:spe.id},
				function() { select_spe(period,spe); },
				function(elem, menu) {
					if (manage_batches) {
						menu.addIconItem(theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add,"right_bottom"), "Add Class", function() { new_class(period, spe); });
						menu.addSeparator();
						menu.addIconItem(theme.icons_16.remove, "Remove Specialization "+spe.name, function() { remove_specialization(period, spe); });
					}
				}
			);
			spe_element.spe = spe;
			spe_element.period = period;
			var spe_item = new TreeItem(spe_element, expand);
			period.item.addItem(spe_item);
			spe.item = spe_item;
			spe.element = spe_element;
			for (var i = 0; i < spe.classes.length; ++i)
				build_class_tree(period, spe, spe.classes[i]);
		}
		function build_class_tree(period, spe, cl) {
			var class_element = build_item_element(
				"/static/curriculum/batch_16.png",
				"Class",
				cl.name,
				{table:"AcademicClass",column:"name",row_key:cl.id},
				function() { select_class(cl); },
				function(elem, menu) {
					if (manage_batches) {
						menu.addIconItem(theme.icons_16.remove, "Remove Class "+cl.name, function() { remove_class(period, spe, cl); });
					}
				}
			);
			class_element.spe = spe;
			class_element.period = period;
			class_element.cl = cl;
			var class_item = new TreeItem(class_element);
			if (spe)
				spe.item.addItem(class_item);
			else
				period.item.addItem(class_item);
			cl.item = class_item;
			cl.element = class_element;
		}

		// Tabs
		var pages = new tabs('students_tabs',true);
		var students_list_container = document.createElement("DIV");
		var curriculum_frame = document.createElement("IFRAME");
		curriculum_frame.style.border = 'none';
		curriculum_frame.style.width = '100%';
		curriculum_frame.style.height = '100%';
		var grades_frame = document.createElement("IFRAME");
		grades_frame.style.border = 'none';
		grades_frame.style.width = '100%';
		grades_frame.style.height = '100%';
		var discipline_frame = document.createElement("IFRAME");
		discipline_frame.style.border = 'none';
		discipline_frame.style.width = '100%';
		discipline_frame.style.height = '100%';
		var health_frame = document.createElement("IFRAME");
		health_frame.style.border = 'none';
		health_frame.style.width = '100%';
		health_frame.style.height = '100%';
		var updates_frame = document.createElement("IFRAME");
		updates_frame.style.border = 'none';
		updates_frame.style.width = '100%';
		updates_frame.style.height = '100%';
		updates_frame.src = "/dynamic/news/page/news?sections="+encodeURIComponent("[{name:'education'}]")+"&title="+encodeURIComponent("Education Updates");
		
		function show_tabs(list) {
			pages.removeAll();
			for (var i = 0; i < list.length; ++i) {
				switch (list[i]) {
				case "Students List": pages.addTab("Students List", "/static/students/student_16.png", students_list_container); break;
				case "Curriculum": pages.addTab("Curriculum", "/static/curriculum/curriculum_16.png", curriculum_frame); break;
				case "Grades": pages.addTab("Grades", "/static/transcripts/grades.gif", grades_frame); break;
				case "Discipline": pages.addTab("Discipline", "/static/discipline/discipline.png", discipline_frame); break;
				case "Health": pages.addTab("Health", "/static/health/health.png", health_frame); break;
				case "Updates": pages.addTab("Updates", "/static/news/news.png", updates_frame); break;
				}
			}
		}
		show_tabs(["Students List","Updates"]);

		// Students List
		things_to_be_ready++;
		var students_list = new data_list(
			students_list_container,
			'Student',
			[
				'Personal Information.First Name',
				'Personal Information.Last Name',
				'Personal Information.Gender',
				'Student.Batch',
				'Student.Specialization'
			],
			[],
			function (list) {
				_update_data_list_buttons();
				something_ready();
			}
		);

		// selection
		var filter_batches = [];
		var filter_period = null;
		var filter_spe = null;
		var filter_class = null;
		function update_data() {
			var filters = _update_students_filters();
			if (filter_class == null && filter_period == null && students_list.getRootTable() != "Student") {
				students_list.setRootTable("Student", filters, function() {
					_update_data_list_buttons();
				});
			} else if ((filter_class != null || filter_period != null) && students_list.getRootTable() != "StudentClass") {
				// TODO set filters
				students_list.setRootTable("StudentClass", filters, function() {
					_update_data_list_buttons();
				});
			} else {
				students_list.resetFilters();
				for (var i = 0; i < filters.length; ++i)
					students_list.addFilter(filters[i]);
				_update_data_list_buttons();
				students_list.reloadData();
			}
		}
		function _update_students_filters() {
			var filters = [];
			if (filter_batches != null && filter_batches.length > 0) {
				var filter = {category:'Student',name:'Batch',data:{value:filter_batches[0]},force:true};
				var f = filter;
				for (var i = 1; i < filter_batches.length; ++i) {
					f.or = {data:{value:filter_batches[i]}};
					f = f.or; 
				}
				filters.push(filter);
				if (filter_period != null) {
					filters.push({category:'Student',name:'Period',data:{value:filter_period.id},force:true});
				}
				if (filter_spe != null) {
					filters.push({category:'Student',name:'Specialization',data:{value:filter_spe.id},force:true});
				}
				if (filter_class != null) {
					filters.push({category:'Student',name:'Class',data:{value:filter_class.id},force:true});
				}
			}
			return filters;
		}
		function _update_data_list_buttons() {
			students_list.resetHeader();
			if (filter_batches != null && filter_batches.length > 0) {
				if (filter_batches.length == 1 && filter_class == null && filter_period == null) {
					var import_students = document.createElement("DIV");
					import_students.className = "button";
					import_students.innerHTML = "<img src='"+theme.icons_16.import+"' style='vertical-align:bottom'/> Import Students";
					import_students.onclick = function() {
						postData('/dynamic/students/page/import_students',{
							batch:filter_batches[0],
							redirect: "/dynamic/training_education/page/batches_classes"
						});
					};
					students_list.addHeader(import_students);
					var create_student = document.createElement("DIV");
					create_student.className = "button";
					create_student.innerHTML = "<img src='/static/application/icon.php?main=/static/students/student_16.png&small="+theme.icons_10.add+"&where=right_bottom' style='vertical-align:bottom'/> Create Student";
					create_student.onclick = function() {
						postData("/dynamic/people/page/create_people",{
							icon: "/static/application/icon.php?main=/static/students/student_32.png&small="+theme.icons_16.add+"&where=right_bottom",
							title: "Create New Student",
							types: ["student"],
							student_batch: filter_batches[0],
							redirect:"/dynamic/training_education/page/batches_classes"
						});
					};
					students_list.addHeader(create_student);
					var batch = null;
					for (var i = 0; i < batches.length; ++i)
						if (batches[i].id == filter_batches[0]) { batch = batches[i]; break; }
					var batch_specializations_ids = [];
					for (var i = 0; i < batch.periods.length; ++i) {
						for (var j = 0; j < batch.periods[i].specializations.length; ++j) {
							var spe_id = batch.periods[i].specializations[j].id;
							if (!batch_specializations_ids.contains(spe_id))
								batch_specializations_ids.push(spe_id);
						}
					}
					if (batch_specializations_ids.length > 0) {
						var assign_spe = document.createElement("DIV");
						assign_spe.className = "button";
						assign_spe.innerHTML = "<img src='/static/application/icon.php?main=/static/curriculum/curriculum_16.png&small="+theme.icons_10.edit+"&where=right_bottom' style='vertical-align:bottom'/> Assign specializations";
						assign_spe.onclick = function() {
							require("popup_window.js",function() {
								var p = new popup_window("Assign Specializations", "/static/application/icon.php?main=/static/curriculum/curriculum_16.png&small="+theme.icons_10.edit+"&where=right_bottom", "");
								var f = p.setContentFrame("/dynamic/students/page/assign_specializations?batch="+batch.id);
								p.addOkCancelButtons(function() {
									p.freeze("Saving specializations...");
									getIFrameWindow(f).save(function(msg) {
										p.set_freeze_content(msg);
									},function(){
										p.close();
										students_list.reloadData();
									});
								});
								p.show();
							});
						};
						students_list.addHeader(assign_spe);
					}
				} else if (filter_class != null || filter_period != null) {
					var assign = document.createElement("DIV");
					assign.className = "button";
					assign.innerHTML = "<img src='/static/application/icon.php?main=/static/students/student_16.png&small="+theme.icons_10.edit+"&where=right_bottom' style='vertical-align:bottom'/> Assign students to "+(filter_class != null ? "class "+filter_class.name : "classes");
					assign.onclick = function() {
						require("popup_window.js",function() {
							var p = new popup_window("Assign Students", "/static/application/icon.php?main=/static/students/student_16.png&small="+theme.icons_10.edit+"&where=right_bottom", "");
							var f = p.setContentFrame("/dynamic/students/page/assign_classes?"+(filter_class != null ? "class="+filter_class.id : "period="+filter_period.id));
							p.addOkCancelButtons(function() {
								p.freeze("Saving class assignment...");
								getIFrameWindow(f).save(function(msg){
									p.set_freeze_content(msg);
								},function(){
									p.close();
									students_list.reloadData();
								});
							});
							p.show();
						});
					};
					students_list.addHeader(assign);
				}
			}
		}
		function select_all_students() {
			filter_batches = [];
			filter_period = null;
			filter_spe = null;
			filter_class = null;
			header.setTitle("All Students");
			header.resetMenu();
			updates_frame.src = "/dynamic/news/page/news?sections="+encodeURIComponent("[{name:'education'}]")+"&title="+encodeURIComponent("Education Updates");
			show_tabs(["Students List","Updates"]);
			update_data();
		}
		function select_current_students() {
			filter_batches = [];
			filter_period = null;
			filter_spe = null;
			filter_class = null;
			for (var i = 0; i < batches.length; ++i)
				if (parseSQLDate(batches[i].end_date).getTime() > new Date().getTime())
					filter_batches.push(batches[i].id);
			header.setTitle("Current Students");
			header.resetMenu();
			var src = "/dynamic/news/page/news?sections="+encodeURIComponent("[{name:'education',tags:[");
			for (var i = 0; i < batches.length; ++i) {
				if (parseSQLDate(batches[i].end_date).getTime() < new Date().getTime()) continue;
				if (i>0) src += encodeURIComponent(",");
				src += "'batch"+batches[i].id+"'";
			}
			src += encodeURIComponent("]}]")+"&title="+encodeURIComponent("Cuurent Students Updates");
			updates_frame.src = src;
			show_tabs(["Students List","Discipline","Health","Updates"]);
			discipline_frame.src = "/dynamic/discipline/page/home"; // TODO
			health_frame.src = "/dynamic/health/page/home"; // TODO
			update_data();
		}
		function select_alumni() {
			filter_batches = [];
			filter_period = null;
			filter_spe = null;
			filter_class = null;
			for (var i = 0; i < batches.length; ++i)
				if (parseSQLDate(batches[i].end_date).getTime() < new Date().getTime())
					filter_batches.push(batches[i].id);
			header.setTitle("Alumni");
			header.resetMenu();
			var src = "/dynamic/news/page/news?sections="+encodeURIComponent("[{name:'education',tags:[");
			for (var i = 0; i < batches.length; ++i) {
				if (parseSQLDate(batches[i].end_date).getTime() > new Date().getTime()) continue;
				if (i>0) src += encodeURIComponent(",");
				src += "'batch"+batches[i].id+"'";
			}
			src += encodeURIComponent("]}]")+"&title="+encodeURIComponent("Alumni Updates");
			updates_frame.src = src;
			show_tabs(["Students List","Updates"]);
			update_data();
		}
		function select_batch(batch) {
			filter_batches = [batch.id];
			filter_period = null;
			filter_spe = null;
			filter_class = null;
			var title = document.createElement("SPAN");
			title.appendChild(document.createTextNode("Batch "));
			if (manage_batches) {
				var batch_name = document.createElement("SPAN");
				title.appendChild(batch_name);
				require("editable_cell.js",function(){
					new editable_cell(title, "StudentBatch", "name", batch.id, "field_text", {max_length:100,min_length:1,can_be_null:false}, batch.name, null, function(field) {
						batch.name = field.getCurrentData();
					});
				});
			} else {
				title.appendChild(document.createTextNode(batch.name));
			}
			header.setTitle(title);
			header.resetMenu();
			var div = document.createElement("DIV");
			div.style.padding = "2px";
			div.appendChild(document.createTextNode("Integration Date: "));
			var span_integration = document.createElement("SPAN");
			div.appendChild(span_integration);
			div.appendChild(document.createTextNode(" Graduation Date: "));
			var span_graduation = document.createElement("SPAN");
			div.appendChild(span_graduation);
			header.addMenuItem(div);
			if (manage_batches) {
				require("editable_cell.js",function(){
					new editable_cell(span_integration, "StudentBatch", "start_date", batch.id, "field_date", {maximum_cell:"end_date",can_be_empty:false}, batch.start_date, null, function(field) {
						batch.start_date = field.getCurrentData();
					});
					new editable_cell(span_graduation, "StudentBatch", "end_date", batch.id, "field_date", {minimum_cell:"start_date",can_be_empty:false}, batch.end_date, null, function(field) {
						batch.end_date = field.getCurrentData();
					});
				});
			} else {
				span_integration.innerHTML = batch.start_date;
				span_graduation.innerHTML = batch.end_date;
			}
			curriculum_frame.src = "/dynamic/curriculum/page/curriculum?batch="+batch.id;
			updates_frame.src = "/dynamic/news/page/news?sections="+encodeURIComponent("[{name:'education',tags:['batch"+batch.id+"']}]")+"&title="+encodeURIComponent("Updates for Batch "+batch.name);
			show_tabs(["Students List","Curriculum","Discipline","Health","Updates"]);
			discipline_frame.src = "/dynamic/discipline/page/home"; // TODO
			health_frame.src = "/dynamic/health/page/home"; // TODO
			update_data();
		}
		function select_period(batch, period) {
			filter_batches = [batch.id];
			filter_period = period;
			filter_spe = null;
			filter_class = null;
			// TODO filter students for this period ??
			var title = document.createElement("SPAN");
			title.appendChild(document.createTextNode("Batch "+batch.name+": "));
			if (manage_batches) {
				var period_name = document.createElement("SPAN");
				title.appendChild(period_name);
				require("editable_cell.js",function(){
					new editable_cell(title, "AcademicPeriod", "name", period.id, "field_text", {max_length:100,min_length:1,can_be_null:false}, period.name, null, function(field) {
						period.name = field.getCurrentData();
					});
				});
			} else {
				title.appendChild(document.createTextNode(period.name));
			}
			header.setTitle(title);
			header.resetMenu();
			var div = document.createElement("DIV");
			div.style.padding = "2px";
			div.appendChild(document.createTextNode("Start: "));
			var span_start = document.createElement("SPAN");
			div.appendChild(span_start);
			div.appendChild(document.createTextNode(" End: "));
			var span_end = document.createElement("SPAN");
			div.appendChild(span_end);
			header.addMenuItem(div);
			if (manage_batches) {
				require("editable_cell.js",function(){
					new editable_cell(span_start, "AcademicPeriod", "start_date", period.id, "field_date", {maximum_cell:"end_date",can_be_empty:false}, period.start_date, null, function(field) {
						period.start_date = field.getCurrentData();
					});
					new editable_cell(span_end, "AcademicPeriod", "end_date", period.id, "field_date", {minimum_cell:"start_date",can_be_empty:false}, period.end_date, null, function(field) {
						period.end_date = field.getCurrentData();
					});
				});
			} else {
				span_start.innerHTML = period.start_date;
				span_end.innerHTML = period.end_date;
			}
			curriculum_frame.src = "/dynamic/curriculum/page/curriculum?period="+period.id;
			updates_frame.src = "/dynamic/news/page/news?sections="+encodeURIComponent("[{name:'education',tags:['period"+period.id+"']}]")+"&title="+encodeURIComponent("Updates for Period "+period.name);
			var t = ["Students List","Curriculum","Updates"];
			if (period.specializations.length == 0) {
				t.push("Grades");
				grades_frame.src = "/dynamic/transcripts/page/students_grades?period="+period.id;
			}
			t.push("Discipline");
			t.push("Health");
			discipline_frame.src = "/dynamic/discipline/page/home"; // TODO
			health_frame.src = "/dynamic/health/page/home"; // TODO
			show_tabs(t);
			update_data();
		}
		function select_spe(period, spe) {
			var batch = period.element.batch;
			
			filter_batches = [batch.id];
			filter_period = period;
			filter_spe = spe;
			filter_class = null;
			var title = document.createElement("SPAN");
			title.appendChild(document.createTextNode("Batch "+batch.name+", Period "+period.name+": Specialization "+spe.name));
			header.setTitle(title);
			header.resetMenu();
			curriculum_frame.src = "/dynamic/curriculum/page/curriculum?period="+period.id;
			grades_frame.src = "/dynamic/transcripts/page/students_grades?period="+period.id+"&specialization="+spe.id;
			discipline_frame.src = "/dynamic/discipline/page/home"; // TODO
			health_frame.src = "/dynamic/health/page/home"; // TODO
			show_tabs(["Students List","Curriculum","Grades","Discipline","Health"]);
			update_data();
		}
		function select_class(cl) {
			var period = cl.element.period;
			var spe = cl.element.spe;
			var batch = period.element.batch;
			
			filter_batches = [batch.id];
			filter_period = period;
			filter_spe = spe;
			filter_class = cl;
			var title = document.createElement("SPAN");
			title.appendChild(document.createTextNode("Batch "+batch.name+", Period "+period.name+": Class "));
			if (manage_batches) {
				var name = document.createElement("SPAN");
				title.appendChild(name);
				require("editable_cell.js",function(){
					new editable_cell(title, "AcademicClass", "name", cl.id, "field_text", {max_length:100,min_length:1,can_be_null:false}, cl.name, null, function(field) {
						cl.name = field.getCurrentData();
					});
				});
			} else {
				title.appendChild(document.createTextNode(cl.name));
			}
			header.setTitle(title);
			header.resetMenu();
			curriculum_frame.src = "/dynamic/curriculum/page/curriculum?period="+period.id;
			grades_frame.src = "/dynamic/transcripts/page/students_grades?class="+cl.id;
			discipline_frame.src = "/dynamic/discipline/page/home"; // TODO
			health_frame.src = "/dynamic/health/page/home"; // TODO
			show_tabs(["Students List","Curriculum","Grades","Discipline","Health"]);
			update_data();
		}
		
		// Functionalities
		function create_new_batch() {
			var container = document.createElement("DIV");
			var error_div = document.createElement("DIV");
			error_div.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> ";
			var error_text = document.createElement("SPAN");
			error_text.style.color = "red";
			error_div.appendChild(error_text);
			error_div.style.visibility = "hidden";
			error_div.style.visibility = "absolute";
			container.appendChild(error_div);

			require("popup_window.js",function(){
				var popup = new popup_window("Create New Batch", "/static/curriculum/batch_16.png", container);
				var table = new create_new_batch_table(container, function(error) {
					if (error) {
						error_text.innerHTML = error;
						error_div.style.visibility = 'visible';
						error_div.style.position = 'static';
						popup.disableButton('ok');
					} else {
						error_div.style.visibility = 'hidden';
						error_div.style.position = 'absolute';
						popup.enableButton('ok');
					}
				});
				popup.addOkCancelButtons(function(){
					popup.freeze();
					table.save(function(id){
						if (id) { popup.close(); location.reload(); return; }
						popup.unfreeze();
					});
				});
				popup.show(); 
			});
		}
		function remove_batch(batch) {
			confirm_dialog("Are you sure you want to remove the batch '"+batch.name+"', including all its content ?",function(yes){
				if (!yes) return;
				var lock = lock_screen();
				service.json("data_model","remove_row",{table:"StudentBatch",row_key:batch.id},function(res){
					unlock_screen(lock);
					if (!res) return;
					location.reload();
				});
			});
		}
		function new_academic_period(batch) {
			var container = document.createElement("DIV");
			var error_div = document.createElement("DIV");
			error_div.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> ";
			var error_text = document.createElement("SPAN");
			error_text.style.color = "red";
			error_div.appendChild(error_text);
			error_div.style.visibility = "hidden";
			error_div.style.visibility = "absolute";
			container.appendChild(error_div);

			require("popup_window.js",function(){
				var popup = new popup_window("Create New Academic Period", theme.icons_16.add, container);
				var config = [];
				config.push({
					data: "Period Start",
					config: {
						minimum:batch.periods.length == 0 ? batch.start_date : batch.periods[batch.periods.length-1].end_date,
						maximum:batch.end_date
					}
				});
				config.push({
					data: "Period End",
					config: {
						minimum:batch.periods.length == 0 ? batch.start_date : batch.periods[batch.periods.length-1].end_date,
						maximum:batch.end_date
					}
				});
				var table = new create_academic_period_table(container, function(error) {
					if (error) {
						error_text.innerHTML = error;
						error_div.style.visibility = 'visible';
						error_div.style.position = 'static';
						popup.disableButton('ok');
					} else {
						error_div.style.visibility = 'hidden';
						error_div.style.position = 'absolute';
						popup.enableButton('ok');
					}
				}, config);
				popup.addOkCancelButtons(function(){
					popup.freeze();
					table.save(function(id){
						if (id) { 
							popup.close(); 
							var period = {
								id: id,
								name: table.get_data("Period Name"),
								start_date: table.get_data("Period Start"),
								end_date: table.get_data("Period End"),
								specializations: [],
								classes:[]
							};
							batch.periods.push(period);
							build_period_tree(batch, period);
							return; 
						}
						popup.unfreeze();
					},{batch:batch.id});
				});
				popup.show(); 
			});
		}
		function remove_period(period) {
			confirm_dialog("Are you sure you want to remove the academic period '"+period.name+"' and its content ?",function(yes){
				if (!yes) return;
				var lock = lock_screen();
				service.json("data_model","remove_row",{table:"AcademicPeriod",row_key:period.id},function(res){
					unlock_screen(lock);
					if (!res) return;
					var batch = period.element.batch;
					batch.periods.remove(period);
					batch.item.removeItem(period.item);
				});
			});
		}
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
				content.appendChild(document.createElement("BR"));
				content.appendChild(document.createElement("HR"));
				var div = document.createElement("DIV"); content.appendChild(div);
				div.style.padding = "3px";
				div.appendChild(document.createTextNode("Add this specialization from period "+period.name+" to period "));
				var select_to_period = document.createElement("SELECT");
				var o = document.createElement("OPTION");
				o.value = period.id;
				o.text = period.name;
				select_to_period.add(o);
				var found = false;
				for (var i = 0; i < period.element.batch.periods.length; ++i) {
					if (!found) {
						if (period.element.batch.periods[i].id == period.id) found = true;
						continue;
					}
					o = document.createElement("OPTION");
					o.value = period.element.batch.periods[i].id;
					o.text = period.element.batch.periods[i].name;
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
						var found = false;
						var periods = [period];
						for (var i = 0; i < period.element.batch.periods.length; ++i) {
							if (!found) {
								if (period.element.batch.periods[i].id == period.id) found = true;
								if (period.element.batch.periods[i].id == select_to_period.value) break;
								continue;
							}
							var spe_found = false;
							for (var j = 0; j < period.element.batch.periods[i].specializations.length; ++j)
								if (period.element.batch.periods[i].specializations[j].id == spe.id) { spe_found = true; break; }
							if (!spe_found)
								periods.push(period.element.batch.periods[i]);
							if (period.element.batch.periods[i].id == select_to_period.value) break;
						}
						var add_spe_to_period = function(period_index) {
							p.freeze("Add specialization "+spe.name+" to period "+periods[period_index].name+"...");
							service.json("curriculum","add_period_specialization",{period:periods[period_index].id,specialization:spe.id},function(res){
								if (!res) { p.unfreeze(); return; }
								specialization_added_to_period(periods[period_index].id, spe.id);
								p.unfreeze();
								if (period_index == periods.length-1)
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
							specialization_added(spe.id, spe.name);
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
		function remove_specialization(period, spe) {
			confirm_dialog("Are you sure you want to remove the specialization '"+spe.name+"' from the period '"+period.name+"', and all classes of this specialization ?",function(yes){
				if (!yes) return;
				var lock = lock_screen();
				service.json("curriculum","remove_specialization_from_period",{specialization:spe.id,period:period.id},function(res){
					unlock_screen(lock);
					if (!res) return;
					period.specializations.remove(spe);
					period.item.removeItem(spe.item);
				});
			});
		}
		
		function new_class(period, spe) {
			require("popup_window.js",function() {
				var content = document.createElement("DIV");
				content.appendChild(document.createTextNode("New class: "));
				var input = document.createElement("INPUT");
				input.type = 'text';
				input.maxLength = 100;
				content.appendChild(input);
				content.appendChild(document.createElement("BR"));
				content.appendChild(document.createElement("HR"));
				var div = document.createElement("DIV"); content.appendChild(div);
				div.style.padding = "3px";
				div.appendChild(document.createTextNode("Add this class from period "+period.name+" to period "));
				var select_to_period = document.createElement("SELECT");
				var o = document.createElement("OPTION");
				o.value = period.id;
				o.text = period.name;
				select_to_period.add(o);
				var found = false;
				for (var i = 0; i < period.element.batch.periods.length; ++i) {
					if (!found) {
						if (period.element.batch.periods[i].id == period.id) found = true;
						continue;
					}
					if (spe) {
						if (period.element.batch.periods[i].classes) break;
						var spe_found = false;
						for (var j = 0; j < period.element.batch.periods[i].specializations.length; ++j)
							if (period.element.batch.periods[i].specializations[j].id == spe.id) { spe_found = true; break; }
						if (!spe_found) break;
					} else {
						if (period.element.batch.periods[i].specializations.length > 0) break;
					}
					o = document.createElement("OPTION");
					o.value = period.element.batch.periods[i].id;
					o.text = period.element.batch.periods[i].name;
					select_to_period.add(o);
				}
				div.appendChild(select_to_period);

				var p = new popup_window("Add Class",theme.build_icon("/static/curriculum/batch_16.png",theme.icons_10.add,"right_bottom"),content);
				p.addOkCancelButtons(function(){
					if (input.value.length == 0) {
						alert("Please enter a name");
						return;
					}
					var existing = spe ? spe.classes : period.classes;
					for (var i = 0; i < existing.length; ++i)
						if (existing[i].name.toLowerCase().trim() == input.value.toLowerCase().trim()) {
							alert("A class already exists with this name");
							return;
						}

					var found = false;
					var periods = [period];
					for (var i = 0; i < period.element.batch.periods.length; ++i) {
						if (!found) {
							if (period.element.batch.periods[i].id == period.id) found = true;
							if (period.element.batch.periods[i].id == select_to_period.value) break;
							continue;
						}

						var classes = null;
						if (spe) {
							if (!period.element.batch.periods[i].specializations) break;
							for (var j = 0; j < period.element.batch.periods[i].specializations.length; ++j)
								if (period.element.batch.periods[i].specializations[j].id == spe.id) { classes = period.element.batch.periods[i].specializations[j].classes; break; }
							if (classes == null) break;
						} else {
							classes = period.classes;
						}
						var class_found = false;
						for (var j = 0; j < classes.length; ++j)
							if (classes[j].name.toLowerCase().trim() == input.value.toLowerCase().trim()) { class_found = true; break; }
						
						if (!class_found)
							periods.push(period.element.batch.periods[i]);
						if (period.element.batch.periods[i].id == select_to_period.value) break;
					}
					
					var add_to_period = function(period_index) {
						p.freeze("Add class "+input.value.trim()+" to period "+periods[period_index].name+"...");
						service.json("curriculum","new_class",{period:periods[period_index].id,specialization:spe ? spe.id : null,name:input.value.trim()},function(res){
							if (!res || !res.id) { p.unfreeze(); return; }

							var cl = {id:res.id,name:input.value.trim()};
							var s = null;
							if (spe) {
								for (var i = 0; i < periods[period_index].specializations.length; ++i)
									if (periods[period_index].specializations[i].id == spe.id) {
										s = periods[period_index].specializations[i];
										break;
									}
								s.classes.push(cl);
							} else
								periods[period_index].classes.push(cl);
							build_class_tree(periods[period_index], s, cl);
							
							p.unfreeze();
							if (period_index == periods.length-1)
								p.close();
							else
								add_to_period(period_index+1);
						});
					};
					add_to_period(0);
				});
				p.show();
			});
		}
		function remove_class(period, spe, cl) {
			confirm_dialog("Are you sure you want to remove the class '"+cl.name+"' ?",function(yes){
				if (!yes) return;
				var lock = lock_screen();
				service.json("data_model","remove_row",{table:"AcademicClass",row_key:cl.id},function(res){
					unlock_screen(lock);
					if (!res) return;
					if (spe) {
						spe.classes.remove(cl);
						spe.item.removeItem(cl.item);
					} else {
						period.classes.remove(cl);
						period.item.removeItem(cl.item);
					}
				});
			});
		}
		
		
		function specialization_added(id ,name) {
			specializations.push({id:id,name:name});
		}
		function specialization_added_to_period(period_id, spe_id) {
			var period = null;
			// search period
			for (var i = 0; i < batches.length; ++i) {
				for (var j = 0; j < batches[i].periods.length; ++j)
					if (batches[i].periods[j].id == period_id) { period = batches[i].periods[j]; break; }
				if (period != null) break;
			}
			if (period == null) return; // should never happen...
			// search specialization
			var spe = null;
			for (var i = 0; i < specializations.length; ++i)
				if (specializations[i].id == spe_id) { spe = specializations[i]; break; }
			if (spe == null) return; // should never happend...
			
			var s = {id:spe_id,name:spe.name,classes:[]};
			if (period.specializations.length == 0 && period.classes) {
				// classes have been moved to the new specialization
				for (var i = 0; i < period.classes.length; ++i) {
					s.classes.push(period.classes[i]);
					period.item.removeItem(period.classes[i].item);
				}
				period.classes = [];
			}
			period.specializations.push(s);
			build_spe_tree(period, s);
		}

		init_students_tree();
		something_ready();
		</script>
		<?php 
	}
	
}
?>