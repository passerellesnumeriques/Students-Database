<?php 
class page_send_feedback extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$this->requireJavascript("form.js");
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
<div class='page_title' style='flex:none'>
	<img src='/static/application/feedback_32.png'/>
	Write a feedback about this application
</div>
<div style='flex:1 1 auto;overflow:auto;background-color:white;padding:5px;'>
	<form name='feedback_form'>
	Which type of feedback would you like to send ?<br/>
		<input type='radio' name='type' value='issue' onchange='validateForm();'/> You have an issue / encounter a bug in the application<br/>
		<input type='radio' name='type' value='suggestion' onchange='validateForm();'/> You have a suggestion/idea to improve the application<br/>
		<input type='radio' name='type' value='help' onchange='validateForm();'/> You need help on a screen<br/>
	<br/>
	On which part ?
	<select name='app_part' onchange='validateForm();'>
		<option value=''></option>
		<?php
		$sections = array();
		foreach (PNApplication::$instance->components as $cname=>$comp)
			foreach ($comp->getPluginImplementations() as $pi)
				if ($pi instanceof ApplicationSectionPlugin)
					if ($pi->canAccess())
						array_push($sections, $pi);
		usort($sections, function($s1, $s2) {
			if ($s1->getPriority() <= $s2->getPriority()) return -1;
			return 1;
		});
		foreach ($sections as $section)
			echo "<option value='".$section->getId()."'>".toHTML($section->getName())."</option>";
		?>
		<option value='_NONE_'> Not in a particular section </option>
	</select><br/>
	<div id='section_page' style='display:none;margin-top:2px;'>
	And on which page ? <input name='app_page' type='text' size='50' onchange='validateForm();'/> 
	</div>
	<br/>
	
	Please give a title to your message:<br/>
	<input type='text' name='title' style='width:95%' onchange='validateForm();' onkeyup='validateForm();'><br/>
	Please describe your feedback below, with as much precision as possible:
	<textarea style='width:95%' rows=10 name='text' onchange='validateForm();' onkeyup='validateForm();'></textarea>
	</form>
	<br/>
	
	You can also attach some files/pictures to better illustrate:<br/>
	<form name='ticket_form' enctype="multipart/form-data" action="https://sourceforge.net/p/studentsdatabase/tickets/save_ticket" method="POST" target='_ticket_post_'>
	  <input id='ticket_summary' name="ticket_form.summary" type="hidden" value="">
	  <input id='ticket_text' name="ticket_form.description" type="hidden">
	  <input name="ticket_form.ticket_num" type="hidden">
	  <input name="ticket_form.status" type="hidden" value="open">
	  <input name="ticket_form.assigned_to" type="hidden" value="">
	  <input name="ticket_form.labels" type="hidden" value="">
	  <input value="studentsdatabase" type="hidden">
	  <input value="tickets" type="hidden">
	  <input type="file" name="ticket_form.attachment" multiple="">
	</form>
	<iframe id='_ticket_post_' name='_ticket_post_' style='display:none'></iframe>
</div>
<div class='page_footer' style='flex:none'>
	<button id='button_submit' class='action' disabled='disabled' onclick="sendFeedback();">Submit feedback</button>
</div>
</div>
<script type='text/javascript'>
function validateForm() {
	var form = document.forms['feedback_form'];
	var ok = true;
	
	var feedback_type = get_radio_value(form, 'type');
	if (!feedback_type) ok = false;

	var part = form.elements['app_part'].value;
	var part_selected = false;
	if (part == '') ok = false;
	else if (part != '_NONE_') part_selected = true;
	document.getElementById('section_page').style.display = (part_selected ? "block" : "none");

	var app_page = part_selected ? form.elements['app_page'].value : '';

	var title = form.elements['title'].value;
	if (title.trim().length == 0) ok = false;
	var description = form.elements['text'].value;
	if (description.trim().length == 0) ok = false;

	document.getElementById('button_submit').disabled = (ok ? '' : 'disabled');

	if (ok) {
		var summary;
		var text;
		if (feedback_type == 'issue') summary = "Issue"; else summary = "Suggestion";
		summary += " [";
		if (part == '_NONE_') summary += "General"; else summary += part;
		summary += "]";
		summary += ": "+title;
		text = "Version: <?php global $pn_app_version; echo $pn_app_version;?>\n";
		text += "Domain: <?php echo PNApplication::$instance->local_domain;?>\n";
		text += "Host: <?php echo $_SERVER["HTTP_HOST"];?>\n";
		text += "User: <?php echo PNApplication::$instance->user_management->username;?>\n";
		text += "Browser: <?php echo $_SERVER["HTTP_USER_AGENT"];?>\n";
		if (part_selected) {
			text += "Application section: "+part+"\n";
			text += "Page: "+app_page+"\n";
		}
		text += "\n";
		text += description;
		document.getElementById('ticket_summary').value = summary;
		document.getElementById('ticket_text').value = text; 
	}
}

function sendFeedback() {
	var frame = document.getElementById('_ticket_post_');
	var lock = lock_screen(null, "Sending your feedback...");
	var is_sent = false;
	var sent = function() {
		is_sent = true;
		window.top.info_dialog("<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> Your feedback has been sent. Thank you !");
		location.href = '/dynamic/application/page/overview';
		sent = null;
	};
	frame.onload = sent;
	frame.onunload = sent;
	frame.onerror = sent; 
	document.forms['ticket_form'].submit();
	setTimeout(function() {
		if (window.closing) return;
		if (is_sent) return;
		sent();
	}, 20000);
}
</script>
<?php 
	}
	
}
?>