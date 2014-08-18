<?php 
class page_configure_transcripts extends Page {
	
	public function getRequiredRights() { return array("edit_transcripts_design"); }
	
	public function execute() {	
		if (!isset($_GET["period"])) {
			echo "<div class='info_box'>Please select a period, a class, or a specialization within a period</div>";
			return;
		}
		// TODO lock;
		
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

		if ($config === null)
			SQLQuery::create()->insert("TranscriptConfig", array("period"=>$_GET["period"],"specialization"=>null));
		
		if (count($spes) == 0) {
			if ($config === null) {
				$selected_subjects = PNApplication::$instance->curriculum->getSubjects($period["batch"], $period["id"]);
				$insert = array();
				foreach ($selected_subjects as $s)
					array_push($insert, array("period"=>$_GET["period"],"specialization"=>null,"subject"=>$s["id"]));
				if (count($insert) > 0)
					SQLQuery::create()->insertMultiple("TranscriptSubjects",$insert);
			}
			$q = SQLQuery::create()
				->select("TranscriptSubjects")
				->whereValue("TranscriptSubjects","period",$period["id"])
				->whereNull("TranscriptSubjects","specialization")
				;
			PNApplication::$instance->curriculum->joinSubjects($q, "TranscriptSubjects", "subject");
			$q->join("TranscriptSubjects","CurriculumSubjectGrading",array("subject"=>"subject"));
			$selected_subjects = $q->execute();
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
			} else
				foreach ($config_spe as $col=>$value) if ($value !== null) $config[$col] = $value;
			
			$q = SQLQuery::create()
				->select("TranscriptSubjects")
				->whereValue("TranscriptSubjects","period",$period["id"])
				->whereValue("TranscriptSubjects","specialization",$spe["id"])
				;
			PNApplication::$instance->curriculum->joinSubjects($q, "TranscriptSubjects", "subject");
			$q->join("TranscriptSubjects","CurriculumSubjectGrading",array("subject"=>"subject"));
			$selected_subjects = $q->execute();
		}

		$all_subjects = PNApplication::$instance->curriculum->getSubjects($batch["id"]);
		$subjects_ids = array();
		foreach ($all_subjects as $s) array_push($subjects_ids, $s["id"]);
		if (count($subjects_ids) > 0) {
			$grading = SQLQuery::create()->select("CurriculumSubjectGrading")->whereIn("CurriculumSubjectGrading","subject",$subjects_ids)->execute();
			for ($i = 0; $i < count($all_subjects); $i++)
				foreach ($grading as $g) if ($g["subject"] == $all_subjects[$i]["id"]) { $all_subjects[$i] = array_merge($all_subjects[$i],$g); break; }
		}
		$categories = PNApplication::$instance->curriculum->getSubjectCategories();
		
		$grading_systems = include("component/transcripts/GradingSystems.inc");
		
		require_once("design.inc");
		defaultTranscriptConfig($config);
		
		$this->requireJavascript("color_choice.js");
		
		?>
<div style='width:100%;height:100%;display:flex;flex-direction:column'>
	<div class='page_title' style='flex:none;'>
		Design transcripts
		<span style='margin-left:10px;font-size:12pt;font-style:italic;'><?php echo $title;?></span>
		<div style='float:right;display:inline-block;font-size:1pt;'>
			<button style='flex:none' onclick="printContent('design');"><img src='<?php echo theme::$icons_16["print"];?>'/> Test Print</button><br/>
			<button style='flex:none' onclick="publish();"><img src='/static/transcripts/publish.png'/> Publish</button>
		</div>
	</div>
	<div style='flex:1 1 auto;display:flex;flex-direction:row'>
		<div style='flex:none;overflow:auto;background-color:white;box-shadow:2px 2px 2px 0px #808080;margin-right:5px;min-width:230px;width:230px;'>
			<div class='page_section_title'>
				Information to include
			</div>
				<input type='checkbox' <?php echo @$config["nb_hours"] == 1 ? "checked='checked'" : "";?> onchange="saveTranscriptConfig('nb_hours',this.checked?1:0);"/> Nb of hours
				<select onchange="saveTranscriptConfig('hours_type',this.value);">
					<option value="Per week" <?php if (@$config["hours_type"] == "Per week") echo "selected='selected'";?>>Per week</option>
					<option value="Per period" <?php if (@$config["hours_type"] == "Per period") echo "selected='selected'";?>>Total period</option>
				</select>
				<br/>
				<input type='checkbox' <?php echo @$config["coefficient"] == 1 ? "checked='checked'" : "";?> onchange="saveTranscriptConfig('coefficient',this.checked?1:0);"/> Coefficient<br/>
				<input type='checkbox' <?php echo @$config["class_average"] == 1 ? "checked='checked'" : "";?> onchange="saveTranscriptConfig('class_average',this.checked?1:0);"/> Class average<br/>
				<input type='checkbox' <?php echo @$config["general_appreciation"] == 1 ? "checked='checked'" : "";?> onchange="saveTranscriptConfig('general_appreciation',this.checked?1:0);"/> General appreciation<br/>
			<?php 
			foreach ($categories as $cat) {
				echo "<div class='page_section_title3' style='color:#602000;font-weight:bold;padding-bottom:0px;margin-bottom:0px;'>".htmlentities($cat["name"])."</div>";
				foreach ($all_subjects as $s) {
					if ($s["category"] <> $cat["id"]) continue;
					if ($s["period"] <> $period["id"]) continue;
					echo "<div style='white-space:nowrap'>";
					echo "<input type='checkbox' onchange='changeSubject(".$s["id"].",this.checked);'";
					foreach ($selected_subjects as $ss) if ($s["id"] == $ss["id"]) { echo " checked='checked'"; break; }
					echo "/> ".htmlentities($s["code"])." - ".htmlentities($s["name"]);
					echo "</div>";
				}
			}
			?>
			<div class='page_section_title3' style='font-weight:bold;padding-bottom:0px;margin-bottom:0px;'>From other periods</div>
			<div id='other_subjects'>
			</div>
			<button onclick="addSubject(this);" class="flat" style="color:#808080;font-style:italic;text-decoration:underline;">Add subject</button>
			<div class='page_section_title2'>
				Signature
			</div>
			<table>
				<tr>
					<td>Location</td>
					<td><input type='text' size=10 value="<?php echo htmlentities(@$config["location"]);?>" onchange="saveTranscriptConfig('location',this.value);"/></td>
				</tr>
				<tr>
					<td>Signatory Name</td>
					<td><input type='text' size=10 value="<?php echo htmlentities(@$config["signatory_name"]);?>" onchange="saveTranscriptConfig('signatory_name',this.value);"/></td>
				</tr>
				<tr>
					<td>Signatory Title</td>
					<td><input type='text' size=10 value="<?php echo htmlentities(@$config["signatory_title"]);?>" onchange="saveTranscriptConfig('signatory_title',this.value);"/></td>
				</tr>
			</table>
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
				Size: <select onchange="saveTranscriptConfig('subject_category_size',this.value);">
					<?php foreach (array("8","9","10","11","12","14","16","18","20","22","24") as $size) echo "<option value='$size'".($config["subject_category_size"] == $size ? " selected='selected'" : "").">$size</option>";?>
				</select>
				<input type='checkbox'<?php if ($config["subject_category_weight"] == "bold") echo " checked='checked'";?> onchange="saveTranscriptConfig('subject_category_weight',this.checked?'bold':'normal');"/> Bold 
			</div>
			<div style='font-weight:bold'>Columns Titles</div>
			<div style='padding-left:15px'>
				Background: <span id='columns_titles_background' style='vertical-align:middle'></span><script type='text/javascript'>new color_widget('columns_titles_background',<?php echo json_encode($config["columns_titles_background"]);?>).onchange = function(cw) { saveTranscriptConfig('columns_titles_background',cw.color); };</script>
				Text: <span id='columns_titles_color' style='vertical-align:middle'></span><script type='text/javascript'>new color_widget('columns_titles_color',<?php echo json_encode($config["columns_titles_color"]);?>).onchange = function(cw) { saveTranscriptConfig('columns_titles_color',cw.color); };</script><br/>
				Size: <select onchange="saveTranscriptConfig('columns_titles_size',this.value);">
					<?php foreach (array("8","9","10","11","12","14","16","18","20","22","24") as $size) echo "<option value='$size'".($config["columns_titles_size"] == $size ? " selected='selected'" : "").">$size</option>";?>
				</select>
				<input type='checkbox'<?php if ($config["columns_titles_weight"] == "bold") echo " checked='checked'";?> onchange="saveTranscriptConfig('columns_titles_weight',this.checked?'bold':'normal');"/> Bold 
			</div>
			<div style='font-weight:bold'>Total Rows</div>
			<div style='padding-left:15px'>
				Background: <span id='total_background' style='vertical-align:middle'></span><script type='text/javascript'>new color_widget('total_background',<?php echo json_encode($config["total_background"]);?>).onchange = function(cw) { saveTranscriptConfig('total_background',cw.color); };</script>
				Text: <span id='total_color' style='vertical-align:middle'></span><script type='text/javascript'>new color_widget('total_color',<?php echo json_encode($config["total_color"]);?>).onchange = function(cw) { saveTranscriptConfig('total_color',cw.color); };</script><br/>
				Size: <select onchange="saveTranscriptConfig('total_size',this.value);">
					<?php foreach (array("8","9","10","11","12","14","16","18","20","22","24") as $size) echo "<option value='$size'".($config["total_size"] == $size ? " selected='selected'" : "").">$size</option>";?>
				</select><br/>
			</div>
			<div style='font-weight:bold'>General Appreciation Title</div>
			<div style='padding-left:15px'>
				Background: <span id='general_comment_title_background' style='vertical-align:middle'></span><script type='text/javascript'>new color_widget('general_comment_title_background',<?php echo json_encode($config["general_comment_title_background"]);?>).onchange = function(cw) { saveTranscriptConfig('general_comment_title_background',cw.color); };</script>
				Text: <span id='general_comment_title_color' style='vertical-align:middle'></span><script type='text/javascript'>new color_widget('general_comment_title_color',<?php echo json_encode($config["general_comment_title_color"]);?>).onchange = function(cw) { saveTranscriptConfig('general_comment_title_color',cw.color); };</script><br/>
				Size: <select onchange="saveTranscriptConfig('general_comment_title_size',this.value);">
					<?php foreach (array("8","9","10","11","12","14","16","18","20","22","24") as $size) echo "<option value='$size'".($config["general_comment_title_size"] == $size ? " selected='selected'" : "").">$size</option>";?>
				</select>
				<input type='checkbox'<?php if ($config["general_comment_title_weight"] == "bold") echo " checked='checked'";?> onchange="saveTranscriptConfig('general_comment_title_weight',this.checked?'bold':'normal');"/> Bold 
			</div>
			<div style='font-weight:bold'>General Appreciation Text</div>
			<div style='padding-left:15px'>
				Size: <select onchange="saveTranscriptConfig('general_comment_size',this.value);">
					<?php foreach (array("8","9","10","11","12","14","16","18","20","22","24") as $size) echo "<option value='$size'".($config["general_comment_size"] == $size ? " selected='selected'" : "").">$size</option>";?>
				</select>
			</div>
		</div>
		<div style='flex:1 1 auto;overflow:auto;text-align:center'>
			<div id='design' style='text-align:left;background-color:white;border-radius:5px;display:inline-block;box-shadow: 2px 2px 2px 0px #808080;width:630px;height:810px;margin-bottom:5px;'>
				<?php generateTranscriptFor($config,$categories,$selected_subjects,$period);?>
			</div>
		</div>
	</div>
</div>
<script type='text/javascript'>
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
function changeSubject(subject_id, selected) {
	var locker = lock_screen(null, "Saving");
	service.json("transcripts","set_transcript_subject",{
		period:<?php echo $_GET["period"];?>,
		specialization:<?php echo isset($_GET["specialization"]) ? $_GET["specialization"] : "null";?>,
		subject:subject_id,
		selected:selected
	},function(res) {
		unlock_screen(locker);
		refreshDesign();
	});
}
var other_subjects = [<?php
$first = true; 
foreach ($all_subjects as $s) {
	if ($s["period"] == $period["id"]) continue;
	if ($first) $first = false; else echo ",";
	echo "{id:".$s["id"].",code:".json_encode($s["code"]).",name:".json_encode($s["name"])."}";
}
?>];
var added_subjects = [<?php 
$first = true; 
foreach ($all_subjects as $s) {
	if ($s["period"] == $period["id"]) continue;
	$found = false;
	foreach ($selected_subjects as $ss) if ($ss["id"] == $s["id"]) { $found = true; break; }
	if (!$found) continue;
	if ($first) $first = false; else echo ",";
	echo $s["id"];
}
?>];

function addSubject(button) {
	require("context_menu.js",function() {
		var menu = new context_menu();
		for (var i = 0; i < other_subjects.length; ++i) {
			if (added_subjects.contains(other_subjects[i].id)) continue;
			menu.addIconItem(null,other_subjects[i].code + " - " + other_subjects[i].name, function(s) {
				added_subjects.push(s.id);
				appendSubject(s);
				changeSubject(s.id, true);
			}, other_subjects[i]);
		}
		menu.showBelowElement(button);
	});
}
function appendSubject(s) {
	var container = document.getElementById('other_subjects');
	var div = document.createElement("DIV");
	div.style.whiteSpace = 'nowrap';
	div.appendChild(document.createTextNode(s.code+" - "+s.name));
	var remove = document.createElement("BUTTON");
	remove.className = "flat small_icon";
	remove.innerHTML = "<img src='"+theme.icons_10.remove+"'/>";
	div.appendChild(remove);
	container.appendChild(div);
	remove.onclick = function() {
		added_subjects.remove(s.id);
		container.removeChild(div);
		changeSubject(s.id, false);
	};
}
for (var i = 0; i < added_subjects.length; ++i) {
	var s = null;
	for (var j = 0; j < other_subjects.length; ++j) if (other_subjects[j].id == added_subjects[i]) { s = other_subjects[j]; break; }
	appendSubject(s);
}
function refreshDesign() {
	var locker = lock_screen(null, "Reloading transcript");
	service.html("transcripts","generate_transcript",{period:<?php echo $_GET["period"];?>,specialization:<?php echo isset($_GET["specialization"]) ? $_GET["specialization"] : "null";?>},document.getElementById('design'),function() {
		unlock_screen(locker);
	});
}
function publish() {
	popup_frame("/static/transcripts/publish.png", "Publish Transcripts", "/dynamic/transcripts/page/publish?period=<?php echo $_GET["period"]; if (isset($_GET["specialization"])) echo "&specialization=".$_GET["specialization"];?>");
}
</script>
		<?php 
	}
	
}
?>