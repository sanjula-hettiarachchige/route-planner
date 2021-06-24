<html>
<body style="background-color: powderblue">

<h1 class="welcome">Ktm Route Planner</h1>
<head>
	<link rel="stylesheet" href="style.css">
</head>
<?php include ("customer_dropdown.php");?>
<p class="line-1">Please select the shops to visit:</p>
<div class="main-div">	
	<form name="form" method="post" id="shop-list-form">
		<div id="user-input-form-main">
			<div id="user-input-form">
				<label class="input-box-label">
				<input class="shop-name" name="shop-name[]" autoComplete="on" list="suggestions"  placeholder="Select shop, or enter postcode"/> 
				   <datalist id="suggestions">
				    	<?php 
				    	  
				    	  foreach ($shop_names as $myrows) {
						     echo $myrows.'<option/>';
						   }
						?>
				    </datalist>
				</label>
			</div>
		</div>
		<div id="button_field">
			<input class="button1" type="button" value="Add shop" onclick="add_shop();"> 
		   <input type="submit" name="button"
		          class="button1" value="Calculate">
	   </div>
   </form>
</div>
<script type="text/javascript" src="myscript.js"></script>
</body>
</html>