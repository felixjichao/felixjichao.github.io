
<?php
header("Content-Type: text/json"); 
$type = $_GET["type"];
$flag = $_GET["flag"];
$detail = $_GET["detail"];
define("DIMG", "./img/d.png");
define("FIMG", "./img/f.png");
define("HIMG", "./img/h.png");
define("RIMG", "./img/r.png");
define("SIMG", "./img/s.svg");
define("TIMG", "./img/t.png");
define("IIMG", "./img/i.png");
define("WIMG", "./img/w.png");
$returnResult = array();
$usedURL = "http://congress.api.sunlightfoundation.com/";
// if (get_http_response_code($usedURL. "bills&apikey=fefe5c37a03a4e01944cafa200b39cac") != "200") {
	//$usedURL = "http://104.198.0.197:8080/";
// }
$LegiQuery = $usedURL . $type . "?&apikey=fefe5c37a03a4e01944cafa200b39cac&per_page=50";

if ($type == "legislators") {
	if ($flag == "details") {
		$returnResult["bioguide_id"] = $detail;
		$LegiQuery .= "&bioguide_id=" . $detail;
		$LegiQueryResult = json_decode(file_get_contents($LegiQuery));
		$LegiQueryResults = $LegiQueryResult->{"results"}[0];
		$returnResult["fullName"] = processKeyValue($LegiQueryResults, "title") . ". " .processKeyValue($LegiQueryResults, "last_name") . ", " . processKeyValue($LegiQueryResults, "first_name");
		$returnResult["email"] = processKeyValue($LegiQueryResults, "oc_email");
		$returnResult["chamber"] = ucwords(processKeyValue($LegiQueryResults, "chamber"));
		if ($LegiQueryResults->{"chamber"} == "house") {
			$returnResult["chamberImg"] = HIMG;
		}
		else if ($LegiQueryResults->{"chamber"} == "senate") {
			$returnResult["chamberImg"] = SIMG;
		}
		$returnResult["contact"] = processKeyValue($LegiQueryResults, "phone");
		$party = processKeyValue($LegiQueryResults, "party");
		$partyImg = "";
		if ($party == "R") {
			$partyImg = RIMG;
			$party = "Republican";
		}
		else if ($party == "D") {
			$partyImg = DIMG;
			$party = "Democrat";
		}
		else {
			$partyImg = IIMG;
			$party = "Independent";
		}
		$returnResult["party"] = $party;
		$returnResult["partyImg"] = $partyImg;
		date_default_timezone_set("America/Los_Angeles");
		$returnResult["termStart"] = date("M d, Y",strtotime(processKeyValue($LegiQueryResults, "term_start")));
		$returnResult["termEnd"] = date("M d, Y",strtotime(processKeyValue($LegiQueryResults, "term_end")));
 
		$returnResult["term"] = round((strtotime("now") - strtotime($returnResult["termStart"])) / (strtotime($returnResult["termEnd"]) - strtotime($returnResult["termStart"])) * 100);
		$returnResult["office"] = processKeyValue($LegiQueryResults, "office");
		$returnResult["state"] = processKeyValue($LegiQueryResults, "state_name");
		$returnResult["fax"] = processKeyValue($LegiQueryResults, "fax");
		$returnResult["birthday"] = date("M d, Y",strtotime(processKeyValue($LegiQueryResults, "birthday"))); 
		$returnResult["website"] = processKeyValue($LegiQueryResults, "website");	
		$returnResult["facebook"] = processKeyValue($LegiQueryResults, "facebook_id");
		$returnResult["twitter"] = processKeyValue($LegiQueryResults, "twitter_id");

		$LegiQuery = $usedURL . "committees?apikey=fefe5c37a03a4e01944cafa200b39cac&member_ids=" . $detail;
		$LegiQueryResult = json_decode(file_get_contents($LegiQuery));
		
		$commInfo = array();
		$commNum = $LegiQueryResult->{"count"};
		$limit = $commNum >= 5 ? 5 : $commNum;
		for ($i = 0; $i < $limit; $i++) {
			$LegiQueryResults = $LegiQueryResult->{"results"}[$i];
			$item = array();
			$item["chamber"] = ucwords($LegiQueryResults->{"chamber"});
			$item["committee_id"] = $LegiQueryResults->{"committee_id"};
			$item["name"] = processKeyValue($LegiQueryResults, "name");
			array_push($commInfo, $item);
		}
		$returnResult["commInfo"] = $commInfo;

		$LegiQuery =  $usedURL . "bills?apikey=fefe5c37a03a4e01944cafa200b39cac&sponsor_id=" . $detail;
		$LegiQueryResult = json_decode(file_get_contents($LegiQuery));
		
		$billInfo = array();
		$billNum = $LegiQueryResult->{"count"};
		$limit = $billNum >= 5 ? 5 : $billNum;
		for ($i = 0; $i < $limit; $i++) {
			$LegiQueryResults = $LegiQueryResult->{"results"}[$i];
			$item = array();
			$item["bill_id"] = strtoupper($LegiQueryResults->{"bill_id"});
			$item["title"] = processKeyValue($LegiQueryResults, "official_title");
			$item["chamber"] = ucwords($LegiQueryResults->{"chamber"});
			$item["bill_type"] = strtoupper($LegiQueryResults->{"bill_type"});
			$item["congress"] = $LegiQueryResults->{"congress"};
			$item["link"] = $LegiQueryResults->{"last_version"}->{"urls"}->{"pdf"};
			array_push($billInfo, $item);
		}
		$returnResult["billInfo"] = $billInfo;
	}
	else {
		$totalNum = 0;
		$perPage = 50;
		if ($flag == "state") {
			$LegiQuery .= "&order=state__asc,last_name__asc";
			$totalNum = 536;
		}
		else if ($flag == "house") {
			$LegiQuery .= "&chamber=house&order=last_name__asc";
			$totalNum = 438;
		}
		else if ($flag == "senate") {
			$LegiQuery .= "&chamber=senate&order=last_name__asc";
			$totalNum = 99;
		}
		else if ($flag == "app") {
			$LegiQuery .= "&order=last_name__asc";
			$totalNum = 536;
		}			
		$pageNum = floor($totalNum / $perPage) + 1;	
		$urls = array();
		$return = array();
		for ($i = 1; $i <= $pageNum; $i++) {
			array_push($urls, $LegiQuery . "&page=" . $i);
		}
		$curl = array();
		$handle = curl_multi_init();
		foreach($urls as $k=>$v){
			$curl[$k] = curl_init($v);
			curl_setopt($curl[$k], CURLOPT_RETURNTRANSFER, 1);
			curl_multi_add_handle($handle, $curl[$k]);
		}
		$flag = null;
		do {
			curl_multi_exec($handle, $flag);
		} while ($flag > 0);

		foreach($urls as $k=>$v){
			$LegiQueryResult = json_decode(curl_multi_getcontent($curl[$k]))->{"results"};
			$item50 =  array();
			for ($j = 0; $j < sizeof($LegiQueryResult); $j++) {
				$LegiQueryResults = $LegiQueryResult[$j];
				$item = array();
				if ($LegiQueryResults->{"party"} == "R") {
					$item["party"] = RIMG;
				}
				else if ($LegiQueryResults->{"party"} == "D") {
					$item["party"] = DIMG;
				}
				else {
					$item["party"] = IIMG;
					//$party = "Independent";
				}
				$item["name"] = $LegiQueryResults->{"last_name"} . ", " . $LegiQueryResults->{"first_name"};
				if ($LegiQueryResults->{"chamber"} == "house") {
					$item["chamberImg"] = HIMG;
					$item["chamber"] = "House";
				}
				else if ($LegiQueryResults->{"chamber"} == "senate") {
					$item["chamberImg"] = SIMG;
					$item["chamber"] = "Senate";
				}
				if (!array_key_exists("district", $LegiQueryResults) || $LegiQueryResults->{"district"} === null) {
					$item["district"] = "N.A.";
				}
				else {
					$item["district"] = "District " . $LegiQueryResults->{"district"};
				}
				$item["state"] = $LegiQueryResults->{"state_name"};
				$item["bioguide_id"] = $LegiQueryResults->{"bioguide_id"};
				array_push($item50, $item);			
			}
			$return[$k] = $item50;
			curl_multi_remove_handle($handle, $curl[$k]);
		}
		curl_multi_close($handle);
		for ($i = 0; $i < $pageNum; $i++) {
			$returnResult = array_merge($returnResult, $return[$i]);
		}		
	}	
	
}
else if ($type == "bills") {
	if ($flag == "details") {
		$returnResult["bill_id"] = $detail;
		$LegiQuery .= "&bill_id=" .  strtolower($detail);
		$LegiQueryResult = json_decode(file_get_contents($LegiQuery));
		$LegiQueryResults = $LegiQueryResult->{"results"}[0];
		$returnResult["bill_id"] = strtoupper($detail);
		$returnResult["title"] = processKeyValue($LegiQueryResults, "official_title");
		$returnResult["type"] = strtoupper(processKeyValue($LegiQueryResults, "bill_type"));
		$returnResult["sponsor"] = processKeyValue($LegiQueryResults->{"sponsor"}, "title") . ". " .processKeyValue($LegiQueryResults->{"sponsor"}, "last_name") . ", " . processKeyValue($LegiQueryResults->{"sponsor"}, "first_name");
		if ($LegiQueryResults->{"chamber"} == "house") {
			$returnResult["chamberImg"] = HIMG;
			$returnResult["chamber"] = "House";
		}
		else if ($LegiQueryResults->{"chamber"} == "senate") {
			$returnResult["chamberImg"] = SIMG;
			$returnResult["chamber"] = "Senate";
		}
		if ($LegiQueryResults->{"history"}->{"active"} === "true") {
			$returnResult["status"] = "Active";
		}
		else {
			$returnResult["status"] = "New";
		}
		date_default_timezone_set("America/Los_Angeles");
		$returnResult["introduced_on"] = date("M d, Y",strtotime(processKeyValue($LegiQueryResults, "introduced_on")));

		$returnResult["congressURL"] = $LegiQueryResults->{"urls"}->{"congress"};
		$returnResult["verStatus"] = $LegiQueryResults->{"last_version"}->{"version_name"};
		$returnResult["billURL"] = $LegiQueryResults->{"last_version"}->{"urls"}->{"pdf"};
	}
	else {
		if ($flag == "active") {
			$LegiQuery .= "&history.active=true&order=introduced_on&last_version.urls.pdf__exists=true";
		}
		else if ($flag == "new") {
			$LegiQuery .= "&history.active=false&order=introduced_on&last_version.urls.pdf__exists=true";
		}
		else if ($flag == "app") {
			$LegiQuery .= "&order=introduced_on&last_version.urls.pdf__exists=true";
		}	
		$LegiQueryResult = json_decode(file_get_contents($LegiQuery));
		
		$countPerPage =  $LegiQueryResult->{"page"}->{"count"};

		for ($i = 0; $i < 50; $i++) {
			$LegiQueryResults = $LegiQueryResult->{"results"}[$i];
			$item = array();
			$item["bill_id"] = strtoupper($LegiQueryResults->{"bill_id"});
			$item["type"] = strtoupper(processKeyValue($LegiQueryResults, "bill_type"));
			$item["title"] = processKeyValue($LegiQueryResults, "official_title");
			if ($LegiQueryResults->{"chamber"} == "house") {
				$item["chamberImg"] = HIMG;
				$item["chamber"] = "House";
			}
			else if ($LegiQueryResults->{"chamber"} == "senate") {
				$item["chamberImg"] = SIMG;
				$item["chamber"] = "Senate";
			}
			$item["introduced_on"] = processKeyValue($LegiQueryResults, "introduced_on");
			$item["sponsor"] = processKeyValue($LegiQueryResults->{"sponsor"}, "title") . ". " .processKeyValue($LegiQueryResults->{"sponsor"}, "last_name") . ", " . processKeyValue($LegiQueryResults->{"sponsor"}, "first_name");
			$item["detail"] = $LegiQueryResults->{"bill_id"};
			array_push($returnResult, $item);
		}
	}
}
else if ($type == "committees") {
	$totalNum = 0;
	$perPage = 50;	
	if ($flag == "house") {
		$LegiQuery .= "&chamber=house&order=name__asc";
		$totalNum = 128;
	}
	else if ($flag == "senate") {
		$LegiQuery .= "&chamber=senate&order=name__asc";
		$totalNum = 97;
	}
	else if ($flag == "joint") {
		$LegiQuery .= "&chamber=joint&order=name__asc";
		$totalNum = 5;
	}
	else if ($flag == "app") {
		$LegiQuery .= "&order=name__asc";
		$totalNum = 228;
	}
	else {
		$LegiQuery .= "&order=committee_id__asc";
		$totalNum = 228;
	}
	//$LegiQuery .= "&order=committee_id__asc";
		
	$pageNum = floor($totalNum / $perPage) + 1;
	$urls = array();
	$return = array();
	for ($i = 1; $i <= $pageNum; $i++) {
		array_push($urls, $LegiQuery . "&page=" . $i);
	}
	$curl = array();
	$handle = curl_multi_init();
	foreach($urls as $k=>$v){
		$curl[$k] = curl_init($v);
		curl_setopt($curl[$k], CURLOPT_RETURNTRANSFER, 1);
		curl_multi_add_handle($handle, $curl[$k]);
	}
	$flag = null;
	do {
		curl_multi_exec($handle, $flag);
	} while ($flag > 0);

	foreach($urls as $k=>$v){
		$LegiQueryResult = json_decode(curl_multi_getcontent($curl[$k]))->{"results"};
		$item50 =  array();
		for ($j = 0; $j < sizeof($LegiQueryResult); $j++) {
			$LegiQueryResults = $LegiQueryResult[$j];
			$chamber = $LegiQueryResults->{"chamber"};
			if ($chamber == "house") {
				$item["chamberImg"] = HIMG;
				$item["chamber"] = "House";
			}
			else if ($chamber == "senate") {
				$item["chamberImg"] = SIMG;
				$item["chamber"] = "Senate";
			}
			else if ($LegiQueryResults->{"chamber"} == "joint") {
				$item["chamberImg"] = SIMG;
				$item["chamber"] = "Joint";
			}
			$item["committee_id"] = strtoupper($LegiQueryResults->{"committee_id"});
			$item["name"] = $LegiQueryResults->{"name"};
			if ($chamber == "house" || $chamber == "senate") {
				if ($LegiQueryResults->{"subcommittee"} === true) {
					$item["sub_comm"] = "true";
					$item["parent_comm"] = $LegiQueryResults->{"parent_committee_id"};
				}
				else {
					$item["sub_comm"] = "false";
					$item["parent_comm"] = "";
				}
			}
			else {
				$item["sub_comm"] = "false";
				$item["parent_comm"] = "";
			}
			if ($chamber == "house") {
				$item["contact"] = $LegiQueryResults->{"phone"};
				$item["office"] = processKeyValue($LegiQueryResults, "office");	
			}
			else {
				$item["contact"] = "N.A.";
				$item["office"] = "N.A.";	
			}
			array_push($item50, $item);
		}
		$return[$k] = $item50;
		curl_multi_remove_handle($handle, $curl[$k]);
	}
	curl_multi_close($handle);
	for ($i = 0; $i < $pageNum; $i++) {
		$returnResult = array_merge($returnResult, $return[$i]);
	}		

}
echo json_encode($returnResult, JSON_UNESCAPED_SLASHES);
function processKeyValue($arr, $key) {
	if (!array_key_exists($key, $arr)) {
		if ($key == "first_name" || $key == "last_name" || $key == "title") {
			return "";
		}
		return "N.A."; 
	}
	if ($arr->{$key} === null) {
		return "N.A.";
	}
	return $arr->{$key};
}
function get_http_response_code($url) {
    $headers = get_headers($url);
    return substr($headers[0], 9, 3);
}
?>


