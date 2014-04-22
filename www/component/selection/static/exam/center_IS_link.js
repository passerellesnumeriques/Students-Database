/**
 * Create a list of the information sessions linked to an exam center
 * This list contains linked to the IS profile with the readonly attribute
 * @param {HTMLElement | String} container
 * @param {ExamCenterISLinked} IS_ids
 */
function center_IS_link(container,IS_ids){
	var t = this;
	container = typeof container == "string" ? document.getElementById(container) : container;
	t.section = null;
	
	/**Private attributes and methods*/
	
	/**
	 * Launch the process, create a section object and set its content
	 */
	t._init = function(){
		t._section_content = document.createElement("div");
		t._setSectionContent();
		require(["section.js","vertical_align.js"],function(){			
			t.section = new section("","Information Sessions linked",t._section_content,false,false,"soft");
			container.appendChild(t.section.element);
			//Set the tooltip
			var info_container = document.createElement("div");
			var info = document.createElement("img");			
			info.src = theme.icons_16.info;
			info_container.appendChild(info);
			t.section.addToolRight(info_container);
			var tip = document.createElement("div");
			tip.innerHTML ="<i>All the applicants assigned to the information sessions below will be automatically assigned to this exam center<br/>Note: to link / unlink informations sessions from an exam center, please go on the exam centers main page</i>";
			tooltip(info,tip);
			new vertical_align(info_container,"middle");
		});		
	};
	
	/**
	 * Set the content of the section, create the list
	 */
	t._setSectionContent = function(){		
		if(IS_ids.length == 0){
			var no = document.createElement('div');
			no.appendChild(document.createTextNode("This exam center has no information session linked"));
			no.style.fontStyle = 'italic';
			t._section_content.appendChild(no);
		} else {
			for(var i = 0; i < IS_ids.length; i++){
				var div = document.createElement("div");
				div.appendChild(document.createTextNode(" - "));
				var link = document.createElement("a");
				div.style.marginLeft = "5px";
				link.className = "black_link";
				link.title = "See Information Session profile";
				link.appendChild(document.createTextNode(t._getISName(IS_ids[i])));
				link.IS_id = IS_ids[i];
				link.onclick = function(){
					var id = this.IS_id;
					require("popup_window.js",function(){
						var pop = new popup_window("Information Session Profile","");
						pop.setContentFrame("/dynamic/selection/page/IS/profile?id="+id+"&readonly=true&hideback=true");
						pop.show();
					});
					return false;
				};
				div.appendChild(link);
				t._section_content.appendChild(div);
			}
		}		
	};
	
	/**
	 * Get an information session name
	 * @param {Number} id the information session ID
	 * @returns {String | NULL} the information session name if found, else NULL
	 */
	t._getISName = function(id){
		for(var i = 0; i < t._all_names.length; i++){
			if(t._all_names[i].id == id)
				return t._all_names[i].name;
		}
		return null;
	};
	
	service.json("selection","IS/get_all_names",{},function(r){
		if(!r)
			return;
		t._all_names = r;
		t._init();
	});
}