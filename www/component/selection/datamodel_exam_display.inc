<?php 
$sm->internalGetTable("ExamSubjectQuestion")
	->setDisplayHandler(null, new datamodel\FieldsDisplayHandler("Exam Subject Question",
		array(
			"max_score"=>"Max Score",
			"correct_answer"=>"Correct Answer",
			"choices"=>"Choices",
		)
	));

?>