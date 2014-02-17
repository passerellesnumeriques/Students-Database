<?php
/**
 * Provides functionalities to convert structures between JSON and database
 */ 
class SelectionJSON{
/**
 * Methods about campaigns
 */
	/**
	 * Campaigns
	 * Get all the existing campaigns
	 * @return string [{id:'',name:''},{id:'',name:''}...]
	 */
	public static function Campaigns(){
		$js = "[]";
		try {$campaigns = SQLQuery::create()->select("SelectionCampaign")->field("id")->field("name")->orderBy("name")->execute();
		} catch (Exception $e){
			PNApplication::error($e);
		}
		if(isset($campaigns[0]["name"])){
			$first = true;
			$js = "[";
			foreach($campaigns as $c){
				if(!$first) $js.= ", ";
				$js.= "{id:".json_encode($c["id"]).", name:".json_encode($c["name"])."}";
				$first = false;
			}
			$js.= "]";
		}
		return $js;
	}
	
/**
 * Methods about steps
 */
	/**
	 * Steps
	 * @return string [{name:'',value:'', text:''},{name:'',value:'', text:''}...]
	 */
	public static function Steps(){
		$all_steps = include("steps.inc");
		$steps = PNApplication::$instance->selection->getSteps();
		$json = "[";
		$first = true;
		foreach($steps as $name => $value){
			if(!$first)
				$json.= ", ";
			$first = false;
			$json.= "{name:".json_encode($name).",value:".json_encode($value);
			$json.= ",text:".json_encode($all_steps[$name][0])."}";
		}
		$json .= "]";
		return $json;
	}
	
/**
 * Methods about Information Sessions (IS)
 */
	/**
	 * Build a JSON information structure for the given id
	 * @param number $id
	 * @return string json InformationSession structure
	 */
	public static function InformationSessionFromID($id){
		if($id == -1 || $id == "-1")
			return "{id:-1, address:null, fake_organization:null, date:null, number_boys_expected:null, number_boys_real:null, number_girls_expected:null, number_girls_real:null, name:null, partners:[]}";
		$IS_row = self::InformationSessionTableDataFromID($id);
		$IS_partners_row = self::PartnersDataFromID($id);
		return self::InformationSession($IS_row, $IS_partners_row);
	}
	
	/**
	 * Get all the data from InformationSession table for the given id
	 * @param number $id
	 * @return array the row coming from the database
	 */
	public static function InformationSessionTableDataFromID($id){
		$q = SQLQuery::create()
			->select("InformationSession")
			->whereValue("InformationSession","id",$id);
		self::InformationSessionSQL($q);
		$row = $q->executeSingleRow();
		return $row; 
	}
	
	/**
	 * Prepares a SQLQuery to retrieve an information session, to be used with method InformationSession 
	 * @param SQLQuery $q
	 */
	public static function InformationSessionSQL(&$q){
		$alias = $q->getTableAlias("InformationSession");
		if(!$alias)
			$alias = "InformationSession";
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
	
	/**
	 * Get all the data from InformationSessionPartner for the given information session
	 * @param unknown $id of the information session
	 * @return array the rows coming from the database
	 */
	public static function PartnersDataFromID($id){
		$q = SQLQuery::create()
			->select("InformationSessionPartner")
			->join("InformationSessionPartner","InformationSessionContactPoint",array("information_session"=>"information_session","organization"=>"organization"))
			->whereValue("InformationSessionPartner","information_session",$id)
			->orderBy("InformationSessionPartner","organization");
		self::ISPartnersSQL($q);
		$rows = $q->execute();
		return $rows;
	}
	
	/**
	 * Prepares the given SQLQuery to retrieve all the information session partners data
	 * @param SQLQuery $q
	 */
	public static function ISPartnersSQL(&$q){
		$alias = $q->getTableAlias("InformationSessionPartner");
		if(!$alias)
			$alias = "InformationSession";
		$alias_contact_point = $q->getTableAlias("InformationSessionContactPoint");
		if(!$alias_contact_point)
			$alias_contact_point = "InformationSessionContactPoint";
		$q
			->field("InformationSessionPartner","organization","organization")
			->field("InformationSessionPartner","host","host")
			->field("host_address")
			->field("InformationSessionContactPoint","people","contact_point");
	}
	
	/**
	 * Get the Information json structure
	 * @param array $row_IS coming from InformationSessionTableDataFromID
	 * @param array $rows_partners coming from PartnersDataFromID method
	 * @return string JSON information session structure
	 */
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
						$name = SQLQuery::create()->select("Organization")->field("name")->whereValue("Organization","id",$p["organization"])->executeSingleValue();
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
		return $data;
		
	}
	
/**
 * Methods about Exam Subjects
 */
	
	/**
	 * Exam Subject From Id
	 * @param number $id
	 * @return string Exam subject to JSON format
	 */
	public static function ExamSubjectFromID($id){
		$q = SQLQuery::create()
			->select("ExamSubject")
			->join("ExamSubject","ExamSubjectPart",array("id" => "exam_subject"))
			->join("ExamSubjectPart","ExamSubjectQuestion",array("id" => "exam_subject_part"))
			->whereValue("ExamSubject","id",$id)
			->orderBy("ExamSubjectPart","id");
		self::ExamSubjectSQL($q);
		self::ExamSubjectPartSQL($q);
		self::ExamSubjectQuestionSQL($q);
		
		$rows = $q->execute();
		if($rows == null)
			return "{}";
		return self::ExamSubject($q, $rows, $id);
	}
	
	/**
	 * Prepares a query to retrieve the exam subject data from table ExamSubject
	 * @param SQLQuery $q
	 */
	public static function ExamSubjectSQL(&$q){
		$alias = $q->getTableAlias("ExamSubject");
		if(!$alias)
			$alias = "ExamSubject";
		$q
			->field($alias, "id","subject_id")
			->field($alias,"name","subject_name")
			->field($alias,"max_score","subject_max_score");
	}
	
	/**
	 * Prepares a query to retrieve the exam subject parts data from table ExamSubjectPart
	 * @param SQLQuery $q
	 */
	public static function ExamSubjectPartSQL(&$q){
		$alias = $q->getTableAlias("ExamSubjectPart");
		if(!$alias)
			$alias = "ExamSubjectPart";
		$q
			->field($alias,"id","part_id")
			->field($alias,"index","part_index")
			->field($alias,"max_score","part_max_score")
			->field($alias,"name","part_name");
	}
	
	/**
	 * Prepares a query to retrieve the exam subject questions data from table ExamSubjectQuestion
	 * @param SQLQuery $q
	 */
	public static function ExamSubjectQuestionSQL(&$q){
		$alias = $q->getTableAlias("ExamSubjectQuestion");
		if(!$alias)
			$alias = "ExamSubjectQuestion";
		$q
			->field($alias,"id","question_id")
			->field($alias,"index","question_index")
			->field($alias,"max_score","question_max_score")
			->field($alias,"correct_answer","question_correct_answer")
			->field($alias,"choices","question_choices");
	}
	
	/**
	 * Get the exam subject JSON structure
	 * @param SQLQuery $q
	 * @param array $rows the rows corresponding to the exam subject
	 * @param number $exam_id the id of the exam
	 * @return string JSON exam subject structure
	 */
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
	
	/**
	 * Convert the exam data from the JSON exam subject structures to the database one
	 * Only takes into account the ExamSubject table fields
	 * @param array $json object coming from the conversion from a json object to an associative array
	 * @return array array to be inserted into the database
	 */
	public static function ExamSubject2DB($json){
		$a = array(
			"max_score" => @$json["max_score"],
			"name" => @$json["name"]
		);
		if(isset($json["id"]))
			$a["id"] = $json["id"];
		return $a;
	}
	
	/**
	 * Convert the exam data from the JSON exam subject part structures to the database one
	 * Only takes into account the ExamSubjectPart table fields
	 * @param string $json object coming from the conversion from a json object to an associative array
	 * @return array array to be inserted into the database
	 */
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
	
	/**
	 * Convert the exam data from the JSON exam subject question structures to the database one
	 * Only takes into account the ExamSubjectQuestion table fields
	 * @param string $json object coming from the conversion from a json object to an associative array
	 * @return array array to be inserted into the database
	 */
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
	
	/**
	 * Get all the exam subject names from the database
	 * @param number (optional) $exclude_id id to exclude from the query
	 * @return array array containing all the exam subject names
	 */
	public static function getAllExamSubjectNames($exclude_id = null){
		$q = SQLQuery::create()
		->select("ExamSubject")
		->field("name");
		if($exclude_id <> null)
			$q->whereNotIn("ExamSubject", "id", array($exclude_id));
		$names = $q->executeSingleField();
		return $names;
	}
	
/**
 * Methods about Exam Topics for Eligibility Rules
 */
	/**
	 * Get the exam topic for eligibility rules JSON structure
	 * @param number $id the id of the topic to retrieve
	 * @return string exam topic for eligibility rules JSON structure
	 */
	public static function ExamTopicForEligibilityRulesFromID ($id){
		$q = SQLQuery::create()
			->select("ExamTopicForEligibilityRule")
			->join("ExamTopicForEligibilityRule","ExamPartTopic",array("id" => "exam_topic_for_eligibility_rule"))
			->join("ExamPartTopic","ExamSubjectPart",array("exam_subject_part" => "id"))
			->join("ExamSubjectPart","ExamSubject",array("exam_subject" => "id"))
			->whereValue("ExamTopicForEligibilityRule","id",$id)
			->orderBy("ExamSubject","id")
			->orderBy("ExamSubjectPart","index");
		self::ExamTopicSQL($q);
		self::ExamSubjectPartSQL($q);
		self::ExamSubjectSQL($q);
		$rows = $q->execute();
		if($rows <> null)
			return self::ExamTopic($q, $rows, $id);
		else 
			return "{}";
	}
	
	/**
	 * Set the query to retrieve all the data from ExamTopicForEligibilityRule table
	 * To be used with ExamTopicPHP | ExamTopic method
	 * @param SQLQuery $q
	 */
	public static function ExamTopicSQL(&$q){
		$alias = $q->getTableAlias("ExamTopicForEligibilityRule");
		if(!$alias)
			$alias = "ExamTopicForEligibilityRule";
		$q
			->field($alias,"id","topic_id")
			->field($alias,"name","topic_name")
			->field($alias,"max_score","topic_max_score")
			->field($alias,"number_questions","topic_number_questions");
	}
	
	/**
	 * Exam Topic PHP <br/>
	 * Get the exam topic for eligibility rules data<br/>
	 * @param SQLQuery $q
	 * @param array $rows the data about the ExamTopic
	 * @param integer id the id of the topic
	 * @return array [id => ""
	 * 					name => ""
	 * 					subjects => [
	 *							id => [
	 *								name => ""
	 *								max_score => ""
	 *								full_subject => boolean
	 * 								parts => array(
	 *									id =>[
	 *										name => ""
	 *										max_score => ""
	 *										index => ""
	 *									],...)
	 * 							],...]
	 *					]
	 * subjects & parts arrays use the subject id (respectively part id) as key
	 */
	public static function ExamTopicPHP($q, $rows, $id){
		$full_exams = SQLQuery::create()
			->select("ExamTopicFullExam")
			->field("exam_subject")
			->whereValue("ExamTopicFullExam","exam_topic_for_eligibility_rule",$id)
			->executeSingleField();
		if($rows <> null){
			$topic = array();
			$topic["id"] = $id;
			$topic["name"] = $rows[0]["topic_name"];
			$topic["max_score"] = $rows[0]["topic_max_score"];
			$topic["number_questions"] = $rows[0]["topic_number_questions"];
			$topic["subjects"] = array();
			foreach($rows as $row){
				if($row["subject_id"] <> null){
					if(isset($topic["subjects"][$row["subject_id"]])){
						$topic["subjects"][$row["subject_id"]]["parts"][$row["part_id"]] = array(
								"name" => $row["part_name"],
								"max_score" => $row["part_max_score"],
								"index" => $row["part_index"]
						);
					} else {
						//create the subject array
						$topic["subjects"][$row["subject_id"]] = array(
								"name" => $row["subject_name"],
								"max_score" => $row["subject_max_score"],
								"parts" => array()
						);
						if(in_array($row["subject_id"],$full_exams))
							$topic["subjects"][$row["subject_id"]]["full_subject"] = true;
						else
							$topic["subjects"][$row["subject_id"]]["full_subject"] = false;
						//create the part
						$topic["subjects"][$row["subject_id"]]["parts"][$row["part_id"]] = array(
								"name" => $row["part_name"],
								"max_score" => $row["part_max_score"],
								"index" => $row["part_index"]
						);
					}
				}
			}
			return $topic;
		} else
			return;
	}
	
	/**
	 * Get the Exam topic for eligibility rules JSON structure
	 * @param SQLQuery $q
	 * @param array $rows the rows corresponding to the exam subject
	 * @param number $id
	 * @return string the exam topic JSON structure
	 */
	public static function ExamTopic($q, $rows, $id){
		$topic = self::ExamTopicPHP($q, $rows, $id);
		$json = "";
		if(!isset($topic["id"]))
			$json = "{}";
		else {
			$json .= "{id:".json_encode($id).", name:".json_encode($topic["name"]);
			$json .= ", max_score:".json_encode($topic["max_score"]).", number_questions:".json_encode($topic["number_questions"]).", subjects:";
			if(count($topic["subjects"] > 0)){
				$json .= "[";
				$first_subject = true;
				foreach($topic["subjects"] as $subject_id => $s){
					if(!$first_subject)
						$json .= ", ";
					$first_subject = false;
					$json .= "{id: ".json_encode($subject_id).", name:".json_encode($s["name"]).", max_score:".json_encode($s["max_score"]);
					$json .= ", full_subject:".json_encode($s["full_subject"]);
					$json .= ", parts:[";
					if(count($s["parts"]) > 0){
						$first_part = true;
						foreach($s["parts"] as $part_id => $p){
							if(!$first_part)
								$json .= ", ";
							$first_part = false;
							$json .= "{id:".json_encode($part_id).", name:".json_encode($p["name"]);
							$json .= ", max_score:".json_encode($p["max_score"]).", index:".json_encode($p["index"]).", questions:[]}";
						}
					}
					$json .= "]}";
				}
				$json .= "]";
			} else
				$json .= "[]";
			$json .= "}";
		}
		return $json;
	}
	
	/**
	 * Get a JSON structure containing all the exam topics for eligibility rules set
	 * into the database
	 * @param number (optional) $excluded_id topic id not to retrieve when the query is executed
	 * @return string JSON structure
	 */
	public static function getJsonAllTopics($excluded_id = null){
		$all_ids = SQLQuery::create()
		->select("ExamTopicForEligibilityRule")
		->field("id")
		->executeSingleField();
		$all_ids_after_exclusion = array();
		if(isset($excluded_id)){
			foreach($all_ids as $id){
				if($id != $excluded_id)
					array_push($all_ids_after_exclusion,$id);
			}
		} else
			$all_ids_after_exclusion = $all_ids;
		$json = "[";
		$first = true;
		foreach($all_ids_after_exclusion as $id){
			if(!$first)
				$json .= ", ";
			$first = false;
			$json .= self::ExamTopicForEligibilityRulesFromID($id);
		}
		$json .= "]";
		return $json;
	}
	
	public static function getJsonAllParts(){
		$all_parts = SQLQuery::create()
		->select("ExamSubjectPart")
		->join("ExamSubjectPart","ExamSubject",array("exam_subject" => "id"))
		->field("ExamSubjectPart","id","part_id")
		->field("ExamSubjectPart","max_score","max_score")
		->field("ExamSubjectPart","name","part_name")
		->field("ExamSubjectPart","index","index")
		->field("ExamSubject","id","id")
		->field("ExamSubject","name","name")
		->orderBy("ExamSubject","id")
		->orderBy("ExamSubjectPart","index")
		->execute();
		if($all_parts <> null){
			$json = "[{id:".json_encode($all_parts[0]["id"]);
			$json .= ", name:".json_encode($all_parts[0]["name"]);
			$json .= ", parts:[";
			$first_part = true;
			$current_subject = $all_parts[0]["id"];
			foreach($all_parts as $p){
				if($p["id"] != $current_subject){
					$current_subject = $p["id"];
					$json .= "]}, {id:".json_encode($p["id"]).", name:".json_encode($p["name"]).", parts:[";
					$first_part = true;
				}
				if(!$first_part)
					$json .= ", ";
				$first_part = false;
				$json .= "{id:".json_encode($p["part_id"]).", ";
				$json .= "name:".json_encode($p["part_name"]).", ";
				$json .= "max_score:".json_encode($p["max_score"]).", ";
				$json .= "index:".json_encode($p["index"])."}";
			}
			$json .= "]}]";
			return $json;
		} else
			return "[]";
	}
	
/**
 * Methods about the Eligibility Rules
 */
	/**
	 * Get the EligibilityRule JSON structure from a given ID
	 * @param number $id
	 * @return string
	 */
	public static function EligibilityRuleFromID($id){
		$q = SQLQuery::create()
			->select("EligibilityRule")
			->join("EligibilityRule", "EligibilityRuleExamTopic", array("id" => "eligibility_rule"))
			->whereValue("EligibilityRule", "id", $id);
		self::EligibilityRuleSQL($q);
		$rows = $q->execute();
		if($rows <> null)
			return self::EligibilityRule($q, $rows, $id);
		else
			return "{}";
	}
	
	/**
	 * Retrieve all the data from the EligibilityRule tables
	 * @param SQLQuery $q the query to used to get the data
	 */
	public static function EligibilityRuleSQL(&$q){
		$alias = $q->getTableAlias("EligibilityRule");
		if(!$alias)
			$alias = "EligibilityRule";
		
		$alias_2 = $q->getTableAlias("EligibilityRuleExamTopic");
		if(!$alias_2)
			$alias_2 = "EligibilityRuleExamTopic";
		
		$q
			->field($alias,"id","id")
			->field($alias,"parent","parent")
			->field($alias_2,"exam_topic_for_eligibility_rule","exam_topic_for_eligibility_rule")
			->field($alias_2,"coefficient","coefficient");
	}
	
	/**
	 * Get the EligibilityRule JSON structure
	 * @param SQLQuery $q query used to retrieve the EligibilityRule data
	 * @param array $rows the rows containing the EligibilityRule data
	 * @param number $id the id of the EligibilityRule
	 * @return string the EligibilityRule JSON structure
	 */
	public static function EligibilityRule($q, $rows, $id){
		$r = "{";
		if($rows <> null){
			$r .= "id:".json_encode($id).", parent:".json_encode($rows[0]["parent"]).", topics:[";
			if(isset($rows[0]["exam_topic_for_eligibility_rule"])){//there is at least one topic
				$first = true;
				foreach ($rows as $row){
					if(!$first)
						$r .= ", ";
					$r .= "{coefficient:".json_encode($row["coefficient"]);
					$r .= ", topic:".self::ExamTopicForEligibilityRulesFromID($row["exam_topic_for_eligibility_rule"]);
					$r .= "}";
					$first = false;
				}
			}
			$r .= "]}";
			return $r;
		} else 
			return "{}";
	}
	
	/**
	 * Prepare the array field=>value for the table EligibilityRule, from a JSON ELigibilityRule structure
	 * @param string $json
	 * @return array
	 */
	public static function EligibilityRule2DB($json){
		$a = array(
			"parent" => @$json["parent"]
		);
		if(isset($json["id"]))
			$a["id"] = $json["id"];
		return $a;
	}
	
	/**
	 * Prepare the array field=>value for the table ElibilityRuleExamTopic
	 * @param string $json
	 * @param number|null $eligibility_rule_id if not given, the field exam_topic_for_eligibility_rule shall be manually added
	 * @return array
	 */
	public static function EligibilityRuleTopic2DB($json, $eligibility_rule_id = null){
		$a = array(
			"coefficient" => @$json["coefficient"],
			"exam_topic_for_eligibility_rule" => @$json["topic"]["id"],
		);
		if(isset($eligibility_rule_id))
			$a["eligibility_rule"] = $eligibility_rule_id;
		return $a;
	}
	
	/**
	 * Get a JSON array containing JSON EligibilityRules objects, from all the eligibility rules set into the database
	 * @return string the JSON array
	 */
	public static function getJSONAllEligibilityRules(){
		$q_all_rules = SQLQuery::create()
		->select("EligibilityRule")
		->field("EligibilityRule","id")
		->executeSingleField();
		$all_rules = "";
		if($q_all_rules <> null){
			$first = true;
			$all_rules .= "[";
			foreach($q_all_rules as $id){
				if(!$first)
					$all_rules .= ", ";
				$first = false;
				$all_rules .= SelectionJSON::EligibilityRuleFromID($id);
			}
			$all_rules .= "]";
		} else
			$all_rules .= "[]";
		return $all_rules;
	}
}