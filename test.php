<?php
include 'WaterBill.php';
//Test program for the water bill class

$temp = new WaterBill('834 Hollins Street');
if($temp->checkWaterBill()==200){
	echo "<pre>";
	print_r($temp->water_bill_array);
	echo "</pre>";
	//print_r($temp->html); //Uncomment this if you want to simply display the webpage returned.
	
}else{
	echo "Could not locate the address";
}
?>