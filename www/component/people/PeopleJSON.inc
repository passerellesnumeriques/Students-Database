<?php 
/**
 * Provides functionalities to convert structures between JSON and database
 */
class PeopleJSON {
	/**
	 * Build a People JSON structure from the given people id
	 * @param integer $id
	 * @return NULL
	 */
	public static function PeopleFromID($id){
		$q = SQLQuery::create()
			->select("People")
			->whereValue("People", "id", $id);
		PNApplication::$instance->storage->joinRevision($q, "People", "picture", "picture_revision");
		self::PeopleSQL($q);
		$row = $q->executeSingleRow();
		if($row <> null)
			return self::People($q, $row);
		else 
			return null;
	}
	
	/**
	 * Prepares the given given SQLQuery to retrieve a People
	 * To be used with People method
	 * @param SQLQuery $q
	 */
	public static function PeopleSQL(&$q){
		$alias = $q->getTableAlias("People");
		if($alias == null)
			$alias = "People";
		$q
			->field($alias,"id","people_id")
			->field($alias,"first_name","first_name")
			->field($alias,"last_name","last_name")
			->field($alias,"middle_name","middle_name")
			->field($alias,"sex","sex")
			->field($alias,"birth","birthdate")
			->field($alias,"picture","picture_id")
			->field($alias,"types","people_types");
		$alias = $q->getTableAlias("Storage");
		if($alias == null) {
			PNApplication::$instance->storage->joinRevision($q, "People", "picture", "picture_revision");
		}
	}
	
	/**
	 * Return a People JSON Object, compatible with the People2DB method
	 * @param SQLQuery $q
	 * @param Array $row result of executeSingleRow from $q
	 * @return string
	 */
	public static function People($q, $row){
		$data = "";
		$data .= "{id:".json_encode($row["people_id"]);
		$data .= ",first_name:".json_encode($row['first_name']);
		$data .= ",last_name:".json_encode($row["last_name"]);
		$data .= ",middle_name:".json_encode($row["middle_name"]);
		$data .= ",sex:".json_encode($row["sex"]);
		$data .= ",birthdate:".json_encode($row["birthdate"]);
		$data .= ",picture_id:".json_encode($row["picture_id"]);
		$data .= ",picture_revision:".json_encode(@$row["picture_revision"]);
		$data .= ",can_edit:".(PNApplication::$instance->people->canModifyTypes($row["people_types"]) ? "true" : "false");
		$data .= "}";
		return $data;
	}
	/**
	 * Return a list of People JSON Object, compatible with the People2DB method
	 * @param SQLQuery $q
	 * @param Array $rows result of execute from $q
	 * @return string
	 */
	public static function Peoples($q, $rows){
		$data = "[";
		$first = true;
		foreach ($rows as $row) {
			if ($first) $first = false; else $data .= ",";
			$data .= self::People($q, $row);
		}
		$data .= "]";
		return $data;
	}
	
	/**
	 * Convert an array coming from a JSON structure, into an array ready to use with database (insert or update)
	 * @param People JSON $json
	 * @return array the associative array with column name from the database
	 */
	public static function People2DB($json){
		$a = array(
			"first_name" => @$json['first_name'],
			"last_name" => @$json['last_name'],
			"middle_name" => @$json['middle_name'],
			"sex" => @$json['sex'],
			"birthdate" => @$json['birthdate'],
		);
		if(isset($json["id"]))
			$a["id"] = $json["id"]; //This is an update
		return $a;
	}
	
}
?>