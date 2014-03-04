function NewsObject(id, section, category, html, people, user, timestamp, update_timestamp) {
	this.id = id;
	this.section = section;
	this.category = category;
	this.html = html;
	this.people = people;
	this.user = user;
	this.timestamp = timestamp;
	this.update_timestamp;
}

if (typeof require != 'undefined') require("animation.js");

function news(container, sections, exclude_sections, onready, onrefreshing) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	this._main_news = [];
	this._latests = [];
	this._latests_timestamp = 0;
	this._olders = [];
	this._olders_timestamp = 0;
	this._refreshing = 0;
	this.more = function(ondone) {
		if (++t._refreshing == 1 && onrefreshing) onrefreshing(true);
		service.json("news", "get_more", {olders:t._olders,olders_timestamp:t._olders_timestamp,sections:t._selected_sections,nb:10}, function(res) {
			if (--t._refreshing == 0 && onrefreshing) onrefreshing(false);
			if (!res) { if (ondone) ondone(false); return; }
			if (res.length == 0) { if (ondone) ondone(false); return; }
			if (t._latests.length == 0) {
				// first time we have news
				t._latests = [res[0].id];
				t._latests_timestamp = res[0].update_timestamp;
				t._olders = [res[res.length-1].id];
				t._olders_timestamp = res[res.length-1].update_timestamp;
				for (var i = 1; i < res.length; ++i)
					if (res[i].update_timestamp == t._latests_timestamp) {
						t._latests.push(res[i].id);
						if (i == res.length - 1) {
							// all have latest, we should show more
							if (t.refresh_timeout) clearTimeout(t.refresh_timeout);
							t.refresh_timeout = setTimeout(t.refresh, 1000);
						}
					} else
						break;
				for (var i = res.length-2; i >= 0; --i)
					if (res[i].update_timestamp == t._olders_timestamp)
						t._olders.push(res[i].id);
					else
						break;
				t.current_interval = 0;
				for (var i = res.length-1; i >= 0; --i)
					t._createNews(res[i]);
			} else {
				if (res[res.length-1].update_timestamp < t._olders_timestamp) {
					t._olders = [res[res.length-1].id];
					t._olders_timestamp = res[res.length-1].update_timestamp;
				} else if (res[res.length-1].update_timestamp == t._olders_timestamp) {
					t._olders.push(res[res.length-1].id);
				}
				for (var i = res.length-2; i >= 0; --i)
					if (res[i].update_timestamp == t._olders_timestamp)
						t._olders.push(res[i].id);
					else
						break;
				for (var i = 0; i < res.length; ++i) {
					if (res[i].update_timestamp == t._latests_timestamp && !t._latests.contains(res[i].id))
						t._latests.push(res[i].id);
					if (res[i].update_timestamp == t._olders_timestamp && !t._olders.contains(res[i].id))
						t._olders.push(res[i].id);
					t._createNews(res[i]);
				}
			}
			if (ondone) ondone(res.length == 10);
		});
	};
	this.refresh = function() {
		if (t._latests.length == 0) { t.more(); return; }
		if (t.refresh_timeout) clearTimeout(t.refresh_timeout);
		if (++t._refreshing == 1 && onrefreshing) onrefreshing(true);
		service.json("news", "get_latests", {latests:t._latests,latests_timestamp:t._latests_timestamp,sections:t._selected_sections}, function(res) {
			if (--t._refreshing == 0 && onrefreshing) onrefreshing(false);
			t.refresh_timout = setTimeout(t.refresh, 30000);
			if (!res) return;
			if (res.length == 0) return;
			if (res[0].update_timestamp > t._latests_timestamp) {
				t._latests = [res[0].id];
				t._latests_timestamp = res[0].update_timestamp;
			} else if (res[0].update_timestamp == t._latests_timestamp) {
				t._latests.push(res[0].id);
			}
			for (var i = 1; i < res.length; ++i)
				if (res[i].update_timestamp == t._latests_timestamp) {
					t._latests.push(res[i].id);
					if (i == res.length-1) {
						// all have the same, we should retrieve more
						clearTimeout(t.refresh_timeout);
						t.refresh_timeout = setTimeout(t.refresh, 1000);
					}
				} else
					break;
			t.current_interval = 0;
			for (var i = res.length-1; i >= 0; --i) {
				if (res[i].update_timestamp == t._olders_timestamp && !t._olders.contains(res[i].id))
					t._olders.push(res[i].id);
				t._createNews(res[i]);
			}
		});
	};
	this._loadReplies = function(n) {
		if (++t._refreshing == 1 && onrefreshing) onrefreshing(true);
		service.json("news", "get_replies", {id:n.id}, function(res) {
			if (--t._refreshing == 0 && onrefreshing) onrefreshing(false);
			if (!res) return;
			if (res.length == 0) return;
			for (var i = 0; i < res.length; ++i)
				t._createReply(res[i], n.id);
		});		
	};
	this.more_news = function(initial_internal_call) {
		while (t._more_container.childNodes.length > 0) t._more_container.removeChild(t._more_container.childNodes[0]);
		if (!initial_internal_call) {
			var loading = document.createElement("IMG");
			loading.src = "/static/news/loading.gif";
			t._more_container.appendChild(loading);
		}
		t.more(function(has_more) {
			while (t._more_container.childNodes.length > 0) t._more_container.removeChild(t._more_container.childNodes[0]);
			if (has_more) {
				var button = document.createElement("SPAN");
				button.className = "button";
				button.appendChild(document.createTextNode("Show More"));
				t._more_container.appendChild(button);
				button.onclick = function() {
					t.more_news(false);
				};
			}
		});
	};
	
	this._createNews = function(n) {
		for (var i = 0; i < this._main_news.length; ++i)
			if (this._main_news[i].news.id == n.id) {
				container.removeChild(this._main_news[i]);
				this._main_news.splice(i,1);
				break;
			}
		div = this._createDiv(n, true);
		this._main_news.push(div);
		for (var i = 0; i < container.childNodes.length; ++i) {
			var d = container.childNodes[i];
			if (!d.news) {
				container.insertBefore(div, d);
				break;
			}
			if (d.news.update_timestamp < n.update_timestamp) {
				container.insertBefore(div, d);
				break;
			}
			if (d.news.update_timestamp == n.update_timestamp && d.news.id > n.id) {
				container.insertBefore(div, d);
				break;
			}
		}
		this._loadReplies(n);
	};
	this._createReply = function(n, root_id) {
		var main = null;
		for (var i = 0; i < this._main_news.length; ++i)
			if (this._main_news[i].news.id == root_id) { main = this._main_news[i]; break; }
		if (main == null) return;
		var div = this._createDiv(n, false);
		main.reply_div.appendChild(div);
	};
	this._createDiv = function(n, main) {
		var div = document.createElement("DIV");
		div.news = n;
		div.style.backgroundColor = "#D0D0E0";
		div.style.marginBottom = "1px";
		if (main) setBorderRadius(div, 3, 3, 3, 3, 3, 3, 3, 3);
		var table = document.createElement("TABLE"); div.appendChild(table);
		table.style.width = "100%";
		table.style.borderCollapse = 'collapse';
		table.style.borderSpacing = '0px';
		var tr = document.createElement("TR"); table.appendChild(tr);
		var td = document.createElement("TD"); tr.appendChild(td);
		if (!main) {
			div.style.borderTop = "1px solid #B0B0D0";
			div.style.borderLeft = "1px solid #C0C0D0";
			div.style.backgroundColor = "#E0E0F0";
			setBorderRadius(div, 5, 5, 0, 0, 0, 0, 0, 0);
		}
		td.style.width = "38px";
		td.style.verticalAlign = "top";
		td.style.padding = '2px 2px 2px 2px';
		var picture = document.createElement("IMG");
		picture.style.width = "35px";
		picture.style.height = "35px";
		picture.style.verticalAlign = "top";
		picture.src = "/dynamic/user_people/service/user_picture?domain="+n.user.domain+"&username="+n.user.username;
		setBorderRadius(picture, 5,5,5,5,5,5,5,5);
		td.appendChild(picture);
		var content = document.createElement("TD");
		tr.appendChild(content);
		content.style.verticalAlign = "top";
		content.style.padding = "0px";

		var header = document.createElement("DIV"); content.appendChild(header);
		var people_name = document.createElement("SPAN"); header.appendChild(people_name);
		people_name.style.fontWeight = "bold";
		people_name.style.fontSize = "10pt";
		people_name.style.color = "#000060";
		people_name.appendChild(document.createTextNode(n.people.first_name+" "+n.people.last_name));
		people_name.style.cursor = "pointer";
		people_name.onmouseover = function() { this.style.color = "#600060"; };
		people_name.onmouseout = function() { this.style.color = "#000060"; };
		people_name.onclick = function() { location.href = "/dynamic/people/page/profile?people="+n.people.id+"&domain="+n.user.domain; };
		var timing = document.createElement("SPAN"); header.appendChild(timing);
		timing.style.color = "#808080";
		timing.style.fontSize = "8pt";
		timing.style.marginLeft = "10px";
		timing.appendChild(div.timing_text = document.createTextNode(t._getTimingString(n.timestamp)));
		
		var msg = document.createElement("DIV"); content.appendChild(msg);
		msg.style.marginTop = "3px";
		msg.style.fontFamily = "Arial";
		msg.style.fontSize = "9pt";
		msg.innerHTML = n.html;

		if (main) {
			var has_only_one_section = sections.length == 1 && t._selected_sections.length == 1;
			var has_only_one_category = has_only_one_section && t._selected_sections[0].categories.length < 2;
			if (!has_only_one_category) {
				td = document.createElement("TD"); tr.appendChild(td);
				td.style.textAlign = "right";
				td.style.verticalAlign = "top";
				td.style.padding = '0px';
				var comp_div = document.createElement("DIV"); td.appendChild(comp_div);
				comp_div.style.textAlign = "right";
				comp_div.style.whiteSpace = "nowrap";
				comp_div.style.display = "inline-block";
				var s = "";
				for (var i = 0; i < t.sections.length; ++i) {
					if (t.sections[i].name == n.section) {
						s += "<table style='border-collapse:collapse;border-spacing:0px;margin-right:1px'><tr>";
						s += "<td valign=top style='padding:0px'>";
						if (!has_only_one_section) s += "<b>"+t.sections[i].display_name+"</b>";
						if (n.category) {
							for (var j = 0; j < t.sections[i].categories.length; ++j) {
								if (t.sections[i].categories[j].name == n.category) {
									s += "<br/>";
									if (t.sections[i].categories[j].icon)
										s += "<img src='"+t.sections[i].categories[j].icon+"' style='vertical-align:bottom'/> ";
									s += "<i>"+t.sections[i].categories[j].display_name+"</i>";
									break;
								}
							}
						}
						s += "</td>";
						if (t.sections[i].icon && !has_only_one_section) s += "<td valign=top style='padding:0px'><img width='32px' height='32px' src='"+t.sections[i].icon+"'/></td>";
						s += "</tr></table>";
						break;
					}
				}
				comp_div.innerHTML = s;
			}
			
			div.reply_div = document.createElement("DIV");
			content.appendChild(div.reply_div);
			// TODO add reply button/section
		}

		if (typeof animation != 'undefined') animation.fadeIn(div, 1500);
		layout.invalidate(container);
		return div;
	};
	
	this._getTimingString = function(timestamp) {
		var d = new Date(timestamp*1000);
		var now = new Date();
		var seconds = (now.getTime()-d.getTime())/1000;
		
		var i;
		if (seconds < 60) i = 500;
		else if (seconds < 60*60) i = 10000;
		else i = 60000;
		if (t.current_interval == 0 || t.current_interval > i) {
			t.current_interval = i;
			if (t.interval) clearInterval(t.interval);
			t.interval = setInterval(t._refreshTimings, i);
		}
		
		if (seconds < 2) return "one second ago";
		if (seconds < 60) return Math.floor(seconds)+" seconds ago";
		var minutes = seconds/60;
		if (minutes < 2) return "one minute ago";
		if (minutes < 60) return Math.floor(minutes)+" minutes ago";
		var hours = minutes / 60;
		if (hours < 2) return "one hour ago";
		if (hours < 24) return Math.floor(hours)+" hours ago (at "+d.toLocaleTimeString()+")";
		var days = hours/24;
		if (days < 2) return "yesterday at "+d.toLocaleTimeString();
		return d.toLocaleString();
	};
	
	this._refreshTimings = function() {
		for (var i = 0; i < t._main_news.length; ++i) {
			t._refreshTiming(t._main_news[i]);
			for (var j = 0; j < t._main_news[i].reply_div.childNodes.length; ++j)
				t._refreshTiming(t._main_news[i].reply_div.childNodes[j]);
		}
	};
	this._refreshTiming = function(d) {
		d.timing_text.nodeValue = t._getTimingString(d.news.timestamp);
	};
	
	this._init = function() {
		t._more_container = document.createElement("DIV");
		container.appendChild(t._more_container);
		t._more_container.style.textAlign = "center";
		t.current_interval = 0;
		t.interval = null;
		var s = [];
		for (var i = 0; i < sections.length; ++i) {
			s.push({name:sections[i].name,categories:sections[i].categories});
		}
		service.json('news', 'get_infos', {sections:s,exclude:exclude_sections}, function(res) {
			if (!res) return;
			t.sections = res;
			t._selected_sections = [];
			for (var i = 0; i < t.sections.length; ++i) {
				var s = {name:t.sections[i].name,categories:[]};
				for (var j = 0; j < sections.length; ++j)
					if (sections[j].name == s.name) { s.tags = sections[i].tags; break; }
				for (var j = 0; j < t.sections[i].categories.length; ++j)
					s.categories.push(t.sections[i].categories[j].name);
				t._selected_sections.push(s);
			}
			t.more_news(true);
			t.refresh_timeout = setTimeout(t.refresh, 30000);
			if (onready) onready(t);
		});
	};
	
	this._init();
}