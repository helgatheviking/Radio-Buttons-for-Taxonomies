(function($) {

/*
 * SINGLE POST SCREEN
 */

	// taxonomy metaboxes
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
			//s.data += '&' + $( ':checked', '#'+taxonomy+'checklist' ).serialize();
			$( '#' + taxonomy + '-add-submit' ).prop( 'disabled', true );
			return s;
		};

		catAddAfter = function( r, s ) { 
			var sup, drop = $('#new'+taxonomy+'_parent');

			//fix for popular radio buttons- when new term is added -uncheck all
        	$('#' + taxonomy + 'checklist-pop :radio').prop('checked',false);  

			$( '#' + taxonomy + '-add-submit' ).prop( 'disabled', false );
			if ( 'undefined' != s.parsed.responses[0] && (sup = s.parsed.responses[0].supplemental.newcat_parent) ) {
				drop.before(sup);
				drop.remove();

				id = $(s.parsed.responses[0].data).find('input').attr('value');  

				//if an existing term is added, check it in the popular list too
				$('#in-popular-' + taxonomy + '-' + id ).prop('checked', true);  
			}
	
			

		};

		//use wpList to handle hierarchical taxonomies
		$('#' + taxonomy + 'checklist:not(.tagchecklist)').wpList({
			alt: '',
			response: taxonomy + '-ajax-response',
			addBefore: catAddBefore,
			addAfter: catAddAfter
		});

		// wpList doesn't work well with non hierarchical taxonomies so we'll need to do that outselves via ajax
		$('#' + taxonomy +'-add .radio-add-submit').on( 'click', function(){  
			var term = $.trim( $('#' + taxonomy+'-add #new'+taxonomy).val() ); 
			var nonce =$('#' + taxonomy+'-add #radio_' + taxonomy + '_nonce' ).val(); 

			//quit if the term is empty
			if ( ! term.length ) return;

			var request = $.ajax({
				type: "POST",
				url: ajaxurl,
				data: { action: "radio_tax_add_taxterm", '_wpnonce_radio-add-tag': nonce, 'taxonomy' : taxonomy, 'term' : term }
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

				//uncheck any currently checked
				$('#' + taxonomy + 'checklist li :radio, #' + taxonomy + 'checklist-pop :radio').prop('checked',false);  
				// term already exists
				if (typeof response.hasterm != 'undefined' ) { 
					//check the existing term in regular list and move to top (mimics add new term)
				  	$( '#in-' + taxonomy + '-' + response.hasterm ).prop('checked',true).parents('li').prependTo('#' + taxonomy + 'checklist'); 

				  	//check the existing term in popular list
				  	$( '#in-popular-' + taxonomy + '-' + response.hasterm ).prop('checked',true).parents('li');
				} 
				// if neither then we must be good to go
				else {
					$('#' + taxonomy + 'checklist').prepend(response.html);
				}

			});

	    }); 

		$('#' + taxonomy + '-add-toggle').click( function() {
			$('#' + taxonomy + '-adder').toggleClass( 'wp-hidden-children' );
			$('a[href="#' + taxonomy + '-all"]', '#' + taxonomy + '-tabs').click();
			$('#new'+taxonomy).focus();
			return false;
		});

		//fix for radio buttons- if click on popular select on all and vice versa
        $('#' + taxonomy + 'checklist li :radio, #' + taxonomy + 'checklist-pop :radio').on('click', function(){  
            var t = $(this), c = t.is(':checked'), id = t.val();
            $('#' + taxonomy + 'checklist li :radio, #' + taxonomy + 'checklist-pop :radio').prop('checked',false);  
            $('#' + taxonomy + 'checklist li :radio[value="'+id+'"], #' + taxonomy + 'checklist-pop :radio[value="'+id+'"]').prop( 'checked', c );  

	    });  //end on radio click

	}); // end taxonomy metaboxes


/*
 * EDIT POST SCREEN
 *
 * @todo: support other taxonomies
 */

	$('.editinline').on('click', function(){

		// reset
		inlineEditPost.revert();  

		tag_id = $(this).parents('tr').attr('id'); 

		id = tag_id.split("-");

		id = ( typeof id[1] != 'undefined' ) ? id[1] : '';  

		// for each checklist get the value and check the correct input

		if ( typeof radio_button_for_taxonomies.taxonomies == 'undefined' ) return;

		//for each radio enabled taxonomy
		$.each( radio_button_for_taxonomies.taxonomies, function( i, taxonomy ) {

			term = $( '#inline_'+id ).find('#' + taxonomy + '_' + id ).text();
			// protect against multiple taxonomies (which are separated with a comma , )
			// this should be overkill, but just in case
			terms = term.split(",");
			term = terms ? terms[0] : term; 

			//uses :radio so doesn't need any other special selector
			$( 'ul.'+taxonomy+'-checklist', '.quick-edit-row' ).find( ':radio[value='+term+']' ).prop( 'checked', true );

		});

	});


})(jQuery);