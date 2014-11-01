<?php
function generatePublishedTranscript($id, $student_id, $id_suffix = null) {
	$config = SQLQuery::create()->select("PublishedTranscript")->whereValue("PublishedTranscript","id",$id)->executeSingleRow();
	$categories = PNApplication::$instance->curriculum->getSubjectCategories();
	$student_grades = SQLQuery::create()->select("PublishedTranscriptStudentSubjectGrade")->whereValue("PublishedTranscriptStudentSubjectGrade","id",$id)->whereValue("PublishedTranscriptStudentSubjectGrade","people",$student_id)->execute();
	$period = PNApplication::$instance->curriculum->getAcademicPeriodAndBatchPeriod($config["period"]);
	$student = PNApplication::$instance->people->getPeople($student_id);
	$student_general_appreciation = SQLQuery::create()->select("PublishedTranscriptStudentGeneralComment")->whereValue("PublishedTranscriptStudentGeneralComment","id",$id)->whereValue("PublishedTranscriptStudentGeneralComment","people",$student_id)->field("comment")->executeSingleValue();
	if ($student_general_appreciation == null) $student_general_appreciation = " ";
	
	$subjects_ids = array();
	foreach ($student_grades as $sg) array_push($subjects_ids, $sg["subject"]);
	
	if (count($subjects_ids) > 0)
		$subjects = PNApplication::$instance->curriculum->getSubjectsQuery($subjects_ids)->bypassSecurity($student_id == PNApplication::$instance->user_management->people_id)->join("CurriculumSubject","CurriculumSubjectGrading",array("id"=>"subject"))->execute();
	else
		$subjects = array();
	
	$classes = PNApplication::$instance->curriculum->getAcademicClassesForPeriod($config["period"], $config["specialization"]);
	$classes_ids = array();
	foreach ($classes as $cl) array_push($classes_ids, $cl["id"]);
	$classes_students = PNApplication::$instance->students->getStudentsForClasses($classes_ids, false);
	$students_ids = array();
	foreach ($classes_students as $s) array_push($students_ids, $s["people"]);
	$class_grades = SQLQuery::create()->select("PublishedTranscriptStudentSubjectGrade")->whereValue("PublishedTranscriptStudentSubjectGrade","id",$id)->whereIn("PublishedTranscriptStudentSubjectGrade","people",$students_ids)->execute();
	$class_averages = array();
	foreach ($subjects as $subject) {
		$total_grade = 0;
		$nb = 0;
		foreach ($class_grades as $cg) if ($cg["subject"] == $subject["id"]) { $total_grade += floatval($cg["grade"]); $nb++; }
		$class_averages[$subject["id"]] = $nb > 0 ? $total_grade/$nb : null;
	}
	
	generateTranscriptFor($id, $config, $categories, $subjects, $period, $student, $student_grades, $student_general_appreciation, $class_averages, $id_suffix);
} 
function generateTranscript($transcript_id, $period_id, $spe_id = null, $student = null, $student_grades = null, $student_general_appreciation = null) {
	$config = SQLQuery::create()
		->select("TranscriptConfig")
		->whereValue("TranscriptConfig","period",$period_id)
		->whereNull("TranscriptConfig","specialization")
		->executeSingleRow();
	if ($config == null) $config = array();
	if ($spe_id <> null) {
		$config_spe = SQLQuery::create()
			->select("TranscriptConfig")
			->whereValue("TranscriptConfig", "period", $period_id)
			->whereValue("TranscriptConfig","specialization",$spe_id)
			->executeSingleRow();
		if ($config_spe <> null)
			foreach ($config_spe as $col=>$value) if ($value !== null) $config[$col] = $value;
	}
	
	$categories = PNApplication::$instance->curriculum->getSubjectCategories();
	$subjects = SQLQuery::create()
		->select("TranscriptSubjects")
		->whereValue("TranscriptSubjects","period",$period_id);
	if ($spe_id <> null) $subjects->whereValue("TranscriptSubjects","specialization",$spe_id);
	else $subjects->whereNull("TranscriptSubjects","specialization");
	PNApplication::$instance->curriculum->joinSubjects($subjects,"TranscriptSubjects","subject");
	$subjects = $subjects->execute();
	$subjects_ids = array();
	foreach ($subjects as $s) array_push($subjects_ids, $s["id"]);
	$grading = SQLQuery::create()->select("CurriculumSubjectGrading")->whereIn("CurriculumSubjectGrading","subject",$subjects_ids)->execute();
	for ($i = 0; $i < count($subjects); $i++)
		foreach ($grading as $g) if ($g["subject"] == $subjects[$i]["id"]) { $subjects[$i] = array_merge($subjects[$i],$g); break; }
	
	$period = PNApplication::$instance->curriculum->getBatchPeriod($period_id, true);
	
	generateTranscriptFor($transcript_id, $config, $categories, $subjects, $period, $student, $student_grades, $student_general_appreciation);
}
function defaultTranscriptConfig(&$config) {
	if (@$config["subject_category_background"] == null) $config["subject_category_background"] = "#009DE1";
	if (@$config["subject_category_color"] == null) $config["subject_category_color"] = "#FFFFFF";
	if (@$config["subject_category_weight"] == null) $config["subject_category_weight"] = "bold";
	if (@$config["subject_category_size"] == null) $config["subject_category_size"] = "14";
	if (@$config["columns_titles_background"] == null) $config["columns_titles_background"] = "#FFFFFF";
	if (@$config["columns_titles_color"] == null) $config["columns_titles_color"] = "#000000";
	if (@$config["columns_titles_weight"] == null) $config["columns_titles_weight"] = "bold";
	if (@$config["columns_titles_size"] == null) $config["columns_titles_size"] = "12";
	if (@$config["general_comment_title_background"] == null) $config["general_comment_title_background"] = "#009DE1";
	if (@$config["general_comment_title_color"] == null) $config["general_comment_title_color"] = "#FFFFFF";
	if (@$config["general_comment_title_weight"] == null) $config["general_comment_title_weight"] = "bold";
	if (@$config["general_comment_title_size"] == null) $config["general_comment_title_size"] = "14";
	if (@$config["general_comment_size"] == null) $config["general_comment_size"] = "11";
	if (@$config["total_background"] == null) $config["total_background"] = "#009DE1";
	if (@$config["total_color"] == null) $config["total_color"] = "#FFFFFF";
	if (@$config["total_size"] == null) $config["total_size"] = "14";
	if (@$config["grading_system"] == null) {
		$grading_systems = include("component/transcripts/GradingSystems.inc");
		$d = PNApplication::$instance->getDomainDescriptor();
		$config["grading_system"] = $d["transcripts"]["default_grading_system"];
	}
}
function generateTranscriptFor($transcript_id, $config, $categories, $subjects, $period, $student = null, $student_grades = null, $student_general_appreciation = null, $class_averages = null, $id_suffix = null) {
	defaultTranscriptConfig($config);
	
	$grading_systems = include("component/transcripts/GradingSystems.inc");
	if ($student == null)
		$student = array(
			"id"=>0,
			"first_name"=>"John",
			"last_name"=>"Smith",
		);
	if ($student_grades == null) {
		$student_grades = array();
		$count = 0;
		foreach ($subjects as $subject) {
			if (!isset($subject["max_grade"])) continue;
			$grade = array("subject"=>$subject["id"]);
			$max = floatval($subject["max_grade"]);
			$passing = floatval($subject["passing_grade"]);
			switch ($count%4) {
				case 0: $grade["grade"] = $passing+($max-$passing)*0.8; break;
				case 1: $grade["grade"] = $passing+($max-$passing)*0.05; break;
				case 2: $grade["grade"] = $passing*0.8; break;
				case 3: $grade["grade"] = null; break;
			}
			$grade["comment"] = "Here will be the comment from the teacher";
			array_push($student_grades, $grade);
			$count++;
		}
	}
	if ($class_averages == null && @$config["class_average"] == 1) {
		$class_averages = array();
		$count = 0;
		foreach ($subjects as $subject) {
			if (!isset($subject["max_grade"])) continue;
			$max = floatval($subject["max_grade"]);
			$passing = floatval($subject["passing_grade"]);
			switch ($count%4) {
				case 2: $grade = $passing+($max-$passing)*0.82; break;
				case 0: $grade = $passing+($max-$passing)*0.12; break;
				case 1: $grade = $passing*0.7; break;
				case 3: $grade = null; break;
			}
			$class_averages[$subject["id"]] = $grade;
			$count++;
		}
	}
	if ($student_general_appreciation == null) {
		$student_general_appreciation = "Here will be the general appreciation of the student, describing its performance along the academic period. This can be entered in the grades page.";
	}
	
	$design = file_get_contents("component/transcripts/page/design.html");

	$design = str_replace("#ID#", "transcript_container_".$transcript_id."_student_".$student["id"]."_period_".$period["id"].($id_suffix <> null ? $id_suffix : ""), $design);
	
	$design = str_replace("#First Name#", $student["first_name"], $design);
	$design = str_replace("#Last Name#", $student["last_name"], $design);
	
	$design = str_replace("#Academic Period#", $period["name"], $design);
	$design = str_replace("#Academic Period Date#", date("d M Y",strtotime($period["academic_period_start"]))." to ".date("d M Y",strtotime($period["academic_period_end"])), $design);
	
	$sign_info = false;
	if (@$config["location"] <> null) {
		$design = str_replace("#Location#", ($sign_info ? ",<br/>" : "").$config["location"], $design);
		$sign_info = true;
	} else
		$design = str_replace("#Location#", "", $design);
	if (@$config["signatory_name"] <> null) {
		$design = str_replace("#Signatory Name#", ($sign_info ? ",<br/>" : "").$config["signatory_name"], $design);
		$sign_info = true;
	} else
		$design = str_replace("#Signatory Name#", "", $design);
	if (@$config["signatory_title"] <> null) {
		$design = str_replace("#Signatory Title#", ($sign_info ? ",<br/>" : "").$config["signatory_title"], $design);
		$sign_info = true;
	} else
		$design = str_replace("#Signatory Title#", "", $design);
	
	$design = str_replace("#General Appreciation#", toHTML($student_general_appreciation), $design);
	
	processTranscriptCondition($design, "subject_code", @$config["subject_code"] == 1);
	processTranscriptCondition($design, "nb_hours", @$config["nb_hours"] == 1);
	processTranscriptCondition($design, "coefficient", @$config["coefficient"] == 1);
	processTranscriptCondition($design, "class_average", @$config["class_average"] == 1);
	processTranscriptCondition($design, "comment", @$config["comment"] == 1);
	processTranscriptCondition($design, "general_appreciation", @$config["general_appreciation"] == 1);

	$design = str_replace("#SUBJECT_CATEGORY_BACKGROUND#", $config["subject_category_background"], $design);
	$design = str_replace("#SUBJECT_CATEGORY_COLOR#", $config["subject_category_color"], $design);
	$design = str_replace("#SUBJECT_CATEGORY_WEIGHT#", $config["subject_category_weight"], $design);
	$design = str_replace("#SUBJECT_CATEGORY_SIZE#", $config["subject_category_size"], $design);
	$design = str_replace("#COLUMNS_TITLES_BACKGROUND#", $config["columns_titles_background"], $design);
	$design = str_replace("#COLUMNS_TITLES_COLOR#", $config["columns_titles_color"], $design);
	$design = str_replace("#COLUMNS_TITLES_WEIGHT#", $config["columns_titles_weight"], $design);
	$design = str_replace("#COLUMNS_TITLES_SIZE#", $config["columns_titles_size"], $design);
	$design = str_replace("#GENERAL_COMMENT_TITLE_BACKGROUND#", $config["general_comment_title_background"], $design);
	$design = str_replace("#GENERAL_COMMENT_TITLE_COLOR#", $config["general_comment_title_color"], $design);
	$design = str_replace("#GENERAL_COMMENT_TITLE_WEIGHT#", $config["general_comment_title_weight"], $design);
	$design = str_replace("#GENERAL_COMMENT_TITLE_SIZE#", $config["general_comment_title_size"], $design);
	$design = str_replace("#GENERAL_COMMENT_SIZE#", $config["general_comment_size"], $design);
	$design = str_replace("#TOTAL_BACKGROUND#", $config["total_background"], $design);
	$design = str_replace("#TOTAL_COLOR#", $config["total_color"], $design);
	$design = str_replace("#TOTAL_SIZE#", $config["total_size"], $design);
	
	$cats = array();
	$single = array();
	foreach ($categories as $cat) {
		$subjs = array();
		foreach ($subjects as $s) if ($s["category"] == $cat["id"]) array_push($subjs,$s);
		if (count($subjs) == 0) continue;
		if (count($subjs) == 1) array_push($single, array($cat,$subjs[0]));
		else array_push($cats, array($cat,$subjs));
	}
	$i = 0;
	$script_grades = "";
	while (($i = strpos($design, "#SUBJECT_CATEGORY#", $i)) !== false) {
		$j = strpos($design, "#END_SUBJECT_CATEGORY#", $i);
		$content = substr($design,$i+18,$j-$i-18);
		$result_categories = "";
		for ($category_index = 0; $category_index < count($cats); $category_index++) {
			$cat = $cats[$category_index][0];
			$list = $cats[$category_index][1];
			$cat_content = "".$content;
			$cat_content = str_replace("#SUBJECT_CATEGORY_NAME#", toHTML($cat["name"]), $cat_content);
			$cat_content = str_replace("#COUNT(SUBJECTS)#", count($list), $cat_content);
			processTranscriptConditionIndex($cat_content, "SUBJECT_CATEGORY", $category_index, count($cats));
			$total_hours = 0;
			$total_grade = 0;
			$total_coef = 0;
			$total_class_av = 0;
			$passing = 0;
			foreach ($list as $subject) {
				if (!isset($subject["max_grade"])) continue;
				if (!isset($subject["coefficient"])) continue;
				if (intval($subject["coefficient"])==0) continue;
				$student_grade = null;
				foreach ($student_grades as $g) if ($g["subject"] == $subject["id"]) { $student_grade = $g["grade"]; break; }
				if ($student_grade === null) continue;
				$total_grade += (floatval($student_grade)*100/floatval($subject["max_grade"]))*intval($subject["coefficient"]);
				$total_coef += intval($subject["coefficient"]);
				$passing += floatval($subject["passing_grade"])*intval($subject["coefficient"]);
				$hours = $subject["hours"];
				if ($hours !== null) {
					if (@$config["hours_type"] == "Per period") {
						if ($subject["hours_type"] == "Per week") {
							$hours = intval($hours)*(intval($period["academic_period_weeks"])-intval($period["academic_period_weeks_break"]));
						}
					} else {
						if ($subject["hours_type"] == "Per period") {
							$hours = floor(intval($hours)/(intval($period["academic_period_weeks"])-intval($period["academic_period_weeks_break"])));
						}
					}
				}
				$total_hours += intval($hours);
				if (@$config["class_average"] == 1) {
					$class_av = @$class_averages[$subject["id"]];
					$total_class_av += $class_av*$subject["coefficient"];
				}
			}
			$total_grade = $total_coef > 0 ? $total_grade/$total_coef : "null";
			$passing = $total_coef > 0 ? $passing/$total_coef : "null";
			$id = "total_grade_".$cat["id"]."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
			$cat_content = str_replace("#TOTAL_GRADE#", "<div id='$id'>$total_grade</div>", $cat_content);
			$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($total_grade).",false,{max:100,passing:".$passing.",color:false,system:".json_encode($grading_systems[$config["grading_system"]])."});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
			
			while (($k = strpos($cat_content, "#SUBJECT#")) !== false) {
				$l = strpos($cat_content, "#END_SUBJECT#", $k);
				$result_subjects = "";
				for ($subject_index = 0; $subject_index < count($list); $subject_index++) {
					$subject = $list[$subject_index];
					$subj_content = substr($cat_content, $k+9, $l-$k-9);
					processTranscriptConditionIndex($subj_content, "SUBJECT", $subject_index, count($list));
					$subj_content = str_replace("#SUBJECT_CODE#", toHTML($subject["code"]), $subj_content);
					$subj_content = str_replace("#SUBJECT_NAME#", toHTML($subject["name"]), $subj_content);
					$subj_content = str_replace("#SUBJECT_COEFFICIENT#", isset($subject["coefficient"]) && isset($subject["max_grade"]) ? $subject["coefficient"] : "", $subj_content);
					$hours = $subject["hours"];
					if ($hours !== null) {
						if (@$config["hours_type"] == "Per period") {
							if ($subject["hours_type"] == "Per week") {
								$hours = intval($hours)*(intval($period["academic_period_weeks"])-intval($period["academic_period_weeks_break"]));
							}
						} else {
							if ($subject["hours_type"] == "Per period") {
								$hours = floor(intval($hours)/(intval($period["academic_period_weeks"])-intval($period["academic_period_weeks_break"])));
							}
						}
					}
					$subj_content = str_replace("#SUBJECT_HOURS#", $hours, $subj_content);
					$student_grade = null;
					foreach ($student_grades as $g) if ($g["subject"] == $subject["id"]) { $student_grade = $g; break; }
					$subj_content = str_replace("#COMMENT#", toHTML($student_grade["comment"]), $subj_content);
					$id = "grade_subject_".$subject["id"]."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
					$subj_content = str_replace("#GRADE#", "<div id='$id' style='position:absolute;height:100%;top:0px;left:0px;width:100%;'>".$student_grade["grade"]."</div>", $subj_content);
					$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($student_grade["grade"]).",false,{max:".(isset($subject["max_grade"]) ? $subject["max_grade"] : "null").",passing:".(isset($subject["passing_grade"]) ? $subject["passing_grade"] : "null").",system:".json_encode($grading_systems[$config["grading_system"]])."});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
					if (@$config["class_average"] == 1) {
						$class_av = @$class_averages[$subject["id"]];
						$id = "class_average_subject_".$subject["id"]."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
						$subj_content = str_replace("#CLASS_AVERAGE#", "<div id='$id'>$class_av</div>", $subj_content);
						$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($class_av).",false,{max:".(isset($subject["max_grade"]) ? $subject["max_grade"] : "null").",passing:".(isset($subject["passing_grade"]) ? $subject["passing_grade"] : "null").",system:".json_encode($grading_systems[$config["grading_system"]]).",color:false});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
					}
					$result_subjects .= $subj_content;
				}
				$cat_content = str_replace("#TOTAL_HOURS#", $total_hours, $cat_content);
				$cat_content = str_replace("#TOTAL_COEFFICIENT#", $total_coef, $cat_content);
				if (@$config["class_average"] == 1) {
					$total_class_av = $total_coef > 0 ? $total_class_av/$total_coef : "";
					$id = "total_class_average_".$cat["id"]."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
					$cat_content = str_replace("#TOTAL_CLASS_AVERAGE#", "<div id='$id'>$total_class_av</div>", $cat_content);
					$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($total_class_av).",false,{max:100,passing:".$passing.",color:false,system:".json_encode($grading_systems[$config["grading_system"]])."});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
				}				
				$cat_content = substr($cat_content,0,$k).$result_subjects.substr($cat_content,$l+13);
			}
			$result_categories .= $cat_content;
		}
		$design = substr($design, 0, $i).$result_categories.substr($design, $j+22);
	}
	while (($i = strpos($design, "#SINGLE_SUBJECT#", $i)) !== false) {
		$j = strpos($design, "#END_SINGLE_SUBJECT#", $i);
		$content = substr($design,$i+18,$j-$i-18);
		$result_single = "";
		for ($subject_index = 0; $subject_index < count($single); $subject_index++) {
			$cat = $single[$subject_index][0];
			$subject = $single[$subject_index][1];
			$subj_content = "".$content;
			$subj_content = str_replace("#CATEGORY_NAME#", toHTML($cat["name"]), $subj_content);
			$subj_content = str_replace("#SUBJECT_CODE#", toHTML($subject["code"]), $subj_content);
			$subj_content = str_replace("#SUBJECT_NAME#", toHTML($subject["name"]), $subj_content);
			$subj_content = str_replace("#SUBJECT_COEFFICIENT#", isset($subject["coefficient"]) && isset($subject["max_grade"]) ? $subject["coefficient"] : "", $subj_content);
			$hours = $subject["hours"];
			if ($hours !== null) {
				if (@$config["hours_type"] == "Per period") {
					if ($subject["hours_type"] == "Per week") {
						$hours = intval($hours)*(intval($period["academic_period_weeks"])-intval($period["academic_period_weeks_break"]));
					}
				} else {
					if ($subject["hours_type"] == "Per period") {
						$hours = floor(intval($hours)/(intval($period["academic_period_weeks"])-intval($period["academic_period_weeks_break"])));
					}
				}
			}
			$subj_content = str_replace("#SUBJECT_HOURS#", $hours, $subj_content);
			$student_grade = null;
			foreach ($student_grades as $g) if ($g["subject"] == $subject["id"]) { $student_grade = $g; break; }
			$subj_content = str_replace("#COMMENT#", toHTML($student_grade["comment"]), $subj_content);
			$id = "grade_subject_".$subject["id"]."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
			$subj_content = str_replace("#GRADE#", "<div id='$id' style='position:absolute;height:100%;top:0px;left:0px;width:100%;'>".$student_grade["grade"]."</div>", $subj_content);
			$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($student_grade["grade"]).",false,{max:".(isset($subject["max_grade"]) ? $subject["max_grade"] : "null").",passing:".(isset($subject["passing_grade"]) ? $subject["passing_grade"] : "null").",system:".json_encode($grading_systems[$config["grading_system"]])."});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
			if (@$config["class_average"] == 1) {
				$class_av = @$class_averages[$subject["id"]];
				$id = "class_average_subject_".$subject["id"]."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
				$subj_content = str_replace("#CLASS_AVERAGE#", "<div id='$id'>$class_av</div>", $subj_content);
				$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($class_av).",false,{max:".(isset($subject["max_grade"]) ? $subject["max_grade"] : "null").",passing:".(isset($subject["passing_grade"]) ? $subject["passing_grade"] : "null").",system:".json_encode($grading_systems[$config["grading_system"]]).",color:false});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
			}
			$result_single .= $subj_content;
		}
		$design = substr($design, 0, $i).$result_single.substr($design, $j+22);
	}
		
	$total_grade = 0;
	$total_class = 0;
	$total_coef = 0;
	$passing = 0;
	foreach ($subjects as $subject) {
		if (!isset($subject["max_grade"])) continue;
		if (!isset($subject["coefficient"])) continue;
		if (intval($subject["coefficient"])==0) continue;
		$student_grade = null;
		foreach ($student_grades as $g) if ($g["subject"] == $subject["id"]) { $student_grade = $g["grade"]; break; }
		if ($student_grade === null) continue;
		$total_grade += floatval($student_grade)*100/floatval($subject["max_grade"])*intval($subject["coefficient"]);
		$total_coef += intval($subject["coefficient"]);
		$passing += floatval($subject["passing_grade"])*intval($subject["coefficient"]);
		if (@$config["class_average"] == 1) {
			$total_class += floatval($class_averages[$subject["id"]])*intval($subject["coefficient"]);
		}
	}
	$total_grade = $total_coef > 0 ? $total_grade/$total_coef : "null";
	$passing = $total_coef > 0 ? $passing/$total_coef : "null";
	$id = "global_grade"."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
	$design = str_replace("#GLOBAL_GRADE#", "<div id='$id'>$total_grade</div>", $design);
	$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($total_grade).",false,{max:100,passing:".$passing.",color:false,system:".json_encode($grading_systems[$config["grading_system"]])."});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
	if (@$config["class_average"] == 1) {
		$total_class = $total_coef > 0 ? $total_class/$total_coef : "null";
		$id = "global_class_average_grade"."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
		$design = str_replace("#GLOBAL_CLASS_AVERAGE_GRADE#", "<div id='$id'>$total_class</div>", $design);
		$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($total_class).",false,{max:100,passing:".$passing.",color:false,system:".json_encode($grading_systems[$config["grading_system"]])."});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
	}
	
	echo $design;
	if ($script_grades <> "") {
		echo "<script type='text/javascript'>\n";
		echo "require([['typed_field.js','field_grade.js']],function(){\n";
		echo "var e,f;\n";
		echo $script_grades;
		echo "});\n";
		echo "</script>\n";
	}
}

function processTranscriptCondition(&$content, $condition_name, $condition_result) {
	while (($i = strpos($content, "#IF:".$condition_name."#")) !== false) {
		// TODO inner IF
		$j = strpos($content, "#ENDIF#",$i);
		if ($j === false) break;
		if ($condition_result) {
			$content = substr($content,0,$i).substr($content,$i+5+strlen($condition_name),$j-($i+5+strlen($condition_name))).substr($content,$j+7);
		} else {
			$content = substr($content,0,$i).substr($content,$j+7);
		}
	}
}
function processTranscriptConditionIndex(&$content, $condition_name, $current_index, $number) {
	while (($i = strpos($content, "#IF:".$condition_name."[")) !== false) {
		// TODO inner IF
		$i2 = strpos($content, "]#", $i);
		if ($i2 === false) break;
		$cd_index = substr($content,$i+5+strlen($condition_name),$i2-($i+5+strlen($condition_name)));
		$j = strpos($content, "#ENDIF#",$i2);
		if ($j === false) break;
		if ($cd_index == $current_index || ($cd_index == "LAST" && $current_index == $number-1)) {
			$content = substr($content,0,$i).substr($content,$i+7+strlen($condition_name)+strlen($cd_index),$j-($i+7+strlen($condition_name)+strlen($cd_index))).substr($content,$j+7);
		} else {
			$content = substr($content,0,$i).substr($content,$j+7);
		}
	}
}
?>