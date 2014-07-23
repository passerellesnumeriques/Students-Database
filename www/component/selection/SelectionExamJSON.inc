<?php 
class SelectionExamJSON {
	
	/**
	 * Retrieve the exam center room data from database
	 * @param SQLQuery $q query
	 * @no_name_check
	 */
	public static function ExamCenterRoomSQL(&$q){
		$alias = $q->getTableAlias("ExamCenterRoom");
		$q->field("ExamCenterRoom","id","exam_room_id")
		  ->field("ExamCenterRoom","name","exam_room_name")
		  ->field("ExamCenterRoom","capacity","exam_room_capacity")
		  ->field("ExamCenterRoom","exam_center","exam_room_center_id");
	}
	
	/** Get a ExamCenterRoom object from the data retrieved from database
	 * @param array $row result from database
	 * @return string JSON ExamCenterRoom object
	 * @no_name_check
	 */
	public static function ExamCenterRoom($row){
		$data  = "{id:".$row["exam_room_id"];
		$data .= ",center_id:".$row["exam_room_center_id"];
		$data .= ",name:".json_encode($row["exam_room_name"]);
		$data .= ",capacity:".$row["exam_room_capacity"];
		$data .= "}";
		return $data;
	}

	/** Get a ExamCenterRoom objects from the data retrieved from database
	 * @param array $rows result from database
	 * @return string JSON ExamCenterRoom object
	 * @no_name_check
	 */
	public static function ExamCenterRooms($rows){
		$data = "[";
		$first = true;
		foreach ($rows as $row) {
			if ($first) $first = false; else $data .= ",";
			$data .= self::ExamCenterRoom($row);
		}
		$data .= "]";
		return $data;
	}
	
	/**
	 * Prepares a query to retrieve the exam subject data from table ExamSubject
	 * @param SQLQuery $q
	 * @no_name_check
	 */
	public static function ExamSubjectSQL(&$q){
		$alias = $q->getTableAlias("ExamSubject");
		if(!$alias) $alias = "ExamSubject";
		$q	->field($alias, "id","subject_id")
			->field($alias,"name","subject_name")
			->field($alias,"max_score","subject_max_score");
	}
	
	public static function ExamSubjectJSON($row) {
		$s = "{";
		$s .= "id:".$row["subject_id"];
		$s .= ",name:".json_encode($row["subject_name"]);
		$s .= ",max_score:".json_encode($row["subject_max_score"]);
		$s .= "}";
		return $s;
	}
	public static function ExamSubjectsJSON($rows) {
		$s = "[";
		$first = true;
		foreach ($rows as $row) {
			if ($first) $first = false; else $s .= ",";
			$s .= self::ExamSubjectJSON($row);
		}
		$s .= "]";
		return $s;
	}
}
?>