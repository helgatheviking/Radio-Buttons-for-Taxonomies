(function($) {

	/*
	 * Quick Edit
	 */

	$( '#the-list' ).on( 'click', 'a.editinline', function(){

		// reset
		inlineEditPost.revert();

		// get the post ID
		var post_id = inlineEditPost.getId(this);

		rowData = $('#inline_'+ post_id);

		// hierarchical taxonomies (we're treating all radio taxes as hierarchical)
		$('.post_category', rowData).each(function(){ 

			var taxonomy;
			var term_ids = $(this).text();

			term_ids = term_ids.trim() !== '' ? term_ids.trim() : '0';

			// protect against multiple taxonomies (which are separated with a comma , )
			// this should be overkill, but just in case
			var term_id = term_ids.split(",");
			term_id = term_id ? term_id[0] : '0';

			taxonomy = $(this).attr('id').replace('_'+post_id, '');

			$('li#'+taxonomy+'-'+term_id ).first().find('input:radio').prop('checked', true );

		});

	});

	/*
	 * Bulk Edit
	 */
	$('#doaction, #doaction2').click(function(e){
		var n = $(this).attr('id').substr(2);
		if ( 'edit' === $( 'select[name="' + n + '"]' ).val() ) {
			e.preventDefault();
			$( '.cat-checklist' ).each( function() {
				if( $(this).find( 'input[type="radio"]' ).length ) {
					$(this).find( 'input[type="radio"]' ).prop('checked', false );
					$(this).prev( 'input' ).remove(); // remove the hidden tax_input input, prevents WP from running its default save routine
				}
			});
		} 
	});

})(jQuery);