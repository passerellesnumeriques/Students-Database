<?php 
class page_home extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("new frame_header('taining_education_page');");
		?>
		<div id='taining_education_page' icon='/static/students/student_32.png' title='Training & Education' page='/dynamic/students/page/batches'>
			<span class='page_menu_item'><a href="/dynamic/students/page/batches" target='training_education_page_content'><img src='/static/students/batch_16.png'/>Batches & Classes</a></span>
		</div>
		<?php 
	}
	
}
?>