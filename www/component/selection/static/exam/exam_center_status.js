/**
 * Create the exam centers status screen
 * This function retrieves its data from selection#exam#center_status service
 * @param {HTMLElement | String} container
 */
function exam_center_status(container){
	if(typeof(container) == "string")
		container = document.getElementById(container);	
	var t = this;
	
	/**Private methods and attributes*/
	t._table = document.createElement("table");
	
	/**
	 * Launch the process, create a table containing the statistics about the Exam centers
	 * A row is added at the bottom of the table, about the exam centers that have no host yet
	 */
	t._init = function(){
		var tr1 = document.createElement("tr");
		var td12 = document.createElement("td");
		var td11 = document.createElement("td");
		
		td11.innerHTML = "<font color='#808080'><b>Existing:</b></font>";
		td11.style.textAlign = "center";
		td11.style.paddingBottom = "10px";
		td12.innerHTML = t._number_EC;
		td12.style.paddingBottom = "10px";
		td12.style.textAlign = "left";
		tr1.appendChild(td11);
		tr1.appendChild(td12);
		var td13 = document.createElement("td");
		var td14 = document.createElement("td");
		td13.innerHTML = "<font color='#808080'><b>All partners:</b></font>";
		td13.style.paddingBottom = "10px";
		td13.style.textAlign = "center";
		td14.innerHTML = t._partners;
		td14.style.paddingBottom = "10px";
		tr1.appendChild(td13);
		tr1.appendChild(td14);
		t._table.appendChild(tr1);
		if(t._no_host != null){
			t._setECNoHostList();
		} else {
			var tr = document.createElement("tr");
			var td = document.createElement("td");
			td.appendChild(document.createTextNode("All the exam centers have an host set"));
			td.colSpan = 4;
			td.style.color = "green";
			td.style.fontStyle = "italic";
			td.style.textAlign = "center";
			tr.appendChild(td);
			t._table.appendChild(tr);
		}
		t._table.style.width = "100%";
		container.appendChild(t._table);
	};
	
	/**
	 * Create a list of all the Exam centers that have no partner yet
	 * The user can directly access the EC profile from the list items
	 */
	t._setECNoHostList = function(){
		var tr_head = document.createElement("tr");
		var td_head = document.createElement("td");
		tr_head.appendChild(td_head);
		t._table.appendChild(tr_head);
		td_head.colSpan = 4;
		td_head.style.textAlign = "left";
		td_head.innerHTML = "<i>Centers below are not fully completed (<b>no host partner</b>):</i>";
		for(var i = 0; i < t._no_host.length; i++){
			var tr = document.createElement("tr");
			var td = document.createElement("td");//Contains the name (clickable) of the IS to finish
			td.appendChild(document.createTextNode(" - "));
			var link = document.createElement('a');
			link.title = "Finish";
			link.appendChild(document.createTextNode(t._no_host[i].name));
			link.className = "black_link";
			link.href = "/dynamic/selection/page/exam/center_profile?id="+t._no_host[i].id;
			td.appendChild(link);
			td.colSpan = 4;
			td.style.textAlign = 'left';
			tr.appendChild(td);
			t._table.appendChild(tr);
		}
	};
	
	/**
	 * Retrieve the exam center data
	 */
	service.json("selection","exam/center_status",{},function(res){
		if(res){
			t._partners = res.partners;
			t._number_EC = res.number_EC;
			t._no_host = res.EC_no_host;
			t._init();
		}
	});
}