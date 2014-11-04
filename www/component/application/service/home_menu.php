<?php
class service_home_menu extends Service {
    public function getRequiredRights() { return array(); }
	public function documentation() { echo "Provides the application home menu"; }
	public function inputDocumentation() { echo "No"; }
	public function outputDocumentation() { echo "The HTML to put in the menu"; }
	public function getOutputFormat($input) { return "text/html"; }
    public function execute(&$component, $input) {
?>
<a class='application_left_menu_item' href='/dynamic/application/page/overview'>
	<img src='/static/application/overview_white.png'/>
    Overview
</a>
<a class='application_left_menu_item' href='/dynamic/news/page/news'>
	<img src='/static/news/news_white.png'/>
    Updates
</a>
<?php
/* 
<a class='application_left_menu_item'>
    To Do List
</a>
*/
?>
<a class='application_left_menu_item' href="/dynamic/calendar/page/calendars">
    <img src='/static/calendar/calendar_white.png'/>
    Your Calendars
</a>
<a class='application_left_menu_item' href="/dynamic/people/page/profile?people=<?php echo PNApplication::$instance->user_management->people_id;?>">
    <img src='/static/people/profile_white.png'/>
    Your Profile
</a>
<a class='application_left_menu_item' href="/dynamic/search/page/search">
    <img src='<?php echo theme::$icons_16["search_white"];?>'/>
    Search
</a>
<a class='application_left_menu_item' onclick="location.href='/dynamic/application/page/logout';">
    <img src='/static/application/logout_white.png'/>
    Logout and Exit
</a>
<div class="application_left_menu_separator"></div>
<a class='application_left_menu_item' href="/dynamic/application/page/send_feedback">
    <img src='/static/application/feedback_white_16.png'/>
    Write Feedback
</a>
<?php
    }
}
?>