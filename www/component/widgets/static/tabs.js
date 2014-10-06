if (typeof theme != 'undefined')
	theme.css("tabs.css");

function tabs(container, fill_tab_content) {
	if (typeof container == 'string') container = document.getElementById(container);
	container.widget = this;
	var t=this;
	t.tabs = [];
	t.selected = -1;
	t.onselect = null;
	
	t.addTab = function(title,icon,content) {
		var tab = {
			id: generateID(),
			title: title,
			icon: icon,
			content: content
		};
		t.tabs.push(tab);
		t._build_tab_header(tab);
		tab.content._prev_display = tab.content.style.display;
		tab.content.style.display = "none";
		t.content.appendChild(tab.content);
		if (t.selected == -1)
			t.select(t.tabs.length-1);
		t._layout();
		return tab;
	};
	t.removeTab = function(index) {
		if (index >= t.tabs.length || index < 0) return;
		if (t.selected == index) {
			t.selected = -1;
			while (t.content.childNodes.length > 0) t.content.removeChild(t.content.childNodes[0]);
		} else if (t.selected > index) t.selected--;
		t.header.removeChild(t.header.childNodes[index]);
		t.tabs[index].content = null;
		t.tabs[index].header = null;
		t.tabs.splice(index,1);
		layout.changed(t.header);
	};
	t.removeAll = function() {
		for (var i = 0; i < t.tabs.length; ++i) { t.tabs[i].content = null; t.tabs[i].header = null; }
		t.tabs = [];
		t.selected = -1;
		while (t.header.childNodes.length > 0) t.header.removeChild(t.header.childNodes[0]);
		while (t.content.childNodes.length > 0) t.content.removeChild(t.content.childNodes[0]);
		layout.changed(t.header);
		layout.changed(t.content);
	};
	
	t.getTabIndexById = function(id) {
		for (var i = 0; i < t.tabs.length; ++i)
			if (t.tabs[i].id == id) return i;
		return -1;
	};
	
	t.select = function(index) {
		if (t.selected != -1) {
			t.tabs[t.selected].header.className = "tab_header";
			t.tabs[t.selected].content.style.display = "none";
		}
		t.tabs[index].header.className = "tab_header selected";
		t.tabs[index].content.style.display = t.tabs[index].content._prev_display;
		t.selected = index;
		layout.changed(t.tabs[index].content);
		if (t.onselect) t.onselect(t);
	};

	t._build_tab_header = function(tab) {
		var div = document.createElement("DIV");
		div.className = "tab_header";
		div.style.display = "inline-block";
		div.innerHTML = (tab.icon != null ? "<img src='"+tab.icon+"' style='vertical-align:bottom'/> " : "")+tab.title;
		div.id = tab.id;
		div.onclick = function() { t.select(t.getTabIndexById(this.id)); };
		tab.header = div;
		t.header.appendChild(div);
		layout.changed(t.header);
	};
	
	t._init = function() {
		var tabs = [];
		while (container.childNodes.length > 0) {
			var e = container.childNodes[0];
			container.removeChild(e);
			if (e.nodeType != 1) continue;
			var title = "No title";
			if (e.hasAttribute("title")) {
				title = e.getAttribute("title");
				e.removeAttribute("title");
			}
			var icon = null;
			if (e.hasAttribute("icon")) {
				icon = e.getAttribute("icon");
				e.removeAttribute("icon");
			}
			tabs.push({title:title,icon:icon,content:e});
		}
		container.appendChild(t.header = document.createElement("DIV"));
		container.appendChild(t.content = document.createElement("DIV"));
		t.content.style.border = "1px solid black";
		for (var i = 0; i < tabs.length; ++i)
			t.addTab(tabs[i].title, tabs[i].icon, tabs[i].content);
		if (t.tabs.length > 0)
			t.select(0);
		container.ondomremoved(function() {
			for (var i = 0; i < t.tabs.length; ++i) { t.tabs[i].content = null; t.tabs[i].header = null; }
			t.header = null;
			t.content = null;
			t.tabs = null;
			container.widget = null;
		});
	};
	t._style_knowledge_content = [];
	t._layout = function() {
		if (fill_tab_content) {
			layout.two_steps_process(function() {
				getWidth(t.content, t._style_knowledge_content);
				return {w:container.clientWidth, h:container.clientHeight - t.header.offsetHeight};
			}, function(sizes) {
				setWidth(t.content, sizes.w, t._style_knowledge_content);
				setHeight(t.content, sizes.h, t._style_knowledge_content);
				layout.changed(t.content);
				if (t.selected != -1) {
					var knowledge = [];
					layout.two_steps_process(function() {
						getWidth(t.tabs[t.selected].content, knowledge);
						return {w:t.content.clientWidth, h:t.content.clientHeight};
					}, function(sizes) {
						setWidth(t.tabs[t.selected].content, sizes.w, knowledge);
						setHeight(t.tabs[t.selected].content, sizes.h, knowledge);
						layout.changed(t.tabs[t.selected].content);
					});
				}
			});
		}
	};
	t._init();
	t._layout();
	t._layout_timeout = null;
	t._layout_timer = function() {
		if (t._layout_timeout) return;
		t._layout_timeout = setTimeout(function() {
			t._layout_timeout = null;
			t._layout();
		},25);
	};
	layout.listenElementSizeChanged(container, t._layout_timer);
	layout.listenElementSizeChanged(t.header, t._layout_timer);
}
