<?php

class Usage {
	
	public $id = '';
	public $apiKey = '';
	public $year = 0;
	public $month = 0;
	public $count = 0;
	public $lastUpdated = 0;
	
	public function updateUsage($inputYear, $inputMonth){
		// Required Classes
		$uuid = new uuid();
		$db = new Database();
		
		// Get all the API Keys
		$apiKeys = array();
		$db->query("SELECT DISTINCT apiKey FROM api_logging");
		while($array = $db->resultArray()){
			$apiKeys[] = $array['apiKey'];
		}
		
		foreach($apiKeys as &$apiKey){
			// Set Initial Value
			$endingTimestamp = 0;
			$dbAPIKey = $db->escape($apiKey);
			
			// Convert to Year and Month
			if(empty($inputYear) && empty($inputMonth)){
				// Last Updated
				$db->query("SELECT MAX(lastUpdated) AS lastUpdate FROM api_usage");
				$lastUpdated = $db->singleResult('lastUpdate');
				$year = date('Y', $lastUpdated);
				$month = date('n', $lastUpdated);
			}else{
				$year = $inputYear;
				$month = $inputMonth;
			}
			$dbYear = $db->escape($year);
			$dbMonth = $db->escape($month);

			// Set Timestamps
			$startingTimestamp = mktime(0, 0, 0, $month, 1, $year);
			$endingTimestamp = mktime(23, 59, 59, $month, date('t',$startingTimestamp), $year);

			// Number of API Queries per Period
			$db->query("SELECT COUNT(id) AS usageCount FROM api_logging WHERE apiKey='$dbAPIKey' AND timestamp BETWEEN $startingTimestamp AND $endingTimestamp");
			if($db->error){break;}
			$usageCount = $db->singleResult('usageCount');
			$dbCount = $db->escape($usageCount);

			// Update Usage Table
			$dbLastUpdated = $db->escape(time());
			$db->query("SELECT id FROM api_usage WHERE apiKey='$dbAPIKey' AND month='$dbMonth' AND year='$dbYear'");
			if($db->result->num_rows > 0){
				// Already have usage this month, update it
				$dbID = $db->singleResult('id');
				$db->query("UPDATE api_usage SET count='$dbCount', lastUpdated='$dbLastUpdated' WHERE id='$dbID'");
				if($db->error){break;}
				//else{echo "Updated $apiKey count to: $usageCount\n";}
			}else{
				// No usage yet, create an entry
				$id = $uuid->generate('api_usage');
				if($uuid->error){break;}
				else{
					$dbID = $db->escape($id);
					$db->query("INSERT INTO api_usage (id, apiKey, year, month, count, lastUpdated) VALUES ('$dbID', '$dbAPIKey', '$dbYear', '$dbMonth', '$dbCount', '$dbLastUpdated')");
					if($db->error){break;}
					//else{echo "Added entry for $apiKey and set count to: $usageCount\n";}
				}
			}
		}
		// Close Database Connection
		$db->close();
	}
	
	public function initialRun(){
		// Truncate api_usage table prior to running this script
		
		// Required Classes
		$uuid = new uuid();
		$db = new Database();
		
		// Get all the API Keys
		$apiKeys = array();
		$db->query("SELECT DISTINCT apiKey FROM api_logging");
		while($array = $db->resultArray()){
			$apiKeys[] = $array['apiKey'];
		}
		
		foreach($apiKeys as &$apiKey){
			// Set Initial Value
			$endingTimestamp = 0;
			
			// Get Starting Timestamp
			$dbAPIKey = $db->escape($apiKey);
			$db->query("SELECT MIN(timestamp) AS startingTimestamp FROM api_logging WHERE apiKey='$dbAPIKey'");
			if($db->error){break;}
			$startingTimestamp = $db->singleResult('startingTimestamp');
			
			while($endingTimestamp < time()){
				// Convert to Year and Month
				$year = date('Y', $startingTimestamp);
				$month = date('n', $startingTimestamp);

				// Ending Timestamp
				$endingTimestamp = mktime(23, 59, 59, $month, date('t',$startingTimestamp), $year);

				// Number of API Queries per Period
				$db->query("SELECT COUNT(id) AS usageCount FROM api_logging WHERE apiKey='$dbAPIKey' AND timestamp BETWEEN $startingTimestamp AND $endingTimestamp");
				if($db->error){break 2;}
				$usageCount = $db->singleResult('usageCount');
				
				// Update Usage Table
				$id = $uuid->generate('api_usage');
				if($uuid->error){break 2;}
				$dbID = $db->escape($id);
				$dbYear = $db->escape($year);
				$dbMonth = $db->escape($month);
				$dbCount = $db->escape($usageCount);
				$dbLastUpdated = $db->escape(time());
				$db->query("INSERT INTO api_usage (id, apiKey, year, month, count, lastUpdated) VALUES ('$dbID', '$dbAPIKey', '$dbYear', '$dbMonth', '$dbCount', '$dbLastUpdated')");
				if($db->error){break 2;}
				
				// Next Month
				$startingTimestamp = $endingTimestamp + 1;
			}
		}
		// Close Database Connection
		$db->close();
	}
}
?>