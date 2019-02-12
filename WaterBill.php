<?php
//WaterBill
/**
 * WaterBill.php
 *
 * @author     Matthew Stubenberg
 * @copyright  2019 Maryland Volunteer Lawyers Service
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    2.0
 */
/*
/*
//This class checks the Baltimore City Gov website and returns the amount due, res code, address of the property (to verify), and if they have a homeowners credit.
//http://cityservices.baltimorecity.gov/water/

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

$temp->checkWaterBill(); Return Values:
		100 if it couldn't find the address
		200 if everything went well
		300 if it found an address but it wasn't the one we searched.
		400 if the curl itself failed and hit an httpcode that wasn't 200.
		
$temp->water_bill_array //To get the results
$temp->html; //To get the raw returned HTML if you want to build your own parser for instance.

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

*/
require_once('simple_html_dom.php');
class WaterBill{
	public $water_bill_array;
	public $html;
	public $eventvalidation;
	public $viewstate;
	
	public function __construct(){
		$this->getPostFields(); //Pull the View State and EventValidation on the creation.
		
	}
	public function checkWaterBill($address){
		$this->water_bill_array = array();
		$this->html = "";
		$corrected_address = $this->fixAddressWaterBill($address);
		$html = $this->curlWebsite($corrected_address);
		$this->html = $html;
		if($html !== false){
			return $this->parseResults($html,$corrected_address);
		}else{
			return 400;
		}

	}
	public function parseResults($html,$corrected_address){
		/*
		Returns 100 if it couldn't find the address
		Returns 200 if everything went well
		Returns 300 if it found an address but it wasn't the one we searched.
		*/
		
		$return_array = array();
		$domparser = new \simple_html_dom();
		$domparser->load($html);
		

		if(!isset($domparser->find("span[id=ctl00_ctl00_rootMasterContent_LocalContentPlaceHolder_lblCurrentBalance]")[0])){
			//Means there was an error on the page somewhere or we didn't find the address.
			return 100;
		}else{
			//Means we found a page with a water bill tag.
			//Now we need to check if it's the same address we searched.
			$prop_address = $domparser->find("span[id=ctl00_ctl00_rootMasterContent_LocalContentPlaceHolder_lblServiceAddress]")[0]->plaintext;
			if(strcasecmp(trim($prop_address),$corrected_address) == 0){
				//Means the address are the same so we are good to go in getting the rest of the values.
				//Array of all the ids for the values we plan to pick up.
				$id_array=array(
					'ctl00_ctl00_rootMasterContent_LocalContentPlaceHolder_lblAccountNumber',
					'ctl00_ctl00_rootMasterContent_LocalContentPlaceHolder_lblServiceAddress',
					'ctl00_ctl00_rootMasterContent_LocalContentPlaceHolder_lblLastReadDate',
					'ctl00_ctl00_rootMasterContent_LocalContentPlaceHolder_lblBillDate',
					'ctl00_ctl00_rootMasterContent_LocalContentPlaceHolder_lblPenaltyDate',
					'ctl00_ctl00_rootMasterContent_LocalContentPlaceHolder_lblLastBillAmount',
					'ctl00_ctl00_rootMasterContent_LocalContentPlaceHolder_lblPreviousBalance',
					'ctl00_ctl00_rootMasterContent_LocalContentPlaceHolder_lblCurrentBalance',
					'ctl00_ctl00_rootMasterContent_LocalContentPlaceHolder_lblPreviousReadDate',
					'ctl00_ctl00_rootMasterContent_LocalContentPlaceHolder_lblLastPayDate',
					'ctl00_ctl00_rootMasterContent_LocalContentPlaceHolder_lblLastPayAmount',
					'ctl00_ctl00_rootMasterContent_LocalContentPlaceHolder_lblTurnOffDate');
				
				foreach($id_array as $id){
					$underscore = strripos($id,'_'); //Should Return the last occurance of _ in a string.
					$key = substr($id,$underscore+4);
					$tempspanarray = $domparser->find("span[id=" . $id . "]");
					if(count($tempspanarray) > 0){
						$value = $domparser->find("span[id=" . $id . "]")[0]->plaintext;
					}else{
						$value = null;
					}				
					$return_array[$key] = trim($value);
				}
				
				$this->water_bill_array = $return_array;
				return 200;
			}else{
				//Means the addresses are different. It must have had a similar one that appeared in the first slot on the result page.
				return 300;
			}
		}
	}
	public function curlWebsite($address){
		//Actually pulls the data from the Water Bill Site.
		$url = "http://cityservices.baltimorecity.gov/water/";

		$data = array(
			'ctl00$ctl00$rootMasterContent$LocalContentPlaceHolder$ucServiceAddress$txtServiceAddress'=>$address,
			'ctl00$ctl00$rootMasterContent$LocalContentPlaceHolder$btnGetInfoServiceAddress'=>'Get Info',
			'__EVENTTARGET'=>'ctl00$ctl00$rootMasterContent$LocalContentPlaceHolder$DataGrid1$ctl02$lnkBtnSelect',
			'__VIEWSTATEGENERATOR'=> 'FD4E06E2',
			'__EVENTVALIDATION'=> $this->eventvalidation,
			'__VIEWSTATE'=> $this->viewstate
			);
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$result = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		if($httpcode == 200){
			//Success
			return $result;	
		}else{
			//Something went wrong and we recieved something other than a response code of 200
			return false;
		}
	}
	public function getPostFields(){
		//In order to get the waterbill info you need the viewstate and eventvalidation values generated on the homepage.
		$url = "http://cityservices.baltimorecity.gov/water/";
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$html = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		if($httpcode == 200){
			//Success
			$domparser = new \simple_html_dom();
			$domparser->load($html);
			$this->eventvalidation = $domparser->find("input[id=__EVENTVALIDATION]")[0]->value;
			$this->viewstate = $domparser->find("input[id=__VIEWSTATE]")[0]->value;
			return true;
		}else{
			//Something went wrong and we recieved something other than a response code of 200
			echo "Event Validation Wrong " . $httpcode;
			return false;
		}
	}
	public function fixAddressWaterBill($address){
		
		$address = str_replace(".","",$address); //Gets Rid of any periods
		
		$addressarray = explode(" ",$address);
		for($x=0;$x< sizeof($addressarray); $x++){
			switch ($addressarray[$x]){
				case "Avenue":
					$addressarray[$x] = "Ave";
					break;
				case "Street":
					$addressarray[$x] = "St";
					break;
				case "Road":
					$addressarray[$x] = "Rd";
					break;
				case "Drive":
					$addressarray[$x] = "Dr";
					break;
				case "Circle":
					$addressarray[$x] = "Cr";
					break;
				case "Terrace":
					$addressarray[$x] = "Terr";
					break;
				case "Boulevard":
					$addressarray[$x] = "Bvld";
					break;
				case "Court":
					$addressarray[$x] = "Ct";
					break;
				case "North":
					$addressarray[$x] = "N";
					break;
				case "South":
					$addressarray[$x] = "S";
					break;
				case "West":
					$addressarray[$x] = "W";
					break;
				case "East":
					$addressarray[$x] = "E";
					break;
			}
		}
		$newaddress = implode(" ",$addressarray);
		return $newaddress;
	}
}
