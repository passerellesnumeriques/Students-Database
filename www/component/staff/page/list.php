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
var list = new data_list(
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
	function (list) {
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
					frame.reload_list = reload_list;
					p.show();
				});
			};
			list.addHeader(create_staff);
		<?php } ?>
		<?php } ?>
		
		list.makeRowsClickable(function(row){
			window.top.popup_frame("/static/people/profile_16.png","Profile","/dynamic/people/page/profile?people="+list.getTableKeyForRow("People",row.row_id),null,95,95);
		});
		layout.invalidate(list.container);
	}
);

function reload_list() {
	list.reloadData();
}

</script>
<?php 
	}
	
}
?>