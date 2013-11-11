<?php 
function people_picture_and_info(&$page, $people_id) {
	$can_edit = PNApplication::$instance->user_management->has_right("edit_people_details");
	$people = SQLQuery::create()->select("People")->where("id",$people_id)->execute_single_row();
	
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
		table_datadisplay_page($page, "People",$people);
		?>
	</td>
	</tr>
	</table>
	<script type='text/javascript'>
	picture('picture','/dynamic/people/service/picture?people=<?php echo $people["id"];?>&version=<?php echo $people["picture_version"]?>',150,200,"<?php echo htmlspecialchars($people["first_name"]." ".$people["last_name"],ENT_QUOTES,"UTF-8");?>");
	<?php if ($can_edit) {?>
	new upload('upload_picture',false,'/dynamic/people/page/set_picture?people=<?php echo $people["id"]?>',
		function(popup){
			popup.setContentFrame('/dynamic/people/page/set_picture?people=<?php echo $people["id"]?>');
		},
		true
	).addHiddenDropArea('picture');
	<?php }?>	
	</script>
	<?php		
}
?>