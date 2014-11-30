<?php
require_once("component/selection/page/SelectionPage.inc"); 
class page_staff_status extends SelectionPage {
	
	public function getRequiredRights() { return array("can_access_selection_data","consult_staff_list"); }
	
	public function executeSelectionPage() {
		$q = PNApplication::$instance->staff->getCurrentStaffsQuery(true, true, true);
		$q->orderBy("People", "last_name", true);
		$q->orderBy("People", "first_name", true);
		$staffs = $q->execute();
		
		$ids = array();
		foreach ($staffs as $staff) array_push($ids, $staff["people_id"]);
		if (count($ids) > 0)
			$res = SQLQuery::create()->select("StaffStatus")->whereIn("StaffStatus","people",$ids)->execute();
		else
			$res = array();
		$can_do = array();
		foreach ($ids as $id) $can_do[$id] = array();
		foreach ($res as $r) {
			if($r["is"] == 1) array_push($can_do[$r["people"]], "is");
			if($r["exam"] == 1) array_push($can_do[$r["people"]], "exam");
			if($r["interview"] == 1) array_push($can_do[$r["people"]], "interview");
			if($r["si"] == 1) array_push($can_do[$r["people"]], "si");
		}
		
		$can_edit = PNApplication::$instance->user_management->has_right("manage_staff_status");
		
		theme::css($this, "grid.css");
		$this->requireJavascript("profile_picture.js");
?>
<div style="width:100%;height:100%;display:flex;flex-direction:column;">
	<div class="page_title" style="flex:none">
		<img src='/static/staff/staff_32.png'/>
		Staff Status
	</div>
	<div style="flex:1 1 auto;overflow:auto;">
		<table class="grid">
			<thead>
				<tr>
					<th colspan=2>Staff</th>
					<th>Department</th>
					<th>Position</th>
					<th>Can do Information<br/>Sessions</th>
					<th>Can supervise<br/>Written Exams</th>
					<th>Can conduct<br/>Interviews</th>
					<th>Can do Social<br/>Investigations</th>
					<th>Comment</th>
				</tr>
			</thead>
			<tbody>
			<?php
			foreach ($staffs as $staff) {
				echo "<tr>";
				$id = $this->generateID();
				echo "<td id='$id'>";
				$this->onload("new profile_picture(document.getElementById('$id'), 45, 60)".($staff["picture_id"] <> null ? ".loadPeopleStorage(".$staff["people_id"].",".$staff["picture_id"].",".$staff["picture_revision"].");" : ".setNoPicture(".$staff["people_id"].",'".$staff["sex"]."');"));
				echo "</td>";
				echo "<td>";
				echo toHTML($staff["last_name"]." ".$staff["first_name"]);
				echo "</td>";
				echo "<td>";
				echo toHTML($staff["staff_department"]);
				echo "</td>";
				echo "<td>";
				echo toHTML($staff["staff_position"]);
				echo "</td>";
				echo "<td align=center>";
				echo "<input type='checkbox'";
				if (in_array("is", $can_do[$staff["people_id"]])) echo " checked='checked'";
				if ($can_edit)
					echo " onchange=\"setStatus(".$staff["people_id"].",this.checked,'is');\"";
				else
					echo " disabled='disabled'";
				echo "/>";
				echo "</td>";
				echo "<td align=center>";
				echo "<input type='checkbox'";
				if (in_array("exam", $can_do[$staff["people_id"]])) echo " checked='checked'";
				if ($can_edit)
					echo " onchange=\"setStatus(".$staff["people_id"].",this.checked,'exam');\"";
				else
					echo " disabled='disabled'";
				echo "</td>";
				echo "<td align=center>";
				echo "<input type='checkbox'";
				if (in_array("interview", $can_do[$staff["people_id"]])) echo " checked='checked'";
				if ($can_edit)
					echo " onchange=\"setStatus(".$staff["people_id"].",this.checked,'interview');\"";
				else
					echo " disabled='disabled'";
				echo "</td>";
				echo "<td align=center>";
				echo "<input type='checkbox'";
				if (in_array("si", $can_do[$staff["people_id"]])) echo " checked='checked'";
				if ($can_edit)
					echo " onchange=\"setStatus(".$staff["people_id"].",this.checked,'si');\"";
				else
					echo " disabled='disabled'";
				echo "</td>";
				echo "<td valign=top>";
				echo "<textarea style='height:100%;min-width:300px;'";
				if ($can_edit)
					echo " onchange=\"setComment(".$staff["people_id"].",this.value);\"";
				else
					echo " disabled='disabled'";
				echo ">";
				foreach ($res as $r) if ($r["people"] == $staff["people_id"]) { if ($r["comment"] <> null) echo toHTML($r["comment"]); break; }
				echo "</textarea>";
				echo "</td>";
				echo "</tr>";
			} 
			?>
			</tbody>
		</table>
	</div>
	<?php if ($can_edit) { ?>
	<div class='page_footer' style='flex:none'>
		<button class='action' onclick='save();' id='save_button'>
			<img src='<?php echo theme::$icons_16["save"];?>'/>
			Save
		</button>
	</div>
	<?php } ?>
</div>
<?php if ($can_edit) { ?>
<script type='text/javascript'>
pnapplication.autoDisableSaveButton(document.getElementById('save_button'));
var changes = {};
var comments = {};
function setStatus(people_id,status,type) {
	var s = people_id+"_"+type;
	if (typeof changes[s] == 'undefined')
		changes[s] = {staff:people_id,type:type,status:status};
	else
		delete changes[s];
	var has_change = false;
	for (var n in changes) { has_change = true; break; }
	if (has_change) pnapplication.dataUnsaved('staff_status');
	else pnapplication.dataSaved('staff_status');
}
function setComment(people_id,comment) {
	if (typeof comments[people_id] == 'undefined')
		comments[people_id] = comment;
	else
		comments[people_id] = comment;
	pnapplication.dataUnsaved('staff_comment');
}
function save() {
	var staffs = [];
	for (var n in changes) {
		var c = changes[n];
		var s = null;
		for (var i = 0; i < staffs.length; ++i) if (staffs[i].people == c.staff) { s = staffs[i]; break; }
		if (s == null) {
			s = {people:c.staff};
			staffs.push(s);
		}
		s[c.type] = c.status;
	}
	for (var id in comments) {
		var s = null;
		for (var i = 0; i < staffs.length; ++i) if (staffs[i].people == id) { s = staffs[i]; break; }
		if (s == null) {
			s = {people:id};
			staffs.push(s);
		}
		s['comment'] = comments[id];
	}
	var locker = lock_screen(null, "Saving Staff Status...");
	service.json("selection","staff/set_status",{staffs:staffs},function(res) {
		if (res) {
			pnapplication.dataSaved('staff_status');
			pnapplication.dataSaved('staff_comment');
			changes = {};
			comments = {};
		}
		unlock_screen(locker);
	});
}
</script>
<?php } ?>
<?php 
	}
	
}
?>