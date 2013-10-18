function tabs(container, fill_tab_content) {
	if (typeof container == 'string') container = document.getElementById(container);
	container.widget = this;
	var t=this;
	t.tabs = [];
	t.selected = -1;
	t.onselect = null;
	
	t.addTab = function(title,icon,content) {
		var tab = {
			id: generate_id(),
			title: title,
			icon: icon,
			content: content
		};
		t.tabs.push(tab);
		t._build_tab_header(tab);
		if (t.selected == -1)
			t.select(t.tabs.length-1);
		t._layout();
		return tab;
	};
	
	t.getTabIndexById = function(id) {
		for (var i = 0; i < t.tabs.length; ++i)
			if (t.tabs[i].id == id) return i;
		return -1;
	};
	
	t.select = function(index) {
		if (t.selected != -1) {
			t.tabs[t.selected].header.style.backgroundColor = "#FFFFFF";
			t.content.removeChild(t.tabs[t.selected].content);
		}
		t.tabs[index].header.style.backgroundColor = "#C0C0C0";
		t.content.appendChild(t.tabs[index].content);
		t.selected = index;
		fireLayoutEventFor(t.tabs[index].content);
		if (t.onselect) t.onselect(t);
	};

	t._build_tab_header = function(tab) {
		var div = document.createElement("DIV");
		div.style.borderTop = "1px solid black";
		div.style.borderLeft = "1px solid black";
		div.style.borderRight = "1px solid black";
		setBorderRadius(div, 3, 3, 3, 3, 0, 0, 0, 0);
		div.style.display = "inline-block";
		div.style.padding = "2px";
		div.style.margin = "1px 2px 0px 1px";
		div.innerHTML = (tab.icon != null ? "<img src='"+tab.icon+"' style='vertical-align:bottom'/> " : "")+tab.title;
		div.style.cursor = "pointer";
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
	};
	t._layout = function() {
		setWidth(t.content, container.clientWidth);
		setHeight(t.content, container.clientHeight - t.header.offsetHeight);
		fireLayoutEventFor(t.content);
		if (t.selected != -1) {
			if (fill_tab_content) {
				setWidth(t.tabs[t.selected].content, t.content.clientWidth);
				setHeight(t.tabs[t.selected].content, t.content.clientHeight);
			}
			fireLayoutEventFor(t.tabs[t.selected].content);
		}
	};
	t._init();
	t._layout();
	addLayoutEvent(container, function() { t._layout(); });
}
