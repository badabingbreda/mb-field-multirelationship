var mbMultirelationship = {

	collectData : function ( selector ) {

		var data = [];

		if (typeof $ == 'undefined' ) var $ = jQuery;

		$collection = $( '#' + selector + ' .__row__' );

		$collection.each( function (index) {
			var $item = $(this).find( 'input[type=radio]' ).filter( ':checked' );
			if ( $item.length == 0 ) return;
			data.push( { id : parseInt($item.data('id')) , label: $item.data( 'label') , value: $item.val() });
		});

		return data;

	},

	optionsFromData : function ( data , options , active ) {

		if (typeof $ == 'undefined' ) var $ = jQuery;

		var newItem = '';

		console.log( active );

		for (let i = 0 ; i < options.length ; i ++ ) {
			 newItem += '<input type="radio" id="attendance_' + data.id + '_' + options[i].slug + '" ' +
			 			'name="attendance_' + data.id + '" value="'+options[i].slug+'" data-id="'+data.id+'" data-label="'+ data.label +'"' +
			 			( ( data.value == options[i].slug ) || ( typeof data.value == 'undefined' && active == options[i].slug ) ? ' checked' : '' ) + '>' +
			 			'<label for="attendance_' + data.id + '_' + options[i].slug + '">' + options[i].label + '</label>';
		}
		return newItem;
	},

	select2Settings : function( selector , params ) {

		var ids = [];

		if (typeof $ == 'undefined' ) var $ = jQuery;

		let items = $( '.items .__row__' );

		for (let i = 0; i < items.length ; i ++ ) {
			ids.push( parseInt($(items[i]).data('id')) );
		}

		//var data = JSON.parse( $( '#' + selector + ' input[type=hidden]' ).val() );
		// for (let i = 0; i < data.length ; i ++ ) {
		// 	ids.push( parseInt(data[i].id) );
		// }

		return { to : $( '#' + selector + '' ).data( 'to' ) , ids: ids , page: params.page || 1 , q : params.term || '' , action: 'get_mrs_options' };
	},

	insertItem : function( data , $item ) {

		if (typeof $ == 'undefined' ) var $ = jQuery;

		var autoselect = $item.attr( 'data-autoselect' );

		let listItem = $(mbmrs.listItem);
		listItem.attr( 'data-id' , data.id );
		listItem.find( '.__mrs__label__' ).html( data.label );
		listItem.find( '.__mrs__options__' ).html( mbMultirelationship.optionsFromData( data, $item.data( 'options' ) , autoselect )  );
		// create eventListeners for the newly create options
		listItem.find( '.__mrs__options__ input[type=radio]' ).each( function() {
			$(this).on( 'click' , function() {
				// collect the data for this ID
				let data = mbMultirelationship.collectData( $item.attr( 'id' ));
				mbMultirelationship.updateData( $item , data );

			} );
		});

		return listItem;

	},

	updateData : function( $item , data ) {

		if (typeof $ == 'undefined' ) var $ = jQuery;

		$item.find( 'table' ).attr( 'data-value' , JSON.stringify( data ) );
		$item.find( 'input[name='+$item.attr('id')+']' ).attr( 'value' , JSON.stringify( data ));

	}



};

(function($) {

	$( document ).ready( () => {

		$( '.multirelationshipField' ).each( function ( index ) {

			$item = $( this );

			$item.find( '.mrs__add_relationship' ).on( 'click' , function() {

				let data = $item.find('.multirelationship-ajax').select2( 'data' );

				listItem = mbMultirelationship.insertItem( { id: data[0].id , label: data[0].text } , $item );
				$item.find( '.items' ).append( listItem );
				$item.find('.multirelationship-ajax').val(null).trigger('change');
				let itemsdata = mbMultirelationship.collectData( $item.attr( 'id' ));
				mbMultirelationship.updateData( $item , itemsdata );




			});

			$item.find( '.basic-relation' ).html( mbmrs.newRelation );

			// get the current data
			var data = $item.find( 'input[name='+$item.attr('id')+']' ).val();

			data = ( typeof data == 'object' ) ? data : JSON.parse( data );

			// add the listitem template for the current values
			for( let i = 0 ; i < data.length ; i++ ) {

				listItem = mbMultirelationship.insertItem( data[i] , $item );
				$item.find( '.items' ).append( listItem );

			}

			$( 'body' ).delegate( '.__mrs__delete_button__' , 'click' , function() {

				// get the parent id
				let id = $(this).closest( '.multirelationshipField' ).attr( 'id' );

				$(this).closest('.__row__').remove();
				let data = mbMultirelationship.collectData( id );
				mbMultirelationship.updateData( $( '#' + id ) , data );

			} );

			// try to create the items


			$item.find( '.multirelationship-ajax' ).select2({
  				ajax: {
    				url: mbmrs.adminUrl,
    				dataType: 'json',
    				data: function(params) {
    					return mbMultirelationship.select2Settings( $item.attr('id') , params );
    				},
  				}
			});


		});


	});


})(jQuery);