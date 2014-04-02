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
			return "{id:-1, geographic_area:null, date:null, number_boys_expected:null, number_boys_real:null, number_girls_expected:null, number_girls_real:null, name:null, partners:[]}";
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
		->field($alias,"geographic_area")
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
			$alias = "InformationSessionPartner";
		$alias_contact_point = $q->getTableAlias("InformationSessionContactPoint");
		if(!$alias_contact_point)
			$alias_contact_point = "InformationSessionContactPoint";
		$q
		->field($alias,"organization","organization")
		->field($alias,"host","host")
		->field($alias,"host_address","host_address")
		->field($alias_contact_point,"people","contact_point");
	}
	
	/**
	 * Get the Information json structure
	 * @param array $row_IS coming from InformationSessionTableDataFromID
	 * @param array $rows_partners coming from PartnersDataFromID method
	 * @return string JSON information session structure
	 */	
	public static function InformationSession($row_IS,$rows_partners){
		$data = "";
		if(isset($row_IS)){
			$data.= "{id:".json_encode($row_IS["id"]).", ";
			$data.= "geographic_area:".json_encode($row_IS["geographic_area"]);
			$data.= ", date:".json_encode($row_IS["date"]);
			$data.= ", number_boys_expected:".json_encode($row_IS["number_boys_expected"]);
			$data.= ", number_girls_expected:".json_encode($row_IS["number_girls_expected"]);
			$data.= ", number_boys_real:".json_encode($row_IS["number_boys_real"]);
			$data.= ", number_girls_real:".json_encode($row_IS["number_girls_real"]);
			$data.= ", name:".json_encode($row_IS["name"]);
			$data.= ", partners:";
			if(isset($rows_partners[0]["organization"])){
				$first = true;
				$current_partner = null;
				$data.= "[";
				foreach($rows_partners as $p){
					if($p["organization"] != $current_partner){
						$name = PNApplication::$instance->contact->getOrganizationName($p["organization"]);
						if(!$first){
							$data.= "]}";
							$data.= ", ";
						}
						$first = false;
						$p["host"] = $p['host'] == 0 ? false : $p["host"];
						$p["host"] = $p['host'] == 1 ? true : $p["host"];
						$data.= "{";
						$data.= "organization:".json_encode($p["organization"]).", ";
						$data.= "organization_name:".json_encode($name).", ";
						$data.= "host:".json_encode($p["host"]).", ";
						$data.= "host_address:".json_encode($p["host_address"]).", ";
						$data.= "contact_points_selected:";
						if(isset($p["contact_point"])){
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
	 * Get a JSON array containing all the informations sessions names
	 * @return string JSON array containing all the existing IS [{id:, name:},...]
	 */
	public static function getJSONAllInformationsSessionsNames(){
		$r = "[";
		$sessions = SQLQuery::create()
		->select("InformationSession")
		->field("InformationSession","id")
		->executeSingleField();
		if($sessions <> null){
			$first = true;
			foreach ($sessions as $id){
				if(!$first)
					$r .= ", ";
				$first = false;
				$name = SQLQuery::create()
					->select("InformationSession")
					->field("InformationSession","name")
					->whereValue("InformationSession", "id", $id)
					->executeSingleValue();
				$r .= "{id:".json_encode($id).", name:".json_encode($name)."}";
			}
		}
		$r .= "]";
		return $r;
	}
	
	/**
	 * Prepare a JSON information session structure to be sent to the database<br/>
	 * This method checks that the IS has a name set. Otherwize, the name by default is the geographic_area_text, from the IS geographic_area attribute
	 * @param string $json the IS structure
	 * @return array fields => values to save
	 */
	public static function InformationSessionTableData2DB($json){
		$a = array(
				"date" => @$json["date"],
				"geographic_area" => @$json["geographic_area"],
				"number_boys_expected" => @$json["number_boys_expected"],
				"number_boys_real" => @$json["number_boys_real"],
				"number_girls_expected" => @$json["number_girls_expected"],
				"number_girls_real" => @$json["number_girls_real"],
		);
		if(isset($json["name"]))
			$a["name"] = $json["name"];
		else if(!isset($json["name"]) && isset($json["geographic_area"])){
			//Make sure that all the informations sessions have at least a name, to be displayed into the datalist
			$a["name"] = PNApplication::$instance->geography->getGeographicAreaText($json["geographic_area"]);
		}
		if(isset($json["id"]))
			$a["id"] = $json["id"];
		return $a;
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
	 * @return array with parameters <ul><li><code>id</code></li><li><code>name</code></li><li><code>subjects</code></li></ul>
	 * Each subjects array contains all the exam subject ids, keys for arrays containing: <ul><li><code>name</code></li><li><code>max_score</code></li><li><code>full_subject</code></li><li><code>parts</code></li></ul>
	 * Each parts array contains all the exam subject parts ids, keys for arrays containing: <ul><li><code>name</code></li><li><code>max_score</code></li><li><code>index</code></li></ul>
	 */
	public static function ExamTopicPHP($q, $rows, $id){
		$full_exams = SQLQuery::create()
			->select("ExamTopicFullSubject")
			->field("exam_subject")
			->whereValue("ExamTopicFullSubject","exam_topic_for_eligibility_rule",$id)
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
	 * @return string JSON array [{topic1},{topic2},...]
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
			->field($alias_2,"expected","expected")
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
					$r .= ", expected:".json_encode($row["expected"]);
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
	 * Prepare the array field=>value for the table EligibilityRuleExamTopic
	 * @param string $json from a ExamTopicForEligibilityRule object
	 * @param number|null $eligibility_rule_id if not given, the field exam_topic_for_eligibility_rule shall be manually added
	 * @return array
	 */
	public static function EligibilityRuleTopic2DB($json, $eligibility_rule_id = null){
		$a = array(
			"coefficient" => @$json["coefficient"],
			"exam_topic_for_eligibility_rule" => @$json["topic"]["id"],
			"expected" => @$json["expected"],
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
	
	
/**Methods about exam centers (EC)*/
	
	/**
	 * Get an exam center data from its ID
	 * @param number $id
	 * @return string ExamCenterData JSON structure
	 */
	public static function ExamCenterFromID($id){
		if($id == -1 || $id == "-1")
			return "{id:-1, geographic_area:null, name:null, partners:[], information_sessions:[],rooms:[]}";
		$EC_row = self::ExamCenterTableDataFromID($id);
		$EC_partners_row = self::ExamCenterPartnersDataFromID($id);
		return self::ExamCenter($EC_row, $EC_partners_row);
	}
	
	/**
	 * Retrieve the data from the ExamCenter table for the given ID 
	 * @param number $id
	 * @return array containing the SQLQuery result
	 */
	public static function ExamCenterTableDataFromID($id){
		$q = SQLQuery::create()
		->select("ExamCenter")
		->whereValue("ExamCenter","id",$id);
		self::ExamCenterSQL($q);
		$row = $q->executeSingleRow();
		return $row;
	}
	
	/**
	 * Prepare a query to retrieve the data from ExamCenter table
	 * @param SQLQuery $q
	 */
	public static function ExamCenterSQL(&$q){
		$alias = $q->getTableAlias("ExamCenter");
		if(!$alias)
			$alias = "ExamCenter";
		$q
		->field($alias,"id")
		->field($alias,"geographic_area")
		->field($alias,"name");
	}
	
	/**
	 * Retrieve the data related to the tables ExamCenterPartner and ExamCenterContactPoint for a given ExamCenter
	 * @param number $id
	 * @return array the result from the query
	 */
	public static function ExamCenterPartnersDataFromID($id){
		$q = SQLQuery::create()
		->select("ExamCenterPartner")
		->join("ExamCenterPartner","ExamCenterContactPoint",array("exam_center"=>"exam_center","organization"=>"organization"))
		->whereValue("ExamCenterPartner","exam_center",$id)
		->orderBy("ExamCenterPartner","organization");
		self::ExamCenterPartnersSQL($q);
		$rows = $q->execute();
		return $rows;
	}
	
	/**
	 * Prepare a SQLQuery to rerieve the data from ExamCenterPartner and ExamCenterContactPoint tables
	 * @param SQLQuery $q
	 */
	public static function ExamCenterPartnersSQL(&$q){
		$alias = $q->getTableAlias("ExamCenterPartner");
		if(!$alias)
			$alias = "ExamCenterPartner";
		$alias_contact_point = $q->getTableAlias("ExamCenterContactPoint");
		if(!$alias_contact_point)
			$alias_contact_point = "ExamCenterContactPoint";
		$q
			->field($alias,"organization","organization")
			->field($alias,"host","host")
			->field($alias,"host_address","host_address")
			->field($alias_contact_point,"people","contact_point");
	}
	
	/**
	 * Retrieve the information sessions linked to a given exam center from ExamCenterInformationSessions table 
	 * @param number $id
	 * @return string ExamCenterISLinked object
	 */
	public static function ExamCenterInformationSessionsFromCenterID($id){
		$q = SQLQuery::create()
			->select("ExamCenterInformationSession")
			->whereValue("ExamCenterInformationSession", "exam_center", $id);
		self::ExamCenterInformationSessionSQL($q);
		$rows = $q->execute();
		return self::ExamCenterInformationSessions($q,$rows);
	}
	
	/**
	 * Prepare a SQLQuery to retrieve the data from table ExamCenterInformationSession
	 * @param SQLQuery $q
	 */
	public static function ExamCenterInformationSessionSQL(&$q){
		$alias = $q->getTableAlias("ExamCenterInformationSession");
			if(!$alias)
				$alias = "ExamCenterInformationSession";
		$q
			->field($alias, "information_session", "information_session")
			->field($alias, "exam_center", "exam_center");
	}
	
	/**
	 * Get a JSON array containing all the informations sessions IDs from the rows retrieved from the database
	 * @param SQLQuery $q the query used to retrieve the informations sessions IDs
	 * @param Array $rows results from the database
	 * @return string JSON array containing the information sessions IDs
	 */
	public static function ExamCenterInformationSessions($q, $rows){
		$data = "[";
		$first = true;
		foreach ($rows as $d){
			if(!$first)
				$data .= ", ";
			$first = false;
			$data .= json_encode($d["information_session"]);
		}
		$data .= "]";
		return $data;
	}
	
	/**
	 * Get the rooms data for a given exam center
	 * @param number $id ID of the exam center
	 * @returns string ExamCenterRooms JSON object
	 */
	public static function ExamCenterRoomsFromCenterID($id){
		$q = SQLQuery::create()
			->select("ExamCenterRoom")
			->whereValue("ExamCenterRoom", "exam_center", $id);
		self::ExamCenterRoomSQL($q);
		$rows = $q->execute();
		return self::ExamCenterRooms($q, $rows);
	}
	
	/**
	 * Retrieve the exam center room data from database
	 * @param SQLQuery $q
	 */
	public static function ExamCenterRoomSQL(&$q){
		$alias = $q->getTableAlias("ExamCenterRoom");
		if(!$alias)
			$alias = "ExamCenterRoom";
		$q
			->field("ExamCenterRoom","id")
			->field("ExamCenterRoom","name")
			->field("ExamCenterRoom","capacity");
	}
	
	/**
	 * Get a ExamCenterRooms object from the data retrieved from database
	 * @param SQLQuery $q the query used to retrieve the data
	 * @param array $rows result from database
	 * @return string JSON ExamCenterRooms object
	 */
	public static function ExamCenterRooms($q, $rows){
		$data = "[";
		$first = true;
		foreach ($rows as $r){
			if(!$first)
				$data.= ", ";
			$first = false;
			$data.= "{id:".json_encode($r["id"]).", ";
			$data.= "name:".json_encode($r["name"]).", ";
			$data.= "capacity:".json_encode($r["capacity"])."}";
		}
		$data.=']';
		return $data;
	}
	
	/**
	 * Get a JSON ExamCenterData from the data retrieved from the database
	 * @param array $row_EC result of the query built by ExamCenterSQL method
	 * @param array $rows_partners result of the query built by ExamCenterPartnersSQL method
	 * @return string JSON ExamCenterData structure
	 */
	public static function ExamCenter($row_EC,$rows_partners){
		$data = "";
		if(isset($row_EC)){
			$IS_linked = self::ExamCenterInformationSessionsFromCenterID($row_EC["id"]);
			$rooms = self::ExamCenterRoomsFromCenterID($row_EC["id"]);
			$data.= "{id:".json_encode($row_EC["id"]).", ";
			$data.= "geographic_area:".json_encode($row_EC["geographic_area"]);
			$data.= ", name:".json_encode($row_EC["name"]);
			$data.= ", partners:";
			if(isset($rows_partners[0]["organization"])){
				$first = true;
				$current_partner = null;
				$data.= "[";
				foreach($rows_partners as $p){
					if($p["organization"] != $current_partner){
						$name = PNApplication::$instance->contact->getOrganizationName($p["organization"]);
						if(!$first){
							$data.= "]}";
							$data.= ", ";
						}
						$first = false;
						$p["host"] = $p['host'] == 0 ? false : $p["host"];
						$p["host"] = $p['host'] == 1 ? true : $p["host"];
						$data.= "{";
						$data.= "organization:".json_encode($p["organization"]).", ";
						$data.= "organization_name:".json_encode($name).", ";
						$data.= "host:".json_encode($p["host"]).", ";
						$data.= "host_address:".json_encode($p["host_address"]).", ";
						$data.= "contact_points_selected:";
						if(isset($p["contact_point"])){
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
			$data .= ",information_sessions:";
			$data .= $IS_linked;
			$data .= ", rooms:".$rooms; 
			$data.= "}";
		} else $data.="{}";
		return $data;
	}
	
	/**
	 * Get a JSON array containing all the exam centers data
	 * @return string JSON array containing all the existing Exam centers data, as ExamCenterData objects
	 */
	public static function getJSONAllExamCenters(){
		$r = "[";
		$centers = SQLQuery::create()
			->select("ExamCenter")
			->field("ExamCenter","id")
			->executeSingleField();
		if($centers <> null){
			$first = true;
			foreach ($centers as $id){
				if(!$first)
					$r .= ", ";
				$first = false;
				$r .= self::ExamCenterFromID($id);
			}
		}
		$r .= "]";
		return $r;
	}
	
	/**
	 * Prepare the ExamCenter table data to be saved into the database
	 * @param array $json ExamCenter object
	 * @param numbe| NULL $id ExamCenter ID, if any
	 * @return array
	 */
	public static function ExamCenter2DB($json, $id){
		$a = array(
			"name" => @$json["name"],
			"geographic_area" => @$json["geographic_area"]
		);
		if(isset($id)){
			$a["id"] = $id;
		}
		return $a;
	}
	
	/**
	 * Prepare the ExamCenterRoom table data to be saved into the database
	 * @param array $json ExamCenterRoom object
	 * @param number | NULL $id ExamCenterRoom ID, if any
	 * @param number | NULL $exam_center the exam center ID, if any
	 * @return array
	 */
	public static function ExamCenterRoom2DB($json, $id, $exam_center){
		$a = array(
			"name" => @$json["name"],
			"capacity" => @$json["capacity"],
			"exam_center" => @$exam_center
		);
		if(isset($id)){
			$a["id"] = $id;
		}
		return $a;
	}
	
	/**
	 * Get all the IS with a host set but not linked to any ExamCenter
	 * @return string JSON array containing ISData objects (but without partners attribute)
	 */
	public static function getJSONAllISWithHostAssignedNotLinkedToEC(){
		$all_free_IS = PNApplication::$instance->selection->getAllISWithHostAssignedNotLinkedToEC();
		$r = "[";
		if($all_free_IS <> null){
			$first = true;
			foreach ($all_free_IS as $data){
				if(!$first)
					$r .= ", ";
				$first = false;
				$r.= "{id:".json_encode($data["id"]).", ";
				$r.= "geographic_area:".json_encode($data["geographic_area"]);
				$r.= ", date:".json_encode($data["date"]);
				$r.= ", number_boys_expected:".json_encode($data["number_boys_expected"]);
				$r.= ", number_girls_expected:".json_encode($data["number_girls_expected"]);
				$r.= ", number_boys_real:".json_encode($data["number_boys_real"]);
				$r.= ", number_girls_real:".json_encode($data["number_girls_real"]);
				$r.= ", name:".json_encode($data["name"])."}";
			}
		}
		$r .= "]";
		return $r;
	}
	
/**
 * Methods about the applicants
 */
	/**
	 * Get an Applicant objet from its ID
	 * @param number $id the people id of the applicant
	 * @return string Applicant JSON object
	 */
	public static function ApplicantFromID($id){
		$q = SQLQuery::create()
			->select("Applicant")
			->whereValue("Applicant", "people", $id);		
		self::ApplicantSQL($q);
		$row = $q->executeSingleRow();
		return self::Applicant($q, $row);
	}
	
	/**
	 * Retrieve the applicant data from the database
	 * @param SQLQuery $q
	 */
	public static function ApplicantSQL(&$q){
		$alias = $q->getTableAlias("Applicant");
		if(!$alias)
			$alias = "Applicant";
		PNApplication::$instance->people->joinPeople($q,"Applicant","people");
		$q
			->field($alias,"applicant_id")
			->field($alias,"exclusion_reason")
			->field($alias,"automatic_exclusion_step")
			->field($alias,"automatic_exclusion_reason")
			->field($alias,"custom_exclusion")
			->field($alias,"excluded")
			->field($alias,"information_session")
			->field($alias,"exam_center")
			->field($alias,"exam_center_room")
			->field($alias,"exam_session");
	}
	
	/**
	 * Get an Applicant object from the database row 
	 * @param SQLQuery $q
	 * @param array $row retrieved from database containing the applicant data
	 * @return string Applicant JSON object
	 */
	public static function Applicant($q, $row){
		$data = "";
		$data .= "{people_id:".json_encode($row["people_id"]);
		$data .= ", first_name:".json_encode($row['first_name']);
		$data .= ", last_name:".json_encode($row["last_name"]);
		$data .= ", middle_name:".json_encode($row["middle_name"]);
		$data .= ", sex:".json_encode($row["sex"]);
		$data .= ", birthdate:".json_encode($row["birthdate"]);
		$data .= ", applicant_id:".json_encode($row["applicant_id"]);
		$data .= ", exclusion_reason:".json_encode($row["exclusion_reason"]);
		$data .= ", automatic_exclusion_step:".json_encode($row["automatic_exclusion_step"]);
		$data .= ", automatic_exclusion_reason:".json_encode($row["automatic_exclusion_reason"]);
		$data .= ", custom_exclusion:".json_encode($row["custom_exclusion"]);
		$data .= ", excluded:".json_encode($row["excluded"]);
		$data .= ", information_session:".json_encode($row["information_session"]);
		$data .= ", exam_center:".json_encode($row["exam_center"]);
		$data .= ", exam_center_room:".json_encode($row["exam_center_room"]);
		$data .= ", exam_session:".json_encode($row["exam_session"]);		
		$data .= "}";
		return $data;
	}
	
/**
 * Exam Sessions methods
 */
	
	public static function ExamSessionFromID($id){
		$q = SQLQuery::create()
			->select("ExamSession")
			->whereValue("ExamSession", "event", $id);
		self::ExamSessionSQL($q);
		$row = $q->executeSingleRow();
		return self::ExamSession($q, $row);
	}
	
	public static function ExamSessionSQL(&$q){
		$alias = $q->getTableAlias("ExamSession");
		if(!$alias)
			$alias = "ExamSession";
		$q
			->field($alias,"event")
			->field($alias,"exam_center");
	}
	
	public static function ExamSession($q, $row){
		require_once 'component/calendar/CalendarJSON.inc';
		$data = "{exam_center:".$row["exam_center"];
		$data .= ", event:";
		$data .= CalendarJSON::CalendarEventFromID($row["event"],PNApplication::$instance->selection->getCalendarId());
		$data .= ", supervisors:";
		$data .= self::ExamSessionSupervisorsFromSessionID($row["event"]);
		$data .= "}";
		return $data;
	}
	
	public static function ExamSessionSupervisorsFromSessionID($id){
		$supervisors = SQLQuery::create()
			->select("ExamSessionSupervisor")
			->field("ExamSessionSupervisor","people")
			->whereValue("ExamSessionSupervisor", "session", $id)
			->executeSingleField();
		$data = "[";
		if($supervisors <> null){
			$first = true;
			foreach ($supervisors as $people_id){
				if(!$first) $data.= ", ";
				$first = false;
				$data.= self::ExamSessionSupervisorFromPeopleID($people_id);
			}
		}
		$data .= "]";
		return $data;
	}
	
	public static function ExamSessionSupervisorFromPeopleID($id){
		$q = SQLQuery::create()
			->select("ExamSessionSupervisor")
			->whereValue("ExamSessionSupervisor", "people", $id);
		self::ExamSessionSupervisorSQL($q);
		$row = $q->executeSingleRow();
		return self::ExamSessionSupervisor($q, $row);
	}
	
	public static function ExamSessionSupervisorSQL(&$q){
		$alias = $q->getTableAlias("ExamSessionSupervisor");
		if($alias == null)
			$alias = "ExamSessionSupervisor";
		$q
		->field("ExamSessionSupervisor","people");
		//Add the people fields
		PNApplication::$instance->people->joinPeople($q, "ExamSessionSupervisor", "people");
	}
	
	public static function ExamSessionSupervisor($q, $row){
		require_once 'component/people/PeopleJSON.inc';
		$r = PeopleJSON::People($q, $row);
		return $r;
	}
	
	public static function ExamSessionsFromExamCenterID($id){
		$sessions = SQLQuery::create()
			->select("ExamSession")
			->field("ExamSession","event")
			->whereValue("ExamSession", "exam_center", $id)
			->executeSingleField();
		$data = "[";
		if($sessions <> null){
			$first = true;
			foreach ($sessions as $ev){
				if(!$first) $data .= ', ';
				$first = false;
				$data .= self::ExamSessionFromID($ev);
			}
		}
		$data .= "]";
		return $data;
	}
	
	public static function ExamCenterRoomsWithRemainingSlotsForSession($session_id){
		//Get the center ID
		$EC_id = SQLQuery::create()
			->select("ExamSession")
			->field("ExamSession","exam_center")
			->whereValue("ExamSession", "event", $session_id)
			->executeSingleValue();
		$data = "{session:".SelectionJSON::ExamSessionFromID($session_id).", ";
		//Get the rooms of the center
		$rooms = SQLQuery::create()
			->select("ExamCenterRoom")
			->whereValue("ExamCenterRoom", "exam_center", $EC_id);
		SelectionJSON::ExamCenterRoomSQL($rooms);
		$rooms = $rooms->execute();
		$data .= "rooms:[";
		$first = true;	
		if ($rooms <> null){
			foreach ($rooms as $room){
				if(!$first) $data.= ", ";
				$first = false;
				$data .= "{id:".$room["id"].", name:".$room["name"];
				$data .= ", remaining:".json_encode(PNApplication::$instance->selection->getRemainingSlotsForExamRoomDuringSession($room["id"], $session_id));
				$data .= "}";
			}
		}
		$data .= "]";
		$data .= "}";
		return $data;
	}
}