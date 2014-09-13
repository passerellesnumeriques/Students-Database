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
	};
	t.removeAll = function() {
		for (var i = 0; i < t.tabs.length; ++i) { t.tabs[i].content = null; t.tabs[i].header = null; }
		t.tabs = [];
		t.selected = -1;
		while (t.header.childNodes.length > 0) t.header.removeChild(t.header.childNodes[0]);
		while (t.content.childNodes.length > 0) t.content.removeChild(t.content.childNodes[0]);
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
		t.tabs[index].content.style.display = "";
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
	t._layout = function() {
		if (fill_tab_content) {
			var knowledge = [];
			setWidth(t.content, container.clientWidth, knowledge);
			setHeight(t.content, container.clientHeight - t.header.offsetHeight, knowledge);
			layout.changed(t.content);
		}
		if (t.selected != -1) {
			if (fill_tab_content) {
				var knowledge = [];
				setWidth(t.tabs[t.selected].content, t.content.clientWidth, knowledge);
				setHeight(t.tabs[t.selected].content, t.content.clientHeight, knowledge);
				layout.changed(t.tabs[t.selected].content);
			}
		}
	};
	t._init();
	t._layout();
	layout.listenElementSizeChanged(container, function() { t._layout(); });
}
