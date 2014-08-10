<?php 
class page_configure_transcripts extends Page {
	
	public function getRequiredRights() { return array("edit_transcripts_design"); }
	
	public function execute() {	
		if (!isset($_GET["period"])) {
			echo "<div class='info_box'>Please select a period, a class, or a specialization within a period</div>";
			return;
		}
		
		$app_conf = SQLQuery::create()->bypassSecurity()
#DEV
			->noWarning() // TODO
#END
			->select("ApplicationConfig")->where("`name` LIKE 'transcripts_%'")->execute();
		$app_config = array();
		foreach ($app_conf as $ac) $app_config[substr($ac["name"],12)] = $ac["value"];
		
		$batch = PNApplication::$instance->curriculum->getBatch($_GET["batch"]);
		$period = PNApplication::$instance->curriculum->getBatchPeriod($_GET["period"], true);
		$spe = null;
		if (isset($_GET["specialization"]))
			$spe = PNApplication::$instance->curriculum->getSpecialization($_GET["specialization"]);
		
		$title = "Batch ".htmlentities($batch["name"]).", Period ".htmlentities($period["name"]);
		if ($spe <> null) $title .= ", Specialization ".htmlentities($spe["name"]);
		
		$config = SQLQuery::create()
			->select("TranscriptConfig")
			->whereValue("TranscriptConfig","period",$_GET["period"])
			->executeSingleRow();
		$spes = PNApplication::$instance->curriculum->getBatchPeriodSpecializations($_GET["period"]);
		if ($config === null) {
			SQLQuery::create()->insert("TranscriptConfig", array("period"=>$_GET["period"],"specialization"=>null));
			if (count($spes) == 0) {
				$selected_subjects = PNApplication::$instance->curriculum->getSubjects($period["batch"], $period["id"]);
				$insert = array();
				foreach ($selected_subjects as $s)
					array_push($insert, array("period"=>$_GET["period"],"specialization"=>null,"subject"=>$s["id"]));
				if (count($insert) > 0)
					SQLQuery::create()->insertMultiple("TranscriptSubjects",$insert);
			}
		} else {
			if (count($spes) == 0) {
				$selected_subjects = SQLQuery::create()
					->select("TranscriptSubjects")
					->whereValue("TranscriptSubjects","period",$period["id"])
					->whereNull("TranscriptSubjects","specialization")
					->field("TranscriptSubjects","subject","id")
					->executeSingleField();
			}				
		}
		if ($spe <> null) {
			$config_spe = SQLQuery::create()
				->select("TranscriptConfig")
				->whereValue("TranscriptConfig","specialization",$_GET["specialization"])
				->executeSingleRow();
			if ($config_spe == null) {
				SQLQuery::create()->insert("TranscriptConfig", array("period"=>$_GET["period"],"specialization"=>$_GET["specialization"]));
				$selected_subjects = PNApplication::$instance->curriculum->getSubjects($period["batch"], $period["id"], $spe["id"]);
				$insert = array();
				foreach ($selected_subjects as $s)
					array_push($insert, array("period"=>$_GET["period"],"specialization"=>$spe["id"],"subject"=>$s["id"]));
				if (count($insert) > 0)
					SQLQuery::create()->insertMultiple("TranscriptSubjects",$insert);
			} else {
				foreach ($config_spe as $col=>$value) if ($value !== null) $config[$col] = $value;
				$selected_subjects = SQLQuery::create()
					->select("TranscriptSubjects")
					->whereValue("TranscriptSubjects","period",$period["id"])
					->whereValue("TranscriptSubjects","specialization",$spe["id"])
					->field("TranscriptSubjects","subject","id")
					->executeSingleField();
			}
		}

		$all_subjects = PNApplication::$instance->curriculum->getSubjects($batch["id"]);
		$subjects_ids = array();
		foreach ($all_subjects as $s) array_push($subjects_ids, $s["id"]);
		$grading = SQLQuery::create()->select("CurriculumSubjectGrading")->whereIn("CurriculumSubjectGrading","subject",$subjects_ids)->execute();
		for ($i = 0; $i < count($all_subjects); $i++)
			foreach ($grading as $g) if ($g["subject"] == $all_subjects[$i]["id"]) { $all_subjects[$i] = array_merge($all_subjects[$i],$g); break; }
		$categories = PNApplication::$instance->curriculum->getSubjectCategories();
		
		$grading_systems = include("component/transcripts/GradingSystems.inc");
		
		require_once("design.inc");
		defaultTranscriptConfig($config);
		
		$this->requireJavascript("color_choice.js");
		
		?>
<div style='width:100%;height:100%;display:flex;flex-direction:column'>
	<div class='page_title' style='flex:none'>
		Design transcripts for <?php echo $title;?>
		<button style='float:right' onclick="printContent('design');"><img src='<?php echo theme::$icons_16["print"];?>'/></button>
	</div>
	<div style='flex:1 1 auto;display:flex;flex-direction:row'>
		<div style='flex:none;overflow:auto;background-color:white;box-shadow:2px 2px 2px 0px #808080;margin-right:5px;min-width:230px;'>
			<div class='page_section_title'>
				Information to include
			</div>
				<input type='checkbox' <?php echo @$config["class_average"] == 1 ? "checked='checked'" : "";?> onchange="saveTranscriptConfig('class_average',this.checked?1:0);"/> Class average<br/>
				<input type='checkbox' <?php echo @$config["general_appreciation"] == 1 ? "checked='checked'" : "";?> onchange="saveTranscriptConfig('general_appreciation',this.checked?1:0);"/> General appreciation<br/>
			<?php 
			$subjects = array();
			foreach ($categories as $cat) {
				echo "<div class='page_section_title3' style='color:#602000;font-weight:bold;padding-bottom:0px;margin-bottom:0px;'>".htmlentities($cat["name"])."</div>";
				foreach ($all_subjects as $s) {
					if ($s["category"] <> $cat["id"]) continue;
					if ($s["period"] <> $period["id"]) continue;
					array_push($subjects, $s);
					echo "<input type='checkbox'";
					if (in_array($s["id"],$selected_subjects)) echo " checked='checked'";
					echo "/> ".htmlentities($s["code"])." - ".htmlentities($s["name"]);
					echo "<br/>";
				}
			}
			?>
			<div class='page_section_title'>
				Display settings
			</div>
			<b>Grading system</b> <select onchange="saveTranscriptConfig('grading_system',this.options[this.selectedIndex].value);">
			<?php
			foreach($grading_systems as $name=>$spec) {
				echo "<option value=\"".$name."\"";
				if ($name == $config["grading_system"]) echo " selected='selected'";
				echo ">".htmlentities($name)."</option>";
			}
			?>
			</select>
			<div style='font-weight:bold'>Subject Category</div>
			<div style='padding-left:15px'>
				Background: <span id='subject_category_background' style='vertical-align:middle'></span><script type='text/javascript'>new color_widget('subject_category_background',<?php echo json_encode($config["subject_category_background"]);?>).onchange = function(cw) { saveTranscriptConfig('subject_category_background',cw.color); };</script>
				Text: <span id='subject_category_color' style='vertical-align:middle'></span><script type='text/javascript'>new color_widget('subject_category_color',<?php echo json_encode($config["subject_category_color"]);?>).onchange = function(cw) { saveTranscriptConfig('subject_category_color',cw.color); };</script><br/>
				Weight: <select onchange="saveTranscriptConfig('subject_category_weight',this.value);"><option value='normal'<?php if ($config["subject_category_weight"] == "normal") echo " selected='selected'";?>>Normal</option><option value='bold'<?php if ($config["subject_category_weight"] == "bold") echo " selected='selected'";?>>Bold</option></select>
				Size: <select onchange="saveTranscriptConfig('subject_category_size',this.value);">
					<?php foreach (array("8","9","10","11","12","14","16","18","20","22","24") as $size) echo "<option value='$size'".($config["subject_category_size"] == $size ? " selected='selected'" : "").">$size</option>";?>
				</select><br/>
			</div>
			<div style='font-weight:bold'>Columns Titles</div>
			<div style='padding-left:15px'>
				Background: <span id='columns_titles_background' style='vertical-align:middle'></span><script type='text/javascript'>new color_widget('columns_titles_background',<?php echo json_encode($config["columns_titles_background"]);?>).onchange = function(cw) { saveTranscriptConfig('columns_titles_background',cw.color); };</script>
				Text: <span id='columns_titles_color' style='vertical-align:middle'></span><script type='text/javascript'>new color_widget('columns_titles_color',<?php echo json_encode($config["columns_titles_color"]);?>).onchange = function(cw) { saveTranscriptConfig('columns_titles_color',cw.color); };</script><br/>
				Weight: <select onchange="saveTranscriptConfig('columns_titles_weight',this.value);"><option value='normal'<?php if ($config["columns_titles_weight"] == "normal") echo " selected='selected'";?>>Normal</option><option value='bold'<?php if ($config["columns_titles_weight"] == "bold") echo " selected='selected'";?>>Bold</option></select>
				Size: <select onchange="saveTranscriptConfig('columns_titles_size',this.value);">
					<?php foreach (array("8","9","10","11","12","14","16","18","20","22","24") as $size) echo "<option value='$size'".($config["columns_titles_size"] == $size ? " selected='selected'" : "").">$size</option>";?>
				</select><br/>
			</div>
			<div style='font-weight:bold'>Total Rows</div>
			<div style='padding-left:15px'>
				Background: <span id='total_background' style='vertical-align:middle'></span><script type='text/javascript'>new color_widget('total_background',<?php echo json_encode($config["total_background"]);?>).onchange = function(cw) { saveTranscriptConfig('total_background',cw.color); };</script>
				Text: <span id='total_color' style='vertical-align:middle'></span><script type='text/javascript'>new color_widget('total_color',<?php echo json_encode($config["total_color"]);?>).onchange = function(cw) { saveTranscriptConfig('total_color',cw.color); };</script><br/>
				Size: <select onchange="saveTranscriptConfig('total_size',this.value);">
					<?php foreach (array("8","9","10","11","12","14","16","18","20","22","24") as $size) echo "<option value='$size'".($config["total_size"] == $size ? " selected='selected'" : "").">$size</option>";?>
				</select><br/>
			</div>
			<div class='page_section_title'>
				General configuration
			</div>
			<table>
				<tr>
					<td>Location</td>
					<td><input type='text' size=10 value="<?php echo htmlentities(@$app_config["location"]);?>" onchange="saveAppConfig('location',this.value);"/></td>
				</tr>
				<tr>
					<td>Signatory Name</td>
					<td><input type='text' size=10 value="<?php echo htmlentities(@$app_config["signatory_name"]);?>" onchange="saveAppConfig('signatory_name',this.value);"/></td>
				</tr>
				<tr>
					<td>Signatory Title</td>
					<td><input type='text' size=10 value="<?php echo htmlentities(@$app_config["signatory_title"]);?>" onchange="saveAppConfig('signatory_title',this.value);"/></td>
				</tr>
			</table>
		</div>
		<div style='flex:1 1 auto;overflow:auto;text-align:center'>
			<div id='design' style='text-align:left;background-color:white;border-radius:5px;display:inline-block;box-shadow: 2px 2px 2px 0px #808080;width:630px;height:810px;margin-bottom:5px;'>
				<?php generateTranscriptFor($config,$app_config,$categories,$subjects,$period);?>
			</div>
		</div>
	</div>
</div>
<script type='text/javascript'>
function saveAppConfig(name, value) {
	var locker = lock_screen(null, "Saving");
	service.json("transcripts","save_transcripts_app_config",{name:name,value:value},function(res) {
		unlock_screen(locker);
		refreshDesign();
	});
}
function saveTranscriptConfig(name, value) {
	var locker = lock_screen(null, "Saving");
	var data = {
		table:'TranscriptConfig',
		key:{period:<?php echo $_GET["period"];?>,specialization:<?php echo isset($_GET["specialization"]) ? $_GET["specialization"] : "null";?>},
		lock:-1
	};
	data["field_"+name] = value; 
	service.json("data_model","save_entity", data, function(res) {
		unlock_screen(locker);
		refreshDesign();
	});
}
function refreshDesign() {
	var locker = lock_screen(null, "Reloading transcript");
	service.html("transcripts","generate_transcript",{period:<?php echo $_GET["period"];?>,specialization:<?php echo isset($_GET["specialization"]) ? $_GET["specialization"] : "null";?>},document.getElementById('design'),function() {
		unlock_screen(locker);
	});
}
</script>
		<?php 
	}
	
}
?>