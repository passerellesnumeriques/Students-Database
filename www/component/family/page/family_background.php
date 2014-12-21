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
				cancel_button.innerHTML = "<img src='"+theme.icons_16.undo+"'/> Cancel changes";
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
				var remove_family = document.createElement("BUTTON");
				remove_family.className = "action red";
				remove_family.innerHTML = "Remove all information";
				if (!family_family.id || family_family.id < 0)
					remove_family.disabled = "disabled";
				sec.addToolBottom(remove_family);
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
						if (!family_family.id || family_family.id < 0)
							remove_family.disabled = "disabled";
						else
							remove_family.disabled = "";
					}); 
				};
				cancel_button.onclick = function() {
					f.cancel();
					save_button.disabled = "disabled";
					cancel_button.disabled = "disabled";
					pnapplication.dataSaved(content.id);
					if (!family_family.id || family_family.id < 0)
						remove_family.disabled = "disabled";
					else
						remove_family.disabled = "";
				};
				remove_family.onclick = function() {
					confirmDialog("Are you sure you want to remove all information about this family ?",function(yes) {
						if (!yes) return;
						var locker = lock_screen(null,"Removing family information...");
						service.json("family","remove_family",{id:family_family.id},function(res) {
							unlock_screen(locker);
							if (!res) return;
							manageFamily(sec,content,{id:-1},[],people_id,can_edit);
						});
					});
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
$family = $this->component->getFamily($people_id, "Child");
echo "manageFamily(parents_section,document.getElementById('parents_family'),".json_encode($family[0]).",".json_encode($family[1]).",".$people_id.",".json_encode($can_edit).");\n";
$family = $this->component->getFamily($people_id, $people["sex"] == "M" ? "Father" : "Mother");
echo "manageFamily(own_family_section,document.getElementById('own_family'),".json_encode($family[0]).",".json_encode($family[1]).",".$people_id.",".json_encode($can_edit).");\n";
?>

window.onuserinactive = function() {
	location.reload();
};
</script>
<?php 
	}
	
}
?>