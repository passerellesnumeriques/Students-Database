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
	 * @no_name_check
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
 * Methods about Exam Subjects
 */
	
	/**
	 * Get all the exam subject names from the database
	 * @param number (optional) $exclude_id id to exclude from the query
	 * @return array array containing all the exam subject names
	 * @no_name_check
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
	
}