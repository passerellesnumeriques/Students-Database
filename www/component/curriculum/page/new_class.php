<?php 
class page_new_class extends Page {
	
	public function getRequiredRights() { return array("manage_batches"); }
	
	public function execute() {
		$period_id = $_GET["period"];
		$period = SQLQuery::getRow("AcademicPeriod", $period_id);
		$periods_after = SQLQuery::create()->select("AcademicPeriod")->where("batch",$period["batch"])->where("start_date",">",$period["start_date"])->orderBy("AcademicPeriod","start_date")->execute();
		$this->add_javascript("/static/javascript/validation.js");
		?>
		<form name='new_class' onsubmit='return false'>
		<table>
			<tr>
				<td>Class name</td>
				<td><input type='text' maxlength=100 name='name'/><div id='name_validation' class='validation_message'></div></td>
			</tr>
			<tr>
				<td>Specialization</td>
				<td>
					<select name='specialization'>
						<option value='NULL'></option>
						<?php
						$list = SQLQuery::create()->select("Specialization")->execute();
						foreach ($list as $spe)
							echo "<option value='".$spe["id"]."'>".htmlentities($spe["name"])."</option>";
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td>From period</td>
				<td><?php echo htmlentities($period["name"]);?></td>
			</tr>
			<tr>
				<td>To period</td>
				<td>
					<select name='to_period'>
						<?php 
						echo "<option value='".$period["id"]."'>".$period["name"]."</option>";
						foreach ($periods_after as $p)
							echo "<option value='".$p["id"]."'>".$p["name"]."</option>";
						?>
					</select>
				</td>
			</tr>
		</table>
		</form>
		<script type='text/javascript'>
		var existing_classes = [<?php
		$periods_ids = array();
		array_push($periods_ids, $period["id"]);
		foreach ($periods_after as $p) array_push($periods_ids,$p["id"]);
		$classes = SQLQuery::create()->select("AcademicClass")->whereIn("AcademicClass","period",$periods_ids)->execute();
		$first = true;
		foreach ($classes as $c) {
			if ($first) $first = false; else echo ",";
			echo "{name:".json_encode($c["name"]).",period:".$c["period"]."}";
		}
		?>];
		function validate() {
			var form = document.forms['new_class'];
			var to_period = form.elements['to_period'];
			
			var err = false;
			var name = form.elements['name'];
			if (name.value.length == 0) { validation_error(name, "Please enter a name"); err = true; }
			else if (!name.value.checkVisible()) { validation_error(name, "Please enter at least one visible character"); err = true; }
			else {
				var periods = [];
				periods.push(<?php echo $period_id;?>);
				for (var i = 1; i < to_period.selectedIndex; ++i) if (!periods.contains(to_period.options[i].value)) periods.push(to_period.options[i].value);
				for (var i = 0; i < existing_classes.length; ++i) {
					if (!periods.contains(existing_classes[i].period)) continue;
					if (name.value.toLowerCase() == existing_classes[i].name.toLowerCase()) {
						validation_error(name, "This class already exists in the selected period(s)");
						err = true;
						break; 
					}
				}
				if (!err) validation_ok(name);
			}
			window.parent.get_popup_window_from_element(window.frameElement).resize();
			return !err;
		}
		function submit(ondone) {
			var form = document.forms['new_class'];
			var lock = lock_screen();
			service.json("curriculum","new_class",
				{
					name: form.elements['name'].value,
					specialization: form.elements['specialization'].value == "NULL" ? null : form.elements['specialization'].value,
					from_period: <?php echo $period_id;?>,
					to_period: form.elements['to_period'].value
				}, function(res) {
					ondone(res);
				}
			);
		}
		</script>
		<?php 
	}
	
}
?>