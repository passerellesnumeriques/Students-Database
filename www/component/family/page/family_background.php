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
		
		$this->requireJavascript("section.js");
?>
<div style=''>
	<div style='float:left;padding:5px;'>
		<div id='parents_family_section' title='Parents and Siblings' css='soft'>
			<div style='padding:5px'>
				<?php $this->buildFamily($people_id, "Child")?>
			</div>
		</div>
	</div>
	<div style='float:left;padding:5px;'>
		<div id='own_family_section' title='Own family' css='soft'>
			<div style='padding:5px'>
				<?php $this->buildFamily($people_id, $people["sex"] == "M" ? "Father" : "Mother")?>
			</div>
		</div>
	</div>
</div>
<script type='text/javascript'>
sectionFromHTML('parents_family_section');
sectionFromHTML('own_family_section');
</script>
<?php 
	}
	
	private function buildFamily($people_id, $member_type) {
		$family_id = SQLQuery::create()->bypassSecurity()
			->select("FamilyMember")
			->whereValue("FamilyMember", "people", $people_id)
			->whereValue("FamilyMember", "member_type", $member_type)
			->field("family")
			->executeSingleValue();
		
		if ($family_id <> null) {
			$family = SQLQuery::create()->bypassSecurity()->select("Family")->whereValue("Family","id",$family_id)->executeSingleRow();
			$members = SQLQuery::create()->bypassSecurity()->select("FamilyMember")->whereValue("FamilyMember","family",$family_id)->execute();
		}
		
		// TODO
		echo "Not yet implemented";
	}
	
}
?>