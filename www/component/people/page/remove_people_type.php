<?php 
class page_remove_people_type extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$people_id = $_GET["people"];
		$type_name = $_GET["type"];
		$people = PNApplication::$instance->people->getPeople($people_id);
		if ($people == null) return;
		$types = PNApplication::$instance->people->parseTypes($people["types"]);
		$type = PNApplication::$instance->people->getPeopleTypePlugin($type_name);
		if ($type == null) {
			PNApplication::error("Unknown people type ".$type_name);
			return;
		}
		if (!$type->canRemove()) {
			PNApplication::error("You are not allowed to remove a ".$type->getName());
			return;
		}
		if (!in_array($type_name, $types)) {
			PNApplication::error("Invalid type: ".$people["first_name"]." ".$people["last_name"]." is not a ".$type->getName());
			return;
		}
		echo "<div style='background-color:white;padding:5px;'>";
		if (count($types) == 1) {
			// this is the only type, the people will be completely removed from the database
?>
<table><tr>
	<td><img src='<?php echo theme::$icons_32["question"];?>'/></td>
	<td>
		Are you sure you want to remove <?php echo toHTML($people["first_name"]." ".$people["last_name"]);?> ?<br/>
		All information related to <?php echo $people["sex"] == "M" ? "him" : "her";?> will be definitely removed from the database.<br/>
		It should be done only if it was a mistake when you created this person.
	</td>
</tr></table>
<script type='text/javascript'>
var popup = window.parent.getPopupFromFrame(window);
popup.addYesNoButtons(function() {
	popup.freeze("Removing <?php echo toHTML($people["first_name"]." ".$people["last_name"]);?> from the database...");
	service.json("data_model","remove_row",{table:"People",row_key:<?php echo $people_id;?>},function(res) {
		if (!res) { popup.unfreeze(); return; }
		<?php if (isset($_GET["onpeopleremoved"])) echo "window.frameElement.".$_GET["onpeopleremoved"]."();";?>
		popup.close();
	});
},function() {
	<?php if (isset($_GET["oncancel"])) echo "window.frameElement.".$_GET["oncancel"]."();";?>
	return true;
});
</script>
<?php 
		} else {
?>
<table><tr>
	<td><img src='<?php echo theme::$icons_32["question"];?>'/></td>
	<td>
		Are you sure you want to remove <?php echo toHTML($people["first_name"]." ".$people["last_name"]);?> as a <?php echo toHTML($type->getName()); ?>?<br/>
		All <i><?php echo toHTML($type->getName());?></i> information related to <?php echo $people["sex"] == "M" ? "him" : "her";?> will be definitely removed from the database, but other information will remain.<br/>
		It should be done only if it was a mistake when you created this person as a <?php echo toHTML($type->getName());?>.
	</td>
</tr></table>
<script type='text/javascript'>
var popup = window.parent.getPopupFromFrame(window);
popup.addYesNoButtons(function() {
	popup.freeze("Removing <?php echo toHTML($people["first_name"]." ".$people["last_name"]);?> from the database...");
	service.json("people","remove_type",{people:<?php echo $people_id;?>,type:<?php echo json_encode($type_name);?>},function(res) {
		if (!res) { popup.unfreeze(); return; }
		<?php if (isset($_GET["ontyperemoved"])) echo "window.frameElement.".$_GET["ontyperemoved"]."();";?>
		popup.close();
	});
},function() {
	<?php if (isset($_GET["oncancel"])) echo "window.frameElement.".$_GET["oncancel"]."();";?>
	return true;
});
</script>
<?php
		}
		echo "</div>";
	}
	
}
?>