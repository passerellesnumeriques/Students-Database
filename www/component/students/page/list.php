<?php 
class page_list extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$this->requireJavascript("data_list.js");
		require_once("component/students_groups/page/TreeFrameSelection.inc");
		$groups_ids = TreeFrameSelection::getGroupsIdsFromParentGroup();
		$can_manage = PNApplication::$instance->user_management->hasRight("manage_batches");
?>
<div id='list_container' style='width:100%;height:100%'>
</div>
<?php
$help_div_id = null;
if (PNApplication::$instance->help->isShown('students_list')) {
	$help_div_id = PNApplication::$instance->help->startHelp('students_list', $this, "relative:list_container:center","relative:list_container:inside_top:80", false);
	echo "This screen displays the list of students with their ";
	PNApplication::$instance->help->spanArrow($this, "information by column", "table.grid>thead");
	echo ".<br/>";
	echo "<br/>";
	echo "Note that the students shown depend on the selected element on ";
	PNApplication::$instance->help->spanArrow($this, "the tree", "@parent#curriculum_tree_container");
	echo ".<br/>";
	echo "<br/>";
	PNApplication::$instance->help->spanArrow($this, "Switch view", ".data_list .header .mac_tabs>.mac_tab:nth-child(2)", "horiz");
	echo " to display or not the pictures of the students.<br/>";
	echo "<br/>";
	echo "You can control which information to display on the ";
	PNApplication::$instance->help->spanArrow($this, "top-right side", ".data_list>.header>.header_right>button:nth-child(4)", "horiz");
	echo ":<ul>";
	echo "<li><img src='/static/data_model/table_column.png' style='vertical-align:bottom'/> Select which columns to show/hide</li>";
	echo "<li><img src='/static/data_model/filter.gif' style='vertical-align:bottom'/> Filters which students to display</li>";
	echo "<li><img src='".theme::$icons_16["_import"]."' style='vertical-align:bottom'/> Import information about the students from an Excel file</li>";
	echo "<li><img src='".theme::$icons_16["_export"]."' style='vertical-align:bottom'/> Export the list to an Excel file</li>";
	echo "<li><img src='".theme::$icons_16["print"]."' style='vertical-align:bottom'/> Print the list of students</li>";
	echo "</ul>";
	echo "<br/>";
	echo "You can also click on a student to display its complete profile<br/>";
	PNApplication::$instance->help->endHelp($help_div_id, "students_list");
} else
	PNApplication::$instance->help->availableHelp("students_list");
?>
<script type='text/javascript'>
var url = new URL(location.href);
var batches = <?php echo json_encode(TreeFrameSelection::getBatchesIds()); ?>;
var can_manage = <?php echo json_encode($can_manage);?>;

function build_filters() {
	var filters = [];
	if (url.params['group'] != null) {
		filters.push(<?php
		function writeGroupFilter($ids, $i) {
			echo "{category:'Student',name:'Group',data:{values:['".$ids[$i]."']},force:true";
			if ($i < count($ids)-1) {
				echo ",or:";
				writeGroupFilter($ids, $i+1);
			}
			echo "}";
		}
		writeGroupFilter($groups_ids, 0);
		?>);
	} else if (url.params['period']) {
		filters.push({category:'Student',name:'Group Type',data:{values:[url.params['group_type']]},force:true});
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
if (url.params['period']) data_list_fields.push("Student.Group");
window.students_list = null;
new data_list(
	'list_container',
	url.params['period'] ? 'StudentGroup' : 'Student', null,
	data_list_fields,
	build_filters(),
	batches == null || batches.length > 1 ? 100 : -1,
	'Personal Information.Last Name', true,
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
			confirmDialog("Are you sure you want to remove those students ?<br/><br/><img src='"+theme.icons_16.warning+"' style='vertical-align:bottom;'/> All information related to those students will be removed from the database!<br/><br/><b>If a student is out of PN, please use the <i>Exclude student</i> functionality on his/her profile page, but do not remove all its information from the database.</b>", function(yes) {
				if (yes) {
					var lock_div = lockScreen(null, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Blocking students from being modified by another user...");
					// get people ids
					var ids = [];
					for (var i = 0; i < sel.length; ++i)
						ids.push(list.getTableKeyForRow("People", sel[i]));
					// first lock all those students
					service.json("data_model","lock_rows",{table:"People",row_keys:ids},function(locks_people) {
						if (!locks_people) { unlockScreen(lock_div); return; }
						for (var i = 0; i < locks_people.length; ++i)
							databaselock.addLock(locks_people[i]);
						service.json("data_model","lock_rows",{table:"Student",row_keys:ids},function(locks_student) {
							if (!locks_student) { 
								for (var i = 0; i < locks_people.length; ++i)
									databaselock.removeLock(locks_people[i]);
								unlockScreen(lock_div); 
								return; 
							}
							var next = function(pos) {
								popupFrame(null,"Remove Student","/dynamic/people/page/remove_people_type?people="+ids[pos]+"&type=student&ontyperemoved=removed&onpeopleremoved=removed&oncancel=removed",null,null,null,function(frame,pop){
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
							unlockScreen(lock_div);
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
		if (TreeFrameSelection::isSingleBatch() && $can_manage) {
			$batch_id = TreeFrameSelection::getBatchId();
			$specializations = PNApplication::$instance->curriculum->getBatchSpecializations($batch_id);
			if (count($specializations) > 0) {
				?>
				var assign_spe = document.createElement("BUTTON");
				assign_spe.className = "action";
				assign_spe.innerHTML = "<img src='/static/application/icon.php?main=/static/curriculum/curriculum_16.png&small="+theme.icons_10.edit+"&where=right_bottom' style='vertical-align:bottom'/> Assign specializations";
				assign_spe.onclick = function() {
					window.parent.require("popup_window.js",function() {
						var p = new window.parent.popup_window("Assign Specializations", "/static/application/icon.php?main=/static/curriculum/curriculum_16.png&small="+theme.icons_10.edit+"&where=right_bottom", "");
						var frame = p.setContentFrame("/dynamic/students/page/assign_specializations?batch=<?php echo $batch_id;?>&onsave=reload_list");
						frame.reload_list = reload_list;
						p.showPercent(95,95);
					});
				};
				list.addFooterTool(assign_spe);
				<?php 
			}
		}
		if ($can_manage && (TreeFrameSelection::getPeriodId() <> null || TreeFrameSelection::getGroupId() <> null)) {
			require("component/students_groups/StudentsGroupsJSON.inc");
			echo "var group_type = ".StudentsGroupsJSON::getGroupTypeById(TreeFrameSelection::getGroupTypeId()).";\n";
		?>
		var assign = document.createElement("BUTTON");
		assign.className = "action";
		assign.innerHTML = "<img src='/static/application/icon.php?main=/static/students/student_16.png&small="+theme.icons_10.edit+"&where=right_bottom' style='vertical-align:bottom'/> Assign students to "+group_type.name;
		assign.onclick = function() {
			window.parent.require("popup_window.js",function() {
				var p = new window.parent.popup_window("Assign Students to "+group_type.name, "/static/application/icon.php?main=/static/curriculum/curriculum_16.png&small="+theme.icons_10.edit+"&where=right_bottom", "");
				var frame = p.setContentFrame("/dynamic/students_groups/page/assign_groups?"+(url.params['group'] ? "group="+url.params['group'] : "period="+url.params['period']+"&group_type="+group_type.id)+"&onsave=reload_list");
				frame.reload_list = reload_list;
				p.showPercent(95,95);
			});
		};
		list.addFooterTool(assign);
		<?php } ?>

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
			window.top.popupFrame("/static/people/profile_16.png","Profile","/dynamic/people/page/profile?people="+list.getTableKeyForRow("People",row.row_id),null,95,95);
		});
		layout.changed(list.container);

		if (batches && batches.length == 1 && can_manage) {
			refreshToDo(function() {
				list.ondataloaded.addListener(function() { refreshToDo(); });
			});
		}

		<?php 
		if ($help_div_id <> null) {?>
			var test_ready = function() {
				if ($(".mac_tabs").length == 0) {
					setTimeout(test_ready,10);
					return;
				}
				window.help_display_ready = true;
			}
			test_ready();
		<?php } ?>
	}
);

function reload_list() {
	window.students_list.reloadData();
}
var to_do_item = null;
function refreshToDo(ondone) {
	if (to_do_item) {
		window.students_list.removeFooterTool(to_do_item);
		to_do_item = null;
	}	
	service.customOutput("students","what_to_do_for_batch",{batch:batches[0]},function(res){
		if (res && res.length > 0) {
			to_do_item = document.createElement("BUTTON");
			to_do_item.className = "action";
			to_do_item.innerHTML = "<img src='"+theme.icons_16.warning+"'/> Actions needed";
			to_do_item.onclick = function() {
				var to_do_div = document.createElement("DIV");
				to_do_div.style.padding = "5px";
				to_do_div.innerHTML = res;
				require("popup_window.js",function() {
					var p = new popup_window("Actions needed on Batch",null,to_do_div);
					p.addCloseButton();
					p.show();
				});
			};
			to_do_item = window.students_list.addFooterTool(to_do_item);
			if (ondone) ondone();
		}
	});
}
</script>
<?php 
	}
	
}
?>