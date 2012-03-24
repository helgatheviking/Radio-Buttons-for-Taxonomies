    jQuery(document).ready(function($) {  
        var taxonomy = radio_tax.slug; //Taxonomy slug, or set manually.
        $('#' + taxonomy + 'checklist li :radio, #' + taxonomy + 'checklist-pop :radio').live( 'click', function(){  
            var t = $(this), c = t.is(':checked'), id = t.val();  
            $('#' + taxonomy + 'checklist li :radio, #' + taxonomy + 'checklist-pop :radio').prop('checked',false);  
            $('#in-' + taxonomy + '-' + id + ', #in-popular-' + taxonomy + '-' + id).prop( 'checked', c );  
        });  
    });  
