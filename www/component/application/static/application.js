// override add_javascript and add_stylesheet
window._add_javascript_original = window.add_javascript;
if (!window.top._loading_application_status) {
	window.top._loading_application_status = new window.top.StatusMessage(window.top.Status_TYPE_PROCESSING, "Loading...");
	window.top._loading_application_nb = 0;
}
window.add_javascript = function(url, onload) {
	var p = new URL(url).path;
	var load = !_scripts_loaded.contains(p);
	if (load) {
		window.top._loading_application_nb++;
		if (window.top._loading_application_nb == 1)
			window.top.status_manager.add_status(window.top._loading_application_status);
	}
	window._add_javascript_original(url, function() {
		if (onload) onload();
		if (load) {
			window.top._loading_application_nb--;
			if (window.top._loading_application_nb == 0)
				window.top.status_manager.remove_status(window.top._loading_application_status);
		}
	});
};
