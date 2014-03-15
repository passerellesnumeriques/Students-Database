<?php 
class page_list extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->require_javascript("data_list.js");
?>
<div id='list_container' style='width:100%;height:100%'>
</div>
<script type='text/javascript'>
var url = new URL(location.href);

function build_filters() {
	var filters = [];
	if (url.params['batches']) {
		var batches = url.params['batches'].split(',');
		var filter = {category:'Student',name:'Batch',data:{value:batches[0]},force:true};
		var f = filter;
		for (var i = 1; i < batches.length; ++i) {
			f.or = {data:{value:batches[i]}};
			f = f.or; 
		}
		filters.push(filter);
	}
	if (url.params['period']) {
		filters.push({category:'Student',name:'Period',data:{value:url.params['period']},force:true});
	}
	if (url.params['spe'] != null) {
		filters.push({category:'Student',name:'Specialization',data:{value:url.params['spe']},force:true});
	}
	if (url.params['class'] != null) {
		filters.push({category:'Student',name:'Class',data:{value:url.params['class']},force:true});
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
var students_list = new data_list(
	'list_container',
	url.params['period'] || url.params['class'] ? 'StudentClass' : 'Student',
	data_list_fields,
	build_filters(),
	function (list) {
		var remove_button = document.createElement("DIV");
		remove_button.className = "button_verysoft";
		remove_button.innerHTML = "<img src='"+theme.icons_16.remove+"'/> Remove selected students";
		remove_button.onclick = function() {
			var sel = list.grid.getSelectionByRowId();
			if (!sel || sel.length == 0) return;
			confirm_dialog("Are you sure you want to remove those students ?<br/><br/><img src='"+theme.icons_16.warning+"' style='vertical-align:bottom;'/> All information related to those students will be removed from the database!<br/><br/>If a student is out of PN, please use the 'Exclude student' functionality on his/her profile page, instead of removing all its information from the database.", function(yes) {
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
							// ask new confirmation
							service.json("data_model","get_data",{table:"People",data:["First Name","Last Name"],keys:ids}, function(peoples) {
								var msg = "The following students are going to be removed:<ul>";
								for (var i = 0; i < peoples.length; ++i)
									msg += "<li>"+peoples[i][0]+" "+peoples[i][1]+"</li>";
								msg += "</ul>";
								msg += "<br/>Please confirm it is correct.";
								unlock_screen(lock_div);
								confirm_dialog(msg, function(yes) {
									if (!yes) {
										service.json("data_model", "unlock", {locks:locks_people}, function(res){});
										service.json("data_model", "unlock", {locks:locks_student}, function(res){});
										for (var i = 0; i < locks_people.length; ++i)
											databaselock.removeLock(locks_people[i]);
										for (var i = 0; i < locks_student.length; ++i)
											databaselock.removeLock(locks_student[i]);
										return;
									}
									lock_div = lock_screen(null, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Removing students...");
									// removing students
									var next = function(pos) {
										set_lock_screen_content(lock_div, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Removing students... ("+(pos+1)+"/"+ids.length+")");
										service.json("data_model", "remove_row", {table:"People",row_key:ids[pos]}, function(res) {
											if (pos < ids.length-1) {
												next(pos+1);
												return;
											}
											set_lock_screen_content(lock_div, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Finalizing the operation...");
											service.json("data_model", "unlock", {locks:locks_people}, function(res){});
											service.json("data_model", "unlock", {locks:locks_student}, function(res){});
											for (var i = 0; i < locks_people.length; ++i)
												databaselock.removeLock(locks_people[i]);
											for (var i = 0; i < locks_student.length; ++i)
												databaselock.removeLock(locks_student[i]);
											unlock_screen(lock_div);
											list.reloadData();
										});
									};
									next(0);
								});
							});
						});
					});
				}
			});
		};
		list.grid.setSelectable(true);
		list.grid.onselect = function(indexes, rows_ids) {
			if (indexes.length == 0) {
				if (remove_button.parentNode)
					remove_button.parentNode.removeChild(remove_button);
			} else {
				list.addHeader(remove_button);
			}
		};
		if (url.params['batches']) {
			var batches = url.params['batches'].split(',');
			if (batches.length == 1) {
				var import_students = document.createElement("DIV");
				import_students.className = "button_verysoft";
				import_students.innerHTML = "<img src='"+theme.icons_16._import+"' style='vertical-align:bottom'/> Import Students";
				import_students.onclick = function() {
					postData('/dynamic/students/page/import_students',{
						batch:batches[0],
						redirect: location.href
					});
				};
				students_list.addHeader(import_students);
				var create_student = document.createElement("DIV");
				create_student.className = "button_verysoft";
				create_student.innerHTML = "<img src='/static/application/icon.php?main=/static/students/student_16.png&small="+theme.icons_10.add+"&where=right_bottom' style='vertical-align:bottom'/> Create Student";
				create_student.onclick = function() {
					window.top.require("popup_window.js",function() {
						var p = new window.top.popup_window('New Student', theme.build_icon("/static/students/student_16.png",theme.icons_10.add), "");
						var frame = p.setContentFrame("/dynamic/students/page/popup_create_student?batch="+batches[0]+"&ondone=reload_list");
						frame.reload_list = reload_list;
						p.show();
					});
					
					/*
					postData("/dynamic/people/page/create_people",{
						icon: "/static/application/icon.php?main=/static/students/student_32.png&small="+theme.icons_16.add+"&where=right_bottom",
						title: "Create New Student",
						types: ["student"],
						student_batch: batches[0],
						redirect:location.href
					});
					*/
				};
				students_list.addHeader(create_student);
			}
		}
		<?php 
		$batch_id = null;
		$period = null;
		$class = null;
		if (isset($_GET["batches"])) {
			$batches_ids = explode(",", $_GET["batches"]);
			if (count($batches_ids) == 1)
				$batch_id = $batches_ids[0];
		} else if (isset($_GET["period"])) {
			$period = PNApplication::$instance->curriculum->getAcademicPeriod($_GET["period"]);
			$batch_id = $period["batch"];
		} else if (isset($_GET["class"])) {
			$class = PNApplication::$instance->curriculum->getAcademicClass($_GET["class"]);
			$period = PNApplication::$instance->curriculum->getAcademicPeriod($class["period"]);
			$batch_id = $period["batch"];
		}
		if ($batch_id <> null) {
			$specializations = PNApplication::$instance->curriculum->getBatchSpecializations($batch_id);
			if (count($specializations) > 0) {
				?>
				var assign_spe = document.createElement("DIV");
				assign_spe.className = "button_verysoft";
				assign_spe.innerHTML = "<img src='/static/application/icon.php?main=/static/curriculum/curriculum_16.png&small="+theme.icons_10.edit+"&where=right_bottom' style='vertical-align:bottom'/> Assign specializations";
				assign_spe.onclick = function() {
					window.parent.require("popup_window.js",function() {
						var p = new window.parent.popup_window("Assign Specializations", "/static/application/icon.php?main=/static/curriculum/curriculum_16.png&small="+theme.icons_10.edit+"&where=right_bottom", "");
						p.setContentFrame("/dynamic/students/page/assign_specializations?batch=<?php echo $batch_id;?>&onsave=reload_list");
						p.show();
					});
				};
				students_list.addHeader(assign_spe);
				<?php 
			}
		}
		?>
		if (url.params['period'] || url.params['class']) {
			var assign = document.createElement("DIV");
			assign.className = "button_verysoft";
			assign.innerHTML = "<img src='/static/application/icon.php?main=/static/students/student_16.png&small="+theme.icons_10.edit+"&where=right_bottom' style='vertical-align:bottom'/> Assign students to "+(url.params['class'] ? "class" : "classes");
			assign.onclick = function() {
				window.parent.require("popup_window.js",function() {
					var p = new window.parent.popup_window("Assign Students to Class<?php if ($class <> null) echo " ".htmlentities($class["name"]); else echo "es";?>", "/static/application/icon.php?main=/static/curriculum/curriculum_16.png&small="+theme.icons_10.edit+"&where=right_bottom", "");
					p.setContentFrame("/dynamic/students/page/assign_classes?"+(url.params['class'] ? "class="+url.params['class'] : "period="+url.params['period'])+"&onsave=reload_list");
					p.show();
				});
			};
			students_list.addHeader(assign);
		}

		list.makeRowsClickable(function(row){
			location.href = "/dynamic/people/page/profile?people="+list.getTableKeyForRow("People",row.row_id);
		});
		layout.invalidate(list.container);
	}
);

function reload_list() {
	students_list.reloadData();
}

</script>
<?php 
	}
	
}
?>