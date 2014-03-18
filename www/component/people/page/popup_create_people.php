<?php 
class page_popup_create_people extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$types = explode(",",$_GET["types"]);
		// check first we can create people with those types
		require_once("component/people/PeopleTypePlugin.inc");
		foreach ($types as $type) {
			$ok = null;
			foreach (PNApplication::$instance->components as $c) {
				foreach ($c->getPluginImplementations() as $pi) {
					if (!($pi instanceof PeopleTypePlugin)) continue;
					if ($pi->getId() <> $type) continue;
					$ok = $pi->canRemove();
					break;
				}
				if ($ok !== null) break;
			}
			if (!$ok) {
				PNApplication::error("You cannot create a people of type ".$type);
				return;
			}
		}
		$input = json_decode($_POST["input"], true);
		
		require_once("component/data_model/page/create_data.inc");
		$values = new DataValues();
		if ($input <> null && isset($input["fixed_columns"]))
			foreach ($input["fixed_columns"] as $v)
				$values->addTableColumnValue($v["table"], $v["column"], $v["value"]);
		if ($input <> null && isset($input["fixed_data"]))
			foreach ($input["fixed_data"] as $v)
				$values->addTableDataValue($v["table"], $v["data"], $v["value"]);
		$prefilled_values = new DataValues();
		if ($input <> null && isset($input["prefilled_data"]))
			foreach ($input["prefilled_data"] as $v)
				$prefilled_values->addTableDataValue($v["table"], $v["data"], $v["value"]);
		
		$types_str = "";
		foreach ($types as $t) $types_str .= "/".$t."/";
		$values->addTableColumnValue("People", "types", $types_str);
		
		$structure_name = createDataPage($this, "People", null, $values, $prefilled_values);
		?>
		<script type='text/javascript'>
		var structure = <?php echo $structure_name;?>;
		var popup = window.parent.get_popup_window_from_frame(window);
		popup.addNextButton(function() {
			popup.freeze("Checking information...");
			var people = [];
			var error = null;
			for (var i = 0; i < structure.length; ++i) {
				var p = {path:structure[i].path};
				error = structure[i].validate();
				if (error != null) break;
				p.value = structure[i].getValue();
				p.columns = typeof structure[i].columns != 'undefined' ? structure[i].columns : [];
				people.push(p);
			}
			if (error != null) {
				alert("Please correct the data before to continue: "+error);
				popup.unfreeze();
				return;
			}
			popup.removeAllButtons();
			var data = {peoples:[people]};
			<?php 
			if (isset($_GET["ondone"])) echo "data.ondone = ".json_encode($_GET["ondone"]).";";
			else if (isset($_GET["donotcreate"])) echo "data.donotcreate = ".json_encode($_GET["donotcreate"]).";";
			?>
			postData("popup_create_people_step_check", data, window);
		});
		popup.addCancelButton();
		</script>
		<?php 
	}
	
}
?>