<?php 

$display->display_column("People","first_name","Personal Information","First Name");

$display->when_arrive_from("Contact_people", "people",
		"Personal Information", "Emails", emails_displayer, emails_importer 
		)

?>