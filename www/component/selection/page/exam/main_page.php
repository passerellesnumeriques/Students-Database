<?php

require_once("/../selection_page.inc");
class page_exam_main_page extends selection_page {
	
	public function get_required_rights() {}
	
	public function execute_selection_page(&$page) {
		$page->add_javascript("/static/widgets/page_header.js");
		$page->add_javascript("/static/widgets/vertical_layout.js");
		$page->onload("new vertical_layout('container');");
		$page->onload("new page_header('page_header',true);");
		$page->onload("new exam_subject_main_page('exam_content');");
		
		?>
		<div id = "container" style = "width:100%; height:100%">
			<div id = "page_header" icon = "/static/selection/exam/exam_16.png" title = "Entrance Examinations">
				<div class = "button" onclick = "location.assign('/dynamic/selection/page/selection_main_page');"><img src = "<?php echo theme::$icons_16['back'];?>"/> Back to selection</div>
			</div>
			<div id = "page_content" style = "overflow:auto" layout = "fill">
				<div id = "exam_content"></div>
				<div id = "eligibility_rules_content">TODO eligibility rules main screen</div>
			</div>
		</div>
		<script type = "text/javascript">
			function exam_subject_main_page(container){
				var t = this;
				if(typeof container == "string")
					container = document.getElementById(container);
				t.table = document.createElement('table');
				
				t._init = function(){
					t._setTableHeaderAndStyle();
					t._setTableContent();
					container.appendChild(t.table);
				}
				
				t._setTableHeaderAndStyle = function(){
					var th = document.createElement("th");
					th.innerHTML = "Exams Subjects";
					t.table.appendChild((document.createElement("tr")).appendChild(th));
					//Set the style
					setCommonStyleTable(t.table, th, "#DADADA");
					t.table.marginLeft = "10px";
					t.table.marginRight = "10px";
					t.table.width = "98%";
				}
				
				t._setTableContent = function(){
				
				}
				
				t._init();
			}
		</script>
		<?php
	}
}
?>