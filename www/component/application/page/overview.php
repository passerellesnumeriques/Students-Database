<?php 
class page_overview extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
?>
<style type='text/css'>
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

.test_section5 {
	margin: 5px;
	border-radius: 5px;
	display: inline-block;
	box-shadow: 2px 2px 2px #D0D0D0;
}
.test_section5_title {
	background: linear-gradient(to bottom, #c0c0c0 0%, #f0f0f0 100%);
	border-top-left-radius: 5px;
	border-top-right-radius: 5px;
	color: #404040;
	font-weight: bold;
	font-size: 9pt;
	text-transform: uppercase;
	padding: 5px;
}
.test_section5_content {
	background-color: white;
	padding: 5px;
	border-bottom-left-radius: 5px;
	border-bottom-right-radius: 5px;
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
<div class="test_section5">
	<div class="test_section5_title">
		Another Possibility
	</div>
	<div class="test_section5_content">
		Bla bla<br/>
		blablabla<br/>
		blablabla<br/>
		blablabla<br/>
		blablabla<br/>
	</div>
</div>

<br/><br/>
<div class="test_button1">A Button</div>&nbsp; &nbsp; 
<div class="test_button2">A Button</div>&nbsp; &nbsp;
<div class="test_button3">A Button</div>&nbsp; &nbsp;
<div class="test_button4">A Button</div>&nbsp; &nbsp;
<div class="test_button5">A Button</div>&nbsp; &nbsp;

<?php 		
	}
	
}
?>