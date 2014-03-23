<?php 
class page_popup_create_people_step_creation extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$input = json_decode($_POST["input"], true);
		$peoples = $input["peoples"];
		echo "<script type='text/javascript'>window.popup = window.parent.get_popup_window_from_frame(window);</script>";
		if (count($peoples) == 0) {
			echo "<div style='background-color:white'>Nobody to create.</div>";
			echo "<script type='text/javascript'>popup.unfreeze();popup.addCancelButton();</script>";
		}
		$this->add_javascript("/static/data_model/DataDisplay.js");
?>
<script type='text/javascript'>
peoples = [<?php 
$first = true;
foreach ($peoples as $people) {
	if ($first) $first = false; else echo ",";
	echo json_encode($people);
}
?>];
<?php if (isset($input["donotcreate"])) {
	echo "window.frameElement.".$input["donotcreate"]."(peoples);";
} else { ?>
function next(index) {
	if (index == peoples.length) {
		<?php if (isset($input["ondone"])) echo "window.frameElement.".$input["ondone"]."(peoples);"?>
		window.popup.close();
		return;
	}
	var p = peoples[index];
	if (!p.reuse_id) {
		var first_name = "";
		var last_name = "";
		for (var i = 0; i < p.length; ++i) {
			var path = new DataPath(p[i].path);
			if (path.lastElement().table != "People") continue;
			for (var j = 0; j < p[i].value.length; ++j) {
				if (p[i].value[j].name == "First Name") first_name = p[i].value[j].value;
				else if (p[i].value[j].name == "Last Name") last_name = p[i].value[j].value;
			}
		}
		var msg = "Creation of "+first_name+" "+last_name;
		if (peoples.length > 1)
			msg += " ("+(index+1)+"/"+peoples.length+")";
		popup.set_freeze_content(msg);
		service.json("data_model","create_data",{root:"People",paths:p},function(res) {
			if (res && res.key) {
				for (var i = 0; i < p.length; ++i)
					if (p[i].path == "People") { p[i].key = res.key; break; }
			}
			next(index+1);
		});
	} else {
		// TODO
	}
}
next(0);
<?php } ?>
</script>
<?php 
	}
	
}
?>