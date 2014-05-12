<?php 
class CurriculumMenuItem {
	
	public $id;
	public $icon;
	public $text;
	public $url;
	public $tooltip;
	public $start_url;
	
	public function __construct($id, $icon, $text, $url, $tooltip, $start_url = null) {
		$this->id = $id;
		$this->icon = $icon;
		$this->text = $text;
		$this->url = $url;
		$this->tooltip = $tooltip;
		$this->start_url = $start_url;
	}
	
	public function generate() {
		echo "<div";
		echo " id=\"".htmlentities($this->id, ENT_HTML401 | ENT_QUOTES, "UTF-8")."\"";
		echo " icon=\"".htmlentities($this->icon, ENT_HTML401 | ENT_QUOTES, "UTF-8")."\"";
		echo " text=\"".htmlentities($this->text, ENT_HTML401 | ENT_QUOTES, "UTF-8")."\"";
		echo " link=\"".htmlentities($this->url, ENT_HTML401 | ENT_QUOTES, "UTF-8")."\"";
		echo " tooltip=\"".htmlentities($this->tooltip, ENT_HTML401 | ENT_QUOTES, "UTF-8")."\"";
		echo " start_url=\"".htmlentities($this->start_url, ENT_HTML401 | ENT_QUOTES, "UTF-8")."\"";
		echo "></div>";
	}
	
}

function curriculum_tree(&$page, $menu_items) {
	$page->addInitScript("window.top.require('datamodel.js');");
	$page->requireJavascript("splitter_vertical.js");
	$page->requireJavascript("vertical_layout.js");
	$page->requireJavascript("header_bar.js");
	theme::css($page, "header_bar.css");
	$page->requireJavascript("frame_header.js");
	theme::css($page, "frame_header.css");
	
	$page->requireJavascript("tree.js");
	theme::css($page, "tree.css");
	$page->requireJavascript("curriculum_objects.js");
	$page->addJavascript("/static/curriculum/curriculum_tree.js");
	$page->addJavascript("/static/curriculum/curriculum_tree_root.js");
	$page->addJavascript("/static/curriculum/curriculum_tree_all_students.js");
	$page->addJavascript("/static/curriculum/curriculum_tree_current_students.js");
	$page->addJavascript("/static/curriculum/curriculum_tree_alumni.js");
	$page->addJavascript("/static/curriculum/curriculum_tree_batch.js");
	$page->addJavascript("/static/curriculum/curriculum_tree_period.js");
	$page->addJavascript("/static/curriculum/curriculum_tree_specialization.js");
	$page->addJavascript("/static/curriculum/curriculum_tree_class.js");
	
	require_once("component/curriculum/CurriculumJSON.inc");
	
	$can_edit = PNApplication::$instance->user_management->has_right("manage_batches");
?>
<div style='width:100%;height:100%' id='container'>
	<div id='left'>
		<div id='tree_header' icon='/static/curriculum/batch_16.png' title='Batches &amp; Classes'>
			<?php if ($can_edit) { ?>
			<div class='button_verysoft' onclick='createNewBatch();'>
				<img src='<?php echo theme::make_icon("/static/curriculum/batch_16.png", theme::$icons_10["add"]);?>'/>
				New Batch
			</div>
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
	<div id='right'>
	<?php foreach ($menu_items as $item) $item->generate(); ?>
	</div>
</div>
<style type='text/css'>
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
<script type='text/javascript'>
new header_bar('tree_header','toolbar');
new vertical_layout('left');
new vertical_layout('right');
new splitter_vertical('container',0.25);
var page_header = new frame_header('right', 'students_page', 28, 'white', 'bottom');
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
});
</script>
<?php 
}
?>