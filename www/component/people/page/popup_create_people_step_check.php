<?php 
class page_popup_create_people_step_check extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$input = json_decode($_POST["input"], true);
		$peoples = $input["peoples"];
		$sub_models = @$input["sub_models"];
		$multiple = isset($input["multiple"]);
		$first_names = array();
		$last_names = array();
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
					$first_name = strtolower(latinize($first_name));
					$last_name = strtolower(latinize($last_name));
					if (!in_array($first_name, $first_names)) array_push($first_names, $first_name);
					if (!in_array($last_name, $last_names)) array_push($last_names, $last_name);
				}
			}
		}
		$q = SQLQuery::create()->bypassSecurity()->select("People");
		$w="";
		foreach ($first_names as $fn) {
			if ($w <> "") $w .= " OR ";
			$w .= "LOWER(`first_name`) LIKE '%".SQLQuery::escape($fn)."%'";
			for ($i = 0; $i < strlen($fn); $i++) {
				$w .= " OR LOWER(`first_name`) LIKE '%";
				if ($i > 0)
					$w .= SQLQuery::escape(substr($fn,0,$i))."%";
				if ($i < strlen($fn)-1)
					$w .= SQLQuery::escape(substr($fn,$i+1))."%";
				$w .= "'";
			}
		}
		$q->where("(".$w.")");
		$w="";
		foreach ($last_names as $ln) {
			if ($w <> "") $w .= " OR ";
					$w .= "LOWER(`last_name`) LIKE '%".SQLQuery::escape($ln)."%'";
			for ($i = 0; $i < strlen($ln); $i++) {
				$w .= " OR LOWER(`last_name`) LIKE '%";
				if ($i > 0)
					$w .= SQLQuery::escape(substr($ln,0,$i))."%";
				if ($i < strlen($ln)-1)
					$w .= SQLQuery::escape(substr($ln,$i+1))."%";
				$w .= "'";
			}
		}
		$q->where("(".$w.")");
		$matching_peoples = $q->execute();
		for ($i = count($matching_peoples)-1; $i >= 0; $i--) {
			$matching_peoples[$i]["fn"] = strtolower(latinize($matching_peoples[$i]["first_name"]));
			$matching_peoples[$i]["ln"] = strtolower(latinize($matching_peoples[$i]["last_name"]));
		}
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
					$fn = strtolower(latinize($first_name));
					$ln = strtolower(latinize($last_name));
					// search same
					$same = null;
					foreach ($matching_peoples as $mp)
						if ($mp["fn"] == $fn && $mp["ln"] == $ln) { $same = $mp; break; }
					$similars = array();
					if ($same == null) {
						// search similar
						foreach ($matching_peoples as $mp) {
							if (!str_similar($fn, $mp["fn"])) continue;
							if (!str_similar($ln, $mp["ln"])) continue;
							array_push($similars, $mp);
						}
					} 
					if ($same == null && count($similars) == 0)
						array_push($ok, $people);
					else
						array_push($to_check, array($people,$same,$similars));
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
			for ($i = 0; $i < count($to_check); $i++) {
				$people = $to_check[$i][0];
				$same = $to_check[$i][1];
				$similars = $to_check[$i][2];
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
						$li_id = $this->generateID();
						echo "<li id='$li_id'>".$first_name." ".$last_name.":<ul>";
						if ($same <> null)
							$this->similarPeople("seems to be the same as", $same, $path, $table, $li_id, $sub_models);
						foreach ($similars as $similar)
							$this->similarPeople("may be the same as", $similar, $path, $table, $li_id, $sub_models);
						echo "</ul>";
						echo "<a href='#' onclick=\"var li = document.getElementById('$li_id');li.parentNode.removeChild(li);peoples.push(window._new_peoples[$i]);window.oneDone();return false;\">"; // TODO
						echo "This is a new person, I want to create it";
						echo "</a>";
						echo "</li>";
					}
				}
			}
			echo "</ul>";
			echo "</form>";
			echo "</div>";
			?>
			<script type='text/javascript'>
			window._new_peoples = [<?php
			$first = true;
			foreach ($to_check as $tc) {
				if ($first) $first = false; else echo ",";
				echo json_encode($tc[0]);
			} 
			?>];
			window.popup.unfreeze();
			window._to_answer = <?php echo count($to_check);?>;
			window.oneDone = function() {
				if (--window._to_answer == 0) {
					window.popup.freeze("Submitting information...");
					send();
				}
			};
			window.popup.addCancelButton(function() {
				<?php if (isset($input["oncancel"])) echo "window.frameElement.".$input["oncancel"]."();"; ?>
				return true;
			});
			function addTypesToPeople(li_id, types, people_id) {
				var li = document.getElementById(li_id);
				li.parentNode.removeChild(li);
				var next = function(i) {
					if (i == types.length) {
						window.oneDone();
						return;
					}
					window.parent.popup_frame(
						null,'New Person',
						'/dynamic/people/page/people_new_type?people='+people_id+'&ondone=done&oncancel=done&type='+types[i]<?php if ($sub_models <> null) echo "+'&sub_models=".urlencode(json_encode($sub_models))."'";?>,
						null,null,null,
						function(frame,pop){
							frame.done = function() {
								next(i+1);
							};
						}
					);
				};
				next(0);
			}
			</script>
			<?php 
		} else {
			// everything ok, start the creation
			echo "<script type='text/javascript'>send();</script>";
		}
	}
	
	private function similarPeople($msg, $similar, $path, $table, $li_id, $sub_models) {
		$id = $this->generateID();
		echo "<li id='$id'>$msg ";
		echo "<a href='#' class='black_link' title='Click to see the profile' onclick=\"window.top.popup_frame('/static/people/profile_16.png','Profile','/dynamic/people/page/profile?people=".$similar["id"]."',null,95,95);return false;\">";
		echo $table->getRowDescription($similar);
		echo "</a><br/>";
		
		$stypes = PNApplication::$instance->people->parseTypes($similar["types"]);
		$missing_types = PNApplication::$instance->people->parseTypes($path["columns"]["types"]);
		foreach ($stypes as $t) {
			for ($i = 0; $i < count($missing_types); $i++)
				if ($missing_types[$i] == $t) {
					array_splice($missing_types, $i, 1);
					break;
				}
		}
		if (count($missing_types) > 0) {
			$types = "";
			foreach ($missing_types as $t) {
				if ($types <> "") $types .= ",";
				$types .= "'$t'";
			}
			echo "<a href='#' onclick=\"addTypesToPeople('$li_id',[$types],".$similar["id"].");return false;\">";
			echo "Yes, they are the same, make it as ";
			$add_types = "";
			$first = true;
			foreach ($missing_types as $t) {
				$pi = PNApplication::$instance->people->getPeopleTypePlugin($t);
				if ($first) $first = false; else { $add_types .= ","; echo ", "; }
				echo $pi->getName();
				$add_types .= $t;
			}
			echo "</a><br/>";
		} else {
			echo "<a href='#' onclick=\"var li = document.getElementById('$li_id');li.parentNode.removeChild(li);window.oneDone();return false;\">";
			echo "Yes, they are the same, do not create it again";
			echo "</a><br/>";
		}
		echo "<a href='#' onclick=\"var li = document.getElementById('$id');li.parentNode.removeChild(li);return false;\">";
		echo "No they are 2 different persons";
		echo "</a><br/>";
		echo "</li>";
	}
	
}
?>