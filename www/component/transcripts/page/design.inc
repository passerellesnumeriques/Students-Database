<?php
function generatePublishedTranscript($id, $student_id, $id_suffix = null) {
	$config = SQLQuery::create()->select("PublishedTranscript")->whereValue("PublishedTranscript","id",$id)->executeSingleRow();
	$categories = PNApplication::$instance->curriculum->getSubjectCategories();
	$student_grades = SQLQuery::create()->select("PublishedTranscriptStudentSubjectGrade")->whereValue("PublishedTranscriptStudentSubjectGrade","id",$id)->whereValue("PublishedTranscriptStudentSubjectGrade","people",$student_id)->execute();
	$period = PNApplication::$instance->curriculum->getAcademicPeriodAndBatchPeriod($config["period"]);
	$batch = PNApplication::$instance->curriculum->getBatch($period["batch"]);
	$student = PNApplication::$instance->people->getPeople($student_id);
	$student_general_appreciation = SQLQuery::create()->select("PublishedTranscriptStudentGeneralComment")->whereValue("PublishedTranscriptStudentGeneralComment","id",$id)->whereValue("PublishedTranscriptStudentGeneralComment","people",$student_id)->field("comment")->executeSingleValue();
	if ($student_general_appreciation == null) $student_general_appreciation = " ";
	
	$q = SQLQuery::create()->bypassSecurity($student_id == PNApplication::$instance->user_management->people_id)->select("PublishedTranscriptSubject")->whereValue("PublishedTranscriptSubject","transcript",$id);
	$q->join("PublishedTranscriptSubject","CurriculumSubjectGrading",array("subject"=>"subject"));
	PNApplication::$instance->curriculum->joinSubjects($q, "PublishedTranscriptSubject", "subject");
	$subjects = $q->execute();
	
	generateTranscriptFor($id, $config, $categories, $subjects, $batch, $period, $student, $student_grades, $student_general_appreciation, $id_suffix);
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
	$batch = PNApplication::$instance->curriculum->getBatch($period["batch"]);
	
	generateTranscriptFor($transcript_id, $config, $categories, $subjects, $batch, $period, $student, $student_grades, $student_general_appreciation);
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
function generateTranscriptFor($transcript_id, $config, $categories, $subjects, $batch, $period, $student = null, $student_grades = null, $student_general_appreciation = null, $id_suffix = null) {
	defaultTranscriptConfig($config);
	
	$grading_systems = include("component/transcripts/GradingSystems.inc");
	if ($student == null) {
		$student = array(
			"id"=>0,
			"first_name"=>"John",
			"last_name"=>"Smith",
		);
		$class_name = "SNA B";
	} else {
		$group = PNApplication::$instance->students_groups->getStudentGroup($student["id"], $period["id"], 1);
		$class_name = $group["name"];
	}
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
	if ($student_general_appreciation == null) {
		$student_general_appreciation = "Here will be the general appreciation of the student, describing its performance along the academic period. This can be entered in the grades page.";
	}
	
	$design = file_get_contents("component/transcripts/page/design.html");

	$design = str_replace("#ID#", "transcript_container_".$transcript_id."_student_".$student["id"]."_period_".$period["id"].($id_suffix <> null ? $id_suffix : ""), $design);
	
	$design = str_replace("#First Name#", $student["first_name"], $design);
	$design = str_replace("#Last Name#", $student["last_name"], $design);
	
	$design = str_replace("#Batch Name#", $batch["name"], $design);
	$design = str_replace("#Class Name#", $class_name, $design);
	
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
	processTranscriptCondition($design, "batch_average", @$config["batch_average"] == 1);
	processTranscriptCondition($design, "batch_lowest", @$config["batch_lowest"] == 1);
	processTranscriptCondition($design, "batch_highest", @$config["batch_highest"] == 1);
	processTranscriptCondition($design, "class_average", @$config["class_average"] == 1);
	processTranscriptCondition($design, "class_lowest", @$config["class_lowest"] == 1);
	processTranscriptCondition($design, "class_highest", @$config["class_highest"] == 1);
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
	$global_total_class_av = 0;
	$global_total_class_coef = 0;
	$global_total_batch_av = 0;
	$global_total_batch_coef = 0;
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
			$passing = 0;
			foreach ($list as $subject) {
				if (!isset($subject["max_grade"])) continue;
				if (!isset($subject["coefficient"])) continue;
				if (intval($subject["coefficient"])==0) continue;
				$student_grade = null;
				foreach ($student_grades as $g) if ($g["subject"] == $subject["id"]) { $student_grade = $g["grade"]; break; }
				if ($student_grade === null) continue;
				$total_grade += floatval($student_grade)*intval($subject["coefficient"]);
				$total_coef += intval($subject["coefficient"]);
				$pass = floatval($subject["passing_grade"])*100/floatval($subject["max_grade"]); // make it as %
				$passing += $pass*intval($subject["coefficient"]);
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
			}
			$total_grade = $total_coef > 0 ? $total_grade/$total_coef : "null";
			$passing = $total_coef > 0 ? $passing/$total_coef : "null";
			$id = "total_grade_".$cat["id"]."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
			$cat_content = str_replace("#TOTAL_GRADE#", "<div id='$id'>$total_grade</div>", $cat_content);
			$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($total_grade).",false,{max:100,passing:".$passing.",color:false,system:".json_encode($grading_systems[$config["grading_system"]])."});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
			
			while (($k = strpos($cat_content, "#SUBJECT#")) !== false) {
				$l = strpos($cat_content, "#END_SUBJECT#", $k);
				$result_subjects = "";
				$total_batch_av = 0;
				$total_class_av = 0;
				$total_batch_coef = 0;
				$total_class_coef = 0;
				for ($subject_index = 0; $subject_index < count($list); $subject_index++) {
					$subject = $list[$subject_index];
					$subj_content = substr($cat_content, $k+9, $l-$k-9);
					processTranscriptConditionIndex($subj_content, "SUBJECT", $subject_index, count($list));
					$subj_content = generateSubjectSection($cat, $subject, $subj_content, $config, $period, $student_grades, $student, $transcript_id, $id_suffix, $script_grades, $grading_systems, $total_class_av, $total_batch_av, $total_class_coef, $total_batch_coef);
					$result_subjects .= $subj_content;
				}
				$cat_content = str_replace("#TOTAL_HOURS#", $total_hours, $cat_content);
				$cat_content = str_replace("#TOTAL_COEFFICIENT#", $total_coef, $cat_content);
				if (@$config["class_average"] == 1) {
					$global_total_class_av += $total_class_av;
					$global_total_class_coef += $total_class_coef;
					$total_class_av = $total_class_coef > 0 ? $total_class_av/$total_class_coef : "";
					$id = "total_class_average_".$cat["id"]."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
					$cat_content = str_replace("#TOTAL_CLASS_AVERAGE#", "<div id='$id'>$total_class_av</div>", $cat_content);
					$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($total_class_av).",false,{max:100,passing:".$passing.",color:false,system:".json_encode($grading_systems[$config["grading_system"]])."});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
				}				
				if (@$config["batch_average"] == 1) {
					$global_total_batch_av += $total_batch_av;
					$global_total_batch_coef += $total_batch_coef;
					$total_batch_av = $total_batch_coef > 0 ? $total_batch_av/$total_batch_coef : "";
					$id = "total_batch_average_".$cat["id"]."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
					$cat_content = str_replace("#TOTAL_BATCH_AVERAGE#", "<div id='$id'>$total_batch_av</div>", $cat_content);
					$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($total_batch_av).",false,{max:100,passing:".$passing.",color:false,system:".json_encode($grading_systems[$config["grading_system"]])."});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
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
			$total_class_av = 0;
			$total_batch_av = 0;
			$total_batch_coef = 0;
			$total_class_coef = 0;
			$subj_content = generateSubjectSection($cat, $subject, "".$content, $config, $period, $student_grades, $student, $transcript_id, $id_suffix, $script_grades, $grading_systems, $total_class_av, $total_batch_av, $total_class_coef, $total_batch_coef);
			$global_total_class_av += $total_class_av;
			$global_total_class_coef += $total_class_coef;
			$global_total_batch_av += $total_batch_av;
			$global_total_batch_coef += $total_batch_coef;
			$result_single .= $subj_content;
		}
		$design = substr($design, 0, $i).$result_single.substr($design, $j+22);
	}
		
	$total_grade = 0;
	$total_coef = 0;
	$passing = 0;
	foreach ($subjects as $subject) {
		if (!isset($subject["max_grade"])) continue;
		if (!isset($subject["coefficient"])) continue;
		if (intval($subject["coefficient"])==0) continue;
		$student_grade = null;
		foreach ($student_grades as $g) if ($g["subject"] == $subject["id"]) { $student_grade = $g["grade"]; break; }
		if ($student_grade === null) continue;
		$total_grade += floatval($student_grade)*intval($subject["coefficient"]);
		$total_coef += intval($subject["coefficient"]);
		$pass = floatval($subject["passing_grade"])*100/floatval($subject["max_grade"]); // make it as %
		$passing += $pass*intval($subject["coefficient"]);
	}
	$total_grade = $total_coef > 0 ? $total_grade/$total_coef : "null";
	$passing = $total_coef > 0 ? $passing/$total_coef : "null";
	$id = "global_grade"."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
	$design = str_replace("#GLOBAL_GRADE#", "<div id='$id'>$total_grade</div>", $design);
	$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($total_grade).",false,{max:100,passing:".$passing.",color:false,system:".json_encode($grading_systems[$config["grading_system"]])."});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
	if (@$config["class_average"] == 1) {
		$global_total_class_av = $global_total_class_coef > 0 ? $global_total_class_av/$global_total_class_coef : "null";
		$id = "global_class_average_grade"."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
		$design = str_replace("#GLOBAL_CLASS_AVERAGE_GRADE#", "<div id='$id'>$global_total_class_av</div>", $design);
		$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($global_total_class_av).",false,{max:100,passing:".$passing.",color:false,system:".json_encode($grading_systems[$config["grading_system"]])."});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
	}
	if (@$config["batch_average"] == 1) {
		$global_total_batch_av = $global_total_batch_coef > 0 ? $global_total_batch_av/$global_total_batch_coef : "null";
		$id = "global_batch_average_grade"."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
		$design = str_replace("#GLOBAL_BATCH_AVERAGE_GRADE#", "<div id='$id'>$global_total_batch_av</div>", $design);
		$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($global_total_batch_av).",false,{max:100,passing:".$passing.",color:false,system:".json_encode($grading_systems[$config["grading_system"]])."});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
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
	
	if (@$config["grades_details"] == 1)
		generateGradesDetails($transcript_id, $subjects, $student, $batch, $period, $class_name, $student_grades, $id_suffix, $config, $grading_systems);
}

function generateSubjectSection($cat, $subject, $subj_content, $config, $period, $student_grades, $student, $transcript_id, $id_suffix, &$script_grades, $grading_systems, &$total_class_av, &$total_batch_av, &$total_class_coef, &$total_batch_coef) {
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
	if (@$config["class_average"] == 1 || @$config["class_lowest"] == 1 || @$config["class_highest"] == 1) {
		if (substr($transcript_id,0,1) == "_") {
			// fake
			$av = isset($subject["max_grade"]) && isset($subject["passing_grade"]) ? floatval($subject["passing_grade"])+(floatval($subject["max_grade"])-floatval($subject["passing_grade"]))*(rand() / getrandmax()) : null;
			if ($av == null) {
				$low = null;
				$high = null;
			} else {
				$low = $av-floatval($subject["passing_grade"])*(rand() / getrandmax());
				if ($low < 0) $low = 0;
				$high = $av+floatval($subject["passing_grade"])*(rand() / getrandmax());
				if ($high > floatval($subject["max_grade"])) $high = floatval($subject["max_grade"]);
			}
		} else {
			$info = PNApplication::$instance->teaching->getStudentGroupingInfoForSubject($student["id"], $subject["id"]);
			if ($info == null) {
				// if no grouping, get all classes for the batch period and specialization
				$groups = PNApplication::$instance->students_groups->getGroups(1, $subject["period"], $subject["specialization"] <> null ? $subject["specialization"] : false);
				$groups_ids = array();
				foreach ($groups as $g) array_push($groups_ids, $g["id"]);
			} else {
				$groups_ids = array($info["group"]["id"]);
				foreach ($info["groups_together"] as $g) array_push($groups_ids, $g["id"]);
			}
			if (count($groups_ids) == 0) {
				$av = $low = $high = null;
			} else {
				$q = PNApplication::$instance->students_groups->getStudentsQueryForGroups($groups_ids,false);
				$q->groupBy("StudentGroup","people");
				$q->join("StudentGroup","PublishedTranscriptStudentSubjectGrade",array("people"=>"people",null=>array("id"=>$transcript_id,"subject"=>$subject["id"])));
				$q->field("PublishedTranscriptStudentSubjectGrade","grade");
				$q->whereNotNull("PublishedTranscriptStudentSubjectGrade","grade");
				$grades = $q->executeSingleField();
				if (count($grades) == 0) {
					$av = $low = $high = null;
				} else {
					$total = 0;
					$high = null;
					$low = null;
					foreach ($grades as $grade) {
						$g = floatval($grade);
						if ($high === null) $high = $low = $g;
						else {
							if ($g > $high) $high = $g;
							if ($g < $low) $low = $g; 
						}
						$total += $g;
					}
					$av = $total/count($grades);
				}
			}
		}
		if (@$config["class_average"] == 1) {
			if ($av <> null) {
				$total_class_av += $av*$subject["coefficient"];
				$total_class_coef += $subject["coefficient"];
			}
			$id = "class_average_subject_".$subject["id"]."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
			$subj_content = str_replace("#CLASS_AVERAGE#", "<div id='$id'>$av</div>", $subj_content);
			$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($av).",false,{max:".(isset($subject["max_grade"]) ? $subject["max_grade"] : "null").",passing:".(isset($subject["passing_grade"]) ? $subject["passing_grade"] : "null").",system:".json_encode($grading_systems[$config["grading_system"]]).",color:false});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
		}
		if (@$config["class_lowest"] == 1) {
			$id = "class_lowest_subject_".$subject["id"]."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
			$subj_content = str_replace("#CLASS_LOWEST#", "<div id='$id'>$low</div>", $subj_content);
			$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($low).",false,{max:".(isset($subject["max_grade"]) ? $subject["max_grade"] : "null").",passing:".(isset($subject["passing_grade"]) ? $subject["passing_grade"] : "null").",system:".json_encode($grading_systems[$config["grading_system"]]).",color:false});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
		}
		if (@$config["class_highest"] == 1) {
			$id = "class_highest_subject_".$subject["id"]."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
			$subj_content = str_replace("#CLASS_HIGHEST#", "<div id='$id'>$high</div>", $subj_content);
			$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($high).",false,{max:".(isset($subject["max_grade"]) ? $subject["max_grade"] : "null").",passing:".(isset($subject["passing_grade"]) ? $subject["passing_grade"] : "null").",system:".json_encode($grading_systems[$config["grading_system"]]).",color:false});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
		}
	}
	if (@$config["batch_average"] == 1 || @$config["batch_lowest"] == 1 || @$config["batch_highest"] == 1) {
		if (substr($transcript_id,0,1) == "_") {
			// fake
			$av = isset($subject["max_grade"]) && isset($subject["passing_grade"]) ? floatval($subject["passing_grade"])+(floatval($subject["max_grade"])-floatval($subject["passing_grade"]))*(rand() / getrandmax()) : null;
			if ($av == null) {
				$low = null;
				$high = null;
			} else {
				$low = $av-floatval($subject["passing_grade"])*(rand() / getrandmax());
				if ($low < 0) $low = 0;
				$high = $av+floatval($subject["passing_grade"])*(rand() / getrandmax());
				if ($high > floatval($subject["max_grade"])) $high = floatval($subject["max_grade"]);
			}
		} else {
			$groups = PNApplication::$instance->students_groups->getGroups(1, $subject["period"], $subject["specialization"] <> null ? $subject["specialization"] : false);
			if (count($groups) == 0) {
				$av = $low = $high = null;
			} else {
				$groups_ids = array();
				foreach ($groups as $g) array_push($groups_ids, $g["id"]);
				$q = PNApplication::$instance->students_groups->getStudentsQueryForGroups($groups_ids,false);
				$q->groupBy("StudentGroup","people");
				$q->join("StudentGroup","PublishedTranscriptStudentSubjectGrade",array("people"=>"people",null=>array("id"=>$transcript_id,"subject"=>$subject["id"])));
				$q->field("PublishedTranscriptStudentSubjectGrade","grade");
				$q->whereNotNull("PublishedTranscriptStudentSubjectGrade","grade");
				$grades = $q->executeSingleField();
				if (count($grades) == 0) {
					$av = $low = $high = null;
				} else {
					$total = 0;
					$high = null;
					$low = null;
					foreach ($grades as $grade) {
						$g = floatval($grade);
						if ($high === null) $high = $low = $g;
						else {
							if ($g > $high) $high = $g;
							if ($g < $low) $low = $g; 
						}
						$total += $g;
					}
					$av = $total/count($grades);
				}
			}
		}
		if (@$config["batch_average"] == 1) {
			if ($av <> null) {
				$total_batch_av += $av*$subject["coefficient"];
				$total_batch_coef += $subject["coefficient"];
			}
			$id = "batch_average_subject_".$subject["id"]."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
			$subj_content = str_replace("#BATCH_AVERAGE#", "<div id='$id'>$av</div>", $subj_content);
			$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($av).",false,{max:".(isset($subject["max_grade"]) ? $subject["max_grade"] : "null").",passing:".(isset($subject["passing_grade"]) ? $subject["passing_grade"] : "null").",system:".json_encode($grading_systems[$config["grading_system"]]).",color:false});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
		}
		if (@$config["batch_lowest"] == 1) {
			$id = "batch_lowest_subject_".$subject["id"]."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
			$subj_content = str_replace("#BATCH_LOWEST#", "<div id='$id'>$low</div>", $subj_content);
			$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($low).",false,{max:".(isset($subject["max_grade"]) ? $subject["max_grade"] : "null").",passing:".(isset($subject["passing_grade"]) ? $subject["passing_grade"] : "null").",system:".json_encode($grading_systems[$config["grading_system"]]).",color:false});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
		}
		if (@$config["batch_highest"] == 1) {
			$id = "batch_highest_subject_".$subject["id"]."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
			$subj_content = str_replace("#BATCH_HIGHEST#", "<div id='$id'>$high</div>", $subj_content);
			$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($high).",false,{max:".(isset($subject["max_grade"]) ? $subject["max_grade"] : "null").",passing:".(isset($subject["passing_grade"]) ? $subject["passing_grade"] : "null").",system:".json_encode($grading_systems[$config["grading_system"]]).",color:false});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
		}
	}
	return $subj_content;
}

function generateGradesDetails($transcript_id, $all_subjects, $student, $batch, $period, $class_name, $student_grades, $id_suffix, $config, $grading_systems) {
	$subjects = array();
	foreach ($all_subjects as $s) {
		if (substr($transcript_id,0,1) == "_") {
			// fake
			$types = SQLQuery::create()->select("CurriculumSubjectEvaluationType")->whereValue("CurriculumSubjectEvaluationType","subject",$s["id"])->execute();
			if (count($types) == 0) continue;
			$subject = $s;
			$subject["types"] = $types;
			$types_ids = array();
			foreach ($types as $t) array_push($types_ids, $t["id"]);
			$evaluations = SQLQuery::create()->select("CurriculumSubjectEvaluation")->whereIn("CurriculumSubjectEvaluation","type",$types_ids)->execute();
			$student_grade = 0;
			$student_grade_coef = 0;
			$good = rand(0,2);
			for ($i = 0; $i < count($subject["types"]); $i++) {
				$subject["types"][$i]["evaluations"] = array();
				$total = 0;
				$total_coef = 0;
				foreach ($evaluations as $e) {
					switch ($good) {
						case 0: $g = rand(floatval($subject["passing_grade"])+0.2*(floatval($subject["max_grade"])-floatval($subject["passing_grade"])),floatval($subject["max_grade"]))*100/floatval($subject["max_grade"]); break;
						case 1: $g = rand(floatval($subject["passing_grade"]),floatval($subject["max_grade"]))*100/floatval($subject["max_grade"]); break;
						case 2: $g = rand(0,100);
					}
					$e["student_grade"] = $g*floatval($e["max_grade"])/100;
					$total += ($e["student_grade"]*100/floatval($e["max_grade"]))*intval($e["weight"]);
					$total_coef += intval($e["weight"]);
					if ($e["type"] == $subject["types"][$i]["id"])
						array_push($subject["types"][$i]["evaluations"], $e);
				}
				$subject["types"][$i]["student_grade"] = $total_coef > 0 ? $total/$total_coef : null;
				$student_grade += $subject["types"][$i]["student_grade"] * intval($subject["types"][$i]["weight"]);
				$student_grade_coef += intval($subject["types"][$i]["weight"]);
			}
			$student_grade = $student_grade_coef > 0 ? $student_grade / $student_grade_coef : null;
			array_push($subjects, $subject);
		} else {
			// published
			$types = SQLQuery::create()->bypassSecurity($student["id"] == PNApplication::$instance->user_management->people_id)->select("PublishedTranscriptEvaluationType")->whereValue("PublishedTranscriptEvaluationType","subject",$s["id"])->whereValue("PublishedTranscriptEvaluationType","transcript",$transcript_id)->execute();
			if (count($types) == 0) continue;
			$subject = $s;
			$subject["types"] = $types;
			$types_ids = array();
			foreach ($types as $t) array_push($types_ids, $t["id"]);
			$evaluations = SQLQuery::create()->bypassSecurity($student["id"] == PNApplication::$instance->user_management->people_id)->select("PublishedTranscriptEvaluation")->whereIn("PublishedTranscriptEvaluation","type",$types_ids)->execute();
			$types_grades = SQLQuery::create()->bypassSecurity($student["id"] == PNApplication::$instance->user_management->people_id)->select("PublishedTranscriptStudentEvaluationTypeGrade")->whereIn("PublishedTranscriptStudentEvaluationTypeGrade","type",$types_ids)->whereValue("PublishedTranscriptStudentEvaluationTypeGrade","people",$student["id"])->execute();
			$eval_ids = array();
			foreach ($evaluations as $e) array_push($eval_ids, $e["id"]);
			if (count($eval_ids) > 0)
				$eval_grade = SQLQuery::create()->bypassSecurity($student["id"] == PNApplication::$instance->user_management->people_id)->select("PublishedTranscriptStudentEvaluationGrade")->whereIn("PublishedTranscriptStudentEvaluationGrade","evaluation",$eval_ids)->whereValue("PublishedTranscriptStudentEvaluationGrade","people",$student["id"])->execute();
			else
				$eval_grade = array();
			for ($i = 0; $i < count($subject["types"]); $i++) {
				$subject["types"][$i]["evaluations"] = array();
				foreach ($evaluations as $e) {
					foreach ($eval_grade as $g) if ($g["evaluation"] == $e["id"]) { $e["student_grade"] = $g["grade"]; break; }
					if ($e["type"] == $subject["types"][$i]["id"])
						array_push($subject["types"][$i]["evaluations"], $e);
				}
				foreach ($types_grades as $g)
					if ($g["type"] == $subject["types"][$i]["id"])
						$subject["types"][$i]["student_grade"] = $g["grade"];
			}
			array_push($subjects, $subject);
			$student_grade = null;
			foreach ($student_grades as $sg) if ($sg["subject"] == $s["id"]) { $student_grade = $sg["grade"]; break; }
		}
	}
	if (count($subjects) == 0) return;
	echo "<style type='text/css'>".file_get_contents(dirname(__FILE__)."/grades_details.css")."</style>";
	echo "<div class='grades_details'>";
	$header = file_get_contents(dirname(__FILE__)."/grades_details_header.html");
	$header = str_replace("#First Name#", $student["first_name"], $header);
	$header = str_replace("#Last Name#", $student["last_name"], $header);
	$header = str_replace("#Batch Name#", $batch["name"], $header);
	$header = str_replace("#Class Name#", $class_name, $header);
	$header = str_replace("#Academic Period#", $period["name"], $header);
	$header = str_replace("#Academic Period Date#", date("d M Y",strtotime($period["academic_period_start"]))." to ".date("d M Y",strtotime($period["academic_period_end"])), $header);
	echo $header;
	$script = "";
	foreach ($subjects as $subject) {
		echo "<div class='subject_container'>";
			echo "<div class='subject_info_container'>";
				echo "<div class='subject_info'>";
					echo "<div class='subject_info_title'>Subject Code</div>";
					echo "<div class='subject_info_data'>".toHTML($subject["code"])."</div>";
					echo "<div class='subject_info_title'>Subject Name</div>";
					echo "<div class='subject_info_data'>".toHTML($subject["name"])."</div>";
				echo "</div>";
				echo "<div class='subject_info'>";
					echo "<div class='subject_info_title'>Passing Grade</div>";
					echo "<div class='subject_info_data'>".$subject["passing_grade"]." / ".$subject["max_grade"]."</div>";
				echo "</div>";
				$groups_ids = PNApplication::$instance->teaching->getGroupsForSubject($subject["id"], $student["id"] == PNApplication::$instance->user_management->people_id);
				if (count($groups_ids) > 0) {
					$teachers = null;
					if ($student["id"] == 0) {
						// fake: let's take one of the teachers
						$groupings = PNApplication::$instance->teaching->getGroupingsForSubject($subject["id"]);
						foreach ($groupings as $grouping_id) {
							$teachers = PNApplication::$instance->teaching->getTeachersAssignedForGrouping($grouping_id);
							if ($teachers <> null && count($teachers) > 0) break;
						}
					} else {
						$groups_ids = PNApplication::$instance->students_groups->getStudentGroupsAmong($student["id"], $groups_ids);
						if (count($groups_ids) > 0) {
							$grouping_id = PNApplication::$instance->teaching->getGroupingIdFromGroup($subject["id"], $groups_ids[0], $student["id"] == PNApplication::$instance->user_management->people_id);
							if ($grouping_id <> null) {
								$teachers = PNApplication::$instance->teaching->getTeachersAssignedForGrouping($grouping_id, true, $student["id"] == PNApplication::$instance->user_management->people_id);
							}
						}
					}
					if ($teachers <> null && count($teachers) > 0) {
						echo "<div class='subject_info'>";
						echo "<div class='subject_info_title'>Instructor".(count($teachers) > 1 ? "s" : "")."</div>";
						echo "<div class='subject_info_data'>";
						$first_teacher = true;
						foreach ($teachers as $teacher) {
							if ($first_teacher) $first_teacher = false; else echo ", ";
							echo $teacher["last_name"]." ".$teacher["first_name"];
						}
						echo "</div>";
					}
				}
				echo "</div>";
			echo "</div>";
			echo "<table class='grades_table'>";
			echo "<tr class='grades_title'>";
			echo "<td colspan=2>Evaluation</td>";
			echo "<td>Grade</td>";
			echo "<td>Max Pts</td>";
			echo "<td>Coef.</td>";
			echo "</tr>";
			foreach ($subject["types"] as $type) {
				echo "<tr class='grades'>";
				echo "<td rowspan=".(count($type["evaluations"])+1)." class='evaluation_type'>";
				echo toHTML($type["name"]);
				echo "<br/>";
				echo "<span style='font-weight:normal'>Coef. ".$type["weight"]."</span>";
				echo "</td>";
				$first = true;
				foreach ($type["evaluations"] as $e) {
					if ($first) $first = false; else echo "<tr class='grades'>";
					echo "<td class='evaluation_name'>";
					echo toHTML($e["name"]);
					echo "</td>";
					if (array_key_exists("student_grade", $e) && $e["student_grade"] !== null) {
						echo "<td class='grade'>";
						echo number_format($e["student_grade"],2);
						echo "</td>";
					} else {
						echo "<td class='grade no_grade'>Absent</td>";
					}
					echo "<td class='max_grade'>";
					echo toHTML($e["max_grade"]);
					echo "</td>";
					echo "<td class='coef'>";
					echo toHTML($e["weight"]);
					echo "</td>";
					echo "</tr>";
				}
				if (!$first) echo "<tr class='grades'>";
				echo "<td class='evaluation_type_total_title'>TOTAL</td>";
				echo "<td class='evaluation_type_total_grade grade' colspan=2>";
				if (@$type["student_grade"] !== null) {
					$id = "grades_details_".$subject["id"]."_".$student["id"]."_eval_type_".$type["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
					echo "<div id='$id'>".number_format($type["student_grade"],2)."</div>";
					$script .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($type["student_grade"]).",false,{max:".(isset($subject["max_grade"]) ? $subject["max_grade"] : "null").",passing:".(isset($subject["passing_grade"]) ? $subject["passing_grade"] : "null").",system:".json_encode($grading_systems[$config["grading_system"]]).",color:false});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
				}
				echo "</td>";
				echo "<td colspan=1></td>";
				echo "</tr>";
			}
			echo "<tr class='grades subject_total'>";
			echo "<td colspan=2>Final Grade</td>";
			echo "<td class='grade' colspan=2>";
			if ($student_grade !== null) {
				$id = "grades_details_".$subject["id"]."_".$student["id"]."_$transcript_id".($id_suffix <> null ? $id_suffix : "");
				echo "<div id='$id'>".number_format($type["student_grade"],2)."</div>";
				$script .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($student_grade).",false,{max:".(isset($subject["max_grade"]) ? $subject["max_grade"] : "null").",passing:".(isset($subject["passing_grade"]) ? $subject["passing_grade"] : "null").",system:".json_encode($grading_systems[$config["grading_system"]]).",color:false});f.fillWidth();e.appendChild(f.getHTMLElement());\n";
			}
			echo "</td>";
			echo "<td></td>";
			echo "</table>";
		echo "</div>";
	}
	echo "</div>";
	echo "<script type='text/javascript'>\n";
	echo "require([['typed_field.js','field_grade.js']],function(){\n";
	echo "var e,f;\n";
	echo $script;
	echo "});\n";
	echo "</script>\n";
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