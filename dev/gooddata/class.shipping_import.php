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
		$filename = '/var/www/mojoparts.com/htdocs/var/import/'.$prefix.'-gooddata-'.date('Y-m-d_H-i').'.csv';
		if ($this->_isDev) {
			$filename = '/home/mojoinda/public_html/var/import/'.$prefix.'-goodata-'.date('Y-m-d_H-i').'.csv';
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


	public function brocksupply_run() {
		try{
			$htmlData = $this->brocksupply_downloadHtml();
			$parsedData = $this->brocksupply_parseHtml($htmlData);
			if(count($parsedData) > 0){
				$this->makeCSV($parsedData, 'brock');
				file_put_contents($filename, $file);
				echo 'File saved to "', $filename, '"', PHP_EOL;
			}
		} catch (Exception $e) {
			echo $e, PHP_EOL;
		}
	}


	protected function brocksupply_parseHtml($htmlData) {
		$dom = new DOMDocument;
		$dom->loadHTML($htmlData);
		foreach ($dom->getElementsByTagName('a') as $node) {
			echo $node->nodeValue, PHP_EOL;
		}
	}


	protected function brocksupply_downloadHtml() {
		echo 'Downloading File...';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://www.ebay.com/itm/231194684544');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$htmlData = curl_exec($ch);
		$errno = curl_errno($ch);
		if ($errno!=0) {
			$message = 'File Download Error: ('.$errno.') '.curl_error($ch);
			curl_close($ch);
			throw new Exception($message);
		}
		curl_close($ch);
		$tmpFile = '/tmp/test-goodata-'.microtime(true).'.html';
//		$ret = file_put_contents($tmpFile, $xlsData);
		$putok = file_put_contents($tmpFile, $htmlData);
		if ($putok==0) {
			throw new Exception('Saving file failed. ('.$tmpFile.')');
		}
		echo 'Done', PHP_EOL;
//		return $tmpFile;
		return $htmlData;
	}
}
