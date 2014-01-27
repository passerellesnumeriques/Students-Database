<?php
/**
 * Provides functionalities to convert structures between JSON and database
 */ 
class SelectionJSON{
/**
 * Methods about Information Sessions (IS)
 */
	public static function InformationSessionFromID($id){
		if($id == -1 || $id == "-1")
			return "{id:-1, address:null, fake_organization:null, date:null, number_boys_expected:null, number_boys_real:null, number_girls_expected:null, number_girls_real:null, name:null, partners:[]}";
		$IS_row = self::InformationSessionTableDataFromID($id);
		$IS_partners_row = self::PartnersDataFromID($id);
		return self::InformationSession($IS_row, $IS_partners_row);
	}
	
	public static function InformationSessionTableDataFromID($id){
		$q = SQLQuery::create()
			->select("Information_session")
			->where_value("Information_session","id",$id);
		self::InformationSessionSQL($q);
		$row = $q->execute_single_row();
		return $row; 
	}
	
	public static function InformationSessionSQL(&$q){
		$alias = $q->get_table_alias("Information_session");
		if(!$alias)
			$alias = "Information_session";
		$q
			->field($alias,"id")
			->field($alias,"date")
			->field($alias,"postal_address")
			->field($alias,"fake_organization")
			->field($alias,"number_boys_expected")
			->field($alias,"number_girls_expected")
			->field($alias,"number_boys_real")
			->field($alias,"number_girls_real")
			->field($alias,"name");
	}
	
	public static function PartnersDataFromID($id){
		$q = SQLQuery::create()
			->select("Information_session_partner")
			->join("Information_session_partner","Information_session_contact_point",array("information_session"=>"information_session","organization"=>"organization"))
			->where_value("Information_session_partner","information_session",$id)
			->order_by("Information_session_partner","organization");
		self::ISPartnersSQL($q);
		$rows = $q->execute();
		return $rows;
	}
	
	public static function ISPartnersSQL(&$q){
		$alias = $q->get_table_alias("Information_session_partner");
		if(!$alias)
			$alias = "Information_session";
		$alias_contact_point = $q->get_table_alias("Information_session_contact_point");
		if(!$alias_contact_point)
			$alias_contact_point = "Information_session_contact_point";
		$q
			->field("Information_session_partner","organization","organization")
			->field("Information_session_partner","host","host")
			->field("host_address")
			->field("Information_session_contact_point","people","contact_point");
	}
	
	public static function InformationSession($row_IS,$rows_partners){
		$data = "";
		if(isset($row_IS) && $row_IS <> null){
			$data.= "{id:".json_encode($row_IS["id"]).", ";
			$data.= "address:".json_encode($row_IS["postal_address"]);
			$data.= ", fake_organization:".json_encode($row_IS["fake_organization"]);
			$data.= ", date:".json_encode($row_IS["date"]);
			$data.= ", number_boys_expected:".json_encode($row_IS["number_boys_expected"]);
			$data.= ", number_girls_expected:".json_encode($row_IS["number_girls_expected"]);
			$data.= ", number_boys_real:".json_encode($row_IS["number_boys_real"]);
			$data.= ", number_girls_real:".json_encode($row_IS["number_girls_real"]);
			$data.= ", name:".json_encode($row_IS["name"]);
			$data.= ", partners:";
			if(isset($rows_partners[0]["organization"]) && $rows_partners[0]["organization"] <> null){
				$first = true;
				$current_partner = null;
				$data.= "[";
				foreach($rows_partners as $p){
					if($p["organization"] != $current_partner){
						$name = SQLQuery::create()->select("Organization")->field("name")->where_value("Organization","id",$p["organization"])->execute_single_value();
						if(!$first){
							$data.= "]}";
							$data.= ", ";
						}
						$first = false;
						$data.= "{";
// 						array_push($partners_array,$p["organization"]);
						$data.= "organization:".json_encode($p["organization"]).", ";
						$data.= "organization_name:".json_encode($name).", ";
						$data.= "host:".json_encode($p["host"]).", ";
						$data.= "host_address:".json_encode($p["host_address"]).", ";
						$data.= "contact_points_selected:";
						if(isset($p["contact_point"]) && $p["contact_point"] <> null){
							$data.= "[";
							$data.= json_encode($p["contact_point"]);
						} else $data.= "[";
						$current_partner = $p["organization"];
					} else {
						$data.= ", ";
						$data.= json_encode($p["contact_point"]);
					}
				}
				$data.= "]}";
				$data.= "]";
			} else $data.= "[]";
	
			$data.= "}";
		} else $data.="{}";
// 		if(!$return_partners_array) return $data;
// 		else return array("data" => $data, "partners" => $partners_array);
		return $data;
		
	}
	
/**
 * Methods about Exam Subjects
 */
	
	/**
	 * Exam Subject From Id
	 * @param {integer} $id
	 * @return {string} Exam subject to JSON format
	 */
	public static function ExamSubjectFromID($id){
		$q = SQLQuery::create()
			->select("Exam_subject")
			->join("Exam_subject","Exam_subject_part",array("id" => "exam_subject"))
			->join("Exam_subject_part","Exam_subject_question",array("id" => "exam_subject_part"))
			->where_value("Exam_subject","id",$id)
			->order_by("Exam_subject_part","id");
		self::ExamSubjectSQL($q);
		self::ExamSubjectPartSQL($q);
		self::ExamSubjectQuestionSQL($q);
		
		$rows = $q->execute();
		if($rows == null)
			return "{}";
		return self::ExamSubject($q, $rows, $id);
	}
	
	public static function ExamSubjectSQL(&$q){
		$alias = $q->get_table_alias("Exam_subject");
		if(!$alias)
			$alias = "Exam_subject";
		$q
			->field($alias,"name","subject_name")
			->field($alias,"max_score","subject_max_score");
	}
	
	public static function ExamSubjectPartSQL(&$q){
		$alias = $q->get_table_alias("Exam_subject_part");
		if(!$alias)
			$alias = "Exam_subject_part";
		$q
			->field($alias,"id","part_id")
			->field($alias,"index","part_index")
			->field($alias,"max_score","part_max_score")
			->field($alias,"name","part_name");
	}
	
	public static function ExamSubjectQuestionSQL(&$q){
		$alias = $q->get_table_alias("Exam_subject_question");
		if(!$alias)
			$alias = "Exam_subject_question";
		$q
			->field($alias,"id","question_id")
			->field($alias,"index","question_index")
			->field($alias,"max_score","question_max_score")
			->field($alias,"correct_answer","question_correct_answer")
			->field($alias,"choices","question_choices");
	}
	
	public static function ExamSubject($q, $rows, $exam_id){
		$json = "";
		if(isset($rows[0]["subject_name"])){
			$json.= "{id:".json_encode($exam_id);
			$json.= ", name:".json_encode($rows[0]["subject_name"]);
			$json.= ", max_score:".json_encode($rows[0]["subject_max_score"]);
			$json.= ", parts:";
			$no_part = true;
			foreach($rows as $d){
				if(isset($d["part_id"])){
					$no_part = false;
					break;
				}
			}
			if($no_part){
				$json.= "[]";
			} else {
				$json.= "[";
				$no_question = true;
				foreach($rows as $d){
					if(isset($d["question_id"])){
						$no_question = false;
						break;
					}
				}
				if($no_question){
					$first = true;
					foreach($rows as $d){
						if(!$first)
							$json.= ", ";
						$first = false;
						$json.= "{id:".json_encode($d["part_id"]);
						$json.= ", index:".json_encode($d["part_index"]);
						$json.= ", name:".json_encode($d["part_name"]);
						$json.= ", max_score:".json_encode($d["part_max_score"]);
						$json.= ", questions:[]";
						$json.= "}";
					}
				} else {
					$current_part = $rows[0]["part_id"];
					$no_question_before = false;
					$first_part = true;
					foreach ($rows as $d){
						if($d["part_id"] != $current_part){
							if($no_question_before){
								$json.= "]}, ";
								$no_question_before = false;
							} else
								$json.= "}]}, ";
							$json.= "{id:".json_encode($d["part_id"]);
							$json.= ", index:".json_encode($d["part_index"]);
							$json.= ", name:".json_encode($d["part_name"]);
							$json.= ", max_score:".json_encode($d["part_max_score"]);
							if(!isset($d["question_id"])){
								$json.= ", questions:[";
								$no_question_before = true;
							} else {
								$json.= ", questions:[{";
								$json.= "id:".json_encode($d["question_id"]);
								$json.= ", index:".json_encode($d["question_index"]);
								$json.= ", max_score:".json_encode($d["question_max_score"]);
								$json.= ", correct_answer:".json_encode($d["question_correct_answer"]);
								$json.= ", choices:".json_encode($d["question_choices"]);
							}
						} else {
							if(!$first_part){
								$json.= "}, {";
								$json.= "id:".json_encode($d["question_id"]);
								$json.= ", index:".json_encode($d["question_index"]);
								$json.= ", max_score:".json_encode($d["question_max_score"]);
								$json.= ", correct_answer:".json_encode($d["question_correct_answer"]);
								$json.= ", choices:".json_encode($d["question_choices"]);
							} else {
								$json.= "{";
								$json.= "id:".json_encode($d["part_id"]);
								$json.= ", index:".json_encode($d["part_index"]);
								$json.= ", name:".json_encode($d["part_name"]);
								$json.= ", max_score:".json_encode($d["part_max_score"]);
								$json.= ", questions:[";
								if(!isset($d["question_id"]))
									$no_question_before = true;
								else{
									$json.= "{id:".json_encode($d["question_id"]);
									$json.= ", index:".json_encode($d["question_index"]);
									$json.= ", max_score:".json_encode($d["question_max_score"]);
									$json.= ", correct_answer:".json_encode($d["question_correct_answer"]);
									$json.= ", choices:".json_encode($d["question_choices"]);
								}
							}
							$first_part = false;
						}
						$current_part = $d["part_id"];
					}
					if(!$no_question_before)
						$json.= "}]}";
					else
						$json.= "]}";
				}
				$json.= "]";
			}
			$json.= "}";
		} else
			$json = "{}";
		return $json;
	}
	
	public static function ExamSubject2DB($json){
		$a = array(
			"max_score" => @$json["max_score"],
			"name" => @$json["name"]
		);
		if(isset($json["id"]))
			$a["id"] = $json["id"];
		return $a;
	}
	
	public static function ExamSubjectPart2DB($json){
		$a = array(
			"exam_subject" => @$json["exam_subject"],
			"index" => @$json["index"],
			"max_score" => @$json["max_score"],
			"name" => @$json["name"]
		);
		if(isset($json["id"]))
			$a["id"] = $json["id"];
		return $a;
	}
	
	public static function ExamSubjectQuestion2DB($json){
		$a = array(
			"exam_subject_part" => @$json["exam_subject_part"],
			"index" => @$json["index"],
			"max_score" => @$json["max_score"],
			"correct_answer" => @$json["correct_answer"],
			"choices" => @$json["choices"]
		);
		if(isset($json["id"]))
			$a["id"] = $json["id"];
		return $a;
	}
	
	public static function getAllExamSubjectNames(){
		$names = SQLQuery::create()
		->select("Exam_subject")
		->field("name")
		->execute_single_field();
		return $names;
	}
}