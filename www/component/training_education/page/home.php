<?php 
class page_home extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("new frame_header('training_education_page');");
		?>
		<div id='training_education_page' style='width:100%;height:100%' icon='/static/students/student_32.png' title='Training & Education' page="batches_classes">
			<a class='page_menu_item' target='training_education_page_content' href='batches_classes'><img src='/static/curriculum/batch_16.png'/> Batches &amp; Classes</a>
			<span id='student_search'></span>
		</div>
		<script type='text/javascript'>
		require("autocomplete.js",function() {
			var ac = new autocomplete('student_search', 3, 'Search a student', function(name, handler) {
				service.json("students","search_student_by_name", {name:name}, function(res) {
					if (!res) { handler([]); return; }
					var items = [];
					for (var i = 0; i < res.length; ++i) {
						var item = new autocomplete_item(res[i].people_id, res[i].first_name+' '+res[i].last_name, res[i].first_name+' '+res[i].last_name+" (Batch "+res[i].batch_name+")");
						items.push(item); 
					}
					handler(items);
				});
			}, function(item) {
				document.getElementById('training_education_page_content').src = "/dynamic/people/page/profile?people="+item.value;
			}, 250);
			setBorderRadius(ac.input,8,8,8,8,8,8,8,8);
			setBoxShadow(ac.input,-1,2,2,0,'#D8D8F0',true);
			ac.input.style.background = "#ffffff url('"+theme.icons_16.search+"') no-repeat 3px 1px";
			ac.input.style.padding = "2px 4px 2px 23px";
		});
		</script>
		<?php 
	}
	
}
?>