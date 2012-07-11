jQuery(document).ready(function($) {  

	$('.radio-buttons-for-taxonomies').each(function(){

        id = $(this).attr('id');

        taxonomy = id.replace(/taxonomy-/g,""); 

        $('.postbox').on('click', $('#' + taxonomy + 'checklist li :radio, #' + taxonomy + 'checklist-pop :radio'), function(){  
            var t = $(this), c = t.is(':checked'), id = t.val();  
            $('#' + taxonomy + 'checklist li :radio, #' + taxonomy + 'checklist-pop :radio').prop('checked',false);  
            $('#in-' + taxonomy + '-' + id + ', #in-popular-' + taxonomy + '-' + id).prop( 'checked', c );  
        });  

        $('#add-new-' + taxonomy).click(function(){ 
		term = $('#' + taxonomy+'-add #new'+taxonomy).val();
		nonce =$('#' + taxonomy+'-add #_wpnonce_radio-add-tag').val();
		$.post(ajaxurl, { 
			action: 'radio_tax_add_taxterm',
			term: term,
			'_wpnonce_radio-add-tag':nonce,
			taxonomy: taxonomy
			}, function(r){  
				if(r!=-1) { 
					$('#' + taxonomy + 'checklist').append(r.html).find('li#'+taxonomy+'-'+r.term+' :radio').attr('checked', true);
				}
			},'json');
	    });  
    });//end each
});  //end ready
