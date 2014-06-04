(function( $ ) {
	
	//Initializing jQuery UI Datepicker
	$( '#sis-event-start-date' ).datepicker({
		dateFormat: 'MM dd, yy',
		onClose: function( selectedDate ){
			$( '#sis-event-end-date' ).datepicker( 'option', 'minDate', selectedDate );
		}
	});
	$( '#sis-event-end-date' ).datepicker({
		dateFormat: 'MM dd, yy',
		onClose: function( selectedDate ){
			$( '#sis-event-start-date' ).datepicker( 'option', 'maxDate', selectedDate );
		}
	});

})( jQuery );