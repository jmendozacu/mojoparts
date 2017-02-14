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
// RWH		imap_close($mBox);
		return $body;
	}
	
	public function markMUIDsRead($muids, $user, $password, $host) {
		if ($this->_isDev) return true;
		$mBox = imap_open($host, $user, $password);
		if ($mBox === false) throw new Exception('Unable to connect to mailbox. '.imap_last_error());
		if(!is_array($muids)){
			$muids = array($muids);
		}
// RWH		imap_close($mBox);
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
			$matches = array();
			if(preg_match('/=3D([0-9]*)&action=3Dtrack&language=3D/', $body, $matches)){
				$tNum = $matches[1];
				if (substr($tNum, 0, 7) == '9611019') $tNum = substr($tNum, 7);
				$matches = array();
				if(preg_match('/Customer PO: ([0-9]*)<br>/', $body, $matches)){
					$lines[$matches[1]] = array('',$matches[1],'',$tNum,'Federal Express');
//	RWH: moved lower				$emailsToMark[] = $muid;
				}
			}
			$emailsToMark[] = $muid; // RWH: moved here because I want everything marked as read
		}
		
		$messages2 = $this->grabMailLike('Your tracking number for order ','tracking@mojoparts.com','Y7iO3qY2','{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX');
		foreach($messages2 as $muid => $body){
			$matches = array();
			if(preg_match('/bers=3D([0-9]*)&action=3Dtrack&language=3D/', $body, $matches)){
				$tNum = $matches[1];
				if (substr($tNum, 0, 7) == '9611019') $tNum = substr($tNum, 7);
				$matches = array();
				if(preg_match('/<b>Cross Reference No: <\/b>([0-9]*)<br>/', $body, $matches)){
					$lines[$matches[1]] = array('',$matches[1],'',$tNum,'Federal Express');
// RWH: moved lower					$emailsToMark[] = $muid;
				}
			}
			$emailsToMark[] = $muid; // RWH: moved here because I want everything marked as read
		}
		
		$this->markMUIDsRead($emailsToMark,'tracking@mojoparts.com','Y7iO3qY2','{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX');
		
		return $lines;
	}


	//////////////////////////////////////////////////
	// !Brocksupply
	//////////////////////////////////////////////////

	public function brocksupply_run() {
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
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'username=135253&password=C5xgAbJ3csD2');
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
