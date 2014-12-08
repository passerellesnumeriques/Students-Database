<?php 
class page_popup_create_people_step_end extends Page {
	
	public function getRequiredRights() { return array() ; }
	
	public function execute() {
		$input = json_decode($_POST["input"], true);
		$peoples_ids = @$input["peoples_ids"];
		$peoples = @$input["peoples"];
		$ondone = @$input["ondone"];
		$step = @$input["step"];
		$steps = @$input["steps"];
		
		if ($step === null) {
			// first call, initialization: list all available steps, and which step is applicable to which peoples
			require_once("component/people/PeopleCreationStepPlugin.inc");
			$steps = array();
			foreach (PNApplication::$instance->components as $c)
				foreach ($c->getPluginImplementations() as $pi)
					if ($pi instanceof PeopleCreationStep)
						array_push($steps, array("plugin"=>$pi, "peoples_ids"=>array()));
			$list = PNApplication::$instance->people->getPeoples($peoples_ids, false, false, false, true);
			foreach ($list as $p) {
				for ($i = 0; $i < count($steps); $i++) {
					if ($steps[$i]["plugin"]->isApplicable($p))
						array_push($steps[$i]["peoples_ids"], $p["people_id"]);
				}
			}
			usort($steps, function($s1, $s2) {
				return $s1["plugin"]->getPriority()-$s2["plugin"]->getPriority();
			});
			for ($i = 0; $i < count($steps); $i++)
				if (count($steps[$i]["peoples_ids"]) == 0) {
					array_splice($steps, $i, 1);
					$i--;
				} else {
					$steps[$i]["plugin"] = $steps[$i]["plugin"]->getId();
				}
			echo "<script type='text/javascript'>";
			echo "postData(location.href,{steps:".json_encode($steps).($ondone <> null ? ",ondone:".json_encode($ondone).",peoples:".json_encode($peoples) : "").",step:0},window);";
			echo "</script>";
			return;
		}
		if ($step == count($steps)) {
			// final call, we are done: just call the handler ondone if needed, and close the popup
?>
<script type='text/javascript'>
var popup = window.parent.get_popup_window_from_frame(window);
popup.onclose = null;
<?php if ($ondone <> null) echo "window.frameElement.".$ondone."(".json_encode($peoples).");"?>
popup.close();
</script>
<?php 
			return;
		}
		$plugin_id = $steps[$step]["plugin"];
		$peoples_ids = $steps[$step]["peoples_ids"];
		require_once("component/people/PeopleCreationStepPlugin.inc");
		$plugin = null;
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi)
				if ($pi instanceof PeopleCreationStep)
					if ($pi->getId() == $plugin_id) { $plugin = $pi; break; }
			if ($plugin <> null) break;
		}
?>
<script type='text/javascript'>
window.popup = window.parent.get_popup_window_from_frame(window);
window.stepDone = function() {
	window.popup.removeButtons();
	postData(location.href,{step:<?php echo $step+1;?>,steps:<?php echo json_encode($steps); if ($ondone <> null) echo ",ondone:".json_encode($ondone).",peoples:".json_encode($peoples);?>},window);
}
</script>
<?php 
		$plugin->generatePageFor($peoples_ids, $this);
	}
	
}
?>