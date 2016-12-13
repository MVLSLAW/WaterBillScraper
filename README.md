# Water Bill Scraper
PHP Water Bill Scraper
Created by Matthew Stubenberg
Copyright Maryland Volunteer Lawyers Service 2016

##Description
This class will let you scrape the Baltimore City Water Bill website with a given address and return an array of water bill data.
http://cityservices.baltimorecity.gov/water/

##Liability Waiver
By using this tool, you release Maryland Volunteer Lawyers Service of any and all liability. Please read the terms of use on the baltimorecity.gov website before using.
http://www.baltimorecity.gov/node/2020

##Usage
<pre>
$temp = new WaterBill('834 Hollins Street');
if($temp->checkWaterBill()==200){
	print_r($temp->water_bill_array);
	
}else{
	echo "Could not locate the address";
}
</pre>
##Return Array:
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
##Other
The class will automatically modify an address to work with the baltimore website so "Street" becomes "St".