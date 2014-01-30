<?php
return array(
	new AccessRightCategory("Geography", array(
		new BooleanRight("edit_geography","Create/edit geography fields"),
		new BooleanRight("manage_geography","Manage Geography fields (create/edit/remove)",array(true=>array("edit_geography"=>true)))
	))
);
?>