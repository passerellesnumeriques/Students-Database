<?php

class students extends Component {
	
	public function getPluginImplementations() {
		require_once("StudentsPeoplePlugin.inc");
		return array(
			new StudentsPeoplePlugin()
		);
	}
	
	public function assign_student_to_specialization($student_people_id, $specialization_id) {
		if ($specialization_id <> null) {
			// check this specialization is available for the batch of this student
			$possible_specializations = SQLQuery::create()
				->select("Student")
				->whereValue("Student", "people", $student_people_id)
				->join("Student", "AcademicPeriod", array("batch"=>"batch"))
				->join("AcademicPeriod", "AcademicPeriodSpecialization", array("id"=>"period"))
				->field("AcademicPeriodSpecialization", "specialization", "specialization")
				->executeSingleField();
			if (!in_array($specialization_id, $possible_specializations)) {
				PNApplication::error("The student does not exist or the specialization is not available for this batch of students");
				return false;
			}
			// check the student is not already assigned to a class with a different specialization
			$different_classes = SQLQuery::create()
				->select("Student")
				->whereValue("Student", "people", $student_people_id)
				->join("Student", "StudentClass", array("people"=>"people"))
				->join("StudentClass", "AcademicClass", array("class"=>"id"))
				->where("`AcademicClass`.`specialization` IS NOT NULL AND `AcademicClass`.`specialization`!='".SQLQuery::escape($specialization_id)."'")
				->execute();
			if (count($different_classes) > 0) {
				PNApplication::error("This student is already assigned to a class with another specialization");
				return false;
			}
		} else {
			// check the student exists
			$student = SQLQuery::create()
				->select("Student")
				->whereValue("Student", "people", $student_people_id)
				->executeSingleRow();
			if ($student == null) {
				PNApplication::error("The student does not exist");
				return false;
			}
			// check the student is not already assigned to a class with a specialization
			$different_classes = SQLQuery::create()
				->select("Student")
				->whereValue("Student", "people", $student_people_id)
				->join("Student", "StudentClass", array("people"=>"people"))
				->join("StudentClass", "AcademicClass", array("class"=>"id"))
				->where("`AcademicClass`.`specialization` IS NOT NULL")
				->execute();
			if (count($different_classes) > 0) {
				PNApplication::error("This student is already assigned to a class with a specialization");
				return false;
			}
		}
		SQLQuery::create()->updateByKey("Student", $student_people_id, array("specialization"=>$specialization_id));
		return true;
	}
	
	public function assign_student_to_class($student_people_id, $class_id, $period_id) {
		// check the class exists
		if ($class_id <> null) {
			$class = SQLQuery::create()->select("AcademicClass")->where("id", $class_id)->executeSingleRow();
			if ($class == null) { PNApplication::error("Invalid class id"); return false; }
			if ($class["period"] <> $period_id) { PNApplication::error("The class is not in the given academic period"); return false; }
		}
		// check the student exists
		$student = SQLQuery::create()->select("Student")->where("people", $student_people_id)->executeSingleRow();
		if ($student == null) { PNApplication::error("Invalid student id"); return false; }
		// check the class specialization is compatible to student specialization
		if ($class_id <> null) {
			if ($class["specialization"] <> $student["specialization"]) { PNApplication::error("Student's specialization does not match with class' specialization"); return false; }
		}
		// get the assignment of this student for this academic period
		$assigned = SQLQuery::create()
			->select("StudentClass")
			->whereValue("StudentClass", "people", $student_people_id)
			->join("StudentClass", "AcademicClass", array("class"=>"id"))
			->whereValue("AcademicClass", "period", $period_id)
			->field("StudentClass", "class", "class")
			->execute();
		// do it
		foreach ($assigned as $a)
			SQLQuery::create()->removeKey("StudentClass", array("people"=>$student_people_id,"class"=>$assigned["class"]));
		if ($class_id <> null) {
			SQLQuery::create()->insert("StudentClass", array("people"=>$student_people_id,"class"=>$class_id));
		}
		return true;
	}
	
}
?>