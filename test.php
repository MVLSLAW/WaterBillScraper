<?php
include 'WaterBill.php';
//Test program for the water bill class

$temp = new WaterBill();
$returncode = $temp->checkWaterBill('834 Hollins St');
if($temp->checkWaterBill('834 Hollins St') == 200){
	echo "<pre>";
	print_r($temp->water_bill_array);
	echo "</pre>";
	//print_r($temp->html); //Uncomment this if you want to simply display the webpage returned.
}else{
	echo "Something went wrong";
}
?>