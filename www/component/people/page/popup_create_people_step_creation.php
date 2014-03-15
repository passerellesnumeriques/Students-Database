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
<div id='info' style='background-color:white'></div>
<script type='text/javascript'>
window.popup.unfreeze();
window.popup.disableClose();
var info = document.getElementById('info');
peoples = [<?php 
$first = true;
foreach ($peoples as $people) {
	if ($first) $first = false; else echo ",";
	echo json_encode($people);
}
?>];
function next(index) {
	if (index == peoples.length) {
		<?php if (isset($input["ondone"])) echo "window.frameElement.".$input["ondone"]."();"?>
		window.popup.close();
		return;
	}
	var p = peoples[index];
	if (!p.reuse_id) {
		var first_name = "";
		var last_name = "";
		for (var i = 0; i < p.length; ++i) {
			var path = new DataPath(p[i].path);
			if (path.table != "People") continue;
			if (typeof p[i].data == 'undefined') continue;
			for (var j = 0; j < p[i].data.length; ++j) {
				if (p[i].data[j].name == "First Name") first_name = p[i].data[j].value;
				else if (p[i].data[j].name == "Last Name") last_name = p[i].data[j].value;
			}
		}
		info.innerHTML = "Creation of "+first_name+" "+last_name;
		if (peoples.length > 1)
			info.innerHTML += " ("+(index+1)+"/"+peoples.length+")";
		layout.invalidate(document.body);
		service.json("data_model","create_data",{root:"People",paths:p},function(res) {
			next(index+1);
		});
	} else {
		// TODO
	}
}
next(0);
</script>
<?php 
	}
	
}
?>