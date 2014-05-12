<?php 
class page_popup_create_people extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$types = explode(",",$_GET["types"]);
		$types_descr = "";
		// check first we can create people with those types
		require_once("component/people/PeopleTypePlugin.inc");
		foreach ($types as $type) {
			$ok = null;
			foreach (PNApplication::$instance->components as $c) {
				foreach ($c->getPluginImplementations() as $pi) {
					if (!($pi instanceof PeopleTypePlugin)) continue;
					if ($pi->getId() <> $type) continue;
					$ok = $pi->canRemove();
					if ($types_descr <> "") $types_descr .= ", ";
					$types_descr .= $pi->getName();
					break;
				}
				if ($ok !== null) break;
			}
			if (!$ok) {
				PNApplication::error("You cannot create a people of type ".$type);
				return;
			}
		}
		if (isset($_POST["input"])) {
			$input = json_decode($_POST["input"], true);
			foreach ($_GET as $n=>$v) if (!isset($input[$n])) $input[$n] = $v;
		} else
			$input = $_GET;
		
		$types_str = "";
		foreach ($types as $t) $types_str .= "/".$t."/";

		if ($input <> null && isset($input["fixed_columns"]))
			$fixed_columns = $input["fixed_columns"];
		else
			$fixed_columns = array();
		array_push($fixed_columns, array("table"=>"People", "column"=>"types", "value"=>$types_str));
		if ($input <> null && isset($input["fixed_data"]))
			$fixed_data = $input["fixed_data"];
		else
			$fixed_data = array();
		if ($input <> null && isset($input["prefilled_columns"]))
			$prefilled_columns = $input["prefilled_columns"];
		else
			$prefilled_columns = array();
		if ($input <> null && isset($input["prefilled_data"]))
			$prefilled_data = $input["prefilled_data"];
		else
			$prefilled_data = array();
		
		echo "<script type='text/javascript'>";
		echo "var data = {";
		echo "fixed_columns:".json_encode($fixed_columns);
		echo ",fixed_data:".json_encode($fixed_data);
		echo ",prefilled_columns:".json_encode($prefilled_columns);
		echo ",prefilled_data:".json_encode($prefilled_data);
		echo ",sub_models:".json_encode(@$input["sub_models"]);
		if (isset($_GET["ondone"])) echo ",ondone:".json_encode($_GET["ondone"]);
		if (isset($_GET["donotcreate"])) echo ",donotcreate:".json_encode($_GET["donotcreate"]);
		echo "};";
		echo "function go() {";
		echo "postData('popup_create_people_step_entry',data,window);";
		echo "}";
		if (isset($_GET["multiple"])) {
			if ($_GET["multiple"] == "true") echo "data.multiple = true; go();";
			else echo "data.multiple = false; go();";
		}
		echo "</script>";
		echo "<div style='padding:10px;background-color:white'>";
		echo "Create ".$types_descr.":<br/>";
		echo " &nbsp; <a href='#' onclick='go();return false;'>Create only one</a><br/>";
		echo " &nbsp; <a href='#' onclick='data.multiple = true; go();return false;'>Create several together</a><br/>";
		echo "</div>";
	}
	
}
?>