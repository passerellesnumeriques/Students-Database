<?php 
class service_exam_center_and_informations_sessions_status extends Service {
	
	public function get_required_rights() { return array("can_access_selection_data"); }
	public function documentation() {
		echo "Get the main figures about the linked informations sessions and exam centers<br/>All the data are retrieved using bypassSecurity";
	}
	public function input_documentation() {
		echo "No";
	}
	public function output_documentation() {
		?>
		<ul>
			<li><code>linked_EC</code> number of exam centers linked to any information session</li>
			<li><code>not_linked_IS</code> number of IS <b>with host set</b> not linked to any exam center</li>
			<li><code>total_EC</code> total number of exam centers created</li>
			<li><code>total_IS</code> total number of informations sessions <b>with host set</b></li>
		</ul>
		<?php
	}
	
	/**
	 * @param $component selection
	 * @see Service::execute()
	 */
	public function execute(&$component, $input) {
		$linked_IS_ids = SQLQuery::create()
			->bypassSecurity()
			->select("ExamCenterInformationSession")
			->field("ExamCenterInformationSession","information_session")
			->distinct()
			->executeSingleField();
		$linked_EC_ids = SQLQuery::create()
			->bypassSecurity()
			->select("ExamCenterInformationSession")
			->field("ExamCenterInformationSession","exam_center")
			->distinct()
			->executeSingleValue();
		$linked_EC = 0;
		if ($linked_EC_ids <> null)
			$linked_EC = count($linked_EC_ids);
		$total_EC = SQLQuery::create()
			->bypassSecurity()
			->select("ExamCenter")
			->count()
			->executeSingleValue();
		$IS_with_no_host = PNApplication::$instance->selection->getAllISWithNoHost();
		$total_IS = SQLQuery::create()
			->bypassSecurity()
			->select("InformationSession")
			->count();
		if($IS_with_no_host <> null)
			$total_IS->whereNotIn("InformationSession", "id", $IS_with_no_host);
		
		$total_IS = $total_IS->executeSingleValue();
		$not_linked_IS = SQLQuery::create()
			->bypassSecurity()
			->select("InformationSession")
			->count()
			->distinct();
		if($linked_IS_ids <> null)
			$not_linked_IS->whereNotIn("InformationSession","id", $linked_IS_ids);
		$not_linked_IS = $not_linked_IS ->executeSingleValue();
		
		echo "{linked_EC:".json_encode($linked_EC).",";
		echo "total_EC:".json_encode($total_EC).",";
		echo "not_linked_IS:".json_encode($not_linked_IS).",";
		echo "total_IS:".json_encode($total_IS)."}";
	}
	
}
?>