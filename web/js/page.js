$(function(){
	var nowTemp = new Date();
	var now = new Date(nowTemp.getFullYear(), nowTemp.getMonth(), nowTemp.getDate(), 0, 0, 0, 0);

	// Let's fetch the dates that we need.

	var bookedDates = [];

	$.get('v1/dates/' + window.location.pathname.substring(6), function(dates) {
		$.each(dates, function(index, date){
			var start = new Date(date.booking_from).valueOf();
			var end = new Date(date.booking_to).valueOf() + 1;

			while (start < end) {
				bookedDates.push(start);
				start = start + (24 * 60 * 60 * 1000);
			}
		})



		var startdate = $('#start_date').datepicker({
		  format: 'yyyy-mm-dd',
		  onRender: function(date) {
		    return ($.inArray(date.valueOf(), bookedDates) >= 0) ? 'disabled booked' : (date.valueOf() < now.valueOf() ? 'disabled' : '');
		  }
		}).on('changeDate', function(ev) {
		  if (ev.date.valueOf() > enddate.date.valueOf()) {
		    var newDate = new Date(ev.date)
		    newDate.setDate(newDate.getDate());
		    enddate.setValue(newDate);
		  }
		  startdate.hide();
		  $('#end_date')[0].focus();
		}).data('datepicker');




		var enddate = $('#end_date').datepicker({
		  format: 'yyyy-mm-dd',
		  onRender: function(date) {
		  	var failme = false;
		  	$.each(bookedDates, function(index, bookeddate) {
		  		if ((bookeddate.valueOf() >= startdate.date.valueOf())  &&  bookeddate.valueOf() <= date.valueOf()) {
		  			failme = true;
		  			return false;
		  		}
		  	})
		    return (failme) ? 'disabled booked' : (date.valueOf() < startdate.date.valueOf() ? 'disabled' : '');
		  }
		}).on('changeDate', function(ev) {
		  enddate.hide();
		}).data('datepicker');



	},'json')
	 

	

})