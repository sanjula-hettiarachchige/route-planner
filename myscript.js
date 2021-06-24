

function add_shop(){
	var html = document.getElementById('user-input-form').innerHTML;
	var clone = document.createElement('div2');
	var current_form = document.getElementById('user-input-form-main');
	clone.innerHTML = html;
	current_form.appendChild(clone);
}



function show_route(){
	var route_plan = document.getElementById('list-shop-order');
	var count=1;
	for (const shop in shop_array){
		var string = count.toString()+shop;
		console.log(shop_array[shop]);
		route_plan.innerHTML += "<br>"+ shop_array[shop] +"</br>";
	}
}