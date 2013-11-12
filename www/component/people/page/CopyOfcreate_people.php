<?php 
class page_create_people extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		if (!isset($_GET["page"])) {
			$input = json_decode($_POST["input"],true);
			require_once("component/widgets/page/wizard.inc");
			create_wizard_page($this, $input["icon"], $input["title"], "/dynamic/people/page/create_people?page=1&type=".$input["people_type"]);
			?>
			<script type='text/javascript'>
			window.create_people = {
				<?php
				$first = true;
				foreach ($input as $name=>$value) {
					if ($first) $first = false; else echo ",";
					echo $name.": ".json_encode($value);
				}
				?>
			};
			</script>
			<?php
			return;
		}
		$page = intval($_GET["page"]);
		$pages = array();
		require_once("component/people/ProfileGeneralInfoPlugin.inc");
		foreach (PNApplication::$instance->components as $c) {
			if (!($c instanceof ProfileGeneralInfoPlugin)) continue;
			$cpages = $c->get_create_people_pages($_GET["type"]);
			foreach ($cpages as $p) array_push($pages, $p);
		}
		usort($pages, "cmp_create_people_pages");
		include($pages[$page-1][1]);
		?>
		<script type='text/javascript'>
		<?php 
			if ($page>1) {?>
				window.parent.enable_wizard_page_previous(true);
				function wizard_page_go_previous() {
					save_create_people_info(false);
					location.href = '/dynamic/people/page/create_people?page=<?php echo ($page-1);?>&type=<?php echo $_GET["type"];?>';
				}
			<?php }
			if ($page<count($pages)) {?>
				window.parent.enable_wizard_page_next(true);
				function wizard_page_go_next() { 
					if (!save_create_people_info(true)) { 
						window.parent.wizard_unfreeze();
						window.parent.enable_wizard_page_next(true);
						window.parent.enable_wizard_page_previous(<?php echo ($page>1 ? "true" : "false");?>);
						return;
					}
					location.href = '/dynamic/people/page/create_people?page=<?php echo ($page+1);?>&type=<?php echo $_GET["type"];?>';
				}
			<?php } else {?>
				window.parent.enable_wizard_page_finish(true);
				function wizard_page_go_finish() { 
					if (!save_create_people_info(true)) { 
						window.parent.wizard_unfreeze();
						window.parent.enable_wizard_page_finish(true);
						window.parent.enable_wizard_page_previous(<?php echo ($page>1 ? "true" : "false");?>);
						return;
					}
					service.json("people","create_people",window.parent.create_people,function(res) {
						if (!res) {
							window.parent.wizard_unfreeze();
							window.parent.enable_wizard_page_finish(true);
							window.parent.enable_wizard_page_previous(<?php echo ($page>1 ? "true" : "false");?>);
							return;
						}
						var u = new URL(window.parent.create_people.redirect);
						u.params["people_id"] = res.id;
						window.parent.location.href = u.toString();
					});
				}
			<?php }
		?>
		window.parent.document.getElementById('wizard_page_title').innerHTML = window.parent.create_people.title+" - <?php echo $pages[$page-1][0];?>";
		window.parent.wizard_page_loaded();
		</script>
		<?php
	}
	
}

function cmp_create_people_pages($p1,$p2) {
	if ($p1[2] < $p2[2]) return -1;
	if ($p1[2] > $p2[2]) return 1;
	return 0;
}
?>