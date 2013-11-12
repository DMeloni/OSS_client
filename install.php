<?php
$progressFile = 'progress.json';
set_time_limit(0);
if(isset($_GET['action']) && $_GET['action'] == 'getProgress'){
  $progressFileJson = json_decode(file_get_contents($progressFile), true);
  if(is_array($progressFileJson)){
    echo json_encode(array('progress' => (int)$progressFileJson['progress']));
  }
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
        rrmdir($mainDirName, $progressFile, $fileCount+1);
        if(!is_dir($mainDirName)){
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
*/
 function rrmdir($dir, $progressFile = null, $fileCount = null) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") {
           rrmdir($dir."/".$object, $progressFile, $fileCount); 
          }
         else {
            unlink($dir."/".$object);
            if($progressFile != null){
              $oldprogressFileJson = json_decode(file_get_contents($progressFile), true);
              $oldprogressFileJson['progress'] += 100/$fileCount;
              file_put_contents($progressFile, json_encode($oldprogressFileJson));
            }
          }
       }
     }
   reset($objects);
   rmdir($dir);
   if($progressFile != null){
    $oldprogressFileJson = json_decode(file_get_contents($progressFile), true);
    $oldprogressFileJson['progress'] += 100/$fileCount;
    file_put_contents($progressFile, json_encode($oldprogressFileJson));
  }
 }
}

$projectName = sprintf("%s_%s", $_GET['openSourceProject'], $_GET['version']);

if(is_dir($projectName)){
    rrmdir($projectName);
}

$zipUrl = $_GET['url'];

file_put_contents($progressFile, json_encode(array('progress' => 0)));



$zipHeaders = get_headers($zipUrl, 1);

// Determine content lenght 
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

file_put_contents($progressFile, json_encode(array('progress' => 80)));

// Suppression of the one 
$tempDir = "temp";

if($nbFiles === 1){
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
}



//Demonstration
rrmdir($projectName);
mkdir($projectName);
file_put_contents(sprintf('%s/%s', $projectName, 'index.php'), '<?php header("Content-Type: text/html; charset=UTF-8"); echo "Afin d’éviter un piratage évident, les programmes ne sont pas opérationnels pour la démonstration d’OSS.";');


// Copie of img logo
if(false !== ($img = file_get_contents($_GET['img']))){
    file_put_contents(sprintf('%s/%s', $projectName, 'OSS_logo.png'), $img);
}

file_put_contents($progressFile, json_encode(array('progress' => 100)));
echo json_encode(array('name' => $projectName));
return;


