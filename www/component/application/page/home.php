<?php 
class page_home extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/section/section.js");
		$this->onload("section_from_html('news');");
		$this->onload("section_from_html('links');");
		$this->onload("section_from_html('dev_links');");
?>
<table style='width:100%'>
<tr>
	<td valign='top' style='width:50%'>
		<div id='news' icon='/static/news/news.png' title='Updates' collapsable='true' style="margin:10px">
			<div style='padding:5px'>
				Not yet implemented<br/>
				Here we will have the updates, what people did, in a kind of <i>facebook</i> style,<br/>
				but the news will be about what people did: X updates health information of student Y...
			</div>
		</div>

		<div id='dev_links'
			title="Temporary / for development and test purposes"
			style="margin:10px"
			collapsable='true' 
		>
			<div style='padding:5px'>
			<a href="/dynamic/contact/page/organizations?creator=Selection">Organizations for Selection</a><br/>
			<a href="/dynamic/contact/page/organization_profile?organization=1">Organization profile</a><br/>
			<a href="#" onclick="postData('/dynamic/people/page/create_people',{types:['student'],icon:'/static/application/icon.php?main=/static/students/student_32.png&small='+theme.icons_16.add+'&where=right_bottom',title:'New Student',redirect:'/dynamic/application/page/home'});return false;">Create student</a><br/>
			<a href="#" onclick="postData('/dynamic/data_model/page/create_data',{root_table:'Student'});return false;">Create data</a><br/>
			<a href="/dynamic/excel/page/test">Excel</a><br/>
			<a href="/dynamic/selection/page/IS_profile">IS profile</a><br/>
			<a href="/dynamic/students/page/import_students">import student</a><br/>
			<a href="/dynamic/selection/page/custom_import_applicants">import applicants</a><br/>
			<a href="/dynamic/selection/page/manage_exam_topic_for_eligibility_rules">exam topic</a><br/>
			<!-- 
			<a href="/dynamic/data_import/page/build_excel_import?import=create_template">Create Excel Import Template</a><br/>
			<a href="#" onclick="postData('/dynamic/data_model/page/create_data',{icon:'/static/application/icon.php?main=/static/students/student_32.png&small='+theme.icons_16.add+'&where=right_bottom',title:'Create new student',root_table:'Student'});return false;">Create a student</a><br/>
			<a href="#" onclick="postData('/dynamic/data_model/page/create_data',{icon:'/static/application/icon.php?main=/static/students/student_32.png&small='+theme.icons_16.add+'&where=right_bottom',title:'Create new people',root_table:'People'});return false;">Create a people</a><br/>
			<a href="#" onclick="postData('/dynamic/data_model/page/create_data',{icon:'/static/application/icon.php?main=/static/students/student_32.png&small='+theme.icons_16.add+'&where=right_bottom',title:'Create new staff',root_table:'StaffPosition'});return false;">Create a staff</a><br/>
			 -->
			<br/><br/>
			Number of people: <?php
			
			if (isset($_GET["create_people"])) {
				$nb = intval($_GET["create_people"]);
				set_time_limit(500);
				$a = array();
				while ($nb-- > 0) array_push($a, array("first_name"=>"a","last_name"=>"a"));
				SQLQuery::create()->bypass_security()->insert_multiple("People", $a);
			}
			
			echo SQLQuery::create()->bypass_security()->select("People")->count()->execute_single_value(); 
			?>
			<br/><a href='?create_people=10'>Create 10 more</a>
			<br/><a href='?create_people=100'>Create 100 more</a>
			<br/><a href='?create_people=1000'>Create 1 000 more</a>
			<br/><a href='?create_people=10000'>Create 10 000 more</a>
			<br/><a href='?create_people=100000'>Create 100 000 more</a>
			<br/><a href='?create_people=200000'>Create 200 000 more</a>
			</div>
		</div>
	</td>
	<td valign='top'>
		<div id='calendars' icon='/static/calendar/event.png' title='Your Calendars' collapsable='true' style="margin:10px;">
			<div id='calendars_container' style="height:300px;"><img src='<?php echo theme::$icons_16["loading"];?>'/></div>
		</div>

		<div id='links'
			icon="/static/application/link.png"
			title="Links"
			style="margin:10px"
			collapsable='true' 
		>
			<div style='padding:5px'>
				<a href='http://www.passerellesnumeriques.org'>Passerelles numériques Web Site</a><br/>
				<a href='https://pnresources.devoteam.com/'>PN Resources</a><br/>
			</div>
		</div>
	</td>
</tr>
</table>
<script type='text/javascript'>
var calendars_section = section_from_html('calendars');
require("calendar.js");
require("calendar_view.js");
require("calendar_view_week.js");
require("calendar.js",function() {
	var manager = new CalendarManager();
	require("calendar_view.js",function() {
		new CalendarView(manager, "week", 'calendars_container', function() {
		});
	});
	window.top.CalendarsProviders.get(function(provider) {
		var div = document.createElement("DIV");
		div.innerHTML = "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> <img src='"+provider.getProviderIcon()+"' width=16px height=16px style='vertical-align:bottom'/> Loading "+provider.getProviderName()+"...";
		div.style.margin = "2px";
		div.style.paddingRight = "5px";
		calendars_section.addTool(div);
		provider.getCalendars(function(calendars) {
			div.innerHTML = "<img src='"+provider.getProviderIcon()+"' width=16px height=16px style='vertical-align:bottom'/> "+provider.getProviderName()+" ("+calendars.length+")";
			div.className = "button";
			fireLayoutEventFor(calendars_section.element);
			// TODO onclick
			for (var i = 0; i < calendars.length; ++i)
				manager.addCalendar(calendars[i]);
		});
	});
});
</script>
<?php
	}
	
}
?>