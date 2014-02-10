<?php 
function people_picture_and_info(&$page, $people_id) {
	$can_edit = PNApplication::$instance->user_management->has_right("edit_people_details");
	$q = SQLQuery::create()
		->select("People")
		->whereValue("People","id",$people_id)
		;
	require_once("component/data_model/Model.inc");
	foreach (DataModel::get()->getTable("People")->getColumns() as $col) {
		$q->field("People", $col->name, "PEOPLE_COL_".$col->name);
	}
	$people = $q->executeSingleRow();
	
	$page->add_javascript("/static/storage/upload.js");
	$page->add_javascript("/static/storage/picture.js");
	?>
	<table>
	<tr>
	<td valign=top>
		<div id='picture'></div>
		<div id='upload_picture' style='width:150px;overflow:hidden'></div>
	</td>
	<td valign=top>
		<?php
		require_once("component/data_model/page/table_datadisplay.inc");
		table_datadisplay_page($page, "People", $people, $q);
		?>
	</td>
	</tr>
	</table>
	<script type='text/javascript'>
	picture('picture','/dynamic/people/service/picture?people=<?php echo $people["PEOPLE_COL_id"];?>&version=<?php echo $people["PEOPLE_COL_picture_version"]?>',150,200,"<?php echo htmlspecialchars($people["PEOPLE_COL_first_name"]." ".$people["PEOPLE_COL_last_name"],ENT_QUOTES,"UTF-8");?>");
	<?php if ($can_edit) {?>
	new upload('upload_picture',false,'/dynamic/people/page/set_picture?people=<?php echo $people["PEOPLE_COL_id"]?>',
		function(popup){
			popup.setContentFrame('/dynamic/people/page/set_picture?people=<?php echo $people["PEOPLE_COL_id"]?>');
		},
		true, null, "Change picture"
	).addHiddenDropArea('picture');
	<?php }?>	
	</script>
	<?php		
}
?>