var xepan_subscriptionday = function(day){
	this.day = day;
	this.events= {};
	
	this.addEvent= function(evt){ // xepan_subscriptionevent object
		// CALL AJAX
		this.events[evt.event._nid] = evt;
		this.events[evt.event._nid] = evt;
		return evt;
	};
	this.hasEvent=function(evt){ // xepan_subscriptionevent object
		return this.events[evt.event._nid] != undefined;
	};
	this.removeEvent= function(evt){ // xepan_subscriptionevent object
		// CALL AJAX
		var self=this;
		delete  this.events[evt.event._nid]
	};
	
	this.render= function(parent){
		console.log('day rendered '+ this.duration);
		day_obj = $('<div class="days clearfix panel panel-default atk-padding-small day-'+this.duration+'"></div>').appendTo($(parent)).data('duration',this.duration);
		duration_title = $('<div class="atk-size-tera pull-left panel panel-default atk-padding-large">'+this.duration+'</div>').appendTo(day_obj);
		$.each(this.events, function(index, e) {
			e.render(day_obj);
		});
		return day_obj;
	};
}
	
jQuery.widget("ui.xepan_daycalendar", {

	days:{}, // For storing days
	// options:{
	// 	events: {}, // JSon encoded events
	// 	url: '',
	// 	schedular_name:'',
	// 	campaign_id:'',
	// 	height:'400px'
	// },

	schedularDate: function(schedule_events){
		$('.draggable-event').draggable({
				zIndex: 999,
				revert: true,      // will cause the event to go back to its
				revertDuration: 0  //  original position after the drag
			})},

	_create: function(){
		this.setupLayout();
		this.schedularDate();
		// console.log(this.element);
	},

	setupLayout:function(){
		var self = this;

		/**
			Add Days Header
		*/
		header = $('<div class="xepan-daycalendar-header row">').appendTo(this.element);
		
		input_group = $('<div class="input-group">').appendTo(header);
		this.add_input = $('<input type="number" placeholder="Enter Day" id="dayInput" class="form-control">').appendTo(input_group);		
		this.add_button = $('<span class="btn btn-primary input-group-addon" type="submit" id="dayAdd">Add Day</span>').appendTo(input_group);
		this.trash = $('<button id="dayTrash" class="btn btn-danger col-md-1" type="button"><span class="fa fa-trash-o"></span></button>').appendTo(input_group);


		/**
			Dragged Schedules
		*/
		body = $('<div></div>').appendTo(this.element);
		this.schedule = $('<div></div> <hr>');


		this.add_button.bind('click', undefined, function(event) {
			var input = parseInt(self.add_input.val());
			if(!input){
				$(self.add_input).effect('highlight');
				return;
			}

			if(self.days[input]){
				self.add_input.effect('shake');
				return;	
			}

			self.addDay(input);

		});	
	},

	addDay: function(day){
		return this.days[day] = new xepan_subscriptionday(day);
	}

});