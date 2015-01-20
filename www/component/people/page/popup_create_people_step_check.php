<?php 
class page_popup_create_people_step_check extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$input = json_decode($_POST["input"], true);
		$peoples = $input["peoples"];
		$root_table = $input["root_table"];
		$sub_model = $input["sub_model"];
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
					// search same and similars
					$same = array();
					$similars = array();
					foreach ($matching_peoples as $mp) {
						if ($mp["fn"] == $fn && $mp["ln"] == $ln) array_push($same, $mp);
						else {
							if (!str_similar($fn, $mp["fn"])) continue;
							if (!str_similar($ln, $mp["ln"])) continue;
							array_push($similars, $mp);
						}
					}
					if (count($same) == 0 && count($similars) == 0)
						array_push($ok, $people);
					else
						array_push($to_check, array($people,$same,$similars));
					break;
				}
			}
		}
		$this->requireJavascript("datadisplay.js");
		echo "<script type='text/javascript'>";
		echo "peoples = [";
		$first = true;
		foreach ($ok as $people) {
			if ($first) $first = false; else echo ",";
			echo json_encode($people);
		}
		echo "];\n";
		?>
		window.popup = window.parent.getPopupFromFrame(window);
		function send() {
			if (!popup.freezer) popup.freeze();
			window.popup.removeButtons();
			var data = {peoples:peoples};
			data.root_table = <?php echo json_encode($root_table);?>;
			data.sub_model = <?php echo json_encode($sub_model);?>;
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
			echo "<div style='background-color:white;padding:5px;'>";
			echo "<form name='to_check'>";
			echo "The following people have been found in the database:<ul>";
			for ($itc = 0; $itc < count($to_check); $itc++) {
				$people = $to_check[$itc][0];
				$same = $to_check[$itc][1];
				$similars = $to_check[$itc][2];
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
						echo "<li id='$li_id' style='margin-bottom:10px;border-bottom:1px solid black'>".toHTML($first_name." ".$last_name);
						if (count($same) > 0) {
							echo " has exactly the same name as ".count($same)." people we already know";
							if (count($similars) > 0)
								echo ", plus we have ".count($similars)." people who have a similar name";
						} else
							echo ": we found ".count($similars)." people who have a similar name";
						echo "<br/>";
						echo "<table>";
						foreach ($same as $similar)
							$this->similarPeople("Exactly the same name", $similar, $path, $table, $li_id, $sub_models, $itc, true, $people);
						foreach ($similars as $similar)
							$this->similarPeople("Similar name", $similar, $path, $table, $li_id, $sub_models, $itc, false, $people);
						echo "</table>";
						echo "<button style='margin: 4px 2px' onclick=\"var li = document.getElementById('$li_id');li.parentNode.removeChild(li);peoples.push(window._new_peoples[$itc]);window.oneDone();return false;\">";
						echo toHTML($first_name." ".$last_name)." is a new person, I want to create it";
						echo "</button>";
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
				popup.onclose = null;
				return true;
			});
			function addTypesToPeople(li_id, types, people_id,inp,fullname) {
				var li = document.getElementById(li_id);
				li.parentNode.removeChild(li);
				var next = function(i) {
					if (i == types.length) {
						window.oneDone();
						return;
					}
					var data = {prefilled_data:[]};
					for (var j = 0; j < window._new_peoples[inp].length; ++j) {
						var p = window._new_peoples[inp][j];
						var path = new DataPath(p.path);
						var table = path.lastElement().table;
						if (p.value.length > 0)
							for (var k = 0; k < p.value.length; ++k)
								data.prefilled_data.push({table:table,data:p.value[k].name,value:p.value[k].value});
					}
					window.parent.popupFrame(
						null,fullname,
						'/dynamic/people/page/people_new_type?people='+people_id+'&ondone=done&oncancel=done&type='+types[i]<?php if ($sub_models <> null) echo "+'&sub_models=".urlencode(json_encode($sub_models))."'";?>,
						data,null,null,
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
	
	private function similarPeople($msg, $similar, $path, $table, $li_id, $sub_models, $itc, $exactly, $people_data) {
		$this->requireJavascript("profile_picture.js");
		$id = $this->generateID();
		echo "<tr id='$id'".($exactly ? " style='background-color:#E0E0FF;'" : "").">";
		$pic_id = $this->generateID();
		echo "<td id='$pic_id' style='cursor:pointer;vertical-align:top;border-bottom:1px solid #808080' title='Click to see the details of this person' onclick=\"window.top.popupFrame('/static/people/profile_16.png','Profile','/dynamic/people/page/profile?people=".$similar["id"]."',null,95,95);return false;\">";
		$this->onload("new profile_picture('$pic_id',30,30,'center','center').loadPeopleID(".$similar["id"].");");
		echo "</td>"; 
		echo "<td style='cursor:pointer;vertical-align:top;border-bottom:1px solid #808080' title='Click to see the details of this person' onclick=\"window.top.popupFrame('/static/people/profile_16.png','Profile','/dynamic/people/page/profile?people=".$similar["id"]."',null,95,95);return false;\">";
		echo $msg.": <br/>";
		echo $table->getRowDescription($similar);
		echo "</td>";
		echo "<td style='vertical-align:top;border-bottom:1px solid #808080'>";
		
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
				$types .= "\"$t\"";
			}
			echo "They are the same ?<br/>";
			echo "<button onclick='addTypesToPeople(\"$li_id\",[$types],".$similar["id"].",$itc,".toHTMLAttribute($similar["first_name"]." ".$similar["last_name"]).");return false;'>Do not create it again, but make it as ";
			$add_types = "";
			$first = true;
			foreach ($missing_types as $t) {
				$pi = PNApplication::$instance->people->getPeopleTypePlugin($t);
				if ($first) $first = false; else { $add_types .= ","; echo ", "; }
				echo $pi->getName();
				$add_types .= $t;
			}
			echo "</button><br/>";
			echo "<button onclick=\"var li = document.getElementById('$li_id');li.parentNode.removeChild(li);return false;\">Do not create it again, and ignore it</button><br/>";
		} else {
			foreach ($stypes as $t) {
				$pi = PNApplication::$instance->people->getPeopleTypePlugin($t);
				$descr = $pi->canReassignSameType($similar["id"]);
				if ($descr <> null) {
					echo "<script type='text/javascript'>";
					echo "function reassign_$li_id(type) {";
					echo "service.json('people','reassign_type',{people:".$similar["id"].",type:type,data:".json_encode($people_data)."},function(res){});";
					echo "}";
					echo "</script>";
					echo "<button onclick=\"reassign_$li_id('$t');var li = document.getElementById('$li_id');li.parentNode.removeChild(li);window.oneDone();return false;\">Yes they are the same, ".toHTML($descr)."</button><br/>";
				}
			}
			echo "<button onclick=\"var li = document.getElementById('$li_id');li.parentNode.removeChild(li);window.oneDone();return false;\">";
			echo "Yes they are the same, do not create it again";
			echo "</button><br/>";
		}
		echo "<button href='#' onclick=\"var li = document.getElementById('$id');li.parentNode.removeChild(li);return false;\">";
		echo "No they are 2 different persons";
		echo "</button><br/>";
		echo "</td>";
		echo "</tr>";
	}
	
}
?>