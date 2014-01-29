<?php 
class service_list_buttons extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Analyze database to add necessary buttons to the students' list"; }
	public function input_documentation() { echo "Same parameters as page list"; }
	public function output_documentation() { echo "JavaScript that builds the buttons"; }
	
	public function get_output_format($input) { return "text/javascript"; }
	
	public function execute(&$component, $input) {
		if (isset($input["batches"])) {
			$batches = explode(",",$input["batches"]);
			if (count($batches) == 1) {
				// we are on a batch
				// 1- we may want to assign students to specializations
				// => check if specializations exist in any period of this batch
				$specializations = SQLQuery::create()
					->select("AcademicPeriod")
					->where_value("AcademicPeriod", "batch", $batches[0])
					->join("AcademicPeriod", "AcademicPeriodSpecialization", array("id"=>"period"))
					->execute();
				if (count($specializations) > 0) {
					?>
					var assign_spe = document.createElement("DIV");
					assign_spe.className = "button";
					assign_spe.innerHTML = "<img src='/static/application/icon.php?main=/static/curriculum/curriculum_16.png&small="+theme.icons_10.edit+"&where=right_bottom' style='vertical-align:bottom'/> Assign specializations";
					assign_spe.onclick = function() {
						require("popup_window.js",function() {
							var p = new popup_window("Assign Specializations", "/static/application/icon.php?main=/static/curriculum/curriculum_16.png&small="+theme.icons_10.edit+"&where=right_bottom", "");
							var f = p.setContentFrame("/dynamic/students/page/assign_specializations?batch=<?php echo $batches[0];?>");
							p.addOkCancelButtons(function() {
								p.freeze("Saving specializations...");
								getIFrameWindow(f).save(function(msg) {
									p.set_freeze_content(msg);
								},function(){
									p.close();
									students_list.reload_data();
								});
							});
							p.show();
						});
					};
					students_list.addHeader(assign_spe);
					<?php 
				}
			}
		}
	}
	
}
?>