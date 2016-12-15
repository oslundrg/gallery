<?php
$ds = DIRECTORY_SEPARATOR; 
$storeFolder = 'uploads'; 

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

if (!empty($_FILES)) {
    $tempFile = $_FILES['file']['tmp_name'];
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
	//save file to disk
    move_uploaded_file($tempFile,$targetFile);
    //echo json_encode(print_r($exif));
	echo "Lat: ".$lat."<br>Lon: ".$lon;
} else {                                                           
    $result  = array();
 
    $files = scandir($storeFolder);
    if ( false!==$files ) {
        foreach ( $files as $file ) {
            if ( '.'!=$file && '..'!=$file) {
                $obj['name'] = $file;
                $obj['size'] = filesize($storeFolder.$ds.$file);
                $result[] = $obj;
            }
        }
    }
     
    header('Content-type: text/json');
    header('Content-type: application/json');
    echo json_encode($result);
}

//$exif = exif_read_data("uploads/photo.JPG", 0, true);
//echo "photo.JPG:\n";
//var_dump($exif);
?>
