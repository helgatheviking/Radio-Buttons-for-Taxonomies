jQuery(document).ready(function($) {  

	$('.radio-buttons-for-taxonomies').each(function(){

        id = $(this).attr('id');

        taxonomy = id.replace(/taxonomy-/g,""); 

        $('#' + taxonomy + 'checklist li :radio, #' + taxonomy + 'checklist-pop :radio').on('click', function(){  
            var t = $(this), c = t.is(':checked'), id = t.val();  console.log(t); console.log(c); console.log(id);
            $('#' + taxonomy + 'checklist li :radio, #' + taxonomy + 'checklist-pop :radio').prop('checked',false);  
            $('#in-' + taxonomy + '-' + id + ', #in-popular-' + taxonomy + '-' + id).prop( 'checked', c );  
        });  

        $('#' + taxonomy + '-add').find('input.radio-tax-add').click(function(){ 
		term = $('#' + taxonomy+'-add #new'+taxonomy).val(); 
		nonce =$('#' + taxonomy+'-add #_wpnonce_radio-add-tag').val(); 
		$.post(ajaxurl, { 
			action: 'radio_tax_add_taxterm',
			term: term,
			'_wpnonce_radio-add-tag':nonce,
			taxonomy: taxonomy
			}, function(r){  
				if(r!=-1) { 
					$('#' + taxonomy + 'checklist').prepend(r.html).find('li#'+taxonomy+'-'+r.term+' :radio').attr('checked', true);
				}
			},'json');
	    });  
    });//end each
});  //end ready
