<?php 
class page_family_background extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$people_id = $_GET["people"];
		
		try {
			$people = PNApplication::$instance->people->getPeople($people_id);
		} catch (Exception $e) {
			PNApplication::error("Access denied.");
			return;
		}
		// TODO more security check ?
		
		$can_edit = PNApplication::$instance->people->canModify($people_id);
		
		$this->requireJavascript("section.js");
		$this->requireJavascript("family.js");
?>
<div style=''>
	<div style='float:left;padding:5px;'>
		<div id='parents_family_section' title='Parents and Siblings' css='soft'>
			<div style='padding:5px' id='parents_family'>
			</div>
		</div>
	</div>
	<div style='float:left;padding:5px;'>
		<div id='own_family_section' title='Own family' css='soft'>
			<div style='padding:5px' id='own_family'>
			</div>
		</div>
	</div>
</div>
<script type='text/javascript'>
parents_section = sectionFromHTML('parents_family_section');
own_family_section = sectionFromHTML('own_family_section');

function manageFamily(sec, content, fam, members, people_id, can_edit) {
	var family_family = fam;
	var family_members = members;
	sec.resetToolBottom();
	new family(content, fam, members, people_id, false);
	if (can_edit) {
		var edit_button = document.createElement("BUTTON");
		edit_button.className = "action";
		edit_button.innerHTML = "<img src='"+theme.icons_16.edit+"'/> Edit";
		edit_button.onclick = function() {
			var edit_mode = function(lock_id) {
				databaselock.addLock(lock_id);
				var f = new family(content, family_family, family_members, people_id, true);
				edit_button.parentNode.removeChild(edit_button);
				var save_button = document.createElement("BUTTON");
				save_button.className = "action";
				save_button.innerHTML = "<img src='"+theme.icons_16.save+"'/> Save";
				save_button.disabled = "disabled";
				sec.addToolBottom(save_button);
				var cancel_button = document.createElement("BUTTON");
				cancel_button.innerHTML = "<img src='"+theme.icons_16.cancel+"'/> Cancel changes";
				cancel_button.disabled = "disabled";
				cancel_button.className = "action";
				sec.addToolBottom(cancel_button);
				f.onchange = function() {
					save_button.disabled = "";
					cancel_button.disabled = "";
					pnapplication.dataUnsaved(content.id);
				};
				var cancel_edit = document.createElement("BUTTON");
				cancel_edit.className = "action";
				cancel_edit.innerHTML = "<img src='"+theme.icons_16.no_edit+"'/> Cancel Editing";
				sec.addToolBottom(cancel_edit);
				cancel_edit.onclick = function() {
					var locker = lock_screen();
					databaselock.unlock(lock_id,function() {
						pnapplication.dataSaved(content.id);
						manageFamily(sec,content,family_family,family_members,people_id,can_edit);
						unlock_screen(locker);
					});
				};
				save_button.onclick = function() {
					f.save(function() {
						save_button.disabled = "disabled";
						cancel_button.disabled = "disabled";
						pnapplication.dataSaved(content.id);
						family_family = f.family;
						family_members = f.members;
					}); 
				};
				cancel_button.onclick = function() {
					f.cancel();
					save_button.disabled = "disabled";
					cancel_button.disabled = "disabled";
					pnapplication.dataSaved(content.id);
				};
			};
			var locker = lock_screen();
			if (family.id > 0) {
				service.json("data_model","lock_row",{table:"Family",row_key:family.id},function(res) {
					unlock_screen(locker);
					if (res && res.lock) edit_mode(res.lock);
				});
			} else {
				service.json("data_model","lock_row",{table:"People",row_key:people_id},function(res) {
					unlock_screen(locker);
					if (res && res.lock) edit_mode(res.lock);
				});
			}
		};
		sec.addToolBottom(edit_button);
	}
}

<?php 
$family = $this->getFamily($people_id, "Child");
echo "manageFamily(parents_section,document.getElementById('parents_family'),".json_encode($family[0]).",".json_encode($family[1]).",".$people_id.",".json_encode($can_edit).");\n";
$family = $this->getFamily($people_id, $people["sex"] == "M" ? "Father" : "Mother");
echo "manageFamily(own_family_section,document.getElementById('own_family'),".json_encode($family[0]).",".json_encode($family[1]).",".$people_id.",".json_encode($can_edit).");\n";
?>

window.onuserinactive = function() {
	location.reload();
};
</script>
<?php 
	}
	
	private function getFamily($people_id, $member_type) {
		$family_id = SQLQuery::create()->bypassSecurity()
			->select("FamilyMember")
			->whereValue("FamilyMember", "people", $people_id)
			->whereValue("FamilyMember", "member_type", $member_type)
			->field("family")
			->executeSingleValue();
		
		if ($family_id <> null) {
			$family = SQLQuery::create()->bypassSecurity()->select("Family")->whereValue("Family","id",$family_id)->executeSingleRow();
			$members = SQLQuery::create()->bypassSecurity()->select("FamilyMember")->whereValue("FamilyMember","family",$family_id)->execute();
		} else {
			$family = array("id"=>-1);
			$members = array(array("family"=>-1,"id"=>-1,"people"=>$people_id,"member_type"=>$member_type));
		}
		
		$peoples_ids = array();
		foreach ($members as $m) array_push($peoples_ids, $m["people"]);
		if (count($peoples_ids) > 0) {
			$q = PNApplication::$instance->people->getPeoplesSQLQuery($peoples_ids);
			require_once("component/people/PeopleJSON.inc");
			PeopleJSON::PeopleSQL($q, false);
			$peoples = $q->execute();
		} else
			$peoples = array();
		
		for ($i = 0; $i < count($members); $i++)
			foreach ($peoples as $p) if ($p["people_id"] == $members[$i]["people"]) { $members[$i]["people"] = $p; break; }
		
		return array($family,$members);
	}
	
}
?>