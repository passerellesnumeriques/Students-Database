<?php 
class page_exam_link_IS_with_exam_center extends Page {
	
	public function getRequiredRights() { return array("manage_exam_center"); }
	
	public function execute() {
		$this->requireJavascript("section.js");
		
		require_once("component/selection/SelectionInformationSessionJSON.inc");
		$q = SQLQuery::create()
			->select("InformationSession")
			->join("InformationSession", "Applicant", array("id"=>"information_session"))
			->groupBy("Applicant", "information_session")
			->countOneField("Applicant", "people", "nb_applicants")
			->join("InformationSession", "ExamCenterInformationSession", array("id"=>"information_session"))
			->field("ExamCenterInformationSession", "exam_center", "linked_exam_center_id")
			;
		SelectionInformationSessionJSON::InformationSessionSQL($q);
		$is_list = $q->execute();
		
		$centers = SQLQuery::create()
			->select("ExamCenter")
			->field("id")
			->field("name")
			->execute();
		?>
		<div>
			<div id='section_is' 
				style='display:inline-block;margin:10px'
				icon='/static/selection/IS/IS_16.png'
				title='Available Information Sessions'
			>
				<table><tbody id='is_table'>
					<tr><th></th><th>Information Session</th><th>Applicants</th></tr>
				</tbody></table>
			</div>
			<div id='section_ec' 
				style='display:inline-block;margin:10px'
				icon='/static/selection/exam/exam_center_16.png'
				title='Exam Centers'
			>
				<div id='centers'></div>
			</div>
		</div>
		<script type='text/javascript'>
		var is_list = <?php echo SelectionInformationSessionJSON::InformationSessionsJSON($is_list);?>;
		<?php 
		// add information to each IS
		for ($i = 0; $i < count($is_list); $i++) {
			echo "is_list[$i].nb_applicants = ".$is_list[$i]["nb_applicants"].";\n";
			echo "is_list[$i].linked_exam_center_id = ".json_encode($is_list[$i]["linked_exam_center_id"]).";\n";
		}
		?>
		
		var centers = [<?php
		$first = true;
		foreach ($centers as $center) {
			if ($first) $first = false; else echo ",";
			echo "{id:".$center["id"].",name:".json_encode($center["name"])."}";
		} 
		?>];
		
		var section_is = sectionFromHTML('section_is');
		var section_ec = sectionFromHTML('section_ec');

		function createISRow(is) {
			var table = document.getElementById('is_table');
			var tr,td;
			table.appendChild(tr = document.createElement("TR"));
			tr.appendChild(td = document.createElement("TD"));
			var cb = document.createElement("INPUT"); cb.type = 'checkbox';
			td.appendChild(cb);
			tr.appendChild(td = document.createElement("TD"));
			td.appendChild(document.createTextNode(is.name));
			tr.appendChild(td = document.createElement("TD"));
			td.appendChild(document.createTextNode(is.nb_applicants));
			td.style.textAlign = 'center';
		}

		function getLinkedIS(center_id) {
			var list = [];
			for (var i = 0; i < is_list.length; ++i) if (is_list[i].linked_exam_center_id == center_id) list.push(is_list[i]);
			return list;
		}

		function addCenter(center) {
			var container = document.getElementById('centers');
			var div = document.createElement("DIV"); container.appendChild(div);
			var radio = document.createElement("INPUT");
			radio.type = 'radio';
			radio.name = 'exam_center';
			div.appendChild(radio);
			div.appendChild(document.createTextNode(center.name));
			var is = getLinkedIS(center.id);
			for (var i = 0; i < is.length; ++i) {
				var is_div = document.createElement("DIV");
				is_div.style.marginLeft = "20px";
				is_div.appendChild(document.createTextNode(" - "+is[i].name));
				div.appendChild(is_div);
			}
		}
		
		for (var i = 0; i < is_list.length; ++i)
			if (!is_list[i].linked_exam_center_id)
				createISRow(is_list[i]);

		for (var i = 0; i < centers.length; ++i)
			addCenter(centers[i]);
		</script>
		<?php 
	}
	
}
?>