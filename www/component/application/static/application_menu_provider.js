if (typeof window.populating_menu != 'undefined' && window.populating_menu) {// to avoid execution when loading application
	resetAllMenus();
	for (var i = 1; i < window.sections.length; ++i) {
		var item = addMenuItem(window.sections[i].icon16, window.sections[i].name, window.sections[i].info_text, window.sections[i].page, null, true);
		item.link.sec = sections[i];
		item.link.onclick = function(ev) {
			selectSection(this.sec);
			stopEventPropagation(ev);
			return false;
		};
	}
}