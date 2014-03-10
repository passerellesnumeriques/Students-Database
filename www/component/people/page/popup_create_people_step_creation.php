<?php 
class page_popup_create_people_step_creation extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$peoples = json_decode($_POST["input"], true);
		$peoples = $peoples["peoples"];
		echo "<script type='text/javascript'>window.popup = window.parent.get_popup_window_from_frame(window);</script>";
		if (count($peoples) == 0) {
			echo "<div>Nobody to create.</div>";
			echo "<script tyupe='text/javascript'>popup.unfreeze();popup.addCancelButton();</script>";
		}
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
		window.popup.enableClose();
		// TODO
		return;
	}
	var p = peoples[index];
	if (!p.reuse_id) {
		var first_name = "";
		var last_name = "";
		for (var i = 0; i < p.tables.People.length; ++i)
			if (p.tables.People[i].name == "First Name") first_name = p.tables.People[i].value;
			else if (p.tables.People[i].name == "Last Name") last_name = p.tables.People[i].value;
		info.innerHTML = "Creation of "+first_name+" "+last_name;
		if (peoples.length > 1)
			info.innerHTML += " ("+(index+1)+"/"+peoples.length+")";
		layout.invalidate(document.body);
		service.json("people","create",p,function(res) {
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