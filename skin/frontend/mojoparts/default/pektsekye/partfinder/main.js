
var PartFinder = {};

PartFinder.Main = Class.create({

	templatePattern : /(^|.|\r|\n)({{(\w+)}})/,

		
	initialize : function(url, levelCount){	
		Object.extend(this, PartFinder.Config);
		this.categorySelectTemplate = new Template(this.categorySelect, this.templatePattern);					
    this.form               = $('partfinder_form');		
    this.extraContainer     = $('partfinder_extra');
    this.categoryContainer  = $('partfinder_category_container');    
    this.searchField        = $('partfinder_search');    
    this.notFoundMessage    = $('partfinder_not_found');    
	},
	

	
  loadLevel : function(value, level){
   
    if (value != ''){
    
      var values = [];
      for (var i=0;i <= level;i++)
        values.push($('partfinder_level_'+ i +'_select').value);    

      new Ajax.Request(this.url, {
          method:       "get",
          asynchronous: false, 
	        parameters: {'values[]':values},               
          onSuccess: function(transport) {
            try {
               if (transport.responseText.isJSON()) {
	                var response = transport.responseText.evalJSON();
	                if (!response.error)           
    	              partFinder.enableLevel(level, response); 	            	              
               }
            } catch (e) {}
         }
      });
      
    } else {
    
      this.disableLevels(level);
      this.hideExtra();      
      
    }
    
  },

 

  
  enableLevel : function(level, options){
  
    var select = $('partfinder_level_'+ (level + 1) +'_select');
    
    if (!select.disabled){
      this.disableLevels(level);
      this.hideExtra();      
    }  
    
		var l = options.length;		  
	  for (var i=0;i<l;i++)
      select.options[i+1] = new Option(options[i], options[i]);
      
    select.disabled = false; 
    select.removeClassName('disabled');     
  },



  disableLevels : function(level){
    var select;	  
	  for (var i=level+1;i < this.levelCount;i++){
      select = $('partfinder_level_'+ i +'_select');
      select.length = 1;
      select.disabled = true;
      select.addClassName('disabled');
	  }
  },
  
	

  showExtra : function(){ 
  
	  this.hideExtra(); 
    	  	   
    if (this.lastLevelIsSelected()){
        
      this.productsFound = false;
      this.categories = [];      
      this.checkResult();
    
      if (this.productsFound){
      
        if (this.categorySearchEnabled){ 
          if (this.categories.length > 0)
            this.addCategorySelect();
          else
            this.submit();
        }
                   
	      this.extraContainer.show();
	      
	    } else {
        this.notFoundMessage.show();
	    }
	    	    
	  }	  
  },



  hideExtra : function(){
  
    if (this.categorySearchEnabled || this.wordSearchEnabled){
      
      this.extraContainer.hide();
      
      if (this.categorySearchEnabled)   
        this.removeSubCategories(-1);
          
      if (this.wordSearchEnabled)
        this.searchField.clear();                       
    }

    this.notFoundMessage.hide();        
    
  },



  removeSubCategories : function(selectId){
  
    var nextSelectId = selectId + 1;
    for (var i = nextSelectId;i < this.categorySelectCount;i++)    
      Element.remove($('partfinder_category_select_' + i));
      
    this.categorySelectCount = nextSelectId;       
  },



    
  checkResult : function(p){
  
    var params = this.getLevelValuesAsParams();

    if (p != undefined)
	    Object.extend(params, p);    

    new Ajax.Request(this.checkResultUrl, {
        method:       "get",
        asynchronous: false, 
	      parameters: params,                     
        onSuccess: function(transport) {
          try {
             if (transport.responseText.isJSON()) {
	              var response = transport.responseText.evalJSON();
	              if (!response.error)           
  	              Object.extend(partFinder, response); 	                	            	              
             }
          } catch (e) {}
       }
    });
  
  },



     
  addCategorySelect : function(){
  
    Element.insert(this.categoryContainer, this.categorySelectTemplate.evaluate({select_id: this.categorySelectCount}));
    
    var select = $('partfinder_category_select_' + this.categorySelectCount);
    var l = this.categories.length;
	  for (var i=0;i<l;i++)    
      select.options[select.options.length] = new Option(this.categories[i].title, this.categories[i].id);
    
    this.categorySelectCount++;
  },




  checkSubCategories : function(selectId, categoryId){
  
    this.removeSubCategories(selectId); 
        
    if (categoryId != ''){
      this.categories = [];
      
      var extra = {};
      extra[this.categoryParamName] = categoryId;
      this.checkResult(extra);
      
      if (this.categories.length > 0)          
        this.addCategorySelect();
      else 
        this.submitCategory(categoryId); 
    }
          
  },


  
  submit : function(){
    
    this.notFoundMessage.hide(); 
    
    if (this.lastLevelIsSelected()){
    
      this.productsFound = false;  
      this.checkResult();
      
      if (this.productsFound)        
        this._submit();
      else
        this.notFoundMessage.show();
                
    }
      
  },

    
  submitCategory : function(categoryId){

    var extra = {};          
    extra[this.categoryParamName] = categoryId;
      
    this._submit(extra);
  },



  submitSearch : function(){
  
    if (this.searchField.value != ''){
     
      this.notFoundMessage.hide();
                  
      this.productsFound = false;
      
      var extra = {};
      extra[this.searchQueryParamName] = this.searchField.value;
         
      this.checkResult(extra);
      
      if (this.productsFound)        
        this._submit(extra);       
      else 
        this.notFoundMessage.show();        
    } 
     
  },



  _submit : function(p){
  
    var params = this.getLevelValuesAsParams();
    
    if (p != undefined)
	    Object.extend(params, p);  
	           
    window.location.href = this.submitUrl + '?' + Object.toQueryString(params);
  },



  getLevelValuesAsParams : function(){  
  
    var params = {};
    
	  for (var i=0;i < this.levelCount;i++)
      params[this.levelParameterNames[i]] = $('partfinder_level_'+ i +'_select').value;
      
    return params;  	  
  },

     
  lastLevelIsSelected : function(){
	  return $('partfinder_level_'+ (this.levelCount - 1) +'_select').value != '';
  } 
         
});
















