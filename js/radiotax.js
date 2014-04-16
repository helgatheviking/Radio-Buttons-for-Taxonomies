(function($) {

/*
 * SINGLE POST SCREEN
 */

	// taxonomy metaboxes
	// mimics the category script from /wp-admin/js/post.js
	$('.radio-buttons-for-taxonomies').each( function(){
		var this_id = $(this).attr('id'), noSyncChecks = false, syncChecks, catAddAfter, taxonomyParts, taxonomy, settingName;

		taxonomyParts = this_id.split('-');
		taxonomyParts.shift();
		taxonomy = taxonomyParts.join('-');
		settingName= taxonomy + '_tab';
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
		$('#new' + taxonomy).one( 'focus', function() { $(this).val( '' ).removeClass( 'form-input-tip' ); } );

		$('#new' + taxonomy).keypress( function(event){
			if( 13 === event.keyCode ) {
				event.preventDefault();
				$('#' + taxonomy + '-add-submit').click();
			}
		});
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
			// disable because we're only allowed the new term to be the set term
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
			var nonce =$('#_ajax_nonce-add-' + taxonomy ).val();

			//quit if the term is empty
			if ( ! term.length ) return;

			var request = $.ajax({
				type: 'POST',
				url: ajaxurl,
				data: { 'action': 'radio_tax_add_taxterm', 'wpnonce_radio-add-term' : nonce, 'taxonomy' : taxonomy, 'term' : term }
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
		$('#' + taxonomy + '-all li :radio, #' + taxonomy + '-pop li :radio').on('click', function(){
			var t = $(this), c = t.is(':checked'), id = t.val();
			$('#' + taxonomy + '-all li :radio, #' + taxonomy + '-pop li :radio').prop('checked',false);
			$('#' + taxonomy + '-all li :radio[value="'+id+'"], #' + taxonomy + '-pop li :radio[value="'+id+'"]').prop( 'checked', c );
		});  //end on radio click

	}); // end taxonomy metaboxes


/*
 * EDIT POST SCREEN
 * Quick Edit
 */
 	// modify the selected taxonomies of the #inlineedit template to use radio buttons 
	$('#inlineedit').find('.inline-edit-categories').find('.cat-checklist').each( function(){  
		var ul = this;

		// extract the taxonomy name from the element
		var tax = $(this).attr("class").match(/ ([a-z]+)-checklist/); 

		// check to see if this taxonomy is a radio button taxonomy, from "localized" global variable radiotaxdata
		if( tax && tax.length > 1 && $.inArray( tax[1], radiotaxdata['taxonomies'] ) ) {

			// if so, change the markup to suit
			$(ul).addClass("radio-checklist").attr("id", tax[1]);
			var input = $("input[name='tax_input\[" + tax[1] + "\]\[\]']")
			input.attr("name", "radio_tax_input[" + tax[1] + "][]");

		}
	});

	$( '#the-list' ).on( 'click', '.editinline', function(){

		// reset
		inlineEditPost.revert();

		var post_id = $(this).parents('tr').attr('id').match(/post-([\d]+)/)[1];

		// for each checklist get the value and check the correct input
		$( 'ul.radio-checklist', '.quick-edit-row' ).each( function () {
			
			var taxonomy = $(this).attr('id');
			
			var value = $( '#' + taxonomy + '_' + post_id ).text();

			term_id = value.trim() !== '' ? value.trim() : "0";

			// protect against multiple taxonomies (which are separated with a comma , )
			// this should be overkill, but just in case			
			if( typeof value === "string" && value.indexOf(",") > 0 ){
				term_id = term_id.split(",")[0];
			}

			//uses :radio so doesn't need any other special selector
			$( this ).find( ":radio[value="+term_id+"]" ).prop( 'checked', true );
		});


	});

	/*
	 * EDIT POST SCREEN
	 * Bulk Edit
	 */
	$( '#bulk_edit' ).on( 'click', function() {

		// define the bulk edit row
		var $bulk_row = $( '#bulk-edit' );

		// get the selected post ids that are being edited
		var post_ids = [];
		$bulk_row.find( '#bulk-titles' ).children().each( function() {
			post_ids.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
		});

		// for each checklist get the value and check the correct input
		$( 'ul.radio-checklist', '.bulk-edit-row' ).each( function () { 

			taxonomy = $(this).attr('id');

			// get the taxonomy terms date
			var term = $bulk_row.find( 'input:checked' ).val();

			// save the data
			$.ajax({
				url: ajaxurl, // this is a variable that WordPress has already defined for us
				type: 'POST',
				async: false,
				cache: false,
				data: {
					action: 'save_bulk_edit_' + taxonomy, // this is the name of our WP AJAX function
					post_ids: post_ids, // and these are the 2 parameters we're passing to our function
					term: term
				}
			});

		});

	});

})(jQuery);