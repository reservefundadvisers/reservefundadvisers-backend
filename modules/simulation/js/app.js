
function init(e){
	if(!e)return;
	
	var root = $(e);
	

	var ui = init_ui();
	




	var chart_element = root.find('#chart');
	var char_title = "NPV";
	var chart_initial_data = [	{ name: 'Loan', type: 'bar', barMaxWidth:15, stack: 'Ad1',
                                            itemStyle: {color: '#ee00000'}, emphasis: { focus: 'series' }, data: ["65.7","55.7","-25.7","15.7","15.7","-26.3","5.7","-15.7"] },
								{ name: 'Collection', type: 'bar', barMaxWidth:15, stack: 'Ad2',
                                            itemStyle: {color: '#eeff00'}, emphasis: { focus: 'series' }, data: ["65.7","55.7","-25.7","15.7","-26.3","5.7","-26.3","5.7"] },
								{ name: 'Investmnt', type: 'bar', barMaxWidth:15, stack: 'Ad3',
                                            itemStyle: {color: '#00ff00'}, emphasis: { focus: 'series' }, data: ["65.7","55.7","-25.7","15.7", "26.69", "59.36", "75.58"] }];
	
	
	var chart = echarts.init(chart_element[0]);
	var option = {
            title: { text: char_title }, tooltip: { trigger: 'item', axisPointer: { type:  'cross', label: { backgroundColor: '#267277' } } }, legend: {top: 50},
            grid: { top: '25%', left: '3%', right: '4%', bottom: '3%', containLabel: true }, xAxis: [ { type: 'category'} ],
            yAxis: [ { type: 'value' } ], series: chart_initial_data
            
        };

	option && chart.setOption(option);
		
	root.find('.btn').click(function(){
								$("#cash_flow_modal").modal('show');
								cash_flow("#cash_flow");
							});
	

	$(window).on('resize', function(){
		if(chart != null && chart != undefined){
			chart.resize();
		}
	});
}


function cash_flow(e){
	if(!e)return;
	
	var root = $(e);
	
	var initial_data = [-250,-90,20,30,50,60,60,30,186.43];
	
	
	var chart_element = root.find('#cash_flow_chart');
	var char_title = "Cash Flow";
	var chart_initial_data = [{ name: 'Cash Flow', type: 'bar', barMaxWidth:15, stack: 'Ad',
                                            itemStyle: {color: '#4CAF50'}, emphasis: { focus: 'series' }, data: initial_data }];
	
	
	var chart = echarts.init(chart_element[0]);
	var option = {
            title: { text: char_title }, tooltip: { trigger: 'item', axisPointer: { type:  'cross', label: { backgroundColor: '#267277' } } }, legend: {top: 50},
            grid: { top: '25%', left: '3%', right: '4%', bottom: '3%', containLabel: true }, xAxis: [ { type: 'category', data: [1,2,3,4,5,6,7,8,9] } ],
            yAxis: [ { type: 'value' } ], series: chart_initial_data
            
        };

	option && chart.setOption(option);
	
	
	var table = root.find('table > tbody');
	
	if(table.length > 0){
		table.html('');
		var i = 1;
		for(const d of initial_data){
			table.append('<tr><td>'+i+'</td><td class="text-right input position-relative"><span>'+d+'</span><input type="number" class="w-100 position-absolute d-none" data-id="'+i+'" value="'+d+'" style="top:0; right:0; left:0;"/></td></tr>');
			i++;
		}
		
		table.find('td.input').click(function(){
			var cell = $(this), input = cell.find('input'), span = cell.find('span') ;
			if(input.hasClass('d-none')){
				table.find('input').addClass('d-none');
				input.removeClass('d-none').focus();
				input.keyup(function(e){
					if(e.keyCode == 13){
						var id = parseInt(input.data('id')) - 1;
						span.html(input.val());
						initial_data[id] = parseFloat(input.val());
						input.addClass('d-none');
						
						chart_initial_data[0].series = initial_data;
						chart.setOption({series: chart_initial_data});
					}
				});
			}
		});
	}
		

	$(window).on('resize', function(){
		if(chart != null && chart != undefined){
			chart.resize();
		}
	});
	
	setTimeout(chart.resize, 200);
	

	
}