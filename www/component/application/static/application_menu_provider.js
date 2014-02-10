if (populating_menu) // to avoid execution when loading application
for (var i = 1; i < window.sections.length; ++i) {
	var item = addMenuItem(window.sections[i].icon16, window.sections[i].name, window.sections[i].info_text, window.sections[i].page);
	item.sec = sections[i];
	item.onclick = function(ev) {
		selectSection(this.sec);
		stopEventPropagation(ev);
		return false;
	};
}
