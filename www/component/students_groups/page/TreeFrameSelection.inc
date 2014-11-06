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
		return count(self::getBatchesIds());
	}
	
	public static function getBatchId() {
		$ids = self::getBatchesIds();
		if (count($ids) == 1) return $ids[0];
		return null;
	}
	
	public static function getGroupId() {
		return isset($_GET["group"]) ? intval($_GET["group"]) : null;
	}
	
}
?>