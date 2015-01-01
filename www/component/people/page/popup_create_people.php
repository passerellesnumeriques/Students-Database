<?php 
class page_popup_create_people extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		if (isset($_GET["oncancel"])) {
			?>
			<script type='text/javascript'>
			var popup = window.top.getPopupFromFrame(window);
			popup.onclose = function() {
				window.frameElement.<?php echo $_GET["oncancel"];?>();
			};
			</script>
			<?php
		}
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
		if ($input <> null && isset($input["precreated"]))
			$precreated = $input["precreated"];
		else 
			$precreated = array();
		
		echo "<script type='text/javascript'>";
		echo "var data = {";
		echo "fixed_columns:".json_encode($fixed_columns);
		echo ",fixed_data:".json_encode($fixed_data);
		echo ",prefilled_columns:".json_encode($prefilled_columns);
		echo ",prefilled_data:".json_encode($prefilled_data);
		echo ",precreated:".json_encode($precreated);
		if (isset($_GET["root_table"])) {
			echo ",root_table:".json_encode($_GET["root_table"]);
			if (isset($_GET["sub_model"])) echo ",sub_model:".json_encode($_GET["sub_model"]);
		}
		echo ",sub_models:".json_encode(@$input["sub_models"]);
		if (isset($_GET["ondone"])) echo ",ondone:".json_encode($_GET["ondone"]);
		if (isset($_GET["donotcreate"])) echo ",donotcreate:".json_encode($_GET["donotcreate"]);
		if (isset($_GET["oncancel"])) echo ",oncancel:".json_encode($_GET["oncancel"]);
		echo "};";
		echo "function go() {";
		echo "postData('popup_create_people_step_entry',data,window);";
		echo "}";
		if (isset($_GET["multiple"])) {
			if ($_GET["multiple"] == "true") echo "data.multiple = true; go();";
			else echo "data.multiple = false; go();";
		}
		echo "</script>";
		if (!isset($_GET["multiple"])) {
		?>
		<div class='page_title'>
			Create <?php echo toHTML($types_descr)?>
		</div>
		<div style='padding:10px;background-color:white'>
		<button class='big' onclick='go();return false;'>
			<img src='<?php echo theme::make_icon("/static/people/one_people_64.png", theme::$icons_16["add"]);?>'/><br/>
			Create a new one
		</button>
		<button class='big' onclick='data.multiple = true; go();return false;'>
			<img src='<?php echo theme::make_icon("/static/people/several_people_64.png", theme::$icons_16["add"]);?>'/><br/>
			Create several at once
		</button>
		<?php if (count($types) == 1 && !isset($_GET["not_from_existing"])) { ?>
		<div class='page_section_title'>This is an existing person</div>
		<div style='padding-left:30px'>
		<?php
		require_once("component/people/PeopleTypePlugin.inc");
		$possible_types = array();
		foreach (PNApplication::$instance->components as $c) {
			if ($c == $this) continue;
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeopleTypePlugin)) continue;
				if (in_array($pi->getId(), $types)) continue;
				if (!$pi->canWrite()) continue;
				array_push($possible_types, $pi);
			}
		}
		
		$this->requireJavascript("people_search.js");
		$this->requireJavascript("custom_search.js");
		foreach ($possible_types as $type) {
			$list = SQLQuery::create()->select("People")->where("`types` LIKE '%/".$type->getId()."/%'")->limit(0, 501)->orderBy("People","last_name")->orderBy("People","first_name")->execute();
			if (count($list) == 0) continue;
			echo "<div style='display:inline-block;text-align:center'>";
			echo "<div class='big_button_style' style='cursor:default;display:block'>";
			echo "<span style='font-weight:bold'>".toHTML($type->getName())."</span><br/>";
			echo "<img src='".$type->getIcon32()."'/><br/>";
			echo "<div style='height:20px'>";
			$select_id = $this->generateID();
			if (count($list) <= 500) {
				echo "<select id='$select_id'>";
				foreach ($list as $p)
					echo "<option value='".$p["id"]."'>".$p["last_name"]." ".$p["first_name"]."</option>";
				echo "</select>";
			} else {
				echo "<i>More than 500</i>";
			}
			echo "</div>";
			$search_id = $this->generateID();
			echo "<div id='$search_id'></div>";
			?>
			<script type='text/javascript'>
			var search<?php echo $type->getId();?> = new people_search('<?php echo $search_id;?>','<?php echo $type->getId();?>',function(people){
				var select = document.getElementById('<?php echo $select_id;?>');
				if (select != null) select.value = people.id;
			});
			function createPeople<?php echo $type->getId()?>() {
				var select = document.getElementById('<?php echo $select_id;?>');
				var people_id = null;
				if (select != null) people_id = select.value;
				else {
					var people = search<?php echo $type->getId();?>.getSelectedPeople();
					if (people) people_id = people.id;
				}
				if (!people_id) { alert('Please select someone'); return; }
				postData('/dynamic/people/page/people_new_type?people='+people_id+'&type=<?php echo $types[0].(isset($_GET["ondone"]) ? "&ondone=".$_GET["ondone"] : "");?>',data,window);
			}
			</script>
			<?php 
			echo "</div>";
			echo "<button onclick=\"createPeople".$type->getId()."();\">Create as ".toHTML($types_descr)."</button>";
			echo "</div>";
		}
		?>
		</div>
		<?php } ?>
		</div>
		<?php
		}
	}
	
}
?>