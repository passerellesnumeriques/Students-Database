<?php 
class page_popup_new_person extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$this->requireJavascript("form.js");
		$input = isset($_POST["input"]) ? json_decode($_POST["input"],true) : array();
?>
<div style='background-color:white;padding:10px;'>
<form name='form' onsubmit='return false'>

<input type='radio' name='type' value='new_person' checked='checked'/> This is a new person<br/>

<?php
require_once("component/people/PeopleTypePlugin.inc");
$types = array();
foreach (PNApplication::$instance->components as $c) {
	if ($c == $this) continue;
	foreach ($c->getPluginImplementations() as $pi) {
		if (!($pi instanceof PeopleTypePlugin)) continue;
		if ($pi->getId() == $_GET["type"]) continue;
		if (!$pi->canWrite()) continue;
		array_push($types, $pi);
	}
}

foreach ($types as $type) {
	$list = SQLQuery::create()
		->select("People")
		->where("`types` LIKE '%/".$type->getId()."/%'")
		->where("`types` NOT LIKE '%/".$_GET["type"]."/%'")
		->limit(0, 2001) // TODO ?
		->orderBy("People","last_name")
		->orderBy("People","first_name")
		->execute();
	if (count($list) == 0) continue;
	echo "<div style='white-space:nowrap'>";
	echo "<input type='radio' name='type' value='".$type->getId()."'/> ";
	echo "This is an existing ".$type->getName().": ";
	if (count($list) <= 2000) {
		echo "<select name='id_".$type->getId()."'>";
		foreach ($list as $p)
			echo "<option value='".$p["id"]."'>".$p["last_name"]." ".$p["first_name"]."</option>";
		echo "</select>";
	}
	echo "</div>";
}
?>
</form>
</div>
<script type='text/javascript'>
var popup = window.parent.getPopupFromFrame(window);
var data = {
	fixed_columns: <?php echo isset($input["fixed_columns"]) ? json_encode($input["fixed_columns"]) : "[]";?>,
	fixed_data: <?php echo isset($input["fixed_data"]) ? json_encode($input["fixed_data"]) : "[]";?>,
	prefilled_columns: <?php echo isset($input["prefilled_columns"]) ? json_encode($input["prefilled_columns"]) : "[]";?>,
	prefilled_data: <?php echo isset($input["prefilled_data"]) ? json_encode($input["prefilled_data"]) : "[]";?>,
	precreated: <?php echo isset($input["precreated"]) ? json_encode($input["precreated"]) : "[]";?>,
	sub_models: <?php echo isset($input["sub_models"]) ? json_encode($input["sub_models"]) : "null";?>,
};
popup.addOkCancelButtons(function() {
	popup.removeButtons();
	var form = document.forms['form'];
	var type = getRadioValue(form, "type");
	if (type == "new_person") {
		window.frameElement._ondone = function(peoples) {
			var people_id = null;
			for (var i = 0; i < peoples[0].length; ++i)
				if (peoples[0][i].path == "People") { people_id = peoples[0][i].key; break; }
			this.<?php echo $_GET["ondone"];?>(people_id);
		};
		postData("/dynamic/people/page/popup_create_people?types=<?php echo $_GET["type"];?>&multiple=false&ondone=_ondone",data);
		return;
	}
	var people_id = form.elements["id_"+type].value;
	location.href = "/dynamic/people/page/people_new_type?people="+people_id+"&type=<?php echo $_GET["type"];?>&ondone=<?php echo $_GET["ondone"];?>";
});
</script>
<?php 
	}
	
}
?>