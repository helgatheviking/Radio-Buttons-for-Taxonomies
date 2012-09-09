(function($) {

/*
 * SINGLE POST SCREEN
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
			//s.data += '&' + $( ':checked', '#'+taxonomy+'checklist' ).serialize();
			$( '#' + taxonomy + '-add-submit' ).prop( 'disabled', true );
			return s;
		};

		catAddAfter = function( r, s ) {  console.log(r); console.log(s);
			var sup, drop = $('#new'+taxonomy+'_parent');

			$( '#' + taxonomy + '-add-submit' ).prop( 'disabled', false );
			if ( 'undefined' != s.parsed.responses[0] && (sup = s.parsed.responses[0].supplemental.newcat_parent) ) {
				drop.before(sup);
				drop.remove();
			}
	
			//fix for popular radio buttons- when new term is added -uncheck all
        	$('#' + taxonomy + 'checklist-pop :radio').prop('checked',false);  

		};

		//wpList doesn't work well with non hierarchical taxonomies so we'll need to do that outselves
		$('#' + taxonomy + 'checklist:not(.tagchecklist)').wpList({
			alt: '',
			response: taxonomy + '-ajax-response',
			addBefore: catAddBefore,
			addAfter: catAddAfter
		});

		//add non-hierarchical via our own ajax
		$('#' + taxonomy +'-add .radio-add-submit').on( 'click', function(){  
			term = $('#' + taxonomy+'-add #new'+taxonomy).val();    console.log(term);
			nonce =$('#' + taxonomy+'-add #_wpnonce_radio-add-tag').val();
			$.post(ajaxurl, {
				action: 'radio_tax_add_taxterm',
				term: term,
				'_wpnonce_radio-add-tag':nonce,
				taxonomy: taxonomy
				}, function(r){
					$('#' + taxonomy + 'checklist').append(r.html).find('li#'+taxonomy+'-'+r.term+' :radio').attr('checked', true);
				},'json');
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
            $('#in-' + taxonomy + '-' + id + ', #in-popular-' + taxonomy + '-' + id).prop( 'checked', c );  

	    });  //end on radio click

	}); // end cats

/*
 * EDIT POST SCREEN
 *
 * @todo: support other taxonomies
 */

	$('.editinline').on('click', function(){

	    matches = $(this).parents('tr').attr('id').match(/\post-(\d+)/);

	    if ( matches ) post_id = matches[1];

		if (post_id) {

			category = $('.post_category', '#post-'+post_id).text();

			// protect against multiple categories (which are separated with a comma ,
			categories = category.split(",");
			category = categories ? categories[0] : category;

			//uses :radio so doesn't need any other special selector
			//seems to need :first for some reason
			$( "ul.cat-checklist :radio[value="+category+"]:first" ).attr( 'checked', 'checked' );

		}

	});



})(jQuery);