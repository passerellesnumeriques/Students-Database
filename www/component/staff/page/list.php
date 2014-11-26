<?php 
class page_list extends Page {
	
	public function getRequiredRights() { return array("consult_staff_list"); }
	
	public function execute() {
		$this->requireJavascript("data_list.js");
		
		$departments = SQLQuery::create()->select("PNDepartment")->orderBy("PNDepartment","name",true)->execute();
		
		$can_edit = PNApplication::$instance->user_management->has_right("manage_staff", true);
?>
<div id='list_container' style='width:100%;height:100%'>
</div>
<script type='text/javascript'>
new data_list(
	'list_container',
	'Staff', null,
	[
		'Personal Information.First Name',
		'Personal Information.Last Name',
		'PN Staff.Last Department',
		'PN Staff.Last Position'
	],
	[],
	null,
	'Personal Information.Last Name', true,
	function (list) {
		list.grid.makeScrollable();
		require("profile_picture.js",function() {
			addDataListPeoplePictureSupport(list);
			addDataListImportPicturesButton(list);
		});
		<?php if ($can_edit) {?>
		var edit_depts = document.createElement("BUTTON");
		edit_depts.className = "flat";
		edit_depts.innerHTML = "<img src='"+theme.build_icon("/static/staff/department.png",theme.icons_10.edit)+"'/> Edit Departments List";
		edit_depts.onclick = function() {
			require("popup_window.js", function() {
				var p = new popup_window("PN Departments", "/static/staff/department.png", "");
				p.onclose = function() { location.reload(); };
				p.setContentFrame("/dynamic/staff/page/departments");
				p.show();
			});
		};
		list.addHeader(edit_depts);

		<?php if (count($departments) > 0) { ?>
			var create_staff = document.createElement("BUTTON");
			create_staff.className = "flat";
			create_staff.innerHTML = "<img src='"+theme.build_icon("/static/staff/staff_16.png",theme.icons_10.add)+"'/> Create Staff";
			create_staff.onclick = function() {
				window.top.require("popup_window.js",function() {
					var p = new window.top.popup_window('New Staff', theme.build_icon("/static/staff/staff_16.png",theme.icons_10.add), "");
					var frame = p.setContentFrame("/dynamic/people/page/popup_create_people?types=staff&ondone=reload_list");
					frame.reload_list = function() { list.reloadData(); };
					p.show();
				});
			};
			list.addHeader(create_staff);
		<?php } ?>

		var google = document.createElement("BUTTON");
		google.className = "flat";
		google.innerHTML = "<img src='/static/google/google.png'/> Synch from Google";
		google.onclick = function() {
			if (!window.top.google.installed) {
				alert("Google is not yet configured. Please ask your administrator to configure it.");
				return;
			}
			popup_frame("/static/google/google.png", "Synchronize Staff information from Google", "/dynamic/staff/page/synch_google", null, null, null, function(frame,popup) {
				frame.synch_done = function() { list.reloadData(); };
			});
		};
		list.addHeader(google);
		
		<?php } ?>
		
		list.makeRowsClickable(function(row){
			window.top.popup_frame("/static/people/profile_16.png","Profile","/dynamic/people/page/profile?people="+list.getTableKeyForRow("People",row.row_id),null,95,95);
		});
		layout.changed(list.container);
	}
);
</script>
<?php 
	}
	
}
?>