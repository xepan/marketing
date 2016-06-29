jQuery.widget("ui.xepan_mindchart",{
	options:{
		data:[
				{id: 1, name: 'Root', parent: 0, level:1}
			],
		maxLevel:4,
		addbutton_false_at_level:4,
		deletebutton_false_at_level:null,
		Labels:[{"add":'Add Category'},{"add":'Add Subcategory'},{"add":'Add Example'}]
	},

	_create: function(){
		$(this.element).orgChart({
			data: this.options.data,
            showControls: true,
            allowEdit: true,
            maxLevel:this.options.maxLevel,
            addbutton_false_at_level:this.options.addbutton_false_at_level,
            Labels:this.options.Labels
		});	
	}
});