<?php

	function getChallanDetails($vehicleNo) {

		$vehicleN = trim(strtoupper($vehicleNo));

		if (!preg_match("/^[a-zA-Z]{2}[a-zA-Z0-9]{0,6}[0-9]{3}$/", $vehicleNo, $match)) {
			return array(
				"success" => false,
				"message" => "Invalid Vehicle Number entered!",
				"data" => json_decode("{}", false)
			);
		}

		$ch = curl_init();

		$header[] = "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Keep-Alive: 300";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$header[] = "Pragma: ";

		// curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_URL, "http://mumbaipolice.in.net/payechallan/PaymentService.htm");
		curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . "/cookies/" . $vehicleNo . ".txt");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 2);

		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15');
		curl_setopt($ch, CURLOPT_REFERER, 'http://www.google.com');
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	
		$output = curl_exec($ch);
		$curl_getinfo = curl_getinfo($ch);

		$url = explode("\" method=\"POST", explode("form-horizontal\" action=\"", $output)[1])[0];
		curl_setopt($ch, CURLOPT_URL, "http://mumbaipolice.in.net/payechallan/" . $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array("challanCategory" => "VNO", "categoryNo" => $vehicleNo));
		$html = curl_exec($ch);

		$rows = "<data>" . explode("</tbody>", explode('<tbody id="tableBody">', $html)[1])[0] . "</data>";
		$array = json_decode(json_encode(simplexml_load_string($rows)),TRUE);

		$finalOutput = array(
			"vehicleNo" => $vehicleNo,
			"total" => array("count" => 0, "amount" => 0),
			"unpaid" => array("count" => 0, "amount" => 0),
			"paid" => array("count" => 0, "amount" => 0),
			"challans" => array()
		);

		foreach ($array["tr"] as $row) {
			if (isset($row["td"]) && floatval($row["td"][5]) != 0) {
				$item = array(
					"challanNo" => $row["td"][0],
					"challanDate" => trim($row["td"][1]),
					"driverName" => isset($row["td"][2][0]) ? $row["td"][2][0] : "",
					"licenseNo" => $row["td"][4],
					"amount" => floatval($row["td"][5]),
					"paid" => (!isset($row["td"][7]["span"]) || $row["td"][7]["span"] != "Pay Now")
				);

				if ($item["paid"]) {
					$finalOutput["paid"]["amount"] += $item["amount"];
					$finalOutput["paid"]["count"]++;
				} else {
					$finalOutput["unpaid"]["amount"] += $item["amount"];
					$finalOutput["unpaid"]["count"]++;
				}
				
				$finalOutput["total"]["count"]++;
				$finalOutput["total"]["amount"] += $item["amount"];

				$finalOutput["challans"][] = $item;
			}
		}

		return array(
			"success" => $curl_getinfo["size_download"] != 0,
			"message" => ($curl_getinfo["size_download"] != 0 ? "OK" : "No data received."),
			"data" => $finalOutput
		);
	}

	if (PHP_SAPI != 'cli') {
		$vehicleNo = $_GET["vehicleNo"];
		echo json_encode(getChallanDetails($vehicleNo), JSON_PRETTY_PRINT);
	} else {
		$vehicleNos = array(
			array("name" => "Donesh Laher", "vehicleNo" => "MH02DW1601"),
			array("name" => "Saurabh Garg", "vehicleNo" => "MH02BZ3217"),
			array("name" => "Kamal Relwani", "vehicleNo" => "MH02EJ5575")
		);

		// Loop through the array
		// Call
		// Fill in the blanks here to complete the code
	}
?>
