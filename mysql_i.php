<?php
	function sqlConnect($host, $user, $password, $new_db) {
		$link = mysqli_connect($host, $user, $password, $new_db);

		if (!$link) {
			echo "Error: Unable to connect to MySQL." . PHP_EOL;
			echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
			echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
			exit;
		}
		
		mysqli_query($link, "SET NAMES 'utf8'");
		mysqli_query($link, "SET CHARACTER SET utf8");
		mysqli_query($link, "SET CHARACTER_SET_CONNECTION=utf8");
		mysqli_query($link, "SET SQL_MODE = ''");
		
		return $link;
	}
	
	function query($link, $sql, $resultType = MYSQLI_ASSOC) {
		$object = mysqli_query($link, $sql);

		if ($object) {
			if (is_object($object)) {
				$i = 0;

				$data = array();

				while ($result = $object->fetch_array($resultType)) {
					$data[$i] = $result;
					$i++;
				}

				$object->free();

				$query = new stdClass();
				$query->row = isset($data[0]) ? $data[0] : array();
				$query->rows = $data;
				$query->num_rows = $i;

				unset($data);

				return $query;
    		} else {
				return true;
			}
		} else {
			$query = new stdClass();
			$query->row = array();
			$query->rows = array();
			$query->num_rows = 0;
			
			return $query;
    	}
  	} 
	
	function multi_query($link, $sql) {
		if (mysqli_multi_query($link, $sql)) {
			do {
  				if (!mysqli_more_results($link)) { // last result
  					$result = mysqli_store_result($link);
					break;
  				}
			} while(mysqli_next_result($link));

			if(mysqli_error($link)){
				return false;
			}
			
			if (is_object($result) && get_class($result) == 'mysqli_result') {
				$i = 0;

				$data = array();

				while ($row = $result->fetch_object()) {
					$data[$i] = $row;

					$i++;
				}

				$query = new stdClass();
				$query->row = isset($data[0]) ? (array) $data[0] : array();
				$query->rows = (array) $data;
				$query->num_rows = $result->num_rows;

				$result->close();

				unset($data);

				return $query;

			} else {
				return true;
			}
		} else {
			return false;
		}
	}
	
	function sqlClose($link) {
		mysqli_close($link);
	}
?>