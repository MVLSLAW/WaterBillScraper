<?php
//WaterBill
/**
 * WaterBill.php
 *
 * @author     Matthew Stubenberg
 * @copyright  2016 Maryland Volunteer Lawyers Service
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    1.0
 */
/*
/*
//This class checks the Baltimore City Gov website and returns the amount due, res code, address of the property (to verify), and if they have a homeowners credit.
//http://cityservices.baltimorecity.gov/water/

$temp = new WaterBill('834 Hollins Street');
if($temp->checkWaterBill()==200){
	echo "<pre>";
	print_r($temp->water_bill_array);
	echo "</pre>";
	
}else{
	echo "Could not locate the address";
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
	public $original_address;
	public $corrected_address;
	public $water_bill_array;
	public $html;
	
	public function __construct($address){
		$this->original_address = $address;
		$this->corrected_address = $this->fixAddressWaterBill($this->original_address);
	}
	public function checkWaterBill(){
		$html = $this->curlWebsite($this->corrected_address);
		$this->html = $html;
		if($html !== false){
			return $this->parseResults($html);
		}else{
			return 400;
		}

	}
	public function parseResults($html){
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
			if(strcasecmp(trim($prop_address),$this->corrected_address) == 0){
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
		//Actually pulls the data from Legal Server.
		$url = "http://cityservices.baltimorecity.gov/water/";
	
		
		$data = array(
			'ctl00$ctl00$rootMasterContent$LocalContentPlaceHolder$ucServiceAddress$txtServiceAddress'=>$address,
			'ctl00$ctl00$rootMasterContent$LocalContentPlaceHolder$btnGetInfoServiceAddress'=>'Get Info',
			'__EVENTTARGET'=>'ctl00$ctl00$rootMasterContent$LocalContentPlaceHolder$DataGrid1$ctl02$lnkBtnSelect',
			'__VIEWSTATEGENERATOR'=> 'FD4E06E2',
			'__EVENTVALIDATION'=> '/wEWBwKXx4iJCQKjn9KcCgLohI2gBgKDrZ69BwLxl+XCDwLU8OKZBALFwa/IDYRotc4azC03n7ja058MGrrDPZdueoFT/H5ngAreqGYp',
			'__VIEWSTATE'=>'/wEPDwUKMTQ0MDg4NjU4NA9kFgJmD2QWAmYPZBYEZg9kFgQCAg8WAh4EVGV4dAVOPGxpbmsgaHJlZj0iL3dhdGVyL1N0eWxlcy5jc3MiIHR5cGU9InRleHQvY3NzIiByZWw9InN0eWxlc2hlZXQiIG1lZGlhPSJhbGwiIC8+ZAIFDxYCHgdWaXNpYmxlZxYCZg8WAh8ABU48bGluayBocmVmPSIvd2F0ZXIvU3R5bGVzLmNzcyIgdHlwZT0idGV4dC9jc3MiIHJlbD0ic3R5bGVzaGVldCIgbWVkaWE9ImFsbCIgLz5kAgEPZBYKAgEPDxYCHghJbWFnZVVybAVWaHR0cDovL2NpdHlzZXJ2aWNlcy5iYWx0aW1vcmVjaXR5Lmdvdi9yZW1vdGVtYXN0ZXJ2My9pbWFnZXMvaW50ZXJuZXQvaWNvbnMvbG9hZGluZy5naWZkZAIEDxYCHwFnZAIGDxYCHwFnFgICAQ8WAh8ABRdTZWFyY2ggZm9yIGEgV2F0ZXIgQmlsbGQCBw9kFgYCAQ9kFgICAQ9kFgRmDw8WBh8ABRJTZWFyY2ggVW5hdmFpbGFibGUeB1Rvb2xUaXAFOFNlYXJjaCBpcyBjdXJyZW50bHkgdW5hdmFpbGFibGUsIHBsZWFzZSB0cnkgYWdhaW4gbGF0ZXIuHghSZWFkT25seWcWBB4Hb25mb2N1cwUxaWYodGhpcy52YWx1ZT09J0tleXdvcmQgb3IgU2VhcmNoJyl0aGlzLnZhbHVlPScnOx4Gb25ibHVyBTFpZih0aGlzLnZhbHVlPT0nJyl0aGlzLnZhbHVlPSdLZXl3b3JkIG9yIFNlYXJjaCc7ZAIBDw8WAh4HRW5hYmxlZGgWAh4Hb25jbGljawVoaWYoZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoJ2N0bDAwX2N0bDAwX3R4dEdvb2dsZUN1c3RvbVNlYXJjaCcpLnZhbHVlPT0nS2V5d29yZCBvciBTZWFyY2gnKXJldHVybiBmYWxzZTtkAgIPZBYEAgEPFgIfAAUOU0VSVklDRVMgSU5ERVhkAgMPFCsAAhQrAAIPFgYeC18hRGF0YUJvdW5kZx4XRW5hYmxlQWpheFNraW5SZW5kZXJpbmdoHgxEYXRhU291cmNlSUQFElNpdGVNYXBEYXRhU291cmNlMWQPFCsAFRQrAAIPFggfAAUWQ2l0eSBDaGFydGVyIGFuZCBDb2Rlcx4LTmF2aWdhdGVVcmwFNmh0dHA6Ly9jaXR5c2VydmljZXMuYmFsdGltb3JlY2l0eS5nb3YvY2hhcnRlcmFuZGNvZGVzLx4FVmFsdWUFFkNpdHkgQ2hhcnRlciBhbmQgQ29kZXMfA2VkZBQrAAIPFggfAAUYQ291bmNpbCBEaXN0cmljdCBMb2NhdG9yHwwFMmh0dHA6Ly9jaXR5c2VydmljZXMuYmFsdGltb3JlY2l0eS5nb3YvQ2l0eUNvdW5jaWwvHw0FGENvdW5jaWwgRGlzdHJpY3QgTG9jYXRvch8DZWRkFCsAAg8WCB8ABRlDb21iaW5lZCBDaGFyaXR5IENhbXBhaWduHwwFKWh0dHA6Ly9jaXR5c2VydmljZXMuYmFsdGltb3JlY2l0eS5nb3YvM0MvHw0FGUNvbWJpbmVkIENoYXJpdHkgQ2FtcGFpZ24fA2VkZBQrAAIPFggfAAURSG90ZWwgVGF4IFJldHVybnMfDAUxaHR0cDovL2NpdHlzZXJ2aWNlcy5iYWx0aW1vcmVjaXR5Lmdvdi9NVFJTT25saW5lLx8NBRFIb3RlbCBUYXggUmV0dXJucx8DZWRkFCsAAg8WCB8ABRZQYXJraW5nIEdhcmFnZS9Mb3QgVGF4HwwFNWh0dHA6Ly9jaXR5c2VydmljZXMuYmFsdGltb3JlY2l0eS5nb3YvTVRSU09ubGluZT9wcmM9Hw0FFlBhcmtpbmcgR2FyYWdlL0xvdCBUYXgfA2VkZBQrAAIPFggfAAUYTGllbiBDZXJ0aWZpY2F0ZSBSZXF1ZXN0HwwFK2h0dHA6Ly9jaXR5c2VydmljZXMuYmFsdGltb3JlY2l0eS5nb3YvbGllbi8fDQUYTGllbiBDZXJ0aWZpY2F0ZSBSZXF1ZXN0HwNlZGQUKwACDxYIHwAFGkxvYW4gQXV0aG9yaXphdGlvbiBSZXF1ZXN0HwwFKmh0dHA6Ly9jaXR5c2VydmljZXMuYmFsdGltb3JlY2l0eS5nb3YvbGFyLx8NBRpMb2FuIEF1dGhvcml6YXRpb24gUmVxdWVzdB8DZWRkFCsAAg8WCB8ABRFNQkUvV0JFIERpcmVjdG9yeR8MBSxodHRwOi8vY2l0eXNlcnZpY2VzLmJhbHRpbW9yZWNpdHkuZ292L213Ym9vLx8NBRFNQkUvV0JFIERpcmVjdG9yeR8DZWRkFCsAAg8WCB8ABRVNaXNjZWxsYW5lb3VzIEJpbGxpbmcfDAUvaHR0cDovL2NpdHlzZXJ2aWNlcy5iYWx0aW1vcmVjaXR5Lmdvdi9taXNjYmlsbC8fDQUVTWlzY2VsbGFuZW91cyBCaWxsaW5nHwNlZGQUKwACDxYIHwAFGVBhcmtpbmcgRmluZXMgSW5mb3JtYXRpb24fDAUzaHR0cDovL2NpdHlzZXJ2aWNlcy5iYWx0aW1vcmVjaXR5Lmdvdi9wYXJraW5nZmluZXMvHw0FGVBhcmtpbmcgRmluZXMgSW5mb3JtYXRpb24fA2VkZBQrAAIPFggfAAUbUGFya2luZyBGaW5lcyBSZXF1ZXN0IFRyaWFsHwwFK2h0dHA6Ly9jaXR5c2VydmljZXMuYmFsdGltb3JlY2l0eS5nb3YvcGZ0ci8fDQUbUGFya2luZyBGaW5lcyBSZXF1ZXN0IFRyaWFsHwNlZGQUKwACDxYIHwAFIFBsYW5uaW5nIFByZS1kZXZlbG9wbWVudCBSZXF1ZXN0HwwFKmh0dHA6Ly9jaXR5c2VydmljZXMuYmFsdGltb3JlY2l0eS5nb3YvcHB0Lx8NBSBQbGFubmluZyBQcmUtZGV2ZWxvcG1lbnQgUmVxdWVzdB8DZWRkFCsAAg8WCB8ABRVQdWJsaWMgV29ya3MgUHJvamVjdHMfDAVBaHR0cDovL2NpdHlzZXJ2aWNlcy5iYWx0aW1vcmVjaXR5Lmdvdi9kcHcvZGNwL3B1YmxpYy9wcm9qZWN0cy5waHAfDQUVUHVibGljIFdvcmtzIFByb2plY3RzHwNlZGQUKwACDxYIHwAFGFB1cmNoYXNlcyBEZWxpdmVyeSBQb2ludB8MBSpodHRwOi8vY2l0eXNlcnZpY2VzLmJhbHRpbW9yZWNpdHkuZ292L3BkcC8fDQUYUHVyY2hhc2VzIERlbGl2ZXJ5IFBvaW50HwNlZGQUKwACDxYIHwAFHVJlYWwgUHJvcGVydHkgVGF4IEluZm9ybWF0aW9uHwwFM2h0dHA6Ly9jaXR5c2VydmljZXMuYmFsdGltb3JlY2l0eS5nb3YvcmVhbHByb3BlcnR5Lx8NBR1SZWFsIFByb3BlcnR5IFRheCBJbmZvcm1hdGlvbh8DZWRkFCsAAg8WCB8ABRFXYXRlciBCaWxsIExvb2t1cB8MBSxodHRwOi8vY2l0eXNlcnZpY2VzLmJhbHRpbW9yZWNpdHkuZ292L3dhdGVyLx8NBRFXYXRlciBCaWxsIExvb2t1cB8DZWRkFCsAAg8WCB8ABRw8aDI+QURESVRJT05BTCBTRVJWSUNFUzwvaDI+HwwFCC93YXRlci8jHw0FHDxoMj5BRERJVElPTkFMIFNFUlZJQ0VTPC9oMj4fA2VkZBQrAAIPFggfAAUDMzExHwwFR2h0dHBzOi8vYmFsdGltb3JlLmN1c3RvbWVyc2VydmljZXJlcXVlc3Qub3JnL3dlYl9pbnRha2VfYmFsdC9Db250cm9sbGVyHw0FAzMxMR8DZWRkFCsAAg8WCB8ABQ5PdGhlciBTZXJ2aWNlcx8MBSJodHRwOi8vYmFsdGltb3JlY2l0eS5nb3Yvc2VydmljZXMvHw0FDk90aGVyIFNlcnZpY2VzHwNlZGQUKwACDxYIHwAFCENpdGlTdGF0HwwFJ2h0dHA6Ly9iYWx0aW1vcmVjaXR5Lmdvdi9uZXdzL2NpdGlzdGF0Lx8NBQhDaXRpU3RhdB8DZWRkFCsAAg8WCB8ABQ9JbnRlcmFjdGl2ZSBNYXAfDAUjaHR0cDovL21hcHMuYmFsdGltb3JlY2l0eS5nb3YvaW1hcC8fDQUPSW50ZXJhY3RpdmUgTWFwHwNlZGQPFCsBFWZmZmZmZmZmZmZmZmZmZmZmZmZmZhYBBXNUZWxlcmlrLldlYi5VSS5SYWRNZW51SXRlbSwgVGVsZXJpay5XZWIuVUksIFZlcnNpb249MjAwOC4yLjgyNi4yMCwgQ3VsdHVyZT1uZXV0cmFsLCBQdWJsaWNLZXlUb2tlbj0xMjFmYWU3ODE2NWJhM2Q0ZBYqZg8PFggfAAUWQ2l0eSBDaGFydGVyIGFuZCBDb2Rlcx8MBTZodHRwOi8vY2l0eXNlcnZpY2VzLmJhbHRpbW9yZWNpdHkuZ292L2NoYXJ0ZXJhbmRjb2Rlcy8fDQUWQ2l0eSBDaGFydGVyIGFuZCBDb2Rlcx8DZWRkAgEPDxYIHwAFGENvdW5jaWwgRGlzdHJpY3QgTG9jYXRvch8MBTJodHRwOi8vY2l0eXNlcnZpY2VzLmJhbHRpbW9yZWNpdHkuZ292L0NpdHlDb3VuY2lsLx8NBRhDb3VuY2lsIERpc3RyaWN0IExvY2F0b3IfA2VkZAICDw8WCB8ABRlDb21iaW5lZCBDaGFyaXR5IENhbXBhaWduHwwFKWh0dHA6Ly9jaXR5c2VydmljZXMuYmFsdGltb3JlY2l0eS5nb3YvM0MvHw0FGUNvbWJpbmVkIENoYXJpdHkgQ2FtcGFpZ24fA2VkZAIDDw8WCB8ABRFIb3RlbCBUYXggUmV0dXJucx8MBTFodHRwOi8vY2l0eXNlcnZpY2VzLmJhbHRpbW9yZWNpdHkuZ292L01UUlNPbmxpbmUvHw0FEUhvdGVsIFRheCBSZXR1cm5zHwNlZGQCBA8PFggfAAUWUGFya2luZyBHYXJhZ2UvTG90IFRheB8MBTVodHRwOi8vY2l0eXNlcnZpY2VzLmJhbHRpbW9yZWNpdHkuZ292L01UUlNPbmxpbmU/cHJjPR8NBRZQYXJraW5nIEdhcmFnZS9Mb3QgVGF4HwNlZGQCBQ8PFggfAAUYTGllbiBDZXJ0aWZpY2F0ZSBSZXF1ZXN0HwwFK2h0dHA6Ly9jaXR5c2VydmljZXMuYmFsdGltb3JlY2l0eS5nb3YvbGllbi8fDQUYTGllbiBDZXJ0aWZpY2F0ZSBSZXF1ZXN0HwNlZGQCBg8PFggfAAUaTG9hbiBBdXRob3JpemF0aW9uIFJlcXVlc3QfDAUqaHR0cDovL2NpdHlzZXJ2aWNlcy5iYWx0aW1vcmVjaXR5Lmdvdi9sYXIvHw0FGkxvYW4gQXV0aG9yaXphdGlvbiBSZXF1ZXN0HwNlZGQCBw8PFggfAAURTUJFL1dCRSBEaXJlY3RvcnkfDAUsaHR0cDovL2NpdHlzZXJ2aWNlcy5iYWx0aW1vcmVjaXR5Lmdvdi9td2Jvby8fDQURTUJFL1dCRSBEaXJlY3RvcnkfA2VkZAIIDw8WCB8ABRVNaXNjZWxsYW5lb3VzIEJpbGxpbmcfDAUvaHR0cDovL2NpdHlzZXJ2aWNlcy5iYWx0aW1vcmVjaXR5Lmdvdi9taXNjYmlsbC8fDQUVTWlzY2VsbGFuZW91cyBCaWxsaW5nHwNlZGQCCQ8PFggfAAUZUGFya2luZyBGaW5lcyBJbmZvcm1hdGlvbh8MBTNodHRwOi8vY2l0eXNlcnZpY2VzLmJhbHRpbW9yZWNpdHkuZ292L3BhcmtpbmdmaW5lcy8fDQUZUGFya2luZyBGaW5lcyBJbmZvcm1hdGlvbh8DZWRkAgoPDxYIHwAFG1BhcmtpbmcgRmluZXMgUmVxdWVzdCBUcmlhbB8MBStodHRwOi8vY2l0eXNlcnZpY2VzLmJhbHRpbW9yZWNpdHkuZ292L3BmdHIvHw0FG1BhcmtpbmcgRmluZXMgUmVxdWVzdCBUcmlhbB8DZWRkAgsPDxYIHwAFIFBsYW5uaW5nIFByZS1kZXZlbG9wbWVudCBSZXF1ZXN0HwwFKmh0dHA6Ly9jaXR5c2VydmljZXMuYmFsdGltb3JlY2l0eS5nb3YvcHB0Lx8NBSBQbGFubmluZyBQcmUtZGV2ZWxvcG1lbnQgUmVxdWVzdB8DZWRkAgwPDxYIHwAFFVB1YmxpYyBXb3JrcyBQcm9qZWN0cx8MBUFodHRwOi8vY2l0eXNlcnZpY2VzLmJhbHRpbW9yZWNpdHkuZ292L2Rwdy9kY3AvcHVibGljL3Byb2plY3RzLnBocB8NBRVQdWJsaWMgV29ya3MgUHJvamVjdHMfA2VkZAINDw8WCB8ABRhQdXJjaGFzZXMgRGVsaXZlcnkgUG9pbnQfDAUqaHR0cDovL2NpdHlzZXJ2aWNlcy5iYWx0aW1vcmVjaXR5Lmdvdi9wZHAvHw0FGFB1cmNoYXNlcyBEZWxpdmVyeSBQb2ludB8DZWRkAg4PDxYIHwAFHVJlYWwgUHJvcGVydHkgVGF4IEluZm9ybWF0aW9uHwwFM2h0dHA6Ly9jaXR5c2VydmljZXMuYmFsdGltb3JlY2l0eS5nb3YvcmVhbHByb3BlcnR5Lx8NBR1SZWFsIFByb3BlcnR5IFRheCBJbmZvcm1hdGlvbh8DZWRkAg8PDxYIHwAFEVdhdGVyIEJpbGwgTG9va3VwHwwFLGh0dHA6Ly9jaXR5c2VydmljZXMuYmFsdGltb3JlY2l0eS5nb3Yvd2F0ZXIvHw0FEVdhdGVyIEJpbGwgTG9va3VwHwNlZGQCEA8PFggfAAUcPGgyPkFERElUSU9OQUwgU0VSVklDRVM8L2gyPh8MBQgvd2F0ZXIvIx8NBRw8aDI+QURESVRJT05BTCBTRVJWSUNFUzwvaDI+HwNlZGQCEQ8PFggfAAUDMzExHwwFR2h0dHBzOi8vYmFsdGltb3JlLmN1c3RvbWVyc2VydmljZXJlcXVlc3Qub3JnL3dlYl9pbnRha2VfYmFsdC9Db250cm9sbGVyHw0FAzMxMR8DZWRkAhIPDxYIHwAFDk90aGVyIFNlcnZpY2VzHwwFImh0dHA6Ly9iYWx0aW1vcmVjaXR5Lmdvdi9zZXJ2aWNlcy8fDQUOT3RoZXIgU2VydmljZXMfA2VkZAITDw8WCB8ABQhDaXRpU3RhdB8MBSdodHRwOi8vYmFsdGltb3JlY2l0eS5nb3YvbmV3cy9jaXRpc3RhdC8fDQUIQ2l0aVN0YXQfA2VkZAIUDw8WCB8ABQ9JbnRlcmFjdGl2ZSBNYXAfDAUjaHR0cDovL21hcHMuYmFsdGltb3JlY2l0eS5nb3YvaW1hcC8fDQUPSW50ZXJhY3RpdmUgTWFwHwNlZGQCBg8WAh8ABfkDPGRpdiBpZD0ibWF5b3JDb2x1bW4iPjxpbWcgdGl0bGU9IkNhdGhlcmluZSBFLiBQdWdoLCBNYXlvciIgYWx0PSJDYXRoZXJpbmUgRS4gUHVnaCwgTWF5b3IiIHNyYz0iaHR0cDovL2NpdHlzZXJ2aWNlcy5iYWx0aW1vcmVjaXR5Lmdvdi9SZW1vdGVNYXN0ZXJWMy9pbWFnZXMvSW50ZXJuZXQvcHVnaF9NYXlvci5qcGciIC8+ICAgICAgICA8YnIgLz48c3Ryb25nPkNhdGhlcmluZSBFLiBQdWdoLCBNYXlvcjwvc3Ryb25nPjxiciAvPiAgICAgICAgQ2l0eSBIYWxsLCBSb29tIDI1MDxiciAvPiAgICAgICAgMTAwIE4uIEhvbGxpZGF5IFN0cmVldDxiciAvPiAgICAgICAgQmFsdGltb3JlLCBNYXJ5bGFuZCAyMTIwMjxiciAvPiAgICAgICAgUGhvbmUgKDQxMCkgMzk2LTM4MzU8YnIgLz4gICAgICAgIEZheCAoNDEwKSA1NzYtOTQyNSAgICA8cD4gICAgICAgIDxhIGhyZWY9Im1haWx0bzptYXlvckBiYWx0aW1vcmVjaXR5LmdvdiI+RW1haWwgVGhlIE1heW9yPC9hPiAgICA8L3A+ICAgIDwvZGl2PmQCCQ9kFgICAQ9kFgoCAQ9kFgICAQ8PFgIfAAUyTm8gcmVjb3JkcyBtYXRjaGVkIHlvdXIgaW5xdWlyeS4gIFBsZWFzZSB0cnkgYWdhaW5kZAIEDw8WAh8BaGRkAgUPDxYCHwFoZBYEAgEPEGRkFgFmZAIEDxBkZBYBAhNkAgcPZBYCAgMPPCsADQIAFCsAAmQXAAEWAhYKDwIFFCsABTwrAAUBBAUIQWNjb3VudE48KwAFAQQFDUFjY291bnROdW1iZXI8KwAFAQQFDEN1c3RvbWVyTmFtZWRkZGUUKwAACyl5VGVsZXJpay5XZWIuVUkuR3JpZENoaWxkTG9hZE1vZGUsIFRlbGVyaWsuV2ViLlVJLCBWZXJzaW9uPTIwMDguMi44MjYuMjAsIEN1bHR1cmU9bmV1dHJhbCwgUHVibGljS2V5VG9rZW49MTIxZmFlNzgxNjViYTNkNAE8KwAHAAspdFRlbGVyaWsuV2ViLlVJLkdyaWRFZGl0TW9kZSwgVGVsZXJpay5XZWIuVUksIFZlcnNpb249MjAwOC4yLjgyNi4yMCwgQ3VsdHVyZT1uZXV0cmFsLCBQdWJsaWNLZXlUb2tlbj0xMjFmYWU3ODE2NWJhM2Q0ARYCHgRfZWZzZGQWBB4KRGF0YU1lbWJlcmUeBF9obG0LKwQBZmQCCg8PFgIfAWhkZBgBBR5fX0NvbnRyb2xzUmVxdWlyZVBvc3RCYWNrS2V5X18WAgUeY3RsMDAkY3RsMDAkaW1nQnRuR29vZ2xlU2VhcmNoBRRjdGwwMCRjdGwwMCRSYWRNZW51MSewhK7WOhF4gcN40qFSV774qQ0dVn6Hvu+z9W2nAseb'
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
