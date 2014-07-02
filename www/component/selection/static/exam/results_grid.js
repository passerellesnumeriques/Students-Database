if (typeof require != 'undefined')
   require ("exam_objects.js"); 


/* results_grid : class to manage the exam subject results
 * @param {String} subject_name : name of the exam subject
 * @param {array} applicants : array of applicants objects
 * @param {String} grid_height : the height of the inside grid
*/

function results_grid(subject,applicants,grid_height) {
   var t=this;
   
    /* html elements */   
   t.elt = {
      container : document.createElement("DIV"),
      grid : document.createElement("DIV"),
      footer : document.createElement("DIV")
   };
      
   t.subject=subject;
   t.applicants = applicants;
   t.grid_res=new grid (t.elt.grid); // creating grid   
   t.index_applicant=-1; // index of selected applicant
 

   /* Getting current applicant
    * return people object matching the applicant
   */
   t.getCurrentApplicant=function(){
      
       if(t.index_applicant==-1) // no applicant selected
         return null;
      
      return t.applicants[t.index_applicant].people;
   }
   
   /* handling the selection of a new applicant's row
   * @param user_func : user function to call (with parameter people object)
   */
   t.onRowApplicantSelection=function(user_func){
      /* making rows selectable and adding listeners */
      t.grid_res.setSelectable(true,true);
      
      /* Adding listener on row focus */
      t.grid_res.onrowfocus.add_listener(function(row) {
         t.grid_res.selectByRowId(row.row_id,true); 
      });
      
      /* handling 'new row selected' event */
      t.grid_res.onrowselectionchange = function(row_id, selected) {

         if (!selected) return; // (unselected event => don't care)       
         
         t.index_applicant=row_id;
         
         /* Call user function, passing people object as parameter*/
         user_func(t.applicants[row_id].people);
      }
   }
   
   /* get container html element */
   t.getContainer=function(){
      return t.elt.container;
   }
   
   
   /* --- internal functions --- */
   
   t._init=function(){
      
      /* Appending html elements into main container */
      t.elt.container.appendChild(t.elt.grid);
      t.elt.container.appendChild(t.elt.footer);
         
         
    /* creating applicant ID column */  
      t._createApplicantColumn();
      
      /* creating all the columns (one for each question) */
      t._createQuestionsColumns();
      
       /* add the applicants rows (one for each applicant) */
      t._createRowsApplicants();
      
      /* creating footer toolbar */
      t._createFooterToolbar();
      
      /* Some CSS :
      making the rows scrollable but not the grid header */   
         
      $(t.elt.container).css({
      "position":"relative",
      "margin":"0 auto 3em auto",
      "padding-top":"2.5em",
      "box-shadow":" 10px 10px 5px #888888"
    });
     
      $(t.elt.grid).css({
      "height":grid_height,
      "overflow":"auto",
    });
      
      $(t.elt.grid).find("thead").css({
      "position":"absolute",
      "top":"0",
       "display":"inherit",
      "width":"100%",
    });
      
      }
   
   /* creating applicant ID column */
   t._createApplicantColumn=function(){
      t.grid_res.addColumn(new GridColumn('col_applic','Applicant ID',null,null,'field_text',false,null,null,{},{}));
   }
   
   /* creating all the columns for each question */
   t._createQuestionsColumns=function(){
      
       for(var i=0;i<subject.parts.length;++i)
      {
         var part=subject.parts[i];
         var sub_cols=[];
         
         for (var j=0;j<part.questions.length;++j)
         {
            var question=part.questions[j];
            
            /* convert question field for grid widget */
            var grid_field=questionGridFieldType(question);
            var grid_args=questionGridFieldArgs(question);
                       
            /* create the new question Column */
            sub_cols.push(new GridColumn('part'+part.index+'q'+question.index,'Question '+question.index,null,null,grid_field,true,null,null,grid_args,'#'));
         }
         
         /* push columns into the ColumContainer */
         t.grid_res.addColumnContainer(new GridColumnContainer(part.name,sub_cols,'#'));
      }
   }
   
   /* creating the rows : one for each applicant */
   t._createRowsApplicants=function(){
      
        $.each(t.applicants,function(index,obj) {
          /* creating applicant row */
          t.grid_res.addRow(index,[{col_id:'col_applic',data_id:'#',data:obj.applicant_id}]);
        });
   }  
   
   /* creating footer toolbar */
   t._createFooterToolbar=function(){
   
      /* creating footer content elements */   
      var foot_content={
          button_wrapper : document.createElement("DIV"),
          button_valid: document.createElement("BUTTON")
      }
     
     foot_content.button_valid.innerHTML='Validate';
     foot_content.button_valid.className='action';
     
     /* Some CSS (jQuery notation for better readability) */
     
      $(t.elt.footer).css({
         "height":"30px",
         "width":"100%",
         "display":"table",
         "background-color":"rgb(229,190,212)",
         "border-radius":"3px",
         });

     $(foot_content.button_wrapper).css({
         "display" : "table-cell",
         "vertical-align" : "middle",
         "text-align":"center"
        });
     
     $(foot_content.button_valid).css({
         "display" : "inline",
        });
     
     /* Append the elements  */
     foot_content.button_wrapper.appendChild(foot_content.button_valid);
     t.elt.footer.appendChild(foot_content.button_wrapper);
     
     /* Click on validate button */
      $(foot_content.button_valid).click(function(){
       
       });
   }
   
   /* Initialization */
   t._init();
}