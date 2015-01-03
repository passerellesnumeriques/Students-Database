<?php 
class TreeFrameSelection {
	
	private static $batches = null;
	private static $batches_ids = null;
	
	public static function getBatchesIds() {
		if (self::$batches_ids == null) {
			if (isset($_GET["batches"])) {
				if ($_GET["batches"] == "current") {
					self::$batches = PNApplication::$instance->curriculum->getCurrentBatches();
					self::$batches_ids = array();
					foreach (self::$batches as $b) array_push(self::$batches_ids, intval($b["id"]));
				} else if ($_GET["batches"] == "alumni") {
					self::$batches = PNApplication::$instance->curriculum->getAlumniBatches();
					self::$batches_ids = array();
					foreach (self::$batches as $b) array_push(self::$batches_ids, intval($b["id"]));
				}
			} else if (isset($_GET["batch"])) {
				self::$batches_ids = array(intval($_GET["batch"]));
			}
		}
		return self::$batches_ids;
	}
	
	public static function isSingleBatch() {
		return count(self::getBatchesIds()) == 1;
	}
	public static function isAllBatches() {
		return @$_GET["batches"] == null && @$_GET["batch"] == null;
	}
	public static function isCurrentBatches() {
		return @$_GET["batches"] == "current";
	}
	public static function isAlumniBatches() {
		return @$_GET["batches"] == "alumni";
	}
	
	public static function getBatchId() {
		$ids = self::getBatchesIds();
		if (count($ids) == 1) return $ids[0];
		return null;
	}
	
	private static $batch = null;
	public static function getBatchName() {
		if (self::$batch == null) {
			$id = self::getBatchId();
			if ($id == null) return null;
			self::$batch = PNApplication::$instance->curriculum->getBatch($id);
		}
		return self::$batch["name"];
	}
	
	public static function getPeriodId() {
		return isset($_GET["period"]) ? intval($_GET["period"]) : null;
	}
	
	public static function getSpecializationId() {
		return isset($_GET["sepcialization"]) ? intval($_GET["specialization"]) : null;
	}
	
	public static function getGroupTypeId() {
		return isset($_GET["group_type"]) ? intval($_GET["group_type"]) : 1;
	}
	
	public static function getGroupId() {
		return isset($_GET["group"]) ? intval($_GET["group"]) : null;
	}
	
	private static $all_groups_ids = null;
	public static function getGroupsIdsFromParentGroup() {
		if (self::$all_groups_ids <> null) return self::$all_groups_ids;
		$group_id = @$_GET["group"];
		if ($group_id == null) return null;
		self::$all_groups_ids = array($group_id);
		self::searchSubGroups(array($group_id));
		return self::$all_groups_ids;
	}
	private static function searchSubGroups($parents) {
		$sub_groups = SQLQuery::create()->bypassSecurity()->select("StudentsGroup")->whereIn("StudentsGroup","parent",$parents)->field("id")->executeSingleField();
		if (count($sub_groups) == 0) return;
		self::$all_groups_ids = array_merge(self::$all_groups_ids, $sub_groups);
		self::searchSubGroups($sub_groups);
	}
	
	private static $students_ids = null;
	public static function getStudentsIds() {
		if (self::$students_ids <> null) return self::$students_ids;
		// only a group is selected
		$group_id = self::getGroupId();
		if ($group_id <> null) {
			$groups_ids = self::getGroupsIdsFromParentGroup();
			return self::$students_ids = SQLQuery::create()->select("StudentGroup")->whereIn("StudentGroup","group",$groups_ids)->field("people")->executeSingleField();
		}
		
		$period_id = self::getPeriodId();
		if ($period_id <> null) {
			// we are in a specific period
			$spe_id = self::getSpecializationId();
			$group_type_id = self::getGroupTypeId();
			if ($spe_id <> null)
				$groups = PNApplication::$instance->students_groups->getGroups($group_type_id, $period_id, $spe_id);
			else
				$groups = PNApplication::$instance->students_groups->getGroups($group_type_id, $period_id, false);
			$groups_ids = array();
			foreach ($groups as $g) array_push($groups_ids, $g["id"]);
			return self::$students_ids = SQLQuery::create()->select("StudentGroup")->whereIn("StudentGroup","group",$groups_ids)->field("people")->executeSingleField();
		}

		$batches_ids = self::getBatchesIds();
		if ($batches_ids <> null) {
			$q = PNApplication::$instance->students->getStudentsQueryForBatches($batches_ids);
			$q->field("people");
			return self::$students_ids = $q->field("people")->executeSingleField();
		}
		
		// all students
		$q = PNApplication::$instance->students->getStudentsQueryForBatches();
		$q->field("people");
		return self::$students_ids = $q->field("people")->executeSingleField();
	}
	
}
?>