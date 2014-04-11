<?php
require_once("/../selection_page.inc");
require_once("component/selection/SelectionJSON.inc");
class page_applicant_manually_assign_to_exam_entity extends selection_page {
	
	public function get_required_rights() {return array("manage_applicant");}
	
	public function execute_selection_page() {		
		$mode = $_GET["mode"];
		$EC_id = @$_GET["center"];
		//generate the page
		$this->require_javascript("vertical_layout.js");
		if($mode == "session"){
			//Add the field time for the sessions names
			$this->require_javascript("typed_field.js");
			$this->require_javascript("field_time.js");
		}
		$this->add_javascript("/static/widgets/grid/grid.js");
		$this->add_javascript("/static/data_model/data_list.js");
		?>
		<div id = "assign_container" style = "width:100%; height:100%; overflow:hidden;">
			<div id = "sections_container" style = "height:100%;"></div>
		</div>
		<?php
		//Lock the Applicant table
		require_once("component/data_model/DataBaseLock.inc");
		$sm = PNApplication::$instance->selection->getCampaignId();$lock = $this->performRequiredLocks("Applicant",null,null,$sm);
		
		if($lock == null){
			?>
			<script type = 'text/javascript'>
			error_dialog("Database is busy so the operation cannot be well processed. Please try again later.");
			</script>
			<?php
			return;
		}
		?>
		<script type='text/javascript'>
			require("applicant_manually_assign_to_entity.js",function(){
				new applicant_manually_assign_to_entity(
						"sections_container",
						<?php echo json_encode($sm);?>,
						<?php echo json_encode($mode);?>,
						<?php echo json_encode($EC_id);?>
						);
			});
		
		</script>
		<?php
	}
}
?>