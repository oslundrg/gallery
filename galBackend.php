<?php
$ds = DIRECTORY_SEPARATOR; 
$storeFolder = 'uploads'; 

	$newGeoJSON = new stdClass();
	$newGeoJSON->type = "FeatureCollection";
	$newGeoJSON->features = array();

function gps($coordinate, $hemisphere) {
	for ($i = 0; $i < 3; $i++) {
		$part = explode('/', $coordinate[$i]);
		if (count($part) == 1) {
			$coordinate[$i] = $part[0];
		} else if (count($part) == 2) {
			$coordinate[$i] = floatval($part[0])/floatval($part[1]);
		} else {
			$coordinate[$i] = 0;
		}
	}
	list($degrees, $minutes, $seconds) = $coordinate;
	$sign = ($hemisphere == 'W' || $hemisphere == 'S') ? -1 : 1;
	return $sign * ($degrees + $minutes/60 + $seconds/3600);
}

//if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
//    {
//        echo "RECEIVED ON SERVER: \n";
//        echo "FILES: \n";
//        print_r($_FILES);
//        echo "\$_POST: \n";
//        print_r($_POST);
//    }

if (!empty($_FILES)) {
    $tempFile = $_FILES['file']['tmp_name'];
	$event = $_POST['event'];
	$date = $_POST['date'];
    $exif = exif_read_data($tempFile, 0);
	//fix orientation if needed
	if (!empty($exif['Orientation'])) {
        $image = imagecreatefromjpeg($tempFile);
        switch ($exif['Orientation']) {
            case 3:
                $image = imagerotate($image, 180, 0);
                break;

            case 6:
                $image = imagerotate($image, -90, 0);
                break;

            case 8:
                $image = imagerotate($image, 90, 0);
                break;
        }

        imagejpeg($image, $tempFile, 90);
    }
	//get lat/log
	$lon = round(gps($exif["GPSLongitude"], $exif['GPSLongitudeRef']),4);
	$lat = round(gps($exif["GPSLatitude"], $exif['GPSLatitudeRef']),6);
    $targetPath = dirname( __FILE__ ) . $ds. $storeFolder . $ds;
    $targetFile =  $targetPath. $_FILES['file']['name'];
	
	$point = new stdClass();
	$point->type = "Feature";
	$point->geometry = new stdClass();
	$point->geometry->type = "Point";
	$point->geometry->coordinates = array( $lon, $lat);
	$point->properties = new stdClass();
	$point->properties->event = $event;
	$point->properties->date = $date;
	//$newGeoJSON->features[] = $point;
	//save file to disk
	file_put_contents($targetFile.".json", json_encode($point));
    move_uploaded_file($tempFile,$targetFile);
    echo json_encode($point);
	//echo "Lat: ".$lat."<br>Lon: ".$lon;
} else {                                                           
    $result  = array();
 
    $files = scandir($storeFolder);
    if ( false!==$files ) {
        foreach ( $files as $file ) {
			$ext = pathinfo($file, PATHINFO_EXTENSION);
            if ( '.'!=$file && '..'!=$file && 'json'!=$ext) {
                $obj['name'] = $file;
                $obj['size'] = filesize($storeFolder.$ds.$file);
				//path to json file
				$jsonFileName = $storeFolder ."/". $file . ".json";
				$json = json_decode(file_get_contents($jsonFileName));
				//test
				//file_put_contents("uploads/test.txt",json_encode($jsonFileName."\n"));
				
				$obj['lat'] = $json->geometry->coordinates[1];
				$obj['lon'] = $json->geometry->coordinates[0];
				$obj['event'] = $json->properties->event;
				$obj['date'] = $json->properties->date;
                $result[] = $obj;
            }
        }
		header('Content-type: text/json');
		header('Content-type: application/json');
		echo json_encode($result);
    }
}

//$exif = exif_read_data("uploads/photo.JPG", 0, true);
//echo "photo.JPG:\n";
//var_dump($exif);
?>
