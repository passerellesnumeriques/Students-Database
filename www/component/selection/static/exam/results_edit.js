if (typeof require != 'undefined'){
   require("results_grid.js"); // class for managing exam results
}

var applicant_picture;

/* Main */
function initResultsEdit(session_id,room_id,subjects,set_correct_answer){

   
   /* Profile picture */
      require("profile_picture.js",function(){
         applicant_picture= new profile_picture('applicant_picture','50','50', 'left', 'top');
      });
   
    //Show a loader gif while waiting for results_grids creation
   var loader_img = $("<img>",{class:"loader_img",src: "/static/selection/exam/loader_results.gif"});
   loader_img.appendTo($("#subj_results"));

   
   /* getting all applicants for this particular exam session */
   service.json("selection","applicant/get_applicants",{exam_session:session_id,exam_center_room:room_id},function(applicants){
                           
       /* Remove Loader picture */
       loader_img.remove();
       
       /* creating tab element */
       var subj_tabs= new tabs('subj_results',false);
       
       var grids=[];
       
       
       for (var j=0; j<subjects.length; ++j){
          /* create the results_grid  */
          var g = new results_grid(subjects[j],applicants,'250px',set_correct_answer);
         
          /* update ApplicantInfoBox on new row selection event */
          g.onRowApplicantSelection(updateApplicantInfoBox);
          
          /* Some CSS needed here */
          //g.elt.container.style.marginTop="-1px"; // to pass over the tab widget border
          
          /* adding grid exam subject to new tab */
          subj_tabs.addTab(subjects[j].name,null,g.getContainer());
          
          grids.push(g);
       }
        // avoid width computing from tab widget
        //layout.removeHandler('subj_results',layout._getHandlers('subj_results')[0]);
          //subj_tabs.content.style.width="";
       layout.changed(subj_tabs.content);
       
      $('#subj_results').show();
      
       /* when a new tab selected : updateApplicantInfoBox */
       subj_tabs.onselect = function() {
           
          //subj_tabs.content.style.width=""; // avoid width computing from tab widget
          
         /* updating ApplicantInfoBox on new tab selection */
         updateApplicantInfoBox(grids[subj_tabs.selected].getCurrentApplicant()); 
       };
   });
}


/*
 * update applicant info Box
 * @param people object representing the applicant
 */
function updateApplicantInfoBox(people)
{

   if (!people) 
      return;
   
   /* Getting applicant picture */
   
   applicant_picture.loadPeopleObject(people,function(){
   
      /* The people fields we want to display */
   var fields = {first_name:"First Name",middle_name:"Middle Name",khmer_first_name:"Khmer first name",khmer_last_name:"Khmer last name",last_name:"Last Name",sex:"Gender",birthdate:"Birth"}; 
 
   var key;
   for (key in fields) {
      if (people[key]==null){
         /* hide unuseful <tr> */
         $("#"+key).parent().hide();
         continue;
      }
      /* fill up table cells */
      $("#"+key).text(fields[key]).next().text(people[key]); // <th>First name</th><td>John Doe</td>
      $("#"+key).parent().show();
   }
       
     $("#applicant_picture").show();
     $("#applicant_table").show();
      });   
}