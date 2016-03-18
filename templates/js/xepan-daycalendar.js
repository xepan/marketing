var xepan_subscriptionday = function(day){
	this.day = day;
	this.events= {};
	
	this.addEvent= function(data){ // xepan_subscriptionevent object
		console.log(data);
		console.log(data.data.id);
		this.events[data.data.id] = data.data;
		// console.log(this.events);
		return data;
	};

	this.hasEvent=function(data){ // xepan_subscriptionevent object
		return this.events[data.data.id] != undefined;
	};

	this.removeEvent= function(data){ // xepan_subscriptionevent object
		// CALL AJAX
		var self=this;
		delete  this.events[data.data.id];
	};
	
	this.render= function(parent){
		this.duration = this.day;
		day_obj = $('<div class="days clearfix panel panel-default atk-padding-small day-'+this.duration+'"></div>').appendTo($(parent)).data('duration',this.duration);
		duration_title = $('<div class="atk-size-tera pull-left panel panel-default atk-padding-large">'+this.duration+'</div>').appendTo(day_obj);
		$.each(this.events, function(index, e) {
			e.render(day_obj);
		});
		return day_obj;
	};
}

var xepan_subscriptionevent = function(evt) { // Json Data of a event
	this.data = evt;
	this.render= function(parent){
		$('<div class="label label-success added_event atk-padding-small pull-left" style="margin: 5px">'+this.data.title+'</div>').appendTo(parent).data('event',this.data);
		// console.log('rendering '+ this.event.title);
	};
	return this;
}
	
jQuery.widget("ui.xepan_daycalendar", {

	num:1 ,
	add_day_btn: undefined,
	add_day_inp: undefined,
	schedular: undefined,
	trash: undefined,
	days:{}, // Internal storage
	options:{
		events: {}, // User send json for all days and events from database as initialization of widget
		url: '',
		schedular_name:'',
		campaign_id:'',
		height:'400px'
	},

	schedularDate: function(schedule_events){
		$('.draggable-event').draggable({
				zIndex: 999,
				revert: true,      // will cause the event to go back to its
				revertDuration: 0  //  original position after the drag
			})},

	_create: function(){
		this.setupLayout();
		this.schedularDate();
	},

	setupLayout:function(){
		var self = this;

		/**
			Add Days Header
		*/
		header = $('<div class="xepan-daycalendar-header xepan-push-large row">').appendTo(this.element);
		
		this.add_input = $('<input type="number" placeholder="Enter Day" id="dayInput" class="xepan-push-small col-md-5">').appendTo(header);
		
		input_group = $('<div class="row col-lg-12">').appendTo(header);		

		this.add_button = $('<span class="btn btn-primary col-md-3" type="submit" id="dayAdd">Add Day</span>').appendTo(input_group);
		
		this.trash = $('<button id="dayTrash" class=" days btn btn-danger col-md-3" type="button"><span class="fa fa-trash-o"></span></button>').appendTo(input_group);
		


		/**
			Dragged Schedules
		*/

		body = $('<div></div>').appendTo(this.element);
		this.schedular = $(' <div class="xepan-push-small"></div>').appendTo(body);


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
			self.render();
		});

				this.trash.sortable({
				connectWith: ".days",
				receive: function(day, ui){
					var duration = ui.sender.data('day');
					self.days[ui.sender.data('duration')].removeEvent(new xepan_subscriptionevent(ui.item.data('data')));
					self.inform('removeEvent',duration,ui.item.data('data')._nid);
					ui.item.remove();
				}
			});

	},

	addDay: function(day){
		return this.days[day] = new xepan_subscriptionday(day);

	},

	render: function(){
		var self=this;
		self.schedular.html('');

		jQuery.each(this.days, function(index, day) {
			$(day.render(self.schedular)).sortable({
				connectWith: ".days",
				items: ".added_event",
				receive: function( event, ui ){
					if(day.hasEvent(new xepan_subscriptionevent(ui.item.data('event')))){
						$(ui.sender).sortable('cancel');
						// console.log('already have');
						return;
					}
					self.days[index].addEvent(new xepan_subscriptionevent(ui.item.data('event')));
					self.days[ui.sender.data('duration')].removeEvent(new xepan_subscriptionevent(ui.item.data('event')));
					// console.log('newsletter '+ui.item.data('event').title+ ' moved from '+ ui.sender.data('duration') + ' to ' + day.duration );
					self.inform('moveEvent',day.duration,ui.item.data('event')._id,ui.sender.data('duration'));
				}
			}).droppable({
				drop: function(event, ui){
					// console.log(ui.helper.data());

					if(!ui.helper.is('.added_event')){
						if(!day.hasEvent(new xepan_subscriptionevent(ui.helper.data()))){
							new_evt=day.addEvent(new xepan_subscriptionevent(ui.helper.data()));
							new_evt.render($('.day-'+day.duration));
							// $('.day-'+ day.duration).scrollTop();
							// self.inform('addEvent',day.duration,ui.helper.data()._id);
						}else{
							// console.log(day.events);
						}
					}else{
						alert("hello");
					}
				}
			});
		});
	},

	inform: function(what,on_day,newsletter_id,from_day){
		var self=this;
		var calendar_name= self.options.schedular_name;
		var param = {};
		// param[calendar_name+'_event_type']=new_event._eventtype;
		param[calendar_name+'_event_act']=what;
		param[calendar_name+'_event_id']=newsletter_id;
		param[calendar_name+'_onday']= on_day;
		param[calendar_name+'_fromday']= from_day;
		param['campaign_id']= self.options.campaign_id;

		var success=true;
		var cogs=$('<div id="banner-loader" class="atk-banner atk-cells atk-visible"><div class="atk-cell atk-align-center atk-valign-middle"><div class="atk-box atk-inline atk-size-zetta atk-banner-cogs"></div></div></div>');
        cogs.appendTo('body');
		
	}

});