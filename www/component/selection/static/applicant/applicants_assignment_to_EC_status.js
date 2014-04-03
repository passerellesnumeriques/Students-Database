/**
 * Populate the container with the main data related to applicants assignment into Exam Center
 * The container is populated after calling the applicant/get_assignment_to_EC_figures service
 * @param {String | HTMLElement} container
 */
function applicants_assignment_to_EC_status(container){
	var t = this;
	container = typeof container == "string" ? document.getElementById(container) : container;
	
	/**
	 * Launch the process, create informative rows, depending on the results coming from the service
	 */
	t._init = function(){
		//Set the info row
		var div1 = document.createElement("div");
		div1.style.paddingLeft = "5px";
		div1.style.fontStyle = "italic";
		if(t._not_assigned == 0 && t._total != 0){
			div1.appendChild(document.createTextNode("All the applicants are assigned to an exam center!"));
			div1.style.color = "green";
		} else if (t._total == 0){
			div1.appendChild(document.createTextNode("There is no applicant yet"));
		} else {
			div1.appendChild(document.createTextNode("Applicants not assigned to any exam center: "+t._not_assigned+" / "+t._total));
			div1.style.color = "red";
		}
		container.appendChild(div1);
		//Set the assign remaining applicants button
		if(t._not_assigned != 0){
			require("popup_window.js",function(){
				var b = document.createElement('div');
				var div = document.createElement('div');
				div.style.textAlign = "center";
				b.className = "button";
				b.appendChild(document.createTextNode("Assign remaining applicants to Exam Centers"));
				b.style.margin = "5px";
				b.style.textAlign = "center";
				div.appendChild(b);
				container.appendChild(div);
				b.onclick = function(){
					var pop = new popup_window("Assign applicants to Exam Centers","","");
					pop.setContentFrame("/dynamic/selection/page/applicant/manually_assign_to_exam_entity?mode=center");
					pop.onclose = function(){location.reload();};//Applicants assignment figures may have been updated
					pop.show();
				};
			});
		}
		//Set the remaining applicants per center (not assigned to session / room) rows
		if(t._total != 0 && t._remaining_per_center != null){
			if(!t._isThereAnyApplicantRemaining()){
				var div = document.createElement("div");
				div.appendChild(document.createTextNode("All the applicants assigned to centers are assigned to sessions and rooms!"));
				div.style.color = "green";
				div.style.fontStyle = "italic";
				div.style.paddingLeft = "5px";
				div.style.paddingTop = "5px";
				container.appendChild(div);
			} else {
				var header = document.createElement("div");
				header.style.color = "red";
				header.style.fontStyle = "italic";
				header.style.paddingLeft = "5px";
				header.style.paddingTop = "5px";
				header.appendChild(document.createTextNode("Some applicants are assigned to exam centers but not to session / room:"));
				container.appendChild(header);
				var body = document.createElement("div");
				body.style.paddingLeft = "5px";
				body.style.paddingTop = "10px";
				container.appendChild(body);
				for(var i = 0; i < t._remaining_per_center.length; i++){
					if(t._remaining_per_center[i].no_session != null || t._remaining_per_center[i].no_room != null){
						var link = document.createElement("a");
						link.appendChild(document.createTextNode(t._remaining_per_center[i].EC_name));
						link.className = "black_link";
						link.title = "See center profile";
						link.EC_id = t._remaining_per_center[i].EC_id;
						link.onclick = function(){
							var EC_id = this.EC_id;
							require("popup_window.js",function(){
								var p = new popup_window("Exam Center Profile");
								p.setContentFrame("/dynamic/selection/page/exam/center_profile?id="+EC_id+"&hideback=true");
								p.onclose = function(){location.reload();};
								p.show();
							});
							return false;
						};
						body.appendChild(link);
						var ul = document.createElement("ul");
						body.appendChild(ul);
						if(t._remaining_per_center[i].no_session != null){
							var li = document.createElement("li");
							li.appendChild(document.createTextNode("Not assigned to any session:"));
							var n = document.createElement("div");
							n.style.display = "inline-block";
							n.style.paddingLeft = "3px";
							n.appendChild(document.createTextNode(t._remaining_per_center[i].no_session));
							li.appendChild(n);
							ul.appendChild(li);
						}
						if(t._remaining_per_center[i].no_room != null){
							var li = document.createElement("li");
							li.appendChild(document.createTextNode("Assigned to session but not to any room:"));
							var n = document.createElement("div");
							n.style.display = "inline-block";
							n.style.paddingLeft = "3px";
							n.appendChild(document.createTextNode(t._remaining_per_center[i].no_room));
							li.appendChild(n);
							ul.appendChild(li);
						}
					}
				}
			}
		}
	};
	
	/**
	 * Check into the t._remaining_per_center objects if there is at least one center in which an applicant is not assigned to a session or assigned to a session but not to any room
	 * @returns {Boolean}
	 */
	t._isThereAnyApplicantRemaining = function(){
		var res = false;
		if(t._remaining_per_center != null){
			for(var i = 0; i < t._remaining_per_center.length; i++){
				if(t._remaining_per_center[i].no_session != null || t._remaining_per_center[i].no_room != null){
					res = true;
					break;
				}
			}
		}
		return res;
	};
	
	service.json("selection","applicant/get_assignment_to_EC_figures",{},function(res){
		if(!res)
			return;
		t._total = res.total;
		t._not_assigned = res.not_assigned;
		t._remaining_per_center = res.remaining_per_center;
		t._init();
	});
}