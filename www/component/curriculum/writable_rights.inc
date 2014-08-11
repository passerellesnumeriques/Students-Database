<?php 
return array(
	new AccessRightCategory("In PN",array(
		new BooleanRight("post_batch_message", "Post message in the Batch category of section Students",array(true=>array("consult_curriculum"=>true))),
		new BooleanRight("edit_curriculum", "Edit batches, academic periods, specializatons, and subjects per academic period",array(true=>array("consult_curriculum"=>true,"post_batch_message"=>true))),
	))
);
?>