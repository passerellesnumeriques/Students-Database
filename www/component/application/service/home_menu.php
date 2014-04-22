<?php
class service_home_menu extends Service {
    public function get_required_rights() { return array(); }
	public function documentation() { echo "Provides the application home menu"; }
	public function input_documentation() { echo "No"; }
	public function output_documentation() { echo "The HTML to put in the menu"; }
	public function get_output_format($input) { return "text/html"; }
    public function execute(&$component, $input) {
?>
<a class='application_left_menu_item' href='/dynamic/application/page/overview'>
	<img src='/static/application/overview_white.png'/>
    Overview
</a>
<a class='application_left_menu_item'>
	<img src='/static/news/news_white.png'/>
    Updates
</a>
<a class='application_left_menu_item'>
    To Do List
</a>
<a class='application_left_menu_item' href="/dynamic/calendar/page/calendars">
    <img src='/static/calendar/calendar_white.png'/>
    Your Calendars
</a>
<a class='application_left_menu_item' href="/dynamic/people/page/profile?people=<?php echo PNApplication::$instance->user_people->user_people_id;?>">
    <img src='/static/people/profile_white.png'/>
    Your Profile
</a>
<a class='application_left_menu_item' onclick="location.href='/dynamic/application/page/logout';">
    <img src='/static/application/logout_white.png'/>
    Logout and Exit
</a>
<div style="margin-top:30px">
	<a href='#' onclick="service.json('theme','set_theme',{theme:'default'},function(res){if(res)window.parent.location.reload();});">Back to first design</a>
</div>
<?php
    }
}
?>