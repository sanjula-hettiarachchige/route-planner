<?php
require_once("");#Path to the wp-config in wordpress
global $wpdb;


#Retrieves the shop names from database
$results = $wpdb->get_results("SELECT ID, user_login FROM wp_users"); 
$shop_names = [];
foreach ($results as $myrows){
	array_push($shop_names,$myrows -> user_login);
}




#If the calculate button is pressed
if(array_key_exists('button', $_POST)) {
	retrieve_postcode();
}

function IsPostcode($postcode)
{
    $postcode = strtoupper(str_replace(' ','',$postcode));
    if(preg_match("/(^[A-Z]{1,2}[0-9R][0-9A-Z]?[\s]?[0-9][ABD-HJLNP-UW-Z]{2}$)/i",$postcode) || preg_match("/(^[A-Z]{1,2}[0-9R][0-9A-Z]$)/i",$postcode))
    {    
        return true;
    }
    else
    {
        return false;
    }
}


#Retrieves the postcodes for the shops listed
function retrieve_postcode() {
	global $wpdb;
	global $shop_name_list;
	$shop_name_list=$_POST['shop-name'];
	$postcode_array = [];
	for($i = 0; $i < count($shop_name_list); $i++){
		$current_shop_name = $shop_name_list[$i];
		$user_id = $wpdb->get_results("SELECT ID FROM wp_users WHERE user_login='$current_shop_name'"); 
		if (IsPostcode($shop_name_list[$i])){
			$shop_postcode = $shop_name_list[$i];
			array_push($postcode_array, $shop_postcode);
		} elseif (count($user_id)==0){
			echo("<p class='line-1'>Please enter a valid shop name or postcode</p>");
		}else{
			$current_shop_name = $shop_name_list[$i];
			$user_id = $wpdb->get_results("SELECT ID FROM wp_users WHERE user_login='$current_shop_name'"); 
			$user_id = $user_id[0]->ID;

			$shop_postcode = $wpdb->get_results("SELECT meta_value FROM wp_usermeta WHERE user_id='$user_id' and meta_key='billing_postcode'"); 
			$shop_postcode = $shop_postcode[0]->meta_value;
			if (is_null($shop_postcode)){
			} else{
				array_push($postcode_array, $shop_postcode);
			}
		}
	}
	calculate_route($postcode_array); #Calculates the route
}

function get_distances($origin_postcode, $destination_postcode){

	$distance_data = file_get_contents('https://maps.googleapis.com/maps/api/distancematrix/json?&origins='.urlencode($origin_postcode).'&destinations='.urlencode($destination_postcode).'&key=AIzaSyCX0ixFsVvDbhQ8WFIdng5_McC1aJUzhlU');
	$distance_arr = json_decode($distance_data);
	if ($distance_arr->status=='OK') {
	    $destination_addresses = $distance_arr->destination_addresses[0];
	    $origin_addresses = $distance_arr->origin_addresses[0];
	} else {
	  echo "<p>The request was Invalid</p>";
	  exit();
	}
   if ($origin_addresses=="" or $destination_addresses=="") {
      echo "<p>Destination or origin address not found</p>";
      exit();
   }
   // Get the elements as array
   $elements = $distance_arr->rows[0]->elements;
   $distance = $elements[0]->distance->text;
   $duration = $elements[0]->duration->text;
   // echo "From: ".$origin_addresses."<br/> To: ".$destination_addresses."<br/> Distance: <strong>".$distance ."</strong><br/>";
   // echo "Duration: <strong>".$duration."";
   if (strpos($duration, 'hour')){
   	preg_match('!(\d+)\s*hours\s*(\d+)!i', $duration, $matches);
   	$total_time = 60*(int)$matches[1] + (int)$matches[2];
   }
   else{
   	preg_match('!(\d+)\s*mins!', $duration, $matches);
   	$total_time = (int)$matches[0];
   }
    
	return($total_time);
  }
	

function calculate_route($postcode_array){
	#$postcode_array = array("ha28aq", "ha28jl", "ha20ad");
	global $shop_name_list;
	$temp_array = array(); #Holds the time matrix as a 2D array
	for ($a = 0; $a < count($postcode_array); $a++) {
		for ($b = $a+1; $b<count($postcode_array); $b++){
			$time = get_distances($postcode_array[$a], $postcode_array[$b]); #Gets the times for each origin and stop
			$temp_array[$a][$b]=$time;
			$temp_array[$b][$a]=$time;
		};
	}
	for ($a=0; $a < count($postcode_array); $a++){
		$temp_array[$a][$a]=INF; #Any diagonal points recorded as infinity
	}

	$route_array = array(); #Array holds the postcodes of the destinations in the order
	$route_shop_array = array();
	array_push($route_array, $postcode_array[0]);
	array_push($route_shop_array,$shop_name_list[0]); #Adds the first stop
	$current_stop = 0; #Defines current stop as the first one in the box
	#Performs the nearest neighbour calculation
	while (count($route_array)<count($temp_array)){
		$current_stop_nodes = array(); #Stores the times to all other nodes from current node
		foreach ($temp_array as $row){
			array_push($current_stop_nodes,$row[$current_stop]);
		}
		$min_time = min($current_stop_nodes); #Gets minimum time
		$min_time_index = array_search($min_time,$current_stop_nodes); #Retrieves corresponding index
		array_push($route_array,$postcode_array[$min_time_index]); #Adds corresponding postcode to array
		array_push($route_shop_array,$shop_name_list[$min_time_index]);

		for ($a=0; $a < count($temp_array); $a++){
			$temp_array[$current_stop][$a]=INF; #Makes all times from that node infinity
		}


		$current_stop = $min_time_index; #Updates the current index pointer
	}
	array_push($route_array,$postcode_array[0]); #Adds the first stop as the last so comes back to origin
	array_push($route_shop_array,$shop_name_list[0]); 
	create_map_link($route_array, $route_shop_array); #Generates the google map for the route
	
}

function create_map_link($route_array, $route_shop_array){
	$base_link = 'https://www.google.co.uk/maps/dir/';
	foreach ($route_array as $stop){
		$base_link = $base_link.urlencode($stop).'/';
	}

	$count = 1;
	echo("</br>");
	echo("<p class='line-1'>Visit the shops in the following order:</p>");
	foreach ($route_shop_array as $shop){
		echo("<p class='line-1'>".(string)$count.".".$shop."   ".$route_array[$count-1]."</p>");
		$count = $count+1;
	}
	echo("</br>");
	?>

	<script type="text/javascript">
		var map_link = '<?php echo $base_link;?>';
   	window.open(map_link, "_blank").focus();
   </script>

   <?php


}

?>