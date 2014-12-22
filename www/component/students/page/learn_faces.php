<?php 
class page_learn_faces extends Page {
	
	public function getRequiredRights() { return array("consult_students_list"); }
	
	public function execute() {
		if (@$_GET["batch"] == "") unset($_GET["batch"]);
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
	<div class='page_title shadow' style='flex:none'>
		Do you know the face of your students ?
		<span style='font-size:10pt'>
		Choose a batch: <select onchange="location.href='?batch='+this.value;">
			<option value=''></option>
			<?php 
			$batches = PNApplication::$instance->curriculum->getBatches();
			foreach ($batches as $b) {
				echo "<option value='".$b["id"]."'".(@$_GET["batch"] == $b["id"] ? " selected='selected'" : "").">".$b["name"]."</option>";
			}
			?>
		</select>
		</span>
	</div>
	<div style='flex: 1 1 100%;display:flex;flex-direction:column;justify-content:center;align-items:center;'>
		<?php if (isset($_GET["batch"])) {
		$this->requireJavascript("progress_bar.js");
		$q = PNApplication::$instance->students->getStudentsQueryForBatches(array($_GET["batch"]));
		PNApplication::$instance->people->joinPeople($q, "Student", "people");
		$students = $q->execute();
		$nb_students = count($students);
		$nb_pictures = 0;
		for ($i = 0; $i < count($students); $i++) {
			if ($students[$i]["picture_id"] <> null) $nb_pictures++;
			else {
				array_splice($students,$i,1);
				$i--;
			}
		}
		if ($nb_pictures == 0) {
			unset($_GET["batch"]);
			echo "<div style='background-color:white;padding:5px;border: 1px solid black; border-radius: 5px;'>Unfortunately we don't have any picture for this batch</div>";
		} else {
			if ($nb_pictures < $nb_students) {
				echo "<div style='background-color:white;padding:5px;border: 1px solid black; border-radius: 5px;margin-bottom:10px;'>Note that we have ".($nb_students-$nb_pictures)." missing picture for this batch</div>";
			}
		?>
		<div style='background-color:white; border: 1px solid black; border-radius: 5px;box-shadow:2px 2px 2px 0px #A0A0A0'>
			<div id='content' style='padding:5px'>
			</div>
			<div id='footer' style='border-top:1px solid black;padding:1px 3px;display:flex;flex-direction:row;justify-content:center;align-items:center;background-color:#E0E0D0;border-bottom-right-radius:5px;border-bottom-left-radius:5px;'>
				<div id='progress' style='flex:none;margin-right:10px;'></div>
				<div id='status' style='flex:none'></div>
			</div>
		</div>
		<?php } } ?>
	</div>
</div>
<?php if (isset($_GET["batch"])) { ?>
<script type='text/javascript'>
var remaining = <?php echo PeopleJSON::Peoples($students);?>;
var done = [];
var content = document.getElementById('content');
var pb = new progress_bar(200,8);
pb.setTotal(remaining.length);
pb.setPosition(0);
var progress_text = document.createElement("DIV");
progress_text.style.textAlign = "center";
document.getElementById('progress').appendChild(progress_text);
document.getElementById('progress').appendChild(pb.element);
var correct = 0;
var wrong = 0;
function pickStudent() {
	var index = Math.floor(Math.random()*(remaining.length*2+done.length));
	if (index >= remaining.length*2) return done[index-remaining.length*2];
	return remaining[Math.floor(index/2)];
}
function getStudent(already, sex) {
	do {
		var s = pickStudent();
		if (already.indexOf(s) >= 0) continue;
		if (s.sex != sex) continue;
		return s;
	} while (true);
}
function chooseName(student, proposals) {
	content.innerHTML = "<div class='page_section_title2'>What is the name of this student ?</div>";
	var pic_container = document.createElement("DIV");
	pic_container.style.display = "flex";
	pic_container.style.justifyContent = "center";
	pic_container.style.alignItems = "center";
	var pic = document.createElement("IMG");
	pic.src = "/dynamic/storage/service/get?id="+student.picture_id+"&revision="+student.picture_revision;
	pic.style.maxHeight = "200px";
	pic_container.appendChild(pic);
	pic_container.style.marginBottom = "5px";
	content.appendChild(pic_container);
	layout.changed(content);
	for (var i = 0; i < 6; ++i) {
		var link = document.createElement("A");
		link.href = "#";
		link.appendChild(document.createTextNode(proposals[i].first_name+" "+proposals[i].last_name));
		link._student = proposals[i];
		link.onclick = function() {
			if (this._student == student) {
				content.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> Congratualtions, you know already this student !<br/><br/>";
				correct++;
			} else {
				content.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> Wrong choice, it was "+student.first_name+" "+student.last_name+"<br/><br/>You should learn this face:";
				content.appendChild(pic_container);
				wrong++;
			}
			var button = document.createElement("BUTTON");
			button.className = "action";
			button.innerHTML = "Continue";
			button.onclick = function() {
				done.push(student);
				next();
			};
			content.appendChild(button);
			layout.changed(content);
			return false;
		};
		content.appendChild(link);
		link.style.marginLeft = "5px";
		link.style.marginRight = "5px";
		link.style.color = "black";
		link.style.fontWeight = "bold";
		link.style.fontSize = "11pt";
		link.style.textDecoration = "underline";
	}
}
function choosePicture(student, proposals) {
	content.innerHTML = "<div class='page_section_title2'>Who is "+student.first_name+" "+student.last_name+" ?</div>";
	for (var i = 0; i < proposals.length; ++i) {
		var pic = document.createElement("IMG");
		pic.src = "/dynamic/storage/service/get?id="+proposals[i].picture_id;
		pic.style.maxHeight = "200px";
		pic.style.margin = "1px 3px";
		pic.style.cursor = "pointer";
		content.appendChild(pic);
		pic._student = proposals[i];
		pic.onclick = function() {
			if (this._student == student) {
				content.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> Congratualtions, you know already this student !<br/><br/>";
				correct++;
			} else {
				content.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> Wrong choice, "+student.first_name+" "+student.last_name+" is:<br/><br/>";
				var pic = document.createElement("IMG");
				pic.src = "/dynamic/storage/service/get?id="+student.picture_id;
				pic.style.maxHeight = "200px";
				content.appendChild(pic);
				content.appendChild(document.createElement("BR"));
				wrong++;
			}
			var button = document.createElement("BUTTON");
			button.className = "action";
			button.innerHTML = "Continue";
			button.onclick = function() {
				done.push(student);
				next();
			};
			content.appendChild(button);
			layout.changed(content);
			return false;
		};	
	}
}
function end() {
	content.innerHTML = "That's all for this batch.<br/><br/><button class='action' onclick='location.reload();'>Try Again</button>";
}
function next() {
	pb.setPosition(done.length);
	progress_text.innerHTML = done.length+"/"+(remaining.length+done.length);
	var status = "";
	if (correct > 0) status += " correct: "+correct;
	if (wrong > 0) status += " wrong: "+wrong;
	document.getElementById('status').innerHTML = status;
	if (remaining.length == 0) { end(); return; }
	var index = Math.floor(Math.random()*remaining.length);
	var student = remaining[index];
	remaining.splice(index,1);
	var proposals = [];
	var index = Math.floor(Math.random()*6);
	for (var i = 0; i < 6; ++i)
		if (i == index) proposals.push(student);
		else proposals.push(getStudent(proposals, student.sex));
	if (Math.random() > 0.5)
		chooseName(student, proposals);
	else
		choosePicture(student, proposals);
}
next();
</script>
<?php } ?>
<?php 
	}
}
?>