# Water Bill Scraper
PHP Water Bill Scraper
Created by Matthew Stubenberg
Copyright Maryland Volunteer Lawyers Service 2019

## Description
This class will let you scrape the Baltimore City Water Bill website with a given address and return an array of water bill data. This site also works with Baltimore County Addresses.
http://cityservices.baltimorecity.gov/water/

## Liability Waiver
By using this tool, you release Maryland Volunteer Lawyers Service of any and all liability. Please read the terms of use on the baltimorecity.gov website before using.
http://www.baltimorecity.gov/node/2020

## Requirements
Tested on php7.2.9 Make sure you have curl and mbstring extensions allowed in your php ini file.

## Usage
<pre>
$temp = new WaterBill();
$returncode = $temp->checkWaterBill('834 Hollins St');
if($returncode == 200){
	print_r($temp->water_bill_array);
	//print_r($temp->html); //Uncomment this if you want to simply display the webpage returned.
	
}else if($returncode == 100){
	echo "Address not found";
}
else{
	echo "Other Error: " . $returncode;
}
</pre>
## Return Array:
Result from the variable $water_bill_array in the class. If a value does not exist for a certain field it will simply be null.
<pre>
Array
(
    [AccountNumber] => 11000212724
    [ServiceAddress] => 834 HOLLINS ST
    [LastReadDate] => 11/12/2016
    [BillDate] => 11/22/2016
    [PenaltyDate] => 12/12/2016
    [LastBillAmount] => -$220.86
    [PreviousBalance] => -$282.82
    [CurrentBalance] => $61.96
    [PreviousReadDate] => 10/12/2016
    [LastPayDate] => 11/18/2016
    [LastPayAmount] => -$282.82
    [TurnOffDate] => 
)
</pre>
## Test
Just run the test.php file which should show you the waterbill for 834 Hollins Street in Baltimore.

## Other
The class will automatically modify an address to work with the baltimore website so "Street" becomes "St".
If you are running a loop of many addresses, make only one WaterBill() object as it will pull a viewstate and eventvalidation on each creation.
<pre>
$temp = new WaterBill();
$addresses = array('834 Hollins Street','123 Fake Street','456 Fake Street');
foreach($addresses as $address){
	$temp->checkWaterBill($address);
	print_r($temp->water_bill_array);
}
</pre>