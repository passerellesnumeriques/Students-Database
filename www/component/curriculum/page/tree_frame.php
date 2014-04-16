<?php 
class page_tree_frame extends Page {
	
	public function get_required_rights() { return array("consult_curriculum"); }
	
	public function execute() {
		$this->add_init_script("window.top.require('datamodel.js');");
		$this->require_javascript("horizontal_layout.js");
		$this->onload("new horizontal_layout('curriculum_tree_frame_container');");
		$this->require_javascript("vertical_layout.js");
		$this->onload("new vertical_layout('curriculum_tree_container');");
		$this->require_javascript("header_bar.js");
		theme::css($this, "header_bar.css");
		$this->onload("new header_bar('tree_header','toolbar');");
		
		$this->require_javascript("tree.js");
		theme::css($this, "tree.css");
		$this->require_javascript("curriculum_objects.js");
		$this->add_javascript("/static/curriculum/curriculum_tree.js");
		$this->add_javascript("/static/curriculum/curriculum_tree_root.js");
		$this->add_javascript("/static/curriculum/curriculum_tree_all_students.js");
		$this->add_javascript("/static/curriculum/curriculum_tree_current_students.js");
		$this->add_javascript("/static/curriculum/curriculum_tree_alumni.js");
		$this->add_javascript("/static/curriculum/curriculum_tree_batch.js");
		$this->add_javascript("/static/curriculum/curriculum_tree_period.js");
		$this->add_javascript("/static/curriculum/curriculum_tree_specialization.js");
		$this->add_javascript("/static/curriculum/curriculum_tree_class.js");
		
		require_once("component/curriculum/CurriculumJSON.inc");
		
		$can_edit = PNApplication::$instance->user_management->has_right("manage_batches");
?>
<style type="text/css">
#curriculum_tree_container {
	border-left: 1px solid #808080;
}
#tree_footer {
	width: 100%;
	background-color: white;
	padding: 5px;
	border-top: 1px solid #A0A0A0;
	box-shadow: 0px 2px 5px #D0D0D0 inset;
}
#tree_footer_title {
	font-weight: bold;
	color: #000080;
	white-space: nowrap;
}
#tree_footer_title img {
	margin-right: 3px;
	vertical-align: bottom;
}
</style>
<div id="curriculum_tree_frame_container" style="width:100%;height:100%;overflow:hidden">
	<iframe name="curriculum_tree_frame" id="curriculum_tree_frame" style="border:none;" layout="fill"></iframe>
	<div id="curriculum_tree_container" layout="250">
		<div id='tree_header' icon='/static/curriculum/batch_16.png' title='Batches &amp; Classes'>
			<?php if ($can_edit) { ?>
			<button class='flat' onclick='create_new_batch();'>
				<img src='<?php echo theme::make_icon("/static/curriculum/batch_16.png", theme::$icons_10["add"]);?>'/>
				New Batch
			</button>
			<?php } ?>
		</div>
		<div id='tree' style='overflow-y:auto;overflow-x:auto;background-color:white;width:100%;height:100%' layout='fill'></div>
		<div id='tree_footer'>
			<table><tr>
				<td id='tree_footer_title' valign=top></td>
				<td id='tree_footer_content' valign=top></td>
			</tr></table>
		</div>
	</div>
</div>
<script type='text/javascript'>
var tr = new tree('tree');
tr.addColumn(new TreeColumn(""));

//List of specializations
var specializations = <?php echo CurriculumJSON::SpecializationsJSON(); ?>;
// Batches
var batches = <?php echo CurriculumJSON::BatchesJSON(); ?>;
var can_edit_batches = <?php echo $can_edit ? "true" : "false";?>;
batches.sort(function(b1,b2) { return parseSQLDate(b1.start_date).getTime() - parseSQLDate(b2.start_date).getTime();});

window.top.require("datamodel.js", function() {
	window.curriculum_root = new CurriculumTreeNode_Root(tr);
	//Initilization of the page
	/*
	var url = new URL(location.href);
	var page = typeof url.params.page != 'undefined' ? url.params.page : "list";
	var node = window.curriculum_root.findTag(url.hash);
	if (node) node.item.select();
	for (var i = 0; i < page_header.getMenuItems().length; ++i) {
		var item = page_header.getMenuItems()[i];
		if (item.id == page) {
			var frame = document.getElementById('students_page');
			frame.src = item.link.href;
		}
	}
	*/
});
</script>
<?php 
	}
	
}
?>