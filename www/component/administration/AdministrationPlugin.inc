<?php
/**
 * Provides pages in the Administration section of the application
 */ 
interface AdministrationPlugin {
	
	/**
	 * Provides the list of pages to display in the Administration section of the application
	 * @return AdministrationPage[] list of pages
	 */
	public function getAdministrationPages();
	
}

/**
 * Describes a page to be displayed in the Administration section of the application
 */
class AdministrationPage {
	
	/** @var string $title Title of the page (displayed in the menu) */
	private $title;
	/** @var string $page URL of the page */
	private $page;
	/** @var string $icon16 Icon to be displayed next to the title */
	private $icon16;
	/** @var string $icon32 Icon to be displayed next to the title */
	private $icon32;
	/** @var string $info_text The tooltip to explain the page */
	private $info_text;
	/** @var function $can_access_function function checking if the current user can access the page: returns true if it can access. */
	private $can_access_function;
	/** @var function $warning_provider_function function generating HTML code displaying warnings, or null if no message */
	private $warning_provider_function;
	
	/**
	 * @param string $title Title of the page
	 * @param string $page URL of the page
	 * @param string $icon16 Icon (16x16 pixels) of the page
	 * @param string $icon32 Icon (32x32 pixels) of the page
	 * @param string $info_text text describing the page that will be displayed in a tooltip
	 * @param function $can_access_function function checking if the current user can access the page: returns true if it can access.
	 * @param function $warning_provider_function function generating HTML code displaying warnings, or null if no message
	 */
	public function __construct($title, $page, $icon16, $icon32, $info_text, $can_access_function, $warning_provider_function = null) {
		$this->title = $title;
		$this->page = $page;
		$this->icon16 = $icon16;
		$this->icon32 = $icon32;
		$this->info_text = $info_text;
		$this->can_access_function = $can_access_function;
		$this->warning_provider_function = $warning_provider_function;
	}
	
	/** @return string URL of the 16x16 pixels icon */
	public function getIcon16() { return $this->icon16; }
	/** @return string URL of the 32x32 pixels icon */
	public function getIcon32() { return $this->icon32; }
	/** @return string The title of the page */
	public function getTitle() { return $this->title; }
	/** @return string The URL of the page */
	public function getPage() { return $this->page; }
	/** @return string The tooltip to explain the page */
	public function getInfoText() { return $this->info_text; }
	/** @return true if the current user can access to this page */
	public function canAccess() { return call_user_func($this->can_access_function); }
	/** @return string|null the html to display */
	public function getWarnings() {
		if ($this->warning_provider_function == null) return null;
		return call_user_func($this->warning_provider_function);
	}
	
}

/**
 * Implementation of AdministrationPlugin to provide the Dashboard page
 */
class AdministrationDashboardPlugin implements AdministrationPlugin {
	
	public function getAdministrationPages() {
		return array(
			new AdministrationPage("Dashboard", "/dynamic/administration/page/dashboard", theme::$icons_16["dashboard"], theme::$icons_32["dashboard"], "Go back to the administration dashboard", 
				function() {
					foreach (PNApplication::$instance->components as $name=>$c)
						foreach ($c->getPluginImplementations("AdministrationPlugin") as $pi)
							if (!($pi instanceof AdministrationDashboardPlugin))
								foreach ($pi->getAdministrationPages() as $page)
									if ($page->canAccess()) return true; // at least one page is accessible
				}
			)
		);
	}
	
}

/**
 * Section to manage the application
 */
class ApplicationAdministrationPlugin implements AdministrationPlugin {
	public function getAdministrationPages() {
		return array(
				new AdministrationPage(
					"Application",
					"/dynamic/administration/page/app_admin",
					"/static/application/logo_16.png",
					"/static/application/logo_32.png",
					"Manage the application, deploy new version...",
					function() { return PNApplication::$instance->user_management->hasRight("manage_application"); },
					function() {
						$s = "";
						$last_cron = PNApplication::$instance->cron->getLastCronExecution();
						if ($last_cron == 0)
							$s .= "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> Sheduled Task for cron is not configured<br/>";
						else if ($last_cron < time()-20*60)
							$s .= "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> Sheduled Task for cron is not well configured as it didn't run since ".date("d M Y H:i", $last_cron)."<br/>";
						else if (PNApplication::$instance->cron->lastCronHasErrors())
							$s .= "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> cron task finished with errors, please check what's wrong.<br/>";
						else foreach (PNApplication::$instance->cron->getTasks() as $task)
							if (PNApplication::$instance->cron->cronTaskHasErrors($task->task_id))
								$s .= "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> Task <i>".toHTML($task->task_name)."</i> finished with errors, please check what's wrong.<br/>";

						$last_cron_maintenance = PNApplication::$instance->cron->getLastCronMaintenanceExecution();
						if ($last_cron_maintenance == 0)
							$s .= "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> Sheduled Task for cron_maintenance is not configured<br/>";
						else if ($last_cron_maintenance < time()-48*60*60)
							$s .= "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> Sheduled Task for cron_maintenance is not well configured as it didn't run since ".date("d M Y H:i", $last_cron_maintenance)."<br/>";
						else if (PNApplication::$instance->cron->lastCronMaintenanceHasErrors())
							$s .= "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> cron_maintenance task finished with errors, please check what's wrong.<br/>";
						else foreach (PNApplication::$instance->cron->getMaintenanceTasks() as $task)
							if (PNApplication::$instance->cron->cronMaintenanceTaskHasErrors($task->task_id))
								$s .= "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> Task <i>".toHTML($task->task_name)."</i> finished with errors, please check what's wrong.<br/>";
						return $s;
					}
				)
		);
	}
}
?>