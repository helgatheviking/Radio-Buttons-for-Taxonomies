(function($) {

/*
 * SINGLE POST SCREEN
 */
 /*
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
        		parent = $('#new' + taxonomy+'_parent').length ? $('#new' + taxonomy+'_parent').val() : false; 
				nonce =$('#' + taxonomy+'-add #_wpnonce_radio-add-tag').val(); 

				var request = $.ajax({
				  type: "POST",
				  url: ajaxurl,
				  data: { 'action': 'radio_tax_add_taxterm', 
				  		'_wpnonce_radio-add-tag': nonce, 
				  		'taxonomy' : taxonomy, 
				  		'term' : term,
				  		'parent' : parent },
				  beforeSend: function() {
				     $('#' + taxonomy + '-ajax-spinner').show(); 
				  }
				});

				request.fail(function(msg, textStatus) {   
				  $('#' + taxonomy + '-ajax-response').addClass('error-message').text(msg); 
				});

				request.done(function(msg, textStatus) { 

					var response = JSON.parse(msg);  console.log(response);

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
				  	 	if(response.parent){
				  	 		alert('has parent');
				  	 	} else {
				  	 		$('#' + taxonomy + 'checklist').prepend(response.html).find('li#'+taxonomy+'-'+response.term+' :radio').attr('checked', true);
				  	 	}
				  	 }

				});

				request.always(function() { 
				  $('#' + taxonomy + '-ajax-spinner').hide();
				});


        }); //end on button click

    });//end each
*/

	// categories
	$('.radio-buttons-for-taxonomies').each( function(){	
		var this_id = $(this).attr('id'), noSyncChecks = false, syncChecks, catAddAfter, taxonomyParts, taxonomy, settingName;

		taxonomyParts = this_id.split('-');
		taxonomyParts.shift();
		taxonomy = taxonomyParts.join('-');  
 		settingName = taxonomy + '_tab';
 		if ( taxonomy == 'category' )
 			settingName = 'cats';

		// TODO: move to jQuery 1.3+, support for multiple hierarchical taxonomies, see wp-lists.dev.js
		$('a', '#' + taxonomy + '-tabs').click( function(){
			var t = $(this).attr('href');
			$(this).parent().addClass('tabs').siblings('li').removeClass('tabs');
			$('#' + taxonomy + '-tabs').siblings('.tabs-panel').hide();
			$(t).show();
			if ( '#' + taxonomy + '-all' == t )
				deleteUserSetting(settingName);
			else
				setUserSetting(settingName, 'pop');
			return false;
		});

		if ( getUserSetting(settingName) )
			$('a[href="#' + taxonomy + '-pop"]', '#' + taxonomy + '-tabs').click();

		// Ajax Cat
		$('#new' + taxonomy).one( 'focus', function() { $(this).val( '' ).removeClass( 'form-input-tip' ) } );
		$('#' + taxonomy + '-add-submit').click( function(){ $('#new' + taxonomy).focus(); });

		syncChecks = function() {
			if ( noSyncChecks )
				return;
			noSyncChecks = true;
			var th = jQuery(this), c = th.is(':checked'), id = th.val().toString();
			$('#in-' + taxonomy + '-' + id + ', #in-' + taxonomy + '-category-' + id).prop( 'checked', c );
			noSyncChecks = false;
		};

		catAddBefore = function( s ) {
			if ( !$('#new'+taxonomy).val() )
				return false;
			s.data += '&' + $( ':checked', '#'+taxonomy+'checklist' ).serialize();
			$( '#' + taxonomy + '-add-submit' ).prop( 'disabled', true );
			return s;
		};

		catAddAfter = function( r, s ) {
			var sup, drop = $('#new'+taxonomy+'_parent');

			$( '#' + taxonomy + '-add-submit' ).prop( 'disabled', false );
			if ( 'undefined' != s.parsed.responses[0] && (sup = s.parsed.responses[0].supplemental.newcat_parent) ) {
				drop.before(sup);
				drop.remove();
			}
		};

		$('#' + taxonomy + 'checklist').wpList({
			alt: '',
			response: taxonomy + '-ajax-response',
			addBefore: catAddBefore,
			addAfter: catAddAfter
		});

		$('#' + taxonomy + '-add-toggle').on('click', function() {
			$('#' + taxonomy + '-adder').toggleClass( 'wp-hidden-children' );
			$('a[href="#' + taxonomy + '-all"]', '#' + taxonomy + '-tabs').click();
			$('#new'+taxonomy).focus();
			return false;
		});

		$('#' + taxonomy + 'checklist li.popular-category :checkbox, #' + taxonomy + 'checklist-pop :checkbox').on( 'click', function(){
			var t = $(this), c = t.is(':checked'), id = t.val();
			if ( id && t.parents('#taxonomy-'+taxonomy).length )
				$('#in-' + taxonomy + '-' + id + ', #in-popular-' + taxonomy + '-' + id).prop( 'checked', c );
		});

	}); // end cats

/*
 * EDIT SCREEN
 */


$('.cat-checklist input').each(function(){
	$(this).prop('type','radio');

});


})(jQuery);