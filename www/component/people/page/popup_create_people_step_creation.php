<?php 
class page_popup_create_people_step_creation extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$input = json_decode($_POST["input"], true);
		$peoples = $input["peoples"];
		$sub_models = @$input["sub_models"];
		$multiple = isset($input["multiple"]);
		echo "<script type='text/javascript'>window.popup = window.parent.getPopupFromFrame(window);window.popup.disableClose();</script>";
		if (count($peoples) == 0) {
			echo "<div style='background-color:white'>Nobody to create.</div>";
			echo "<script type='text/javascript'>popup.unfreeze();popup.addCancelButton();</script>";
		}
		$this->addJavascript("/static/data_model/datadisplay.js");
?>
<div id='container' style='padding:10px;background-color:white;'><div style='width:300px;height:100px'></div></div>
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
	echo "window.popup.onclose = null;";
	echo "window.popup.close();";
} else { ?>
var problems = [];
var success = [];
function next(index, span, pb) {
	if (index == peoples.length) {
		window.popup.unfreeze(); // freeze progress, original freeze remains
		var container = document.getElementById('container');
		if (problems.length < peoples.length) {
			container.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom' onload='layout.changed(this);'/> "+(success.length)+" people successfully created.<br/>";
		}
		if (problems.length > 0) {
			container.innerHTML += "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom' onload='layout.changed(this);'/> The creation of "+(problems.length)+" people failed:";
			var ul = document.createElement("UL");
			container.appendChild(ul);
			for (var i = 0; i < problems.length; ++i) {
				var li = document.createElement("LI");
				li.appendChild(document.createTextNode(problems[i].fn+" "+problems[i].ln));
				ul.appendChild(li);
			}
		}
		window.popup.removeButtons();
		layout.changed(container);
		if (success.length > 0)
			window.popup.addContinueButton(function() {
				window.popup.removeButtons();
				var ids = [];
				for (var i = 0; i < success.length; ++i) ids.push(success[i].id);
				postData("/dynamic/people/page/popup_create_people_step_end",{peoples:peoples,peoples_ids:ids<?php if(isset($input["ondone"])) echo ",ondone:".json_encode($input["ondone"]); ?>},window);
			});
		else
			window.popup.addCloseButton();
		window.popup.unfreeze();
		window.popup.onclose = function() {
			try {
				<?php if (isset($input["ondone"])) echo "window.frameElement.".$input["ondone"]."(peoples);"?>
			} catch (e) { logException(e); }
		};
		window.popup.disableClose();
		return;
	}
	var p = peoples[index];
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
	if (!p[0].reuse_id) {
		var msg = "Creation of "+first_name+" "+last_name;
		if (peoples.length > 1)
			msg += " ("+(index+1)+"/"+peoples.length+")";
		span.removeAllChildren();
		span.appendChild(document.createTextNode(msg));
		var data = {root:"People",sub_model:null,sub_models:<?php echo json_encode($sub_models);?>,paths:p};
		<?php if ($multiple) echo "data.multiple = true;"; ?>
		service.json("data_model","create_data",data,function(res) {
			pb.addAmount(1);
			if (res && res.key) {
				for (var i = 0; i < p.length; ++i)
					if (p[i].path == "People") { p[i].key = res.key; break; }
				success.push({fn:first_name,ln:last_name,paths:p,id:res.key});
			} else
				problems.push({fn:first_name,ln:last_name,paths:p});
			next(index+1, span, pb);
		});
	} else {
		var msg = "Setting "+first_name+" "+last_name;
		if (peoples.length > 1)
			msg += " ("+(index+1)+"/"+peoples.length+")";
		span.removeAllChildren();
		span.appendChild(document.createTextNode(msg));
		var types = p[0].add_types.split(",");
		service.json("people","add_types",{people:p[0].reuse_id,types:types},function(res){
			pb.addAmount(1);
			if (res) {
				for (var i = 0; i < p.length; ++i)
					if (p[i].path == "People") { p[i].key = p[0].reuse_id; break; }
				success.push({fn:first_name,ln:last_name,paths:p,id:p[0].reuse_id});
			} else
				problems.push({fn:first_name,ln:last_name,paths:p});
			next(index+1, span, pb);
		});
	}
}
window.popup.freezeWithProgress("Creation of people...", peoples.length, function(span, pb) {
	next(0, span, pb);
});
<?php } ?>
</script>
<?php 
	}
	
}
?>