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
	
	public static function ExamSubjectsFullJSON() {
		$subjects = SQLQuery::create()->select("ExamSubject")->execute();
		$versions = SQLQuery::create()->select("ExamSubjectVersion")->orderBy("ExamSubjectVersion", "exam_subject")->execute();
		$parts = SQLQuery::create()->select("ExamSubjectPart")->orderBy("ExamSubjectPart", "exam_subject")->orderBy("ExamSubjectPart", "index")->execute();
		$questions = SQLQuery::create()->select("ExamSubjectQuestion")->orderBy("ExamSubjectQuestion", "exam_subject_part")->orderBy("ExamSubjectQuestion", "index")->execute();
		return self::ExamSubjectsFullJSONFromDB($subjects, $versions, $parts, $questions);
	}
	public static function ExamSubjectFullJSON($subject_id) {
		$subjects = SQLQuery::create()->select("ExamSubject")->whereValue("ExamSubject","id",$subject_id)->execute();
		$versions = SQLQuery::create()->select("ExamSubjectVersion")->orderBy("ExamSubjectVersion", "exam_subject")->whereValue("ExamSubjectVersion", "exam_subject", $subject_id)->execute();
		$parts = SQLQuery::create()->select("ExamSubjectPart")->orderBy("ExamSubjectPart", "exam_subject")->orderBy("ExamSubjectPart", "index")->whereValue("ExamSubjectPart","exam_subject",$subject_id)->execute();
		$parts_ids = array();
		foreach ($parts as $part) array_push($parts_ids, $part["id"]);
		$questions = count($parts_ids) == 0 ? array() : SQLQuery::create()->select("ExamSubjectQuestion")->orderBy("ExamSubjectQuestion", "exam_subject_part")->orderBy("ExamSubjectQuestion", "index")->whereIn("ExamSubjectQuestion","exam_subject_part",$parts_ids)->execute();
		return self::ExamSubjectsFullJSONFromDB($subjects, $versions, $parts, $questions, true);
	}
	public static function ExamSubjectsFullJSONFromDB($subjects, $versions, $parts, $questions, $single = false) {
		// merge everything into a single list
		if (!$single) $s = "["; else $s = "";
		$subject_i = 0;
		$version_i = 0;
		$part_i = 0;
		$question_i = 0;
		while ($subject_i < count($subjects)) {
			if ($subject_i > 0) $s .= ",";
			$s = "{";
			$s .= "id:".$subjects[$subject_i]["id"];
			$s .= ",name:".json_encode($subjects[$subject_i]["name"]);
			$s .= ",max_score:".json_encode($subjects[$subject_i]["max_score"]);
			$s .= ",parts:[";
			$first_part = true;
			while ($part_i < count($parts) && $parts[$part_i]["exam_subject"] == $subjects[$subject_i]["id"]) {
				if ($first_part) $first_part = false; else $s .= ",";
				$s .= "{";
				$s .= "id:".$parts[$part_i]["id"];
				$s .= ",index:".$parts[$part_i]["index"];
				$s .= ",max_score:".$parts[$part_i]["max_score"];
				$s .= ",name:".json_encode($parts[$part_i]["name"]);
				$s .= ",questions:[";
				$first_question = true;
				while ($question_i < count($questions) && $questions[$question_i]["exam_subject_part"] == $parts[$part_i]["id"]) {
					if ($first_question) $first_question = false; else $s .= ",";
					$s .= "{";
					$s .= "id:".$questions[$question_i]["id"];
					$s .= ",index:".$questions[$question_i]["index"];
					$s .= ",max_score:".$questions[$question_i]["max_score"];
					$s .= ",type:".json_encode($questions[$question_i]["type"]);
					$s .= ",type_config:".json_encode($questions[$question_i]["type_config"]);
					$s .= "}";
					$question_i++;
				}
				$s .= "]";
				$s .= "}";
				$part_i++;
			}
			$s .= "],versions:[";
			$first_version = true;
			while ($version_i < count($versions) && $versions[$version_i]["exam_subject"] == $subjects[$subject_i]["id"]) {
				if ($first_version) $first_version = false; else $s .= ",";
				$s .= $versions[$version_i]["id"];
				$version_i++;
			}
			$s .= "]";
			$s .= "}";
			$subject_i++;
		}
		if (!$single) $s .= "]";
		return $s;
	}
	
}
?>