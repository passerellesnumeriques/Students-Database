<?php
function custom_import_applicants (&$page,$fixed_data,$prefilled_data,$all_ids,$generate_id,$check_date,$limit_date){
	/** Lock the applicant table because this page will perform some checks on the
	 * applicant ids (given or generated), so must be sure that no one is updating it;
	 */
	require_once("component/data_model/DataBaseLock.inc");
	$campaign_id = PNApplication::$instance->selection->getCampaignId();
	require_once("component/data_model/Model.inc");
	$table = DataModel::get()->getTable("Applicant");
	$locked_by = null;
	$lock_id = null;
	$lock_id = DataBaseLock::lockTable($table->getSQLNameFor($campaign_id), $locked_by);
	if($lock_id == null & $locked_by <> null){
		PNApplication::error($locked_by." is already managing the applicants for this campaign");
		return;
	} else {
		DataBaseLock::generateScript($lock_id);
	}
	
	require_once("component/data_import/page/import_data.inc");
	$data_list = array();
	$applicant = DataModel::get()->getTable("Applicant");
	$display = $applicant->getDisplayHandler(null);
	$data_list = array_merge($data_list, $display->getDisplayableData());
	
	// import_data($page, $icon, $title, $data_list, $fixed_data, $prefilled_data, $create_button, $create_function);
	import_data(
		$page,
		"/static/application/icon.php?main=/static/students/student_32.png&small=".theme::$icons_16["add"]."&where=right_bottom",
		"Import Applicants",
		$data_list,
		$fixed_data,
		$prefilled_data,
		"<img src='".theme::make_icon("/static/students/student_16.png",theme::$icons_10["add"])."'/> Import Applicants",
		"importApplicants"
	);
	?>
	<script type='text/javascript'>
	function importApplicants(applicants,locker){

		var applicants_to_create = [];
	
		var getData = function(category, data, student) {
			for (var i = 0; i < student.length; ++i)
				if (student[i].data.category == category && student[i].data.name == data)
					return student[i].value;
		};
		
		var all_ids = <?php 
			$first = true;
			echo "[";
			foreach($all_ids as $id){
				if(!$first)
					echo ", ";
				$first = false;
				echo json_encode($id);
			}
			echo "];"; ?>
		var generate_id = <?php echo json_encode($generate_id);?>;
		var db_lock_id = <?php echo json_encode($lock_id);?>;
		var check_date = <?php echo json_encode($check_date);?>;
		var limit_date = <?php echo json_encode($limit_date);?>;
		
		var createApplicantData = function(){
			for(var i = 0; i < applicants.length; i++){
				applicants_to_create[i] = {};
				applicants_to_create[i].first_name = getData("Applicant Information","First Name",applicants[i]);
				applicants_to_create[i].middle_name = getData("Applicant Information","Middle Name",applicants[i]);
				applicants_to_create[i].last_name = getData("Applicant Information","Last Name",applicants[i]);
				applicants_to_create[i].khmer_first_name = getData("Applicant Information","Khmer First Name",applicants[i]);
				applicants_to_create[i].khmer_last_name = getData("Applicant Information","Khmer Last Name",applicants[i]);
				applicants_to_create[i].sex = getData("Applicant Information","Gender",applicants[i]);
				applicants_to_create[i].birth = getData("Applicant Information","Birth Date",applicants[i]);
				applicants_to_create[i].applicant_id = getData("Applicant Information","Applicant ID",applicants[i]);
			}
			new create_applicants(applicants_to_create,all_ids,generate_id,check_date,limit_date,db_lock_id,locker);
		};
		require("create_applicants.js",function(){
			createApplicantData();
		});
	}
	</script>
	<?php 
	
}

?>