<?php 
function import_data(&$page, $icon, $title, $data_list, $fixed_data, $prefilled_data, $create_button, $create_function) {
	
	$page_id = $page->generate_id();
	echo "<div id='$page_id' style='width:100%;height:100%'>";
		
	$header_id = $page->generate_id();
	echo "<div id='$header_id' icon='$icon' title='$title' layout='fixed'></div>";
	$page->add_javascript("/static/widgets/page_header.js");
	
	$container_id = $page->generate_id();
	echo "<div layout='fill' id='$container_id' style='overflow:hidden'></div>";
	$page->add_javascript("/static/data_import/custom_import.js");
	
	echo "</div>";
	
	$page->add_javascript("/static/data_model/DataDisplay.js");
	?>
	<script type="text/javascript">
	require("vertical_layout.js",function() {
		new vertical_layout('<?php echo $page_id;?>');
	});
	var header = new page_header('<?php echo $header_id;?>');
	new custom_import('<?php echo $container_id;?>',function(imp) {
		var upload_excel = document.createElement("DIV");
		upload_excel.className = "button";
		upload_excel.innerHTML = "<img src='/static/data_import/import_excel_16.png'/> Upload Excel File";
		upload_excel.onclick = function() {
			imp.upload_excel(function(async){
				var win = getIFrameWindow(imp.excel_frame);
				if (win.excel) win.excel = null;
				if (async) {
					upload_excel.lock = lock_screen(null,"<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Uploading Excel file...");
				}
			}, function(async, pc){
				if (async && pc != -1) {
					set_lock_screen_content(upload_excel.lock,"<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Uploading Excel file... "+pc+"%");
					return;
				}
				var status = 1;
				var lock;
				if (async) {
					lock = upload_excel.lock;
					set_lock_screen_content(upload_excel.lock,"<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Analyzing Excel file... ");
				} else
					lock = lock_screen(null,"<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Uploading Excel file...");
				var check = function() {
					var win = getIFrameWindow(imp.excel_frame);
					if (!win.excel || !win.excel.tabs) {
						if (win.page_errors) {
							unlock_screen(lock);
							return;
						}
						if (win.excel_uploaded) {
							if (status == 1) {
								status = 2;
								set_lock_screen_content(lock, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Preparing Excel...");
							}
						}
						setTimeout(check,25);
						return;
					}
					unlock_screen(lock);
				};
				check();
			});
		};
		header.addMenuItem(upload_excel);
		var reset_all = document.createElement("DIV");
		reset_all.className = "button";
		reset_all.innerHTML = "<img src='"+theme.icons_16.remove+"'/> Remove all";
		reset_all.onclick = function() {
			imp.removeAll();
		};
		header.addMenuItem(reset_all);
		var create = document.createElement("DIV");
		create.className = "button";
		create.innerHTML = <?php echo json_encode($create_button);?>;
		create.onclick = function() {
			var lock = lock_screen();
			// check there is something to import
			var nb_rows = imp.grid.getNbRows();
			if (nb_rows == 0) {
				unlock_screen(lock);
				error_dialog("You didn't import any data");
				return;
			}
			// check there is no error in any field, and retrieve data
			var data = [];
			for (var row = 0; row < nb_rows; row++) {
				var row_data = [];
				for (var col = 0; col < imp.grid.getNbColumns(); ++col) {
					var field = imp.grid.getCellField(row, col);
					if (typeof imp.grid.columns[col].attached_data == 'string') continue;
					if (field.hasError()) {
						unlock_screen(lock);
						error_dialog("You have an error on row "+(row+1)+": "+imp.grid.columns[col].attached_data.name+": "+field.getError());
						field.getHTMLElement().focus();
						return;
					}
					var cell = {data:imp.grid.columns[col].attached_data,value:field.getCurrentData()};
					row_data.push(cell);
				}
				data.push(row_data);
			}
			// ok: call the creation
			<?php echo $create_function;?>(data, lock);
		};
		header.addMenuItem(create);
		fireLayoutEventFor(document.getElementById('<?php echo $page_id;?>'));
		<?php 
		foreach ($data_list as $data) {
			echo "imp.add_data(".$data->javascriptDataDisplay(null);
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