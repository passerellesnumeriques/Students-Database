<?php 
function import_data(&$page, $icon, $title, $data_list, $fixed_data, $prefilled_data, $import_button_text, $import_function) {

	$container_id = $page->generateID();
	echo "<div id='$container_id' style='width:100%;height:100%'></div>";
	
	$page->addJavascript("/static/data_import/custom_import.js");
	$page->addJavascript("/static/data_model/DataDisplay.js");
	?>
	<script type="text/javascript">
	var assist;
	var imp = new custom_import(<?php echo "'$container_id',".json_encode($icon).",".json_encode($title).",".json_encode($import_button_text).",$import_function";?>,function(imp) {
		<?php 
		foreach ($data_list as $data) {
			echo "imp.addData(".$data->javascriptDataDisplay(null);
			$found = false;
			foreach ($fixed_data as $fd)
				if ($fd["category"] == $data->handler->category && $fd["name"] == $data->getDisplayName()) {
					$found = true;
					echo ",".json_encode($fd["data"]);
				}
			if (!$found) echo ",null";
			$found = false;
			foreach ($prefilled_data as $fd)
				if ($fd["category"] == $data->handler->category && $fd["name"] == $data->getDisplayName()) {
					$found = true;
					echo ",".json_encode($fd["data"]);
				}
			if (!$found) echo ",null";
			echo ");\n";
		}
		?>
	});
	</script>
	<?php 
		
}
?>