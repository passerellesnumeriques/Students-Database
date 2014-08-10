<?php 
function generateTranscript($period_id, $spe_id = null, $student = null, $student_grades = null) {
	$config = SQLQuery::create()
		->select("TranscriptConfig")
		->whereValue("TranscriptConfig","period",$period_id)
		->executeSingleRow();
	if ($config == null) $config = array();
	if ($spe_id <> null) {
		$config_spe = SQLQuery::create()
			->select("TranscriptConfig")
			->whereValue("TranscriptConfig","specialization",$spe_id)
			->executeSingleRow();
		if ($config_spe <> null)
			foreach ($config_spe as $col=>$value) if ($value !== null) $config[$col] = $value;
	}
	
	$app_conf = SQLQuery::create()->bypassSecurity()
#DEV
		->noWarning() // TODO
#END
		->select("ApplicationConfig")->where("`name` LIKE 'transcripts_%'")->execute();
	$app_config = array();
	foreach ($app_conf as $ac) $app_config[substr($ac["name"],12)] = $ac["value"];
	
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
	
	generateTranscriptFor($config, $app_config, $categories, $subjects, $period, $student, $student_grades);
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
	if (@$config["total_background"] == null) $config["total_background"] = "#009DE1";
	if (@$config["total_color"] == null) $config["total_color"] = "#FFFFFF";
	if (@$config["total_size"] == null) $config["total_size"] = "14";
	if (@$config["grading_system"] == null) {
		$grading_systems = include("component/transcripts/GradingSystems.inc");
		$d = PNApplication::$instance->getDomainDescriptor();
		$config["grading_system"] = $d["transcripts"]["default_grading_system"];
	}
}
function generateTranscriptFor($config, $app_config, $categories, $subjects, $period, $student = null, $student_grades = null, $class_averages = null) {
	defaultTranscriptConfig($config);
	
	$grading_systems = include("component/transcripts/GradingSystems.inc");
	if ($student == null)
		$student = array(
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
			switch (($count%4)==0) {
				case 0: $grade["grade"] = $passing+($max-$passing)*0.8; break;
				case 1: $grade["grade"] = $passing+($max-$passing)*0.05; break;
				case 2: $grade["grade"] = $passing*0.8; break;
				case 3: $grade["grade"] = null;
			}
			array_push($student_grades, $grade);
			$count++;
		}
	}
	if ($class_averages == null && @$config["class_average"] == 1) {
		// TODO
	}
	
	$design = file_get_contents("component/transcripts/page/design.html");
	
	$design = str_replace("#First Name#", $student["first_name"], $design);
	$design = str_replace("#Last Name#", $student["last_name"], $design);
	
	$design = str_replace("#Academic Period#", $period["name"], $design);
	$design = str_replace("#Academic Period Date#", date("d M Y",strtotime($period["academic_period_start"]))." to ".date("d M Y",strtotime($period["academic_period_end"])), $design);
	
	$design = str_replace("#Location#", @$app_config["location"], $design);
	$design = str_replace("#Signatory Name#", @$app_config["signatory_name"], $design);
	$design = str_replace("#Signatory Title#", @$app_config["signatory_title"], $design);
	
	processTranscriptCondition($design, "class_average", @$config["class_average"] == 1);
	processTranscriptCondition($design, "general_appreciation", @$config["general_appreciation"] == 1);

	$design = str_replace("#SUBJECT_CATEGORY_BACKGROUND#", $config["subject_category_background"], $design);
	$design = str_replace("#SUBJECT_CATEGORY_COLOR#", $config["subject_category_color"], $design);
	$design = str_replace("#SUBJECT_CATEGORY_WEIGHT#", $config["subject_category_weight"], $design);
	$design = str_replace("#SUBJECT_CATEGORY_SIZE#", $config["subject_category_size"], $design);
	$design = str_replace("#COLUMNS_TITLES_BACKGROUND#", $config["columns_titles_background"], $design);
	$design = str_replace("#COLUMNS_TITLES_COLOR#", $config["columns_titles_color"], $design);
	$design = str_replace("#COLUMNS_TITLES_WEIGHT#", $config["columns_titles_weight"], $design);
	$design = str_replace("#COLUMNS_TITLES_SIZE#", $config["columns_titles_size"], $design);
	$design = str_replace("#TOTAL_BACKGROUND#", $config["total_background"], $design);
	$design = str_replace("#TOTAL_COLOR#", $config["total_color"], $design);
	$design = str_replace("#TOTAL_SIZE#", $config["total_size"], $design);
	
	$cats = array();
	foreach ($categories as $cat) {
		$subjs = array();
		foreach ($subjects as $s) if ($s["category"] == $cat["id"]) array_push($subjs,$s);
		if (count($subjs) > 0) array_push($cats, array($cat,$subjs));
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
			$cat_content = str_replace("#SUBJECT_CATEGORY_NAME#", htmlentities($cat["name"]), $cat_content);
			$cat_content = str_replace("#COUNT(SUBJECTS)#", count($list), $cat_content);
			processTranscriptConditionIndex($cat_content, "SUBJECT_CATEGORY", $category_index, count($cats));
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
				$total_grade += floatval($student_grade)*100/floatval($subject["max_grade"])*intval($subject["coefficient"]);
				$total_coef += intval($subject["coefficient"]);
				$passing = floatval($subject["passing_grade"])*intval($subject["coefficient"]);
			}
			$total_grade = $total_coef > 0 ? $total_grade/$total_coef : "null";
			$passing = $total_coef > 0 ? $passing/$total_coef : "null";
			$id = "total_grade_".$cat["id"];
			$cat_content = str_replace("#TOTAL_GRADE#", "<div id='$id' style='position:absolute;height:100%;top:0px;left:0px;width:100%;'>$total_grade</span>", $cat_content);
			$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($total_grade).",false,{max:100,passing:".$passing.",color:false,system:".json_encode($grading_systems[$config["grading_system"]])."});f.fillWidth();e.appendChild(f.getHTMLElement());";
				
			while (($k = strpos($cat_content, "#SUBJECT#")) !== false) {
				$l = strpos($cat_content, "#END_SUBJECT#", $k);
				$result_subjects = "";
				for ($subject_index = 0; $subject_index < count($list); $subject_index++) {
					$subject = $list[$subject_index];
					$subj_content = substr($cat_content, $k+9, $l-$k-9);
					processTranscriptConditionIndex($subj_content, "SUBJECT", $subject_index, count($list));
					$subj_content = str_replace("#SUBJECT_CODE#", htmlentities($subject["code"]), $subj_content);
					$subj_content = str_replace("#SUBJECT_NAME#", htmlentities($subject["name"]), $subj_content);
					$student_grade = null;
					foreach ($student_grades as $g) if ($g["subject"] == $subject["id"]) { $student_grade = $g["grade"]; break; }
					$id = "grade_subject_".$subject["id"];
					$subj_content = str_replace("#GRADE#", "<div id='$id' style='position:absolute;height:100%;top:0px;left:0px;width:100%;'>$student_grade</span>", $subj_content);
					$script_grades .= "e=document.getElementById('$id');e.removeAllChildren();f=new field_grade(".json_encode($student_grade).",false,{max:".(isset($subject["max_grade"]) ? $subject["max_grade"] : "null").",passing:".(isset($subject["passing_grade"]) ? $subject["passing_grade"] : "null").",system:".json_encode($grading_systems[$config["grading_system"]])."});f.fillWidth();e.appendChild(f.getHTMLElement());";
					$result_subjects .= $subj_content;
				}
				$cat_content = substr($cat_content,0,$k).$result_subjects.substr($cat_content,$l+13);
			}
			$result_categories .= $cat_content;
		}
		$design = substr($design, 0, $i).$result_categories.substr($design, $j+22);
	}
	
	echo $design;
	if ($script_grades <> "") {
		echo "<script type='text/javascript'>";
		echo "require(['typed_field.js','field_grade.js'],function(){";
		echo "var e,f;";
		echo $script_grades;
		echo "});";
		echo "</script>";
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