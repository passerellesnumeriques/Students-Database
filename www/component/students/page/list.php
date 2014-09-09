<?php 
class page_list extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$this->requireJavascript("data_list.js");
		$batches = null;
		if (isset($_GET["batches"])) {
			if ($_GET["batches"] == "current") {
				$list = PNApplication::$instance->curriculum->getCurrentBatches();
				$batches = array();
				foreach ($list as $b) array_push($batches, $b["id"]);
			} else if ($_GET["batches"] == "alumni") {
				$list = PNApplication::$instance->curriculum->getAlumniBatches();
				$batches = array();
				foreach ($list as $b) array_push($batches, $b["id"]);
			}
		}
		if (isset($_GET["batch"])) {
			$batches = array($_GET["batch"]);
		}
		$can_manage = PNApplication::$instance->user_management->has_right("manage_batches");
?>
<div id='list_container' style='width:100%;height:100%'>
</div>
<script type='text/javascript'>
var url = new URL(location.href);
var batches = <?php echo json_encode($batches); ?>;
var can_manage = <?php echo json_encode($can_manage);?>;

function build_filters() {
	var filters = [];
	if (url.params['class'] != null) {
		filters.push({category:'Student',name:'Class',data:{values:[url.params['class']]},force:true});
	} else if (url.params['period']) {
		filters.push({category:'Student',name:'Period',data:{values:[url.params['period']]},force:true});
		if (url.params['specialization'] != null) {
			filters.push({category:'Student',name:'Specialization',data:{values:[url.params['specialization']]},force:true});
		}
	} else if (batches && batches.length > 0) {
		var filter = {category:'Student',name:'Batch',data:{values:batches},force:true};
		filters.push(filter);
	}
	return filters;
}

var data_list_fields = [
	'Personal Information.First Name',
	'Personal Information.Last Name',
	'Personal Information.Gender',
	'Student.Batch',
	'Student.Specialization',
];
if (url.params['period']) data_list_fields.push("Student.Class");
window.students_list = null;
new data_list(
	'list_container',
	url.params['period'] || url.params['class'] ? 'StudentClass' : 'Student', null,
	data_list_fields,
	build_filters(),
	batches == null || batches.length > 1 ? 100 : -1,
	function (list) {
		window.students_list = list;

		list.grid.makeScrollable();
		
		list.addTitle("/static/curriculum/batch_16.png", "Students");
		require("profile_picture.js",function() {
			addDataListPeoplePictureSupport(list);
		});
	
		var remove_button = document.createElement("BUTTON");
		remove_button.className = "action red";
		remove_button.disabled = "disabled";
		remove_button.innerHTML = "<img src='"+theme.icons_16.remove_white+"'/> Remove selected students";
		remove_button.onclick = function() {
			var sel = list.grid.getSelectionByRowId();
			if (!sel || sel.length == 0) return;
			confirm_dialog("Are you sure you want to remove those students ?<br/><br/><img src='"+theme.icons_16.warning+"' style='vertical-align:bottom;'/> All information related to those students will be removed from the database!<br/><br/><b>If a student is out of PN, please use the <i>Exclude student</i> functionality on his/her profile page, but do not remove all its information from the database.</b>", function(yes) {
				if (yes) {
					var lock_div = lock_screen(null, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Blocking students from being modified by another user...");
					// get people ids
					var ids = [];
					for (var i = 0; i < sel.length; ++i)
						ids.push(list.getTableKeyForRow("People", sel[i]));
					// first lock all those students
					service.json("data_model","lock_rows",{table:"People",row_keys:ids},function(locks_people) {
						if (!locks_people) { unlock_screen(lock_div); return; }
						for (var i = 0; i < locks_people.length; ++i)
							databaselock.addLock(locks_people[i]);
						service.json("data_model","lock_rows",{table:"Student",row_keys:ids},function(locks_student) {
							if (!locks_student) { 
								for (var i = 0; i < locks_people.length; ++i)
									databaselock.removeLock(locks_people[i]);
								unlock_screen(lock_div); 
								return; 
							}
							var next = function(pos) {
								popup_frame(null,"Remove Student","/dynamic/people/page/remove_people_type?people="+ids[pos]+"&type=student&ontyperemoved=removed&onpeopleremoved=removed&oncancel=removed",null,null,null,function(frame,pop){
									frame.removed = function() {
										if (pos < ids.length-1) {
											next(pos+1);
											return;
										}
										service.json("data_model", "unlock", {locks:locks_people}, function(res){});
										service.json("data_model", "unlock", {locks:locks_student}, function(res){});
										for (var i = 0; i < locks_people.length; ++i)
											databaselock.removeLock(locks_people[i]);
										for (var i = 0; i < locks_student.length; ++i)
											databaselock.removeLock(locks_student[i]);
										list.reloadData();
									};
								});
															
							};
							unlock_screen(lock_div);
							next(0);
						});
					});
				}
			});
		};
		if (can_manage)
			list.addFooterTool(remove_button);
		list.grid.setSelectable(true);
		list.grid.onselect = function(indexes, rows_ids) {
			if (indexes.length == 0) {
				remove_button.disabled = "disabled";
			} else {
				remove_button.disabled = "";
			}
		};
		<?php 
		if ($batches <> null && count($batches) == 1 && $can_manage) {
			$specializations = PNApplication::$instance->curriculum->getBatchSpecializations($batches[0]);
			if (count($specializations) > 0) {
				?>
				var assign_spe = document.createElement("BUTTON");
				assign_spe.className = "action";
				assign_spe.innerHTML = "<img src='/static/application/icon.php?main=/static/curriculum/curriculum_16.png&small="+theme.icons_10.edit+"&where=right_bottom' style='vertical-align:bottom'/> Assign specializations";
				assign_spe.onclick = function() {
					window.parent.require("popup_window.js",function() {
						var p = new window.parent.popup_window("Assign Specializations", "/static/application/icon.php?main=/static/curriculum/curriculum_16.png&small="+theme.icons_10.edit+"&where=right_bottom", "");
						var frame = p.setContentFrame("/dynamic/students/page/assign_specializations?batch=<?php echo $batches[0];?>&onsave=reload_list");
						frame.reload_list = reload_list;
						p.showPercent(95,95);
					});
				};
				list.addFooterTool(assign_spe);
				<?php 
			}
		}
		?>
		if (can_manage && (url.params['period'] || url.params['class'])) {
			var assign = document.createElement("BUTTON");
			assign.className = "action";
			assign.innerHTML = "<img src='/static/application/icon.php?main=/static/students/student_16.png&small="+theme.icons_10.edit+"&where=right_bottom' style='vertical-align:bottom'/> Assign students to "+(url.params['class'] ? "class" : "classes");
			assign.onclick = function() {
				window.parent.require("popup_window.js",function() {
					var p = new window.parent.popup_window("Assign Students to Classes", "/static/application/icon.php?main=/static/curriculum/curriculum_16.png&small="+theme.icons_10.edit+"&where=right_bottom", "");
					var frame = p.setContentFrame("/dynamic/students/page/assign_classes?"+(url.params['class'] ? "class="+url.params['class'] : "period="+url.params['period'])+"&onsave=reload_list");
					frame.reload_list = reload_list;
					p.showPercent(95,95);
				});
			};
			list.addFooterTool(assign);
		}

		if (batches && batches.length == 1 && can_manage) {
			var import_students = document.createElement("BUTTON");
			import_students.className = "flat";
			import_students.innerHTML = "<img src='"+theme.icons_16._import+"' style='vertical-align:bottom'/> Import Students";
			import_students.disabled = "disabled";
			window.top.require(["popup_window.js","excel_import.js"], function() {
				import_students.disabled = "";
				import_students.onclick = function(ev) {
					var container = document.createElement("DIV");
					container.style.width = "100%";
					container.style.height = "100%";
					var popup = new window.top.popup_window("Import Students", theme.icons_16._import, container);
					new window.top.excel_import(popup, container, function(imp) {
						popup.showPercent(95,95);
						imp.init();
						imp.loadImportDataURL(
							"/dynamic/people/page/popup_create_people?types=student&ondone=reload_list&multiple=true",
							{
								prefilled_data: [{table:"Student",data:"Batch",value:batches[0]}]
							}
						);
						imp.frame_import.reload_list = reload_list;
						imp.uploadFile(ev);
					});
				};
			});
			list.addHeader(import_students);
			var create_student = document.createElement("BUTTON");
			create_student.className = "flat";
			create_student.innerHTML = "<img src='/static/application/icon.php?main=/static/students/student_16.png&small="+theme.icons_10.add+"&where=right_bottom' style='vertical-align:bottom'/> Create Student";
			create_student.onclick = function() {
				window.top.require("popup_window.js",function() {
					var p = new window.top.popup_window('New Student', theme.build_icon("/static/students/student_16.png",theme.icons_10.add), "");
					var frame = p.setContentFrame(
						"/dynamic/people/page/popup_create_people?types=student&ondone=reload_list",
						null,
						{
							prefilled_data: [{table:"Student",data:"Batch",value:batches[0]}]
						}
					);
					frame.reload_list = reload_list;
					p.show();
				});
			};
			list.addHeader(create_student);
		}

		if (can_manage) {
			require("profile_picture.js",function() {
				addDataListImportPicturesButton(list);
			});
		}
		
		list.makeRowsClickable(function(row){
			if (typeof row.row_id == 'undefined') return;
			window.top.popup_frame("/static/people/profile_16.png","Profile","/dynamic/people/page/profile?people="+list.getTableKeyForRow("People",row.row_id),null,95,95);
		});
		layout.changed(list.container);

		if (batches && batches.length == 1 && can_manage) {
			refreshToDo(function() {
				list.ondataloaded.add_listener(function() { refreshToDo(); });
			});
		}
	}
);

function reload_list() {
	window.students_list.reloadData();
}
var to_do_div = null;
function refreshToDo(ondone) {
	service.customOutput("students","what_to_do_for_batch",{batch:batches[0]},function(res){
		if (res && res.length > 0) {
			if (!to_do_div) {
				to_do_div = document.createElement("DIV");
				to_do_div.style.maxHeight = "50px";
				to_do_div.style.overflow = "auto";
				to_do_div.className = "warning_footer";
				window.students_list.addFooter(to_do_div);
			}
			to_do_div.innerHTML = res;
			if (ondone) ondone();
		}
	});
}
</script>
<?php 
	}
	
}
?>