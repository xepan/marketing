// Day Widget

var xepan_subscriptionday = function(duration){
	this.duration = duration;
	this.events= {};
	
	this.addEvent= function(evt){ // xepan_subscriptionevent object
		// CALL AJAX
		this.events[evt.event._nid] = evt;// event_html.appendTo($(this.element).closest('.days'));
		// this.events[evt._nid] = evt;// event_html.appendTo($(this.element).closest('.days'));
		return evt;
	};
	this.hasEvent=function(evt){ // xepan_subscriptionevent object
		return this.events[evt.event._nid] != undefined;
	};
	this.removeEvent= function(evt){ // xepan_subscriptionevent object
		// CALL AJAX
		var self=this;
		delete  this.events[evt.event._nid];
	};
	
	this.render= function(parent){
		self  = this;
		day_obj = $('<div class="days clearfix panel panel-default atk-padding-small day-'+this.duration+'" data-duration='+this.duration+'></div>').appendTo($(parent)).data('duration',this.duration);
		// day_remove = $('<div class="subscription-day-remove label label-danger btn-xm" data-duration='+this.duration+'>X</div>').appendTo(day_obj);
		
		// $(day_remove).click(function(){
		// 	console.log(self.events);
			
		// 	// duration = $(this).data('duration');
		// 	// var days = new xepan_subscriptionday(duration);
		// 	// days.removeDay(duration);
		// });

		duration_title = $('<div class="atk-size-tera pull-left panel panel-default atk-padding-large">'+this.duration+'</div>').appendTo(day_obj);
		$.each(this.events, function(index, e) {
			e.render(day_obj);
		});

		return day_obj;
	};
}

var xepan_subscriptionevent = function(evt) { // Json Data of a event
	this.event = evt;
	this.render= function(parent){
		$('<div class="label label-success added_event atk-padding-small pull-left" style="margin: 5px">'+this.event.title+'</div>').appendTo(parent).data('event',this.event);
		// console.log('rendering '+ this.event.title);
	};
}

// Subscription Calander Widget
jQuery.widget("ui.xepan_subscriptioncalander",{
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


	_create: function(){
		var self=this;
		this.add_day_inp = $('<input type="number" class="input"/>').appendTo(this.element);//.spinner();
		this.add_day_btn = $('<button class="btn btn-default">Add Day</button>').appendTo(this.element);
		this.schedular = $('<div></div>').appendTo(this.element);
		this.trash = $('<div class="days pull-right"></div>').addClass('ui-corner-all').prependTo(this.schedular).css('width','50px').css('background-color','red').css('height',this.options.height);
		this.schedular = $('<div></div>').appendTo(this.schedular);
		this.schedular.addClass('well well-sm').css('max-height',this.options.height).css('overflow-y','scroll');

		this.add_day_btn.bind('click', undefined, function(event) {
			var inp = parseInt(self.add_day_inp.val());
			if(!inp){
				$(self.add_day_inp).effect('highlight');
				return;
			}

			if(self.days[inp]){
				self.add_day_inp.effect('shake');
				return;	
			}

			self.addDay(inp);
			self.render();

		});

		this.trash.sortable({
			connectWith: ".days",
			receive: function(event,ui){
				var duration = ui.sender.data('duration');
				self.days[ui.sender.data('duration')].removeEvent(new xepan_subscriptionevent(ui.item.data('event')));
				// self.render(this.schedular);
				self.inform('removeEvent',duration,ui.item.data('event')._nid);
				ui.item.remove();
			}
		});

	},

	_init: function(){
		// Create all days recursively with events coming from database here
		var self= this;
		// console.log(this.options.events);
		self.addDay(0);
		$.each(this.options.days, function(index, day) {
			// console.log(day);
			if(self.days[index]==undefined){
			 	self.addDay(index);
			}

			day_index = index;
			$.each(day.events,function(index,evt){	
				 // console.log(day_index);
				self.days[day_index].addEvent(new xepan_subscriptionevent(evt.event));
			});
		});
		// console.log(this.days);
		this.render();
	},

	addDay: function(duration, name){
		// day_html = $('<div id="day'+duration+'">'+duration+'</div>');
		// day_html.addClass('panel panel-success');
		// this.days[duration] = $(day_html).appendTo(this.schedular).xepan_subscriptionday({duration : duration});
		return this.days[duration] = new xepan_subscriptionday(duration);//$(day_html).appendTo(this.schedular).xepan_subscriptionday({duration : duration});
	},

	removeDay: function(duration){
		self  =  this;
		delete  self.days[duration];
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
					self.inform('moveEvent',day.duration,ui.item.data('event')._nid,ui.sender.data('duration'));
				}
			}).droppable({
				drop: function(event, ui){
					
					if(!ui.helper.is('.added_event')){
						if(!day.hasEvent(new xepan_subscriptionevent(ui.helper.data('eventsource')))){
							new_evt=day.addEvent(new xepan_subscriptionevent(ui.helper.data('eventsource')));
							new_evt.render($('.day-'+day.duration));
							// $('.day-'+ day.duration).scrollTop();
							self.inform('addEvent',day.duration,ui.helper.data('eventsource')._nid);
						}
					}
				}
			});
		});
	},

	to_field : function(field){
		var self = this;
		$(field).val(JSON.stringify(this.days));
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