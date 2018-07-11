<?php

require_once dirname(__FILE__).'/excel_reader2.php';

class shipping_import {

	function __construct($isDev = false) {
		$this->_isDev = $isDev;
	}


	//////////////////////////////////////////////////
	// !CSV Maker
	//////////////////////////////////////////////////

	/**
	 * makeCSV function.
	 *
	 * $data should be a 2d array. Interior array should have 5 cols.
	 *  2nd col is order number
	 *  4th col is tracking number
	 *  5th col is carrier
	 *
	 * @access public
	 * @param mixed $data
	 * @param mixed $prefix (default: null)
	 * @return void
	 */
	public function makeCSV($data, $prefix=null) {
		$data = $this->cleanCells($data);
		$rows = array();
		foreach ($data as $row) {
			$rows[] = implode(',', $row);
		}
		$csv = $this->_csvHeader.PHP_EOL.implode(PHP_EOL, $rows);
		if ($prefix==null) {
			$prefix = time();
		}
		$filename = '/var/www/html/var/import/'.$prefix.'-tracking-'.date('Y-m-d_H-i').'.csv';
		if ($this->_isDev) {
			// $filename = '/home/mojoinda/public_html/var/import/'.$prefix.'-tracking-'.date('Y-m-d_H-i').'.csv';
		}
		$ret = file_put_contents($filename, $csv);
		if ($ret==0) {
			throw new Exception('Saving file failed. ('.$filename.')');
		}
		echo 'File saved to "', $filename, '"', PHP_EOL;
		return true;
	}


	protected $_csvHeader = 'INVOICE #,PO #,INVOICE DATE,TRACKING #,CARRIER';

	public function cleanCells($array) {
		if (is_array($array)) {
			foreach ($array as $key => $value) {
				$array[$key] = $this->cleanCells($value);
			}
			return $array;
		}else {
			return trim(str_replace(array('"', ','), '', $array));
		}
	}


	//////////////////////////////////////////////////
	// !Email Grabber
	//////////////////////////////////////////////////

	public function grabMailLike($subject, $user, $password, $host) {
		echo "Getting " . __METHOD__ . "\r\n";
		$counter  = 0;

		echo "Connecting...\r\n";
		$mBox = imap_open($host, $user, $password);
		if ($mBox === false) throw new Exception('Unable to connect to mailbox. '.imap_last_error());

		echo "Searching...\r\n";
		$messages = imap_search($mBox, 'UNSEEN SUBJECT "'.$subject.'"', SE_UID);
		if($messages == false) return array();
		$body = array();
		foreach ($messages as $muid) {
			echo "Working on [" . $muid . "]...\r\n";
			$body[$muid] = imap_body($mBox, $muid, FT_UID|FT_PEEK);
			$counter++;
		}
		return $body;
	}
	
	public function markMUIDsRead($muids, $user, $password, $host) {
		if ($this->_isDev) return true;
		$mBox = imap_open($host, $user, $password);
		if ($mBox === false) throw new Exception('Unable to connect to mailbox. '.imap_last_error());
		if(!is_array($muids)){
			$muids = array($muids);
		}
		return imap_setflag_full($mBox, implode(',',$muids), '\\Seen', ST_UID);
	}
	
	
	//////////////////////////////////////////////////
	// !PDF Manip
	//////////////////////////////////////////////////

	
	public function extractFromPDF($pdf,$element){
		require_once dirname(dirname(__FILE__)).'/lib/Zend/Loader/Autoloader.php';
		$this->loader = new Zend_Loader_Autoloader();
		
	}

	
	//////////////////////////////////////////////////
	// !USAutoSupply (WOS?)
	//////////////////////////////////////////////////

	public function pfg_run(){
		try{
			$lines = $this->pfg_readEmails();
			if(count($lines)>0){
				$this->makeCSV($lines, 'pfg');
			}
		} catch (Exception $e) {
			echo $e, PHP_EOL;
			if (!$this->_isDev) {
				mail('ryan.hand@mojoparts.com', 'Shipping Import Error [PFG]', $e->getMessage()."\n\n".print_r($e, true));
				error_log('Shipping Import Error [PFG]: ' . $e->getMessage());
			}
		}
	}

	public function pfg_readEmails(){
		$emailsToMark = array();
		$lines = array();
		$messages1 = $this->grabMailLike('wos mojo parts','tracking@mojoparts.com','Y7iO3qY2','{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX');
		foreach($messages1 as $muid => $body){
			$body = preg_replace("/=\s|\r|\n/", "", $body); // remove any line breaks gmail adds, since these can appear anywhere and cause preg_match to fail
			$matches = array();
			$carrier = '';
			$tNum = '';

			if (preg_match('/bers=3D([0-9]*)&action=3Dtrack&language=3D/s', $body, $matches)) {
				$carrier = 'Federal Express';
				$tNum = $matches[1];
					if (substr($tNum, 0, 7) == '9611019') $tNum = substr($tNum, 7);
			} elseif ((preg_match('/PublicTrackingResultsList=2Easpx\?billnumber=3D0000000000([0-9]*)=22 target=3D=22_blank=22>/s', $body, $matches))
				|| (preg_match('/PublicTrackingResultsList=2Easpx\?billnumber=3D([0-9]*)=22 target=3D=22_blank=22>/s', $body, $matches))) {
					$carrier = 'AGS';
					$tNum = $matches[1];
			} elseif (preg_match('/app\/manifestrpts_p_app\/shipmentTracking=2Edo=22 target=3D=22_blank=22>([0-9]*)<\/a>/s', $body, $matches)) {
					$carrier = 'CNWY';
					$tNum = $matches[1];
			} elseif (preg_match('/<br>Tracking Number: <br><a href=3D=22(XPO-[0-9]*)=22 target=3D=22_blank=22>/s', $body, $matches)) {
					$carrier = 'CNWY';
					$tNum = $matches[1];
			} elseif (preg_match('/<br>Tracking Number: <br><a href=3D=22([0-9]*)=22 target=3D=22_blank=22>/s', $body, $matches)) {
					$carrier = 'CNWY';
					$tNum = $matches[1];
			} elseif (preg_match('/<br>Tracking Number: <br><a href=3D=22https:\/\/www=2Expo=2Ecom\/tracking\/XPO-([0-9]*)\/0\/CON_WAY=22 target=3D=22_blank=22>XPO-[0-9]*<\/a><br><br>/s', $body, $matches)) {
					$carrier = 'CNWY';
					$tNum = $matches[1];
			} elseif (preg_match('/<br>Tracking Number: <br><a href=3D=22https:\/\/www=2Expo=2Ecom\/tracking\/([0-9]*)\/0\/CON_WAY=22 target=3D=22_blank=22>[0-9]*<\/a><br><br>/s', $body, $matches)) {
					$carrier = 'CNWY';
					$tNum = $matches[1];
			} else {
				mail('ryan.hand@mojoparts.com', 'Unknown tracking number/carrier [PFG - WOS MOJO PARTS]', $body);
			}

			if ($carrier != '') {
				$matches = array();
				if(preg_match('/Customer PO: ([CJ-]*[0-9]*)<br>/', $body, $matches)){
					$lines[$matches[1]] = array('',$matches[1],'',$tNum,$carrier);
				}
			}
			$emailsToMark[] = $muid;
		}
	
		$messages2 = $this->grabMailLike('Your tracking number for order ','tracking@mojoparts.com','Y7iO3qY2','{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX');
		foreach($messages2 as $muid => $body){
			$body = preg_replace("/=\s|\r|\n/", "", $body); // remove any line breaks gmail adds, since these can appear anywhere and cause preg_match to fail
			$matches = array();
			$carrier = NULL;
			$tNum = NULL;

			if (preg_match('/bers=3D([0-9]*)&action=3Dtrack&language=3D/s', $body, $matches)) {
				$carrier = 'Federal Express';
				$tNum = $matches[1];
				if (substr($tNum, 0, 7) == '9611019') $tNum = substr($tNum, 7);
			} elseif ((preg_match('/PublicTrackingResultsList=2Easpx\?billnumber=3D0000000000([0-9]*)=22 target=3D=22_blank=22>/s', $body, $matches)) 
				|| (preg_match('/PublicTrackingResultsList=2Easpx\?billnumber=3D([0-9]*)=22 target=3D=22_blank=22>/s', $body, $matches))) {
					$carrier = 'AGS';
					$tNum = $matches[1];
			} elseif (preg_match('/app\/manifestrpts_p_app\/shipmentTracking=2Edo=22 target=3D=22_blank=22>([0-9]*)<\/a>/s', $body, $matches)) {
					$carrier = 'CNWY';
					$tNum = $matches[1];
			} elseif (preg_match('/<br>Here are the tracking number\(s\) for your package\(s\)<br><br><b>Freight carrier: <\/b><br><br>.*<a href=3D=22(XPO-[0-9]*)=22 target=3D=22_blank=22>/s', $body, $matches)) {
					$carrier = 'CNWY';
					$tNum = $matches[1];
			} elseif (preg_match('/<br>Here are the tracking number\(s\) for your package\(s\)<br><br><b>Freight carrier: <\/b><br><br>.*XPO: <a href=3D=22([0-9]*)=22 target=3D=22_blank=22>/s', $body, $matches)) {
					$carrier = 'CNWY';
					$tNum = $matches[1];
			} elseif (preg_match('/<br>Here are the tracking number\(s\) for your package\(s\)<br><br><b>Freight carrier: <\/b><br><br>.*XPO: <a href=3D=22https:\/\/www=2Expo=2Ecom\/tracking\/[XPO-]*([0-9]*)\/0\/CON_WAY/s', $body, $matches)) {
					$carrier = 'CNWY';
					$tNum = $matches[1];
			} else {
				mail('ryan.hand@mojoparts.com', 'Unknown tracking number/carrier [PFG - Your Tracking Number]', $body);
			}

			if (!empty($carrier)) {
				$matches = array();
				if(preg_match('/<b>Cross Reference No: <\/b>([CJ-]*[0-9]*)<br>/', $body, $matches)){
					$lines[$matches[1]] = array('',$matches[1],'',$tNum,$carrier);
				}
			}
			$emailsToMark[] = $muid;
		}

		$messages3 = $this->grabMailLike('Brock Supply Invoice','tracking@mojoparts.com','Y7iO3qY2','{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX');
		foreach($messages3 as $muid => $body){
			$body = preg_replace("/=\s|\r|\n/", "", $body); // remove any line breaks gmail adds, since these can appear anywhere and cause preg_match to fail
			$matches = array();
			$carrier = '';
			$tNum = '';
			if (preg_match('/Thank you! Your order has shipped and your invoice is below.*>([0-9A-Z]*)<\/a><\/p>/s', $body, $matches)){
echo "email body: ".$body.PHP_EOL;
				$tNum = $matches[1];
echo "BROCK tracking#: ".$tNum.PHP_EOL;
				if (substr($tNum,0,2) == '1Z'){
					$carrier = 'United Parcel Service';	
				} elseif (substr($tNum,0,3) == 'C11'){
					$carrier = 'OnTrac';	
				} elseif (substr($tNum,0,3) == '940'){
					$carrier = 'United States Postal Service';	
				} elseif (substr($tNum,0,4) == '6129' || substr($tNum,0,4) == '7489'){
					$carrier = 'Federal Express';	
				} elseif (substr($tNum,0,2) == 'BS'){
					$carrier = 'LoneStar Overnight';	
				} else {
					mail('ryan.hand@mojoparts.com', 'Unknown tracking number/carrier [Brock]', $body);
				}
			}
echo "BROCK carrier: ".$carrier.PHP_EOL;

			if ($carrier != '') {
				$matches = array();
				if (preg_match('/>PO#<\/td>.*<td align=3D"left">([0-9]*) <\/td>/s', $body, $matches)) {
					$lines[$matches[1]] = array('',$matches[1],'',$tNum,$carrier);
echo "BROCK PO#: ".print_r($matches).PHP_EOL;
				}
			}
			$emailsToMark[] = $muid;
		}
		
		$this->markMUIDsRead($emailsToMark,'tracking@mojoparts.com','Y7iO3qY2','{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX');
		
		return $lines;
	}


	//////////////////////////////////////////////////
	// !Brocksupply
	//////////////////////////////////////////////////

	public function brocksupply_run() {
/*		THIS IS NOW BEING HANDLED AS PART OF THE PFG TRACKING # EMAIL PROCESSING
		try{
			$file = $this->brocksupply_downloadXls();
			$data = $this->brocksupply_readXls($file);
			if(count($data) > 0){
				$this->makeCSV($data, 'brock');
			}
		} catch (Exception $e) {
			echo $e, PHP_EOL;
			if (!$this->_isDev) {
				mail('ryan.hand@mojoparts.com', 'Shipping Import Error [Brocksupply]', $e->getMessage()."\n\n".print_r($e, true));
				error_log('Shipping Import Error [Brocksupply]: ' . $e->getMessage());
			}
		}
*/

	}


	protected function brocksupply_readXls($file) {
		$data = new Spreadsheet_Excel_Reader($file);
		if ($data->rowcount(0)==0) {
			throw new Exception('No Rows in XLS file. ('.$file.')');
		}
		$csvData = array();
		for ($i=2;$i<=$data->rowcount(0);$i++) {
			$invoiceNumber = $data->value($i, 1, 0);
			$poNumber = $data->value($i, 2, 0);
			$invoiceDate = $data->value($i, 3, 0);
			$carrier = $data->value($i, 4, 0);
			$trackingNumber = $data->value($i, 5, 0);
			foreach (explode('&', $trackingNumber) as $tNum) {
				if ($tNum=='' || trim($tNum)=='Pending') {
					continue;
				}
				$tNum = str_replace(' ', '', trim($tNum));
				$csvData[] = array($invoiceNumber, $poNumber, $invoiceDate, $tNum, $carrier);
			}
		}
		unset($data);
		unlink($file);
		return $csvData;
	}


	protected function brocksupply_downloadXls() {
		echo 'Downloading File...';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://www.brocksupply.com/login/validate');
		if ($this->_isDev) { 
			curl_setopt($ch, CURLOPT_CAINFO, '/home/mojoinda/ca-certificates.crt');
		} else {
			curl_setopt($ch, CURLOPT_CAINFO, '/etc/ssl/mojoparts.com/ca');
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIEFILE, '');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'username=mojoautosupply@gmail.com&password=p2Wq8SKzeq57');
		curl_exec($ch);
		$errno = curl_errno($ch);
		if ($errno!=0) {
			$message = 'File Download Error: ('.$errno.') '.curl_error($ch);
			curl_close($ch);
			throw new Exception($message);
		}
		curl_setopt($ch, CURLOPT_URL, 'https://www.brocksupply.com/reports/tracking2');
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'date='.urlencode(date('m/d/Y', strtotime('today'))));
		$xlsData = curl_exec($ch);
		$errno = curl_errno($ch);
		if ($errno!=0) {
			$message = 'File Download Error: ('.$errno.') '.curl_error($ch);
			curl_close($ch);
			throw new Exception($message);
		}
		curl_close($ch);
		$tmpFile = '/tmp/shipping-brocksupply-tmp-'.microtime(true).'.xls';
		$ret = file_put_contents($tmpFile, $xlsData);
		if ($ret==0) {
			throw new Exception('Saving file failed. ('.$tmpFile.')');
		}
		echo 'Done', PHP_EOL;
		return $tmpFile;
	}


}
