<?php 
class StudentsJSON {
	
	public static function Student($student) {
		require_once("component/people/PeopleJSON.inc");
		$s = PeopleJSON::People($student);
		$s = substr($s,0,strlen($s)-1);
		$s .= ",exclusion_date:".json_encode($student["exclusion_date"]);
		$s .= ",exclusion_reason:".json_encode($student["exclusion_reason"]);
		$s .= ",university_id:".json_encode($student["university_id"]);
		$s .= "}";
		return $s;
	}
	
	public static function Students($students) {
		$s = "[";
		$first = true;
		foreach ($students as $student) {
			if ($first) $first = false; else $s .= ",";
			$s .= self::Student($student);
		}
		$s .= "]";
		return $s;
	}
	
}
?>