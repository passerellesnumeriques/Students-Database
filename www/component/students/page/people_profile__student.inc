<?php 
require_once("component/data_model/page/table_datadisplay.inc");
table_datadisplay_page_from_key($this, "Student", $people_id);

$student = SQLQuery::create()
	->select("Student")
	->whereValue("Student", "people", $people_id)
	->join("Student", "People", array("people"=>"id"))
	->executeSingleRow();

$batch = SQLQuery::create()
	->select("StudentBatch")
	->whereValue("StudentBatch", "id", $student["batch"])
	->executeSingleRow();

if ($student["exclusion_date"] == null) {
	echo "<div class='button' onclick='exclude();'><img src='".theme::make_icon("/static/students/student_16.png", theme::$icons_10["forbidden"])."'/> Exclude</div>";
	?>
	<script type='text/javascript'>
	function exclude() {
		require(["popup_window.js",["typed_field.js",["field_date.js","field_text.js"]]],function() {
			var content = document.createElement("TABLE");
			var tr, td;
			content.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.appendChild(document.createTextNode("Date of exclusion"));
			tr.appendChild(td = document.createElement("TD"));
			var date = new field_date(null,true,{can_be_empty:false,minimum:<?php echo json_encode($batch["start_date"]);?>,maximum:<?php echo json_encode($batch["end_date"]);?>});
			td.appendChild(date.getHTMLElement());
			content.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			td.appendChild(document.createTextNode("Reason of exclusion"));
			tr.appendChild(td = document.createElement("TD"));
			var reason = new field_text(null,true,{can_be_null:false,min_length:1,max_length:5000});
			td.appendChild(reason.getHTMLElement());
			var p = new popup_window("Exclude <?php echo $student["first_name"]." ".$student["last_name"];?>", "<?php echo theme::make_icon("/static/students/student_16.png", theme::$icons_10["forbidden"])?>",content);
			p.addOkCancelButtons(function(){
				if (date.hasError() || reason.hasError()) return;
				p.freeze("Excluding student");
				service.json("students","exclude_student",{student:<?php echo $people_id;?>,date:date.getCurrentData(),reason:reason.getCurrentData()},function(res) {
					if (!res) { p.unfreeze(); return; }
					location.reload();
				});
			});
			p.show();
		});
	}
	</script>
	<?php 
} else {
	echo "<div class='button' onclick='unexclude();'><img src='".theme::make_icon("/static/students/student_16.png", theme::$icons_10["add"])."'/> Back after exclusion</div>";
	?>
	<?php 
}
?>