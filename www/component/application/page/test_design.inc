<?php
$this->require_javascript("vertical_layout.js");
$this->onload("new vertical_layout('application_container');"); 
$this->require_javascript("horizontal_layout.js");
$this->onload("new horizontal_layout('application_header');"); 
$this->onload("new horizontal_layout('application_content');");
?>
<style type="text/css">
#application_container {
	background-color:#009DE1;
}
#application_header {
	background-color:#009DE1;
	height:30px;
}
#application_title>img {
	height: 28px;
	margin:1px 1px 1px 2px;
	vertical-align:top;
}
#application_title>div {
	display:inline-block;
	font-size:10pt;
	font-family:Verdana;
	color:black;
	font-weight:bold;
	line-height: 14px;
}
.application_menu_item {
	display:inline-block;
	height:25px;
	color: white;
	font-family:Verdana;
	font-weight:bold;
	padding: 5px 2px 0px 2px;
	cursor: pointer;
}
.application_menu_item>img {
	vertical-align:bottom;
	margin-right: 1px;
}
.application_menu_item:hover {
	background-color: rgba(255,255,255,0.3);
}
.application_menu_item.selected {
	background-color: #22bbea;
	position: relative;
}
.application_menu_item.selected:before {
    position: absolute;
    display: inline-block;
    border-left: 7px solid transparent;
    border-bottom: 7px solid #e8e8e8;
    border-right: 7px solid transparent;
    border-bottom-color: #e8e8e8;
    margin-left: -3px;
    left: 50%;
    bottom: 0px;
    content: '';
}
#application_right_menu #user_picture {
	width: 28px;
	height:28px;
    border: 1px solid rgba(0,0,0,0);
    cursor: pointer;
    border-radius: 5px;
}
#application_right_menu #user_picture:hover {
    background-color: rgba(255,255,255,0.4);
    border: 1px solid rgba(128,128,128,0.6);
}
#application_right_menu>img {
	vertical-align:middle;
	margin-right: 3px;
}
#application_search {
	border-radius: 7px;
	background-color: white;
	color: black;
	border: 1px solid transparent;
	width: 100px;
	margin-top: 5px;
	vertical-align: top;
	background: #ffffff url('/static/application/menu/search.png') no-repeat 3px 1px;
	padding-left: 20px;
	font-size: 9pt;
}
#application_left_menu {
	background-color: #009DE1;
	padding-top: 10px;
	height: 100%;
}
.application_left_menu_item {
	color: white;
	font-weight: bold;
	font-family: Verdana;
	padding: 1px 5px 1px 5px;
	height: 18px;
	position: relative;
	border-left: 5px solid transparent;
	cursor: pointer;
}
.application_left_menu_item>img {
	vertical-align: bottom;
}
.application_left_menu_item:hover {
	background-color: rgba(255,255,255,0.3);
	border-left: 5px solid #0080C0;
}
.application_left_menu_item.selected {
	background-color: #ff9933;
	border-left: 5px solid #CC6600;
}
.application_left_menu_item.selected:after {
    position: absolute;
    display: inline-block;
    border-top: 10px solid transparent;
    border-left: 5px solid #ff9933;
    border-bottom: 10px solid transparent;
    border-left-color: #ff9933;
    right: -5px;
    top: 0px;
    content: '';
}
#application_page {
	background-color: #e8e8e8;
	border-top-left-radius: 5px;
	padding: 10px;
}
.test_section {
	margin: 5px;
	border-radius: 5px;
	display: inline-block;
}
.test_section_title {
	background-color: #000000;
	border-top-left-radius: 5px;
	border-top-right-radius: 5px;
	color: white;
	font-weight: normal;
	font-size: 9pt;
	padding: 5px;
}
.test_section_content {
	background-color: white;
	padding: 5px;
	border-bottom-left-radius: 5px;
	border-bottom-right-radius: 5px;
}

.test_section1bis {
	margin: 5px;
	border-radius: 5px;
	display: inline-block;
	box-shadow: 2px 2px 2px #D0D0D0;
}
.test_section1bis_title {
	background: linear-gradient(to bottom, #404040 0%, #000000 100%);
	border-top-left-radius: 5px;
	border-top-right-radius: 5px;
	color: #F0F0F0;
	font-weight: bold;
	font-size: 9pt;
	text-transform: uppercase;
	padding: 5px;
}
.test_section1bis_content {
	background-color: white;
	padding: 5px;
	border-bottom-left-radius: 5px;
	border-bottom-right-radius: 5px;
}

.test_section2 {
	margin: 5px;
	border-radius: 5px;
	border: 1px solid #808080;
	display: inline-block;
}
.test_section2_title {
	background: linear-gradient(to bottom, #f0f0f0 0%, #c0c0c0 100%);
	border-top-left-radius: 5px;
	border-top-right-radius: 5px;
	border-bottom: 1px solid #808080;
	color: #404040;
	font-weight: bold;
	font-size: 9pt;
	padding: 5px;
	text-transform: uppercase;
}
.test_section2_content {
	background-color: white;
	padding: 5px;
	border-bottom-left-radius: 5px;
	border-bottom-right-radius: 5px;
}

.test_section3 {
	background-color: white;
	margin: 5px;
	padding: 5px;
	border-radius: 5px;
	display: inline-block;
}
.test_section3_title {
	background-color: #606060;
	color: white;
	font-weight: normal;
	font-size: 9pt;
	padding: 5px;
}
.test_section3_content {
	background-color: white;
	padding: 5px;
	border-bottom-left-radius: 5px;
	border-bottom-right-radius: 5px;
}

.test_section4 {
	margin: 5px;
	padding: 5px;
	display: inline-block;
	vertical-align: top;
}
.test_section4_title {
	background-color: #606060;
	color: white;
	font-weight: normal;
	font-size: 9pt;
	padding: 3px 5px 3px 5px;
	position: relative;
	left: 10px;
	top: 0px;
	z-index: 1;
	white-space: nowrap;
	display: inline-block;
	border-radius: 5px;
}
.test_section4_content {
	position: relative;
	top: -8px;
	left: 0px;
	background-color: white;
	border: 1px solid #808080;
	padding: 5px;
	padding-top: 10px;
	border-radius: 5px;
}

.test_button1 {
	display: inline-block;
	padding: 2px 3px 2px 3px;
	border-radius: 3px;
	border: 1px solid #0080D0;
	background: linear-gradient(to bottom, #22bbea 0%, #009DE1 100%);
	color: white;
}

.test_button2 {
	display: inline-block;
	padding: 2px 3px 2px 3px;
	border-radius: 3px;
	border: 1px solid #808080;
	background: linear-gradient(to bottom, #f0f0f0 0%, #d0d0d0 100%);
}

.test_button3 {
	display: inline-block;
	padding: 3px 5px 3px 5px;
	border-radius: 7px;
	background: linear-gradient(to bottom, #3498db, #2980b9);
	color: white;
	font-size: 9pt;
	font-weight: bold;
	text-transform: uppercase;
}

.test_button4 {
	display: inline-block;
	padding: 3px 5px 3px 5px;
	border-radius: 7px;
	background: linear-gradient(to bottom, #3e779d, #65a9d7);
	color: white;
	font-size: 9pt;
	font-weight: bold;
}

.test_button5 {
	display: inline-block;
	padding: 3px 5px 3px 5px;
	border-radius: 7px;
	border: 1px solid #22bbea;
	background: linear-gradient(to bottom, #ffffff 0%, #a0c0ff 50%, #80C0FF 50%, #009DE1 100%);
	color: black;
	font-size: 9pt;
	font-weight: bold;
}

</style>
<div id="application_container" style="height:100%;width:100%">
<div id="application_header">
	<div id="application_title">
		<img src='/static/application/logo.png'/>
		<div>Students<br/>Management</div>
	</div>
	<div id="application_menu" layout="fill" style='text-align:center'>
		<div class="application_menu_item">
			<img src='/static/application/menu/dashboard.png'/>
			Home
		</div>
		<div class="application_menu_item selected">
			<img src='/static/application/menu/selection.png'/>
			Selection
		</div>
		<div class="application_menu_item">
			<img src='/static/application/menu/training.png'/>
			Training
		</div>
		<div class="application_menu_item">
			<img src='/static/application/menu/education.png'/>
			Education
		</div>
		<div class="application_menu_item">
			<img src='/static/application/menu/internship.png'/>
			Internship
		</div>
		<div class="application_menu_item">
			<img src='/static/application/menu/alumni.png'/>
			Alumni
		</div>
		<div class="application_menu_item">
			<img src='/static/application/menu/administration.png'/>
			Admin
		</div>
	</div>
	<div id="application_right_menu">
		<input type='text' id='application_search'/>
		<img id='user_picture'/>
		<img src='/static/application/menu/notifications.png'/>
		<img src='/static/application/menu/calendar.png'/>
		<img src='/static/application/menu/logout.png'/>
	</div>
</div>
<div id="application_content" layout="fill">
	<div id="application_left_menu">
		<div class="application_left_menu_item">
			<img src='/static/application/menu/tmp/is.png'/>
			Information Sessions
		</div>
		<div class="application_left_menu_item">
			<img src='/static/application/menu/tmp/exam.png'/>
			Written Exams
		</div>
		<div class="application_left_menu_item" style="padding-left: 20px">
			<img src='/static/application/menu/tmp/subject.png'/>
			Subjects
		</div>
		<div class="application_left_menu_item selected" style="padding-left: 20px">
			<img src='/static/application/menu/dashboard.png'/>
			Exam Centers
		</div>
		<div class="application_left_menu_item" style="padding-left: 20px">
			<img src='/static/application/menu/tmp/results.png'/>
			Results
		</div>
		<div class="application_left_menu_item">
			<img src='/static/application/menu/tmp/interview.png'/>
			Interviews
		</div>
	</div>
	<div id="application_page" layout="fill">
		<div class="test_section">
			<div class="test_section_title">
				Example of content
			</div>
			<div class="test_section_content">
				Bla bla<br/>
				blablabla<br/>
				blablabla<br/>
				blablabla<br/>
				blablabla<br/>
			</div>
		</div>
		<div class="test_section1bis">
			<div class="test_section1bis_title">
				Another Example
			</div>
			<div class="test_section1bis_content">
				Bla bla<br/>
				blablabla<br/>
				blablabla<br/>
				blablabla<br/>
				blablabla<br/>
			</div>
		</div>
		<div class="test_section2">
			<div class="test_section2_title">
				Another Possibility
			</div>
			<div class="test_section2_content">
				Bla bla<br/>
				blablabla<br/>
				blablabla<br/>
				blablabla<br/>
				blablabla<br/>
			</div>
		</div>
		<div class="test_section3">
			<div class="test_section3_title">
				A Third One
			</div>
			<div class="test_section3_content">
				Bla bla<br/>
				blablabla<br/>
				blablabla<br/>
				blablabla<br/>
				blablabla<br/>
			</div>
		</div>
		<div class="test_section4">
			<div class="test_section4_title">
				A Different One
			</div>
			<div class="test_section4_content">
				Bla bla<br/>
				blablablablablablablablabla<br/>
				blablablablablablablablabla<br/>
				blablablablablablablablabla<br/>
				blablablablablablablablabla<br/>
			</div>
		</div>
		<br/><br/>
		<div class="test_button1">A Button</div>&nbsp; &nbsp; 
		<div class="test_button2">A Button</div>&nbsp; &nbsp;
		<div class="test_button3">A Button</div>&nbsp; &nbsp;
		<div class="test_button4">A Button</div>&nbsp; &nbsp;
		<div class="test_button5">A Button</div>&nbsp; &nbsp;
	</div>
</div>
</div>
<script type='text/javascript'>
user_picture = document.getElementById('user_picture');
user_picture.src = "/dynamic/people/service/picture?people=<?php echo PNApplication::$instance->user_people->user_people_id;?>";
user_picture.onclick = function() { userMenu(); };
</script>