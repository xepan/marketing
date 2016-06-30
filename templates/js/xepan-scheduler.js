$.each({
	
	getDateEvents : function(field){
		events_list = $('#calendar').fullCalendar('clientEvents');
		event_data = [];

		$.each(events_list,function(index,value){
			event_data.push({title:value.title,start:value.start,document_id:value.document_id,'client_event_id':value._id});
		});
		$(field).val(JSON.stringify(event_data));
	},

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
				// var title = prompt('Event Title:');
				// if (title) {
				// 	calendar.fullCalendar('renderEvent',
				// 		{
				// 			title: title,
				// 			start: start,
				// 			end: end,
				// 			allDay: allDay
				// 		},
				// 		true // make the event "stick"
				// 	);
				// }
				calendar.fullCalendar('unselect');
			},
			editable: true,
			droppable: true, // this allows things to be dropped onto the calendar !!!
			drop: function(date, allDay) { // this function is called when something is dropped
				// alert($(this).text());
				// // retrieve the dropped element's stored Event Object
				var originalEventObject = $(this).data('eventsource');
				
				// // we need to copy it, so that multiple events don't have a reference to the same object
				var copiedEventObject = $.extend({}, originalEventObject);
				
				// // assign it the date that was reported
				copiedEventObject.start = date;
				copiedEventObject.allDay = allDay;
				copiedEventObject.contenttype = $(this).data('contenttype');
				
				// // copy label class from the event object
				var labelClass = $(this).data('eventclass');
				
				if (labelClass) {
					copiedEventObject.className = labelClass;
				}
				
				// render the event on the calendar
				// the last `true` argument determines if the event "sticks" (http://arshaw.com/fullcalendar/docs/event_rendering/renderEvent/)
				// {title:$(this).text(),'start':date,'document_id':$(this).data('id'),'contenttype':$(this).data('contenttype')}
				$('#calendar').fullCalendar('renderEvent',copiedEventObject , true);
				// 
				// is the "remove after drop" checkbox checked?
				if ($('#drop-remove').is(':checked')) {
					// if so, remove the element from the "Draggable Events" list
					$(this).remove();
				}
				
			},
			events: schedule_events,

			eventDragStop:function(event,jsEvent,ui,view){
				var trashEl = jQuery('#calendarTrash');
			    var ofs = trashEl.offset();

			    var x1 = ofs.left;
			    var x2 = ofs.left + trashEl.outerWidth(true);
			    var y1 = ofs.top;
			    var y2 = ofs.top + trashEl.outerHeight(true);
			    
			    if (jsEvent.pageX >= x1 && jsEvent.pageX<= x2 &&
			        jsEvent.pageY>= y1 && jsEvent.pageY <= y2) {
			        $($element).fullCalendar('removeEvents', event._id);
			    }
			},
			
			eventRender: function(event, element, view) {
        		return $('<a class="fc-day-grid-event fc-h-event fc-event fc-start fc-end fc-draggable xepan-marketing-campaign-content-'+event.contenttype+'"  title="'+event.title+'" ><div class="fc-content"> <span class="fc-title">'+event.title+'</span></div></a>');
    		}
		};

		$($element).fullCalendar($options);
		event_trash = $($element).children('.fc-toolbar').children('.fc-left').children('.fc-button-group')
		.append('<button id="calendarTrash" class="btn btn-danger" type="button"><span class="fa fa-trash-o"></span></button>');
		
	},

	schedularDays: function(){

	}
}, $.univ._import);