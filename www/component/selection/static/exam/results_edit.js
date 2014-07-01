if (typeof require != 'undefined')
   require ("results_grid.js"); // class for managing exam results

/* Main */
if (typeof $(document).ready != 'undefined') // handle background loading case
$(document).ready(function(){

   /* Get useful data (previously hidden into the DOM) */
   var session_id=document.getElementById("session_id").textContent.trim();
   var room_id=document.getElementById("room_id").textContent.trim();

   /* creating tab element */
   var subj_tabs= new tabs('subj_results',false);
   
   service.json("selection","applicant/get_applicants",{exam_session:session_id,exam_center_room:room_id},function(applicants){
         /* creating one tab for each exam subject */
      //service.json("selection","exam/get_all_subject_names",{},function(names){
      service.json("selection","exam/get_subjects",{},function(subjects){
         
         ////DEBUG
         //console.log(subjects);
         
         var grids=[];
         var g;
         for (var j=0; j<subjects.length; ++j){
            /* create the results_grid  */
            g = new results_grid(subjects[j],applicants);
               
            //TODO : add a loader gif during grid creation 
            
            /* update ApplicantInfoBox on new row selection event */
            g.onRowApplicantSelection(updateApplicantInfoBox);
            
            /* adding grid exam subject to new tab */
            subj_tabs.addTab(subjects[j].name,null,g.getContainer());
            grids.push(g);
         }
         
      /* Compute height for subj_tabs container */
     //     TODO : maybe a more direct way to set the height ? */
        //setTabHeight(g);
        $('#subj_results').show();
         
         /* when a new tab selected : updateApplicantInfoBox */
         subj_tabs.onselect = function() {
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
   $("#applicant_photo").attr("src","/dynamic/people/service/picture?people="+people.people_id);
   
  /* The people fields we want to display */
   var fields = {first_name:"First Name",middle_name:"Middle Name",last_name:"Last Name",sex:"Gender",birth:"Birth"}; 
 
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

/* Compute height for subj_tabs container
 * @param result_grid
*/
//     TODO : maybe a more direct way to set the height ? */
function setTabHeight(result_grid)
{
           
   //var h;
   //h=$('#subj_results').children().eq(0).outerHeight(); // tab widget height
   //h+=$('table.grid').outerHeight(); // table grid height
   ////// border (of div containing table grid)
   //h+=parseInt($("#subj_results").children().eq(1).css('border-width'))*2;
   ////footer results_grid toolbar
   //h+=$(result_grid.elt.footer).outerHeight();
   //
   
   ///* setting height */
   //$('#subj_results').css('height',h+'px');
   $('#subj_results').css('height','200px');
}