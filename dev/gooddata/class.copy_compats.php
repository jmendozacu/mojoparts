<?php

class copy_compats {

	function __construct() {
	}

	/*
	 * loadPageInput
	 */
	public function loadPageInput() {
		$skus = trim($_GET['skus']);
		$skus=str_replace("\r",",",$skus);
		$skus=str_replace("\n","",$skus);
		$next = trim($_GET['next']);
		$text = trim($_GET['skus']);
		if($next > 0) {
			$textAr = explode(",", $skus);
		}
		else {
			$textAr = explode("\n", $text);
		}
		$textAr = array_filter($textAr, 'trim');
		$sku=str_replace(" ","",$textAr[$next]);
		$sku=str_replace("\r","",$sku);
		//$sku=str_replace("\n","",$sku);
		//echo "Array size: ".count($textAr)."<br/>";
		//echo "Skus: ".$skus."<br/>";
		//echo "Next: ".$next."<br/>";
		if($next == count($textAr)-1) {
			$next = -1;
		//echo $next."<br/>";
		}
		else {
			$next= $next+1;
		//echo $next."<br/>";
		}
		//$skus = preg_split('/[\r\n]+/', $_GET["sku"], -1, PREG_SPLIT_NO_EMPTY);
		//$sku = skus[0];
		$customSearch = $_GET["customSearch"];

		return;
	}

}

?>