<?php 
class page_popup_create_people_step_check extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$input = json_decode($_POST["input"], true);
		$peoples = $input["peoples"];
		$sub_models = @$input["sub_models"];
		$multiple = isset($input["multiple"]);
		$ok = array();
		$to_check = array();
		foreach ($peoples as $people) {
			foreach ($people as $path) {
				$i = strrpos($path["path"], "<");
				$j = strrpos($path["path"], ">");
				$p = $i === false && $j === false ? $path["path"] : ($i === false ? substr($path["path"], $j+1) : ($j === false ? substr($path["path"], $i+1) : ($i > $j ? substr($path["path"], $i+1) : substr($path["path"], $j+1))));
				$i = strpos($p, "(");
				if ($i !== false) $p = substr($p, 0, $i);
				if ($p == "People") {
					$first_name = null;
					$last_name = null;
					foreach ($path["value"] as $d)
						if ($d["name"] == "First Name") 
							$first_name = $d["value"];
						else if ($d["name"] == "Last Name") 
							$last_name = $d["value"];
					$similar = SQLQuery::create()->bypassSecurity()
						->select("People")
						->where("LOWER(`first_name`) = '".SQLQuery::escape(strtolower(utf8_decode($first_name)))."'")
						->where("LOWER(`last_name`) = '".SQLQuery::escape(strtolower(utf8_decode($last_name)))."'")
						->execute();
					if (count($similar) == 0)
						array_push($ok, $people);
					else {
						array_push($to_check, array($people,$similar));
					}
					break;
				}
			}
		}
		echo "<script type='text/javascript'>";
		echo "peoples = [";
		$first = true;
		foreach ($ok as $people) {
			if ($first) $first = false; else echo ",";
			echo json_encode($people);
		}
		echo "];\n";
		?>
		window.popup = window.parent.get_popup_window_from_frame(window);
		function send() {
			if (!popup.freezer) popup.freeze();
			window.popup.removeButtons();
			var data = {peoples:peoples};
			data.sub_models = <?php echo json_encode($sub_models);?>;
			<?php if ($multiple) echo "data.multiple = true;";?>
			<?php 
			if (isset($input["ondone"])) echo "data.ondone = ".json_encode($input["ondone"]).";";
			else if (isset($input["donotcreate"])) echo "data.donotcreate = ".json_encode($input["donotcreate"]).";";
			if (isset($input["oncancel"])) echo "data.oncancel = ".json_encode($input["oncancel"]).";";
			?>
			postData("popup_create_people_step_creation",data,window);
		}
		<?php 
		echo "</script>";
		if (count($to_check) > 0) {
			require_once("component/data_model/Model.inc");
			$table = DataModel::get()->getTable("People");
			echo "<div style='background-color:white'>";
			echo "<form name='to_check'>";
			echo "The following people have been found in the database:<ul>";
			$ids = array();
			$peoples_to_include = array();
			foreach ($to_check as $tc) {
				$people = $tc[0];
				$similar = $tc[1];
				foreach ($people as $path) {
					$i = strrpos($path["path"], "<");
					$j = strrpos($path["path"], ">");
					$p = $i === false && $j === false ? $path["path"] : ($i === false ? substr($path["path"], $j+1) : ($j === false ? substr($path["path"], $i+1) : ($i > $j ? substr($path["path"], $i+1) : substr($path["path"], $j+1))));
					$i = strpos($p, "(");
					if ($i !== false) $p = substr($p, 0, $i);
					if ($p == "People") {
						$first_name = null;
						$last_name = null;
						foreach ($path["value"] as $d)
							if ($d["name"] == "First Name")
							$first_name = $d["value"];
						else if ($d["name"] == "Last Name")
							$last_name = $d["value"];
						echo "<li>".$first_name." ".$last_name." seems to be the same as:<ul>";
						$id = $this->generateID();
						foreach ($similar as $s) {
							echo "<li>";
							echo $table->getRowDescription($s);
							$stypes = PNApplication::$instance->people->parseTypes($s["types"]);
							$missing_types = PNApplication::$instance->people->parseTypes($path["columns"]["types"]);
							foreach ($stypes as $t) {
								for ($i = 0; $i < count($missing_types); $i++)
									if ($missing_types[$i] == $t) {
										array_splice($missing_types, $i, 1);
										break;
									}
							}
							// TODO
							/*
							if (count($missing_types) > 0) {
								echo "<br/>";
								echo "<input type='radio' name='$id' value='".$s["id"]."'/> Reuse this person, and make it as ";
								$first = true;
								foreach ($missing_types as $t) {
									$pi = PNApplication::$instance->people->getPeopleTypePlugin($t);
									if ($first) $first = false; else echo ", ";
									echo $pi->getName();
								}
							}
							*/
							echo "</li>";
						}
						array_push($ids, $id);
						array_push($peoples_to_include, $people);
						echo "</ul>";
						echo "<input type='radio' name='$id' value='cancel' checked='checked'/> Cancel creation";
						echo "<br/>";
						echo "<input type='radio' name='$id' value='create'/> Create new people anyway";
						echo "</li>";
					}
				}
			}
			echo "</ul>";
			echo "</form>";
			echo "</div>";
			?>
			<script type='text/javascript'>
			window.popup.unfreeze();
			radios = [<?php
			$first = true;
			foreach ($ids as $id) {
				if ($first) $first = false; else echo ",";
				echo json_encode($id);
			} 
			?>];
			to_include = [<?php
				$first = true;
				foreach ($peoples_to_include as $people) {
					if ($first) $first = false; else echo ",";
					echo json_encode($people);
				} 
			?>];
			window.popup.addIconTextButton(theme.icons_16.ok, "Create", "create", function() {
				window.popup.freeze("Submitting information...");
				var form = document.forms['to_check'];
				for (var i = 0; i < radios.length; ++i) {
					var list = form.elements[radios[i]];
					var value = null;
					for (var j = 0; j < list.length; ++j) if (list[j].checked) { value = list[j].value; break; }
					if (value == 'cancel') continue;
					if (value == 'create') {
						peoples.push(to_include[i]);
					} else {
						// TODO
						//to_include[i].reuse_id = value;
						//peoples.push(to_include[i]);
					}
				}
				send();
			});
			window.popup.addCancelButton(function() {
				<?php if (isset($input["oncancel"])) echo "window.frameElement.".$input["oncancel"]; ?>
			});
			</script>
			<?php 
		} else {
			// everything ok, start the creation
			echo "<script type='text/javascript'>send();</script>";
		}
	}
	
}
?>