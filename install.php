<?php

/*
 * Disable all unsupported error messages
 */
error_reporting(0);

$repositoryServer = 'http://opensourcestore.info/s/';

$progressFile = sprintf('progress_%s_%s.json', $_GET['openSourceProject'], $_GET['version']);

set_time_limit(0);
if(isset($_GET['action']) && $_GET['action'] == 'getProgress'){
  if(!is_file($progressFile)){
  	echo json_encode(array('progress' => 100));
  	return;
  }
  $progressFileJson = json_decode(@file_get_contents($progressFile), true);
  if(is_array($progressFileJson)){
    echo json_encode(array('progress' => (int)$progressFileJson['progress']));
  }
  @unlink($progressFile);
  return;
}


/*
* Suppression of OSP 
*/
if(isset($_GET['action']) && $_GET['action'] == 'delete'){
    file_put_contents($progressFile, json_encode(array('progress' => 0)));
    $version = $_GET['version'];
    $name = $_GET['openSourceProject'];
    $mainDirName = sprintf('%s_%s', $name, $version);
    if(is_dir($mainDirName)){
        $fileCount = getFileCount($mainDirName);
        if(false !== rrmdir($mainDirName, $progressFile, $fileCount+1)){
        	echo json_encode(array('status' => 0));
        }else{
          echo json_encode(array('status' => 1));
        }
    }else{
      echo json_encode(array('status' => 0));
    }
    return;
}

/*
* Recursive file count
*/
function getFileCount($path) {
    $size = 0;
    $ignore = array('.','..');
    $files = scandir($path);
    foreach($files as $t) {
        if(in_array($t, $ignore)) continue;
        if (is_dir(rtrim($path, '/') . '/' . $t)) {
            $size++;
            $size += getFileCount(rtrim($path, '/') . '/' . $t);
        } else {
            $size++;
        }   
    }
    return $size;
}

/*
* Recursive rmdir
* 
* @param $dir : the dir to remove
* @param $progressFile : the file to update
* @param $fileCount : the number of all files 
*/
 function rrmdir($dir, $progressFile = null, $fileCount = null) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") {
           if(false === rrmdir($dir."/".$object, $progressFile, $fileCount)){
           	return false;
           } 
          }
         else {
            if(false !== @unlink($dir."/".$object)){
	            if($progressFile != null){
	              $oldprogressFileJson = json_decode(file_get_contents($progressFile), true);
	              $oldprogressFileJson['progress'] += 100/$fileCount;
	              file_put_contents($progressFile, json_encode($oldprogressFileJson));
	            }
            }else{
            	return false;
            }
          }
       }
     }
   reset($objects);
   if(false !== @rmdir($dir)){
	   if($progressFile != null){
	    $oldprogressFileJson = json_decode(file_get_contents($progressFile), true);
	    $oldprogressFileJson['progress'] += 100/$fileCount;
	    file_put_contents($progressFile, json_encode($oldprogressFileJson));
	  }
	  return true;
   }
	return false;
 }
}

$projectName = sprintf("%s_%s", $_GET['openSourceProject'], $_GET['version']);

if(is_dir($projectName)){
    if(!rrmdir($projectName)){
    	echo json_encode(array('progress' => 0, 'status' => 1, 'message' => 'Unable to delete old project dir (write rights ?).'));
    	return;    	
    }
}

$zipUrl = $_GET['url'];
$packageExtension = $_GET['type'];

file_put_contents($progressFile, json_encode(array('progress' => 0)));

/*
 * Download package information from the store server
*/
if(false === ($responseJson = @file_get_contents(sprintf($repositoryServer.'?action=%s&openSourceProject=%s&version=%s', 'showOpenSourceProject', urlencode($_GET["openSourceProject"]), $_GET['version'])))){
	echo json_encode(array('progress' => 0, 'status' => 1, 'message' => 'Unable to access store. Wait. If persist, change your store.'));
	return;
}

/*
 * Control of package url : check if url submitted by user (ajax) equals url given by the store
*/
$openSourceProject = json_decode($responseJson, true);
if(!isset($openSourceProject['url']) || $openSourceProject['url'] !== $zipUrl) {
	echo json_encode(array('progress' => 0, 'status' => 1, 'message' => 'User and store package url are differents, please refresh project page.'));
	return;	
}

/*
 * Control if https is used and if module openssl is actived
 */
if(substr($zipUrl, 0, 5) === 'https' && !extension_loaded('openssl')){
	echo json_encode(array('progress' => 0, 'status' => 1, 'message' => 'The installation of the package need the php extension "php_openssl".'));
	return;	
}

$zipHeaders = get_headers($zipUrl, 1);

/*
 * Determine content lenght
 * for the % advancement
 */ 
$length = 0;
if(isset($zipHeaders['Content-Length'])){
  if (is_array($zipHeaders['Content-Length'])) {
    $length = end($zipHeaders['Content-Length']);
  }
  else {
    $length = $zipHeaders['Content-Length'];
   }
}

$handle = fopen($zipUrl, "rb");
$zipContent = '';
$i=0;
while (!feof($handle)) {
  $i++;
  $zipContent .= fread($handle, 8192);
  if($length >= 1 &&  (int)((50/$length) * $i * 8192) <= 50 ){
    file_put_contents($progressFile, json_encode(array('progress' => (int)((50/$length) * $i * 8192) )));                 
  }
}
fclose($handle);


// Temp dir
$tempDir = "temp";

if($packageExtension === 'zip'){
	/*
	 * Test if ZipArchive class exists
	 */
	if(!class_exists('ZipArchive')){
		echo json_encode(array('progress' => 0, 'status' => 1, 'message' => 'The installation of the zip package need the class "ZipArchive" (PHP 5 >= 5.2.0, PECL zip >= 1.1.0).'));
		return;
	}
	
	file_put_contents("temp.zip", $zipContent);
	file_put_contents($progressFile, json_encode(array('progress' => 55)));
	//var_export($zipContent);
	
	$zip = new ZipArchive();
	if ($zip->open("temp.zip") === true) {
	    for($i = 0; $i < $zip->numFiles; $i++) {
	        $zip->extractTo($projectName, array($zip->getNameIndex($i)));
	        file_put_contents($progressFile, json_encode(array('progress' => 55 + (int)((20/$zip->numFiles) * $i)) ));                 
	    }                  
	    $zip->close();               
	}
	
	file_put_contents($progressFile, json_encode(array('progress' => 75)));
	
	$nbFiles= 0;
	$mainDirName = $projectName;
	if ($handle = opendir($projectName)) {
	    while (false !== ($file = readdir($handle))) {
	       if ($file != "." && $file != "..") {
	           $nbFiles++;
	           $mainDirName = $file;
	       }
	   }
	   closedir($handle);
	}
	
	/** 
	 * Return count of files into dir
	 */
	function countFilesIntoDir($dir){
		$nbFiles = 0;
		if ($handle = opendir($dir)) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					$nbFiles++;
					$mainDirName = $file;
				}
			}
			closedir($handle);
		}
		return $nbFiles;
	}
	
	file_put_contents($progressFile, json_encode(array('progress' => 80)));
	
	if($nbFiles === 1 || ($nbFiles == 2 && in_array('__MACOSX', scandir($projectName)))){ 
	    if(is_dir($tempDir)){
	        rrmdir($tempDir);
	    }
	    file_put_contents($progressFile, json_encode(array('progress' => 85)));
	    $try = 0;
	    while(true !== @rename(sprintf("%s/%s", $projectName, $mainDirName), $tempDir )
	      && $try <= 3
	      ){
	      sleep(1);
	      $try++;
	    }
	    rrmdir($projectName);
	    rename ($tempDir, $projectName);
	    
	    if(countFilesIntoDir($projectName) === 3){
	    	$scanDir = scandir($projectName, SCANDIR_SORT_ASCENDING );
	    	$dirAlone = end($scanDir);
	    	 if(is_dir($tempDir)){
		        rrmdir($tempDir);
	    	 }

	        /*
	         * Try 3 times to rename temp project to real name
	        */	        
	        $try = 0;
	        while(true !== @rename(sprintf("%s/%s", $projectName, $dirAlone), $tempDir )
	        		&& $try <= 3
	        ){
	        	sleep(1);
	        	$try++;
	        }
	        rrmdir($projectName);
	        rename ($tempDir, $projectName);		        
	    }
	}
}

if($packageExtension === 'php'){
	mkdir($projectName);
	file_put_contents(sprintf('%s/%s', $projectName, 'index.php'), $zipContent);
}

if($packageExtension === 'tar.gz' ){
	/*
	 * Test if PharData class exists
	*/
	if(!class_exists('PharData')){
		echo json_encode(array('progress' => 0, 'status' => 1, 'message' => 'The installation of the zip package need the class "PharData" (PHP >= 5.3.0, PECL phar >= 2.0.0).'));
		return;
	}
	
	mkdir($projectName);
	
	if(false === @file_put_contents('temp.tar',gzdecode ($zipContent))){
		echo json_encode(array('progress' => 0, 'status' => 1, 'message' => 'Unable to create temp.tar file.'));
		return;		
	}
	
	$phar = new PharData('temp.tar');
	$phar->extractTo($projectName); // extract all files
	
	$files = scandir($projectName, SCANDIR_SORT_ASCENDING );

	$nbFiles = count($files);
	if($nbFiles === 3 ){// main dir + '..' + '.'
		$mainDirName = end($files);
		if(is_dir($tempDir)){
			rrmdir($tempDir);
		}
	
		file_put_contents($progressFile, json_encode(array('progress' => 85)));
		 
		/*
		 * Try 3 times to rename temp project to real name 
		 */
		$try = 0;
		while(true !== @rename(sprintf("%s/%s", $projectName, $mainDirName), $tempDir )
				&& $try <= 3
		){
			sleep(1);
			$try++;
		}

		if(is_dir(sprintf("%s/%s", $projectName, $mainDirName))){
			echo json_encode(array('progress' => 0, 'status' => 1, 'message' => 'Unable to rename project dir to temp dir.'));
			return;
		}
		
		rrmdir($projectName);
		
		if(false === rename ($tempDir, $projectName)){
			echo json_encode(array('progress' => 0, 'status' => 1, 'message' => 'Unable to rename temp dir to project dir.'));
			return;			
		}
	}	
}

// Copie of img logo
if(false !== ($img = file_get_contents($_GET['img']))){
    file_put_contents(sprintf('%s/%s', $projectName, 'OSS_logo.png'), $img);
}

file_put_contents($progressFile, json_encode(array('progress' => 100)));
echo json_encode(array('name' => $projectName, 'status' => 0));

@unlink($progressFile);

return;


