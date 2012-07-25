(function($) {

/*
 * SINGLE POST SCREEN
 */
 
	$('.radio-buttons-for-taxonomies').each(function(){  

        var id = $(this).attr('id');

        var taxonomy = id.replace(/taxonomy-/g,"");  

        $('#' + taxonomy + 'checklist li :radio, #' + taxonomy + 'checklist-pop :radio').on('click', function(){  
            var t = $(this), c = t.is(':checked'), id = t.val();  
            $('#' + taxonomy + 'checklist li :radio, #' + taxonomy + 'checklist-pop :radio').prop('checked',false);  
            $('#in-' + taxonomy + '-' + id + ', #in-popular-' + taxonomy + '-' + id).prop( 'checked', c );  

	    });  //end on radio click

        $('#' + taxonomy + '-add').find('input.radio-tax-add').click(function(){  
        		term = $('#' + taxonomy+'-add #new'+taxonomy).val(); 
				nonce =$('#' + taxonomy+'-add #_wpnonce_radio-add-tag').val(); 

				var request = $.ajax({
				  type: "POST",
				  url: ajaxurl,
				  data: { action: "radio_tax_add_taxterm", '_wpnonce_radio-add-tag': nonce, 'taxonomy' : taxonomy, 'term' : term },
				  beforeSend: function() {
				     $('#' + taxonomy + '-ajax-spinner').show(); 
				  }
				});

				request.fail(function(msg, textStatus) {   
				  $('#' + taxonomy + '-ajax-response').addClass('error-message').text(msg); 
				});

				request.done(function(msg, textStatus) { 

					var response = JSON.parse(msg);

			  		//something went wrong in the admin side
				  	if( typeof response.error != 'undefined') {
				  	 	$('#' + taxonomy + '-ajax-response').addClass('error-message').text(response.error); 
				  	 } 
				  	 // term already exists
				  	 else if (typeof response.hasterm != 'undefined' ) { 
				  	 	//uncheck any 
				  	 	$('#' + taxonomy + 'checklist li :radio, #' + taxonomy + 'checklist-pop :radio').prop('checked',false);  
				  	 	//check the existing term
				  	 	$('#' + taxonomy + 'checklist li :radio[value=' + response.hasterm + '], #' + taxonomy + 'checklist-pop :radio[value=' + response.hasterm + ']').prop('checked',true); 
				  	 } 
				  	 // if neither then we must be good to go
				  	 else {
				  	 	$('#' + taxonomy + 'checklist').prepend(response.html).find('li#'+taxonomy+'-'+response.term+' :radio').attr('checked', true);
				  	 }

				});

				request.always(function() { 
				  $('#' + taxonomy + '-ajax-spinner').hide();
				});


        }); //end on button click

    });//end each

/*
 * EDIT SCREEN
 */


$('.cat-checklist input').each(function(){
	$(this).prop('type','radio');

});


})(jQuery);