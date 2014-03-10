<?php 
class page_popup_create_people extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$types = explode(",",$_GET["types"]);
		// check first we can create people with those types
		require_once("component/people/PeopleTypePlugin.inc");
		foreach ($types as $type) {
			$ok = null;
			foreach (PNApplication::$instance->components as $c) {
				foreach ($c->getPluginImplementations() as $pi) {
					if (!($pi instanceof PeopleTypePlugin)) continue;
					if ($pi->getId() <> $type) continue;
					$ok = $pi->canRemove();
					break;
				}
				if ($ok !== null) break;
			}
			if (!$ok) {
				PNApplication::error("You cannot create a people of type ".$type);
				return;
			}
		}
		// generate page
		$this->require_javascript("section.js");
		?>
		<script type='text/javascript'>
		window.create_people = {
			types: <?php echo json_encode($types);?>,
			tables: {}
		};
		window.create_people_validations = [];
		</script>
		<?php 
		$sections = array();
		require_once("component/people/PeopleCreatePlugin.inc");
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeopleCreatePlugin)) continue;
				if (!($pi->isValidForTypes($types))) continue;
				array_push($sections, $pi);
			}
		}
		usort($sections, function($s1,$s2) { return $s1->getPriority() - $s2->getPriority(); });
		foreach ($sections as $s) {
			$id = $this->generateID();
			echo "<div id='$id' icon=\"".$s->getIcon()."\" title=\"".htmlentities($s->getName())."\" collapsable='false' css='soft' style='margin:5px'>";
			$s->generateSection($this);
			echo "</div>";
			$this->onload("section_from_html('$id');");
		}
		?>
		<script type='text/javascript'>
		var popup = window.parent.get_popup_window_from_frame(window);
		popup.addNextButton(function() {
			popup.freeze();
			for (var i = 0; i < create_people_validations.length; ++i) {
				var error = window.create_people_validations[i].fct(window.create_people_validations[i].param);
				if (error != null) {
					alert("Please correct the data before to continue: "+error);
					popup.unfreeze();
					return;
				}
			}
			popup.removeAllButtons();
			postData("popup_create_people_step_check", {peoples:[window.create_people]}, window);
		});
		popup.addCancelButton();
		</script>
		<?php 
	}
	
}
?>