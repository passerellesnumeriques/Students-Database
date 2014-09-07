<?php
function template_list(&$page, $type, $root_table, $sub_model, $known_columns) {
	$templates = PNApplication::$instance->data_import->getTemplates($type, $sub_model);
	if (count($templates) == 0) {
		echo "<div style='text-align:center;font-style:italic'>There is no template yet</div>";
	} else {
		echo "<ul>";
		foreach ($templates as $t) {
			echo "<li>";
			echo toHTML($t["name"]);
			echo " (";
			switch ($t["template_type"]) {
				case "multiple": echo "multiple applicants, one by row"; break;
				case "single": echo "single applicant"; break;
			}
			echo ")";
			echo " ";
			echo "<button class='flat icon' title='Import data using this template' onclick='useTemplate(".$t["id"].")'><img src='/static/data_import/import_excel_16.png'/></button>";
			echo " ";
			echo "<button class='flat icon' title='Edit this template' onclick='editTemplate(".$t["id"].")'><img src='".theme::$icons_16["edit"]."'/></button>";
			echo " ";
			echo "<button class='flat icon' title='Remove this template' onclick='removeTemplate(this,".$t["id"].");'><img src='".theme::$icons_16["remove"]."'/></button>";
			echo "</li>";
		}
		echo "</ul>";
?>
<script type='text/javascript'>
var templates = [<?php
if (count($templates) > 0) {
	$multiple_ids = array();
	$single_ids = array();
	foreach ($templates as $t)
		switch ($t["template_type"]) {
		case "multiple": array_push($multiple_ids, $t["id"]); break;
		case "single": array_push($single_ids, $t["id"]); break;
		}
	$first_template = true;
	if (count($multiple_ids) > 0) {
		$data = SQLQuery::create()->bypassSecurity()->select("DataImportTemplateMultiple")->whereIn("DataImportTemplateMultiple","template",$multiple_ids)->execute();
		foreach ($templates as $t) {
			if ($t["template_type"] <> "multiple") continue;
			if ($first_template) $first_template = false; else echo ",";
			echo "{";
			echo "id:".$t["id"];
			echo ",name:".json_encode($t["name"]);
			echo ",type:'multiple'";
			echo ",to_import:[";
			$first_data = true;
			foreach ($data as $d) {
				if ($d["template"] <> $t["id"]) continue;
				if ($first_data) $first_data = false; else echo ",";
				echo "{";
				echo "data:".json_encode($d["data_name"]);
				echo ",path:".json_encode($d["data_path"]);
				if ($d["sub_index"] !== null) echo ",sub_index:".$d["sub_index"];
				if ($d["index"] !== null) echo ",index:".$d["index"];
				if ($d["column"] !== null) {
					echo ",sheet_name:".json_encode($d["sheet"]);
					echo ",column:".$d["column"];
					echo ",row_start:".$d["row_start"];
				} else {
					echo ",value:".$d["value"];
				}
				echo "}";
			}
			echo "]";
			echo "}";
		}
	}
	if (count($single_ids) > 0) {
		// TODO
	}
} 
?>];
function editTemplate(id) {
	var temp = null;
	for (var i = 0; i < templates.length; ++i)
		if (templates[i].id == id) { temp = templates[i]; break; }
	popup.freeze();
	require("edit_template.js",function() {
		new edit_template('container', <?php echo json_encode($type);?>, <?php echo json_encode($root_table);?>, <?php echo json_encode($sub_model);?>, <?php echo $known_columns;?>, temp, function() {
			popup.unfreeze();
		});
	});
}
function removeTemplate(button,id) {
	var temp = null;
	for (var i = 0; i < templates.length; ++i)
		if (templates[i].id == id) { temp = templates[i]; break; }
	confirm_dialog("Are you sure to remove the template "+temp.name+" ?",function(yes) {
		if (!yes) return;
		popup.freeze("Removing template...");
		service.json("data_import","remove_template",{id:id},function(res) {
			popup.unfreeze();
			if (!res) return;
			var li = button.parentNode;
			var ul = li.parentNode;
			ul.removeChild(li);
			layout.invalidate(ul);
		});
	});
}
function useTemplate(id) {
	var temp = null;
	for (var i = 0; i < templates.length; ++i)
		if (templates[i].id == id) { temp = templates[i]; break; }
	// TODO
}
</script>
<?php 
	}
} 
?>