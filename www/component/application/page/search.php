<?php 
class page_search extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$q = $_GET["q"];
		require_once("component/data_model/Model.inc");
		$to_search = array();
		$categories = array();
		foreach (DataModel::get()->getTables() as $table) {
			$disp = $table->getDisplayableData();
			if (count($disp) == 0) continue; // nothing displayable
			if ($table->getModel() instanceof SubDataModel) {
				foreach ($table->getModel()->getExistingInstances() as $sm) {
					foreach ($disp as $field=>$d) {
						if (!isset($categories[$d[0]]))
							$categories[$d[0]] = array();
						array_push($categories[$d[0]], $d[1]);
						array_push($to_search, array($table->getName(), $field, $sm, $d[0], $d[1]));
					}
				}
			} else {
				foreach ($disp as $field=>$d) {
					if (!isset($categories[$d[0]]))
						$categories[$d[0]] = array();
					array_push($categories[$d[0]], $d[1]);
					array_push($to_search, array($table->getName(), $field, null, $d[0], $d[1]));
				}
			}
		}
		$this->add_javascript("/static/widgets/collapsable_section/collapsable_section.js");
		$categories_ids = array();
		$fields_ids = array();
		foreach ($categories as $cat_name=>$cat) {
			$id = $this->generate_id();
			$categories_ids[$cat_name] = $id;
			echo "<div class='collapsable_section' id='".$id."' style='visibility:hidden;position:absolute;margin:5px'>";
			echo "<div class='collapsable_section_header' style='padding:2px'>".$cat_name."</div>";
			echo "<div class='collapsable_section_content'>";
			foreach ($cat as $name) {
				$id = $this->generate_id();
				$fields_ids[$cat_name."/".$name] = $id;
				echo "<div class='collapsable_section' id='".$id."' style='visibility:hidden;position:absolute;margin:5px'>";
				echo "<div class='collapsable_section_header' style='padding:2px'>".$name."</div>";
				echo "<div class='collapsable_section_content' id='".$id."_result' style='padding:5px'>";
				echo "</div>";
				echo "</div>";
			}
			echo "</div>";
			echo "</div>";
		}
		echo "<script type='text/javascript'>";
		echo "var search_lock = lock_screen(null,\"<img src='' style='vertical-align:bottom'/> Searching ".htmlentities($q, ENT_QUOTES, "UTF-8")."...\");";
		echo "var nb_searches = ".count($to_search).";\n";
		foreach ($to_search as $ts) {
			echo "service.json('application','search',{table:".json_encode($ts[0]).",column:".json_encode($ts[1]).",sub_model:".json_encode($ts[2]).",q:".json_encode($q)."},function(result){\n";
			echo "if (--nb_searches == 0) unlock_screen(search_lock);\n";
			echo "if (!result || result.length == 0) return;\n";
			// there are results: show the category and field
			$category_id = $categories_ids[$ts[3]];
			$field_id = $fields_ids[$ts[3]."/".$ts[4]];
			echo "var e = document.getElementById('".$category_id."');";
			echo "e.style.visibility = 'visible';";
			echo "e.style.position = 'static';";
			echo "e = document.getElementById('".$field_id."');";
			echo "e.style.visibility = 'visible';";
			echo "e.style.position = 'static';";
			// fill with data
			echo "\ne = document.getElementById('".$field_id."_result');";
			?>
			for (var i = 0; i < result.length; ++i) {
				var div = document.createElement("DIV");
				div.appendChild(document.createTextNode(result[i].value));
				if (result[i].links.length > 0) {
					for (var j = 0; j < result[i].links.length; ++j) {
						var a = document.createElement("A");
						a.style.paddingLeft = '3px';
						a.href = result[i].links[j].url;
						a.innerHTML = "<img src='"+result[i].links[j].icon+"' style='border:none;'/>";
						div.appendChild(a);
					}
				}
				e.appendChild(div);
			}
			<?php
			echo "});";
		}
		echo "</script>";
	}
	
}
?>