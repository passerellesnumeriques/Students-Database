<?php 
class page_list extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->require_javascript("data_list.js");
?>
<div id='list_container' style='width:100%;height:100%'>
</div>
<script type='text/javascript'>
var url = new URL(location.href);

function build_filters() {
	var filters = [];
	if (url.params['batches']) {
		var batches = url.params['batches'].split(',');
		var filter = {category:'Student',name:'Batch',data:{value:batches[0]},force:true};
		var f = filter;
		for (var i = 1; i < batches.length; ++i) {
			f.or = {data:{value:batches[i]}};
			f = f.or; 
		}
		filters.push(filter);
	}
	if (url.params['period']) {
		filters.push({category:'Student',name:'Period',data:{value:url.params['period']},force:true});
	}
	if (url.params['spe'] != null) {
		filters.push({category:'Student',name:'Specialization',data:{value:url.params['spe']},force:true});
	}
	if (url.params['class'] != null) {
		filters.push({category:'Student',name:'Class',data:{value:url.params['class']},force:true});
	}
	return filters;
}

var students_list = new data_list(
	'list_container',
	url.params['period'] || url.params['class'] ? 'StudentClass' : 'Student',
	[
		'Personal Information.First Name',
		'Personal Information.Last Name',
		'Personal Information.Gender',
		'Student.Batch',
		'Student.Specialization'
	],
	build_filters(),
	function (list) {
		if (url.params['batches']) {
			var batches = url.params['batches'].split(',');
			if (batches.length == 1) {
				var import_students = document.createElement("DIV");
				import_students.className = "button";
				import_students.innerHTML = "<img src='"+theme.icons_16._import+"' style='vertical-align:bottom'/> Import Students";
				import_students.onclick = function() {
					postData('/dynamic/students/page/import_students',{
						batch:batches[0],
						redirect: "/dynamic/training_education/page/batches_classes"
					});
				};
				students_list.addHeader(import_students);
				var create_student = document.createElement("DIV");
				create_student.className = "button";
				create_student.innerHTML = "<img src='/static/application/icon.php?main=/static/students/student_16.png&small="+theme.icons_10.add+"&where=right_bottom' style='vertical-align:bottom'/> Create Student";
				create_student.onclick = function() {
					postData("/dynamic/people/page/create_people",{
						icon: "/static/application/icon.php?main=/static/students/student_32.png&small="+theme.icons_16.add+"&where=right_bottom",
						title: "Create New Student",
						types: ["student"],
						student_batch: batches[0],
						redirect:"/dynamic/training_education/page/batches_classes"
					});
				};
				students_list.addHeader(create_student);
			}
		}
		if (url.params['period'] || url.params['class']) {
			var assign = document.createElement("DIV");
			assign.className = "button";
			assign.innerHTML = "<img src='/static/application/icon.php?main=/static/students/student_16.png&small="+theme.icons_10.edit+"&where=right_bottom' style='vertical-align:bottom'/> Assign students to "+(url.params['class'] ? "class" : "classes");
			assign.onclick = function() {
				require("popup_window.js",function() {
					var p = new popup_window("Assign Students", "/static/application/icon.php?main=/static/students/student_16.png&small="+theme.icons_10.edit+"&where=right_bottom", "");
					var f = p.setContentFrame("/dynamic/students/page/assign_classes?"+(url.params['class'] ? "class="+url.params['class'] : "period="+url.params['period']));
					p.addOkCancelButtons(function() {
						p.freeze("Saving class assignment...");
						getIFrameWindow(f).save(function(msg){
							p.set_freeze_content(msg);
						},function(){
							p.close();
							students_list.reloadData();
						});
					});
					p.show();
				});
			};
			students_list.addHeader(assign);
		}
		// buttons that need additional info: dynamic
		service.customOutput("training_education","list_buttons",url.params,function(js){
			eval(js);
		});
	}
);

</script>
<?php 
	}
	
}
?>