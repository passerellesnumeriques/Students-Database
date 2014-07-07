if (typeof require != 'undefined')
   require ("results_grid.js"); // class for managing exam results

/* Main */
if (typeof $(document).ready != 'undefined') // handle background loading case
$(document).ready(function(){

   /* Get useful data (previously hidden into the DOM) */
   var session_id=document.getElementById("session_id").textContent.trim();
   var room_id=document.getElementById("room_id").textContent.trim();

   
    //Show a loader gif while waiting for results_grids creation
   var loader_img = $("<img>", {id: "loaderResultsGridImg", src: "/static/selection/exam/loader_results.gif"});
   loader_img.css({
      "display":"block",
      "margin":"0 auto"});
   $("#subj_results").html(loader_img);
   
   service.json("selection","applicant/get_applicants",{exam_session:session_id,exam_center_room:room_id},function(applicants){
         /* creating one tab for each exam subject */
      service.json("selection","exam/get_subjects",{},function(subjects){
         
         /* Remove Loader picture */
         loader_img.remove();
         
         /* creating tab element */
         var subj_tabs= new tabs('subj_results',false);
         
         var grids=[];
         
         for (var j=0; j<subjects.length; ++j){
            /* create the results_grid  */
            var g = new results_grid(subjects[j],applicants,'250px');
               
            /* update ApplicantInfoBox on new row selection event */
            g.onRowApplicantSelection(updateApplicantInfoBox);
            
            /* adding grid exam subject to new tab */
            subj_tabs.addTab(subjects[j].name,null,g.getContainer());
            subj_tabs.content.style.width="";
            
            grids.push(g);
         }
         
        //grids[0].putScrollBarOutside(); 
        $('#subj_results').show();
        
         
         /* when a new tab selected : updateApplicantInfoBox */
         subj_tabs.onselect = function() {
             
            subj_tabs.content.style.width=""; /* avoid tab width property */
            //grids[subj_tabs.selected].putScrollBarOutside();
            
           /* updating ApplicantInfoBox on new tab selection */
           updateApplicantInfoBox(grids[subj_tabs.selected].getCurrentApplicant()); 
         };
      });
   });
});


/*
 * update applicant info Box
 * @param people object representing the applicant
 */
function updateApplicantInfoBox(people)
{

   if (!people) 
      return;
   
   /* Picture of applicant */
   $("#applicant_photo").attr("src","/dynamic/storage/service/get?id="+people.picture_id+"&revision="+people.picture_revision);
   
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
       
     $("#applicant_photo").show();
     $("#applicant_table").show();
        
}