<?php 
class page_new_campaign extends Page {
	
	public function getRequiredRights() { return array("manage_selection_campaign"); }
	
	public function execute() {
?>
<div style='background-color:white;padding:5px;'>
	<div id='page1'>
		Enter a name for the new campaign: <input id='campaign_name' type='text' size=30 maxlength=30 onkeyup='setTimeout(validateFirstPage,1);'/><br/>
		<br/>
		Is this campaign handling several programs (leading to the creation of several batches of students) ? 
		<select id='has_programs' onchange='validateFirstPage();'>
			<option value='No'>No</option>
			<option value='Yes'>Yes</option>
		</select>
	</div>
	<div id='page2' style='display:none'>
		How many programs ? <input id='nb_programs' type='text' size=3 maxlength=3 value='2' onkeyup='setTimeout(validatePage2,1);'/>
	</div>
	<div id='page3' style='display:none'>
		Enter names of the programs:<br/>
	</div>
</div>
<script type='text/javascript'>
var popup = window.parent.getPopupFromFrame(window);

var nb_programs = 1;
function create() {
	popup.freeze("Creation of the new selection campaign...");
	var data = {};
	data.name = document.getElementById('campaign_name').value.trim();
	if (nb_programs > 1) {
		data.programs = [];
		for (var i = 0; i < nb_programs; ++i)
			data.programs.push(document.getElementById('program_name_'+i).value.trim());
	}
	service.json("selection","create_campaign",data,function(res) {
		popup.close();
		// refresh menu
		getIFrameWindow(findFrame('pn_application_frame')).reloadMenu();
		// refresh page
		getIFrameWindow(findFrame('application_frame')).location.reload();
	});
}
function showPage2() {
	document.getElementById('page1').style.display = 'none';
	document.getElementById('page2').style.display = '';
	popup.removeButton('next');
	popup.addNextButton(showPage3);
	validatePage2();
	layout.changed(document.body);
}
function showPage3() {
	document.getElementById('page2').style.display = 'none';
	var page3 = document.getElementById('page3');
	page3.style.display = '';
	for (var i = 0; i < nb_programs; ++i) {
		var input = document.createElement("INPUT");
		input.type = 'text';
		input.size = 30;
		input.maxLength = 30;
		input.id = 'program_name_'+i;
		input.style.margin = "2px 0px";
		input.onkeyup = function() { setTimeout(validatePage3,1); };
		page3.appendChild(input);
		page3.appendChild(document.createElement("BR"));
	}
	popup.removeButton('next');
	popup.addCreateButton(create);
	validatePage3();
	layout.changed(document.body);
}

popup.addCancelButton();
popup.addCreateButton(create);
popup.disableButton('create');

function validateFirstPage() {
	var has_programs = document.getElementById('has_programs').value;
	if (has_programs == 'Yes') {
		if (!popup.hasButton('next')) {
			popup.removeButton('create');
			popup.addNextButton(showPage2);
		}
	} else {
		if (!popup.hasButton('create')) {
			popup.removeButton('next');
			popup.addCreateButton(create);
		}
	}
	var name = document.getElementById('campaign_name').value;
	name = name.trim();
	if (name.length == 0) {
		popup.disableButton('create');
		popup.disableButton('next');
	} else {
		popup.enableButton('create');
		popup.enableButton('next');
	}
}
function validatePage2() {
	var nb = document.getElementById('nb_programs').value;
	nb = nb.trim();
	if (nb.length == 0) nb = null;
	else {
		nb = parseInt(nb);
		if (isNaN(nb)) nb = null;
		else if (nb <= 1) nb = null;
	}
	if (nb == null)
		popup.disableButton('next');
	else
		popup.enableButton('next');
	nb_programs = nb;
}
function validatePage3() {
	for (var i = 0; i < nb_programs; ++i) {
		var name = document.getElementById('program_name_'+i).value.trim();
		if (name.length == 0) {
			popup.disableButton('create');
			return;
		}
	}
	popup.enableButton('create');
}
</script>
<?php
	}
	
}
?>