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
	if (batches && batches.length > 0) {
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
	if (url.params['specialization'] != null) {
		filters.push({category:'Student',name:'Specialization',data:{value:url.params['specialization']},force:true});
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
	url.params['period'] || url.params['class'] ? 'StudentClass' : 'Student', null,
	data_list_fields,
	build_filters(),
	batches != null && batches.length > 5 ? 200 : -1,
	function (list) {
		list.addTitle("/static/curriculum/batch_16.png", "Students");
		list.addPictureSupport("People",function(container,people_id,width,height) {
			while (container.childNodes.length > 0) container.removeChild(container.childNodes[0]);
			require("profile_picture.js",function() {
				new profile_picture(container,width,height,"center","middle").loadPeopleID(people_id);
			});
		},function(handler)  {
			require("profile_picture.js");
			var people_ids = [];
			for (var i = 0; i < list.grid.getNbRows(); ++i)
				people_ids.push(list.getTableKeyForRow("People",i));
			service.json("people","get_peoples",{ids:people_ids},function(peoples) {
				require("profile_picture.js",function() {
					var pics = [];
					for (var i = 0; i < peoples.length; ++i) {
						var pic = {people:peoples[i]};
						pic.picture_provider = function(container,width,height,onloaded) {
							this.pic = new profile_picture(container,width,height,"center","bottom");
							this.pic.loadPeopleObject(this.people,onloaded);
							return this.pic;
						};
						pic.name_provider = function() {
							return this.people.first_name+"<br/>"+this.people.last_name;
						};
						pic.onclick_title = "Click to see profile of "+pic.people.first_name+" "+pic.people.last_name;
						pic.onclick = function(ev,pic) {
							window.top.require("popup_window.js", function() {
								var p = new window.top.popup_window("Profile", null, "");
								p.setContentFrame("/dynamic/people/page/profile?people="+pic.people.id);
								p.showPercent(95,95);
							});
						};
						pics.push(pic);
					}
					handler(pics);
				});
			});
		});
	
		var remove_button = document.createElement("BUTTON");
		remove_button.className = "action important";
		remove_button.disabled = "disabled";
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
						p.show();
					});
				};
				students_list.addFooterTool(assign_spe);
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
					p.show();
				});
			};
			students_list.addFooterTool(assign);
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
			students_list.addHeader(import_students);
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
			students_list.addHeader(create_student);
		}

		var import_pictures;
		import_pictures = document.createElement("BUTTON");
		import_pictures.className = "flat";
		import_pictures.disabled = "disabled";
		import_pictures.innerHTML = "<img src='/static/images_tool/people_picture.png'/> Import Pictures";
		require("images_tool.js",function() {
			var tool = new images_tool();
			tool.usePopup(true, function() {
				var pictures = [];
				for (var i = 0; i < tool.getPictures().length; ++i) pictures.push(tool.getPictures()[i]);
				var nb = 0;
				for (var i = 0; i < pictures.length; ++i)
					if (tool.getTool("people").getPeople(pictures[i]))
						nb++;
				if (nb == 0) return;
				tool.popup.freeze_progress("Saving pictures...", nb, function(span_message, progress_bar) {
					var next = function(index) {
						if (index == pictures.length) {
							if (tool.getPictures().length > 0) {
								tool.popup.unfreeze();
								return;
							}
							tool.popup.close();
							list.reloadData();
							return;
						}
						var people = tool.getTool("people").getPeople(pictures[index]);
						if (!people) {
							next(index+1);
							return;
						}
						span_message.removeAllChildren();
						span_message.appendChild(document.createTextNode("Saving picture for "+people.first_name+" "+people.last_name));
						var data = pictures[index].getResultData();
						service.json("people", "save_picture", {id:people.id,picture:data}, function(res) {
							if (res)
								tool.removePicture(pictures[index]);
							progress_bar.addAmount(1);
							next(index+1);
						});
					};
					next(0);
				});
			});
			tool.useUpload();
			tool.useFaceDetection();
			tool.addTool("crop",function() {
				tool.setToolValue("crop", null, {aspect_ratio:0.75}, true);
			});
			tool.addTool("scale", function() {
				tool.setToolValue("scale", null, {max_width:300,max_height:300}, false);
			});
			tool.addTool("people", function() {});
			tool.init(function() {
				import_pictures.disabled = "";
				import_pictures.onclick = function(ev) {
					tool.reset();
					var people_ids = [];
					for (var i = 0; i < list.grid.getNbRows(); ++i)
						people_ids.push(list.getTableKeyForRow("People",i));
					if (people_ids.length == 0) {
						alert("Nobody in the list");
						return;
					}
					service.json("people","get_peoples",{ids:people_ids},function(peoples) {
						tool.setToolValue("people", null, peoples, false);
					});
					tool.launchUpload(ev, true);
				};
			});
		});
		if (can_manage)
			list.addHeader(import_pictures);

		list.makeRowsClickable(function(row){
			window.top.popup_frame("/static/people/profile_16.png","Profile","/dynamic/people/page/profile?people="+list.getTableKeyForRow("People",row.row_id),null,95,95);
		});
		layout.invalidate(list.container);

		if (batches && batches.length == 1 && can_manage)
			service.customOutput("students","what_to_do_for_batch",{batch:batches[0]},function(res){
				if (res && res.length > 0) {
					var div = document.createElement("DIV");
					div.className = "warning_footer";
					div.innerHTML = res;
					list.addFooter(div);
				}
			});
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