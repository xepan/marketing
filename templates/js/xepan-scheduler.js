$.each({
	schedularDate: function(schedule_events){
		
		$('.draggable-event').draggable({
				zIndex: 999,
				revert: true,      // will cause the event to go back to its
				revertDuration: 0  //  original position after the drag
			});

		$element = this.jquery;

		var date = new Date();
		var d = date.getDate();
		var m = date.getMonth();
		var y = date.getFullYear();

		$options={
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,agendaWeek,agendaDay'
			},
			isRTL: $('body').hasClass('rtl'), //rtl support for calendar
			selectable: true,
			selectHelper: true,
			select: function(start, end, allDay) {
				var title = prompt('Event Title:');
				if (title) {
					calendar.fullCalendar('renderEvent',
						{
							title: title,
							start: start,
							end: end,
							allDay: allDay
						},
						true // make the event "stick"
					);
				}
				calendar.fullCalendar('unselect');
			},
			editable: true,
			droppable: true, // this allows things to be dropped onto the calendar !!!
			drop: function(date, allDay) { // this function is called when something is dropped
				// alert($(this).text());

				// // retrieve the dropped element's stored Event Object
				// var originalEventObject = $(this).data('eventObject');
				
				// // we need to copy it, so that multiple events don't have a reference to the same object
				// var copiedEventObject = $.extend({}, originalEventObject);
				
				// // assign it the date that was reported
				// copiedEventObject.start = date;
				// copiedEventObject.allDay = allDay;
				
				// // copy label class from the event object
				// var labelClass = $(this).data('eventclass');
				
				// if (labelClass) {
				// 	copiedEventObject.className = labelClass;
				// }
				
				// render the event on the calendar
				// the last `true` argument determines if the event "sticks" (http://arshaw.com/fullcalendar/docs/event_rendering/renderEvent/)
				$('#calendar').fullCalendar('renderEvent', {title:$(this).text(),'id':$(this).data('id'),'start':date}, true);
				
				// is the "remove after drop" checkbox checked?
				if ($('#drop-remove').is(':checked')) {
					// if so, remove the element from the "Draggable Events" list
					$(this).remove();
				}
				
			},
			events: schedule_events
		};

		$($element).fullCalendar($options);

	},

	schedularDays: function(){

	}
}, $.univ._import);