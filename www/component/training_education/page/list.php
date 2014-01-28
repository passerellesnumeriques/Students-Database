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
	}
);

</script>
<?php 
	}
	
}
?>