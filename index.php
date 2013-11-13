<?php
$repositoryServer = 'http://localhost/OSS/server.php';
$repositoryServer = 'http://stuper.info/codiad/workspace/OSS/server.php';
$repositoryRootServer = 'http://stuper.info/codiad/workspace/OSS/';

$repositoryServer = 'http://localhost/OSS_server/server.php';
$repositoryRootServer = 'http://localhost/OSS_server/';

$projectsDir = '.';

header("Content-Type: text/html; charset=UTF-8");
?><!DOCTYPE html>
<html>
  <head>
    <title>OSS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="css/bootstrap-responsive.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <link href="css/style.css" rel="stylesheet">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <nav class="navbar navbar-default" role="navigation">
      <!-- Brand and toggle get grouped for better mobile display -->
      <div class="navbar-header">
        <a class="navbar-brand" href="?page=tools">Tools</a>
        <a class="navbar-brand" href="?page=store">Store</a>
      </div>    
    </nav>

    <div class="container bs-docs-container"/>
      <div class="row">

        <?php if((isset($_GET['page']) && $_GET['page'] === 'store') || isset($_GET["openSourceProject"])|| isset($_GET["category"])) { ?>
        <div class="col-md-2">
          <div class="bs-sidebar hidden-print" role="complementary">
            <ul class="nav nav-pills nav-stacked">
                <?php
                  if(false !== ($responseJson = file_get_contents(sprintf($repositoryServer.'?action=%s', 'showCategories')))){
                    $categories = json_decode($responseJson, true);
                    foreach($categories as $category){
                    ?><li><a href="?category=<?php echo urlencode($category);?>"><?php echo urlencode($category);?></a></li><?php
                  }
                }?>
            </ul>
            </div>
        </div>
        <?php } ?>
        
        <!-- show my apps -->
        <?php 
        if(empty($_GET) || (isset($_GET['page']) && $_GET['page'] === 'tools')) {?>            
        <div class="col-md-12" role="main"><?php
            $openSourceProjects = array();
            $files = scandir($projectsDir);
            foreach($files as $t) {
                $projectDir = explode('_', $t);
                if(2 === count($projectDir) && is_dir(sprintf('%s/%s', $projectsDir, $t))){
                    $projectName = $projectDir[0];
                    $projectVersion = $projectDir[1];
                    $openSourceProjects[] = array(
                        'name' => sprintf('%s (%s)', $projectName, $projectVersion),
                        'url' => sprintf('%s/%s', $projectsDir, $t),
                        'img' =>  sprintf('%s_%s/OSS_logo.png', $projectName, $projectVersion)
                    );                            
                }

            }
          ?><ul class="OSProjects"><?php
          foreach($openSourceProjects as $openSourceProject){
          ?>
              <li>
                <a href="<?php if(isset($_GET["category"])){echo '?openSourceProject='.$openSourceProject['name'];}else{echo $openSourceProject['url'];}?>">
                  <div class="OSProjectIcon">
                    <img src="<?php echo $openSourceProject['img'];?>"/> 
                  </div>
                  <div class="OSProjectName">
                  <?php echo $openSourceProject['name'];?>
                  </div>
                </a>
              </li>
          <?php
          }
          ?></ul>
        </div>
        <?php                 
        }  
        else {?>   
            <div class="col-md-10" role="main">
              <?php
                if(!isset($_GET["openSourceProject"])) {
                    $openSourceProjects = array();
                    if(isset($_GET["category"])) {
                      if(false !== ($responseJson = file_get_contents(sprintf($repositoryServer.'?action=%s&category=%s', 'showOpenSourceProjects', $_GET["category"])))){
                          $openSourceProjects = json_decode($responseJson, true);                    
                      }
                    }
                  ?><ul class="OSProjects"><?php
                  foreach($openSourceProjects as $openSourceProject){
                  //Download img
                  $projectImg = $openSourceProject['img'];
                  if(!is_file($projectImg)) {
                      if(false !== ($imgContent = file_get_contents(sprintf('%s/%s', $repositoryRootServer, $projectImg)))){
                        file_put_contents($projectImg, $imgContent);
                      }
                  }                      
                  ?>
                      <li>
                        <a href="<?php if(isset($_GET["category"])){echo '?openSourceProject='.$openSourceProject['name'];}else{echo $openSourceProject['url'];}?>">
                          <div class="OSProjectIcon">
                            <img src="<?php echo $openSourceProject['img'];?>"/> 
                          </div>
                          <div class="OSProjectName">
                          <?php echo $openSourceProject['name'];?>
                          </div>
                        </a>
                      </li>
                  <?php
                  }
                  ?></ul><?php                 
                }
                
                if(isset($_GET["openSourceProject"])) {
                  $version = 'last';
                  $projectType = 'zip';
                  $projectName=$_GET["openSourceProject"];
                  if(isset($_GET["version"])){
                    $version = $_GET["version"];
                  }
                  if(false !== ($responseJson = file_get_contents(sprintf($repositoryServer.'?action=%s&openSourceProject=%s&version=%s', 'showOpenSourceProject', urlencode($_GET["openSourceProject"]), $version)))){
                      $openSourceProject = json_decode($responseJson, true);
                      
                      $projectName=strtolower($openSourceProject['name']);
                      $version=$openSourceProject['version'];
                      $projectUrl = $openSourceProject['url'];
                      $projectImg = $openSourceProject['img'];
                      if(isset($openSourceProject['type'])){
                      	$projectType = $openSourceProject['type'];
                      }
                      
                      //Download img
                      if(!is_file($projectImg)) {
                          if(false !== ($imgContent = file_get_contents(sprintf('%s/%s', $repositoryRootServer, $projectImg)))){
                            file_put_contents($projectImg, $imgContent);
                          }
                      }
                      
                      ?><div class="OSProject">
                          <div class="OSProjectHeader">
                            <div class="OSProjectIcon">
                                <img src="<?php echo $openSourceProject['img'];?>"/> 
                            </div>
                            <div id="OSProjectVersion">
                            <?php
                            if(count($openSourceProject['versions']) > 1){?>
                              <div class="btn-group">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                  <?php echo $openSourceProject['version'];?> <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu" role="menu">
                                  <?php
                                  foreach($openSourceProject['versions'] as $versionTmp){
                                    if($versionTmp !== $openSourceProject['version']){
                                    ?><li><a href="?openSourceProject=<?php echo $projectName;?>&amp;version=<?php echo $versionTmp;?>"><?php echo $versionTmp;?></a></li><?php
                                    }
                                  }
                                  ?>
                                </ul>
                              </div>
                              <?php
                              }?>
                              </div>
                            <?php
    
                            $isInstalled = false; 
                            if(is_dir(sprintf('%s_%s', strtolower($openSourceProject['name']), $openSourceProject['version']))) {
                              $isInstalled = true;
                            }
    
                            // Display download button OR goto button && delete button
                            ?>
                            <div class="OSProjectPanelButton"> 
                                <button <?php if($isInstalled){echo ' style="display:none;" ';}?> id="OSProjectDownloadButton" class="btn btn-success OSProjectDownloadButton">Installer</button>
                                <a href="<?php echo sprintf('%s_%s', strtolower($openSourceProject['name']), $openSourceProject['version'].'/'); ?>" <?php if(!$isInstalled){echo ' style="display:none;" ';}?> id="OSProjectExecuteButton" class="btn btn-success">Executer</a>
                                <button <?php if(!$isInstalled){echo ' style="display:none;" ';}?> id="OSProjectDeleteButton" class="btn btn-danger">Supprimer</button>
     
                                <div id="OSProjectProgressBar" class="progress">
                                  <div id="OSProjectProgressValue" class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="90" style="width: 0%;">
                                    <span class="sr-only">0% Complete</span>
                                  </div>
                                </div>
                           </div>                     
                          </div>
                          <div class="divclear" />
                          <div>
                              <div>
                                <h3>Informations générales</h3>
                                  Nom de l'application: <?php echo $openSourceProject['name'];?>
                                  <br/>
                                  Auteur: <?php echo $openSourceProject['author'];?>
                                  <br/>
                                  <a href="<?php echo $openSourceProject['url'];?>">Lien du package</a>
                                  <br/>
                                  <a href="<?php echo $openSourceProject['link'];?>">Site web officiel</a>
                                  <br/>
                                  Version: <?php echo $openSourceProject['version'];?>
                                  <br/>
                                  Configuration: 
                                   <?php 
                                  if(isset($openSourceProject['configuration']) && $openSourceProject['configuration']){
                                    ?><span class="redSpan" title="You have to edit some files to configure the project" ><span class="glyphicon glyphicon-warning-sign" ></span><?php echo $openSourceProject['configuration'];?></span><?php
                                  }else{
                                    ?><span class="greenSpan" title="The configuration is user friendly" ><span class="glyphicon glyphicon-thumbs-up"></span>Assisted</span><?php
                                  }?>  
                                  
                                  <br/>
                                  Database:   
                                   <?php 
                                  if(isset($openSourceProject['database']) && $openSourceProject['database']){
                                    ?><span class="redSpan" title="You have to configure some SQL access" ><span class="glyphicon glyphicon-warning-sign" ></span><?php echo $openSourceProject['database'];?></span><?php
                                  }else{
                                    ?><span class="greenSpan" title="The datas are saved on web server" ><span class="glyphicon glyphicon-thumbs-up"></span>No database required</span><?php
                                  }?>                             
                                  <br/>
                                  <?php 
                                  if(isset($openSourceProject['trust'])){
                                    ?>Trusted source: <span class="redSpan" title="This package is potentially unsafe" ><span class="glyphicon glyphicon-warning-sign"/>No</span><?php
                                  }?>
                              </div>
    
                              <div>
                                <h3>Description</h3>
                                <div>
                                  <?php echo $openSourceProject['description'];?>
                                </div>
                              </div>
                              <?php
                              if(isset($openSourceProject['screenshoots']) 
                                && count($openSourceProject['screenshoots']) > 0) { ?>     
                                                   
                              <div id="sceenShootsContainer">
                                <h3>Screenshoots</h3>
                                <div>
                                <?php
								foreach($openSourceProject['screenshoots'] as $screenshoot) {
									//Download img
									if(!is_file($screenshoot)) {
										if(false !== ($imgContent = file_get_contents(sprintf('%s/%s', $repositoryRootServer, $screenshoot)))){
											file_put_contents($screenshoot, $imgContent);
										}
									}
                                    ?><img width="100%" alt="screenshoot" src="<?php echo $screenshoot; ?>" /><?php
                                  }?>
                                </div>
                              </div>  
                              <?php
                              }
                              ?>
                              <div>
                                <h3>Debug technique</h3>
                                  <?php echo $responseJson;?>
                              </div>
    
                          </div>
                      </div>
                      <?php
                  }              
                }?>
              </div>
            </div>
        <?php } ?>

    </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://code.jquery.com/jquery.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="js/bootstrap.min.js"></script>

    <script>
    
      var alertFallback = true;
       if (typeof console === "undefined" || typeof console.log === "undefined") {
         console = {};
         if (alertFallback) {
             console.log = function(msg) {
                  alert(msg);
             };
         } else {
             console.log = function() {};
         }
       }
   
    
      $( ".categoryLink" ).click(function() {
        $(this).parent().parent().children("ul li").attr("class", "");
        $(this).parent().attr("class", "active");
        $(this).parent().parent().children().children( "ul" ).slideUp("slow", function() {});
        $(this).parent().children( "ul" ).slideDown( "slow", function() {});
      });
    </script>

    <?php 
    if(isset($version)){
    ?>
    <script>
      var installationOver = false;
      var projectFolder = '';
      var getStatusInterval;
      // Download and install Open Source Project
      $( "#OSProjectDownloadButton" ).click(function() {
          //start your long-running process
          if(installationOver == false){
            console.log('installation...');
            $( "#OSProjectDownloadButton" ).attr('disabled', true);
            
            $.ajax(
                    {
                        type: 'GET',
                        url: "install.php?version=" + "<?php echo $version; ?>" 
                        	+ "&openSourceProject=" + "<?php echo $projectName; ?>" 
                        	+ "&url=" + "<?php echo urlencode($projectUrl); ?>"
                        	+ "&type=" + "<?php echo urlencode($projectType); ?>"  
                        	+ "&img=" + "<?php echo urlencode($projectImg); ?>",
                        async: true,
                        dataType: "json",
                        success:
                            function (data) {
                                console.log('installation terminée');
                                clearInterval(getStatusInterval);
                                installationOver = true;
                                projectFolder = data.name;
                                console.log(projectFolder);

                                $( "#OSProjectDownloadButton" ).hide();
                                $( "#OSProjectDeleteButton" ).attr('disabled', false);
                                $( "#OSProjectExecuteButton" ).attr('disabled', false); 
                                
                                //Demonstration
                                //$( "#OSProjectExecuteButton" ).attr('disabled', true); 
                                
                                $( "#OSProjectExecuteButton" ).show();
                                $( "#OSProjectDeleteButton" ).show();

                                $( "#OSProjectProgressBar").css('visibility', 'hidden');
                                //do something - your long process is finished
                            }
                    });
            $("#OSProjectProgressValue").width(0 + '%');
            $( "#OSProjectProgressBar").css('visibility', 'visible');
            getStatusInterval = setInterval("getStatus()", 2000);
          }else{
            console.log('Test...');
            //Redirection on project folder
            window.location=  projectFolder;
          }
      });

      // Check Progress of treatment and update progress bar
      function getStatus() {
          //check your progress
          $.ajax(
                  {
                      type: 'GET',
                      url: "install.php?action=getProgress&version=" + "<?php echo $version; ?>" + "&openSourceProject=" + "<?php echo $projectName; ?>",
                      async: true,
                      dataType:"json",
                      success:
                          function (data) {
                              //assume the data returned in the percentage complete
                              var percentage = parseInt(data.progress);
                              console.log(data.progress);
                              //write your status somewhere, like a jQuery progress bar?
                              $("#OSProjectProgressValue").width(percentage + '%');
                              if(percentage >= 100){
                                clearInterval(getStatusInterval);
                              }
                          }
                  });
      }
      
      // Delete Open Source Project
      $( "#OSProjectDeleteButton" ).click(function() {
          //start your long-running process
            console.log('suppression...');
            $( "#OSProjectDeleteButton" ).attr('disabled', true);
            $( "#OSProjectExecuteButton" ).hide();
            $.ajax(
                    {
                        type: 'GET',
                        url: "install.php?action=delete&version=" + "<?php echo $version; ?>" + "&openSourceProject=" + "<?php echo $projectName; ?>",
                        async: true,
                        dataType: "json",
                        success:
                            function (data) {
                                console.log('suppression terminée');
                                clearInterval(getStatusInterval);
                                if(data.status == 0){
                                  installationOver = false;
                                  $( "#OSProjectDownloadButton" ).attr('disabled', false);
                                  $( "#OSProjectDownloadButton" ).show();
                                  $( "#OSProjectDeleteButton" ).hide();
                                  $( "#OSProjectProgressBar").css('visibility', 'hidden');
                                }else{
                                  console.log('probleme lors de la suppression');
                            }

                    }
            });
            $("#OSProjectProgressValue").width(0 + '%');
            $( "#OSProjectProgressBar").css('visibility', 'visible');
            getStatusInterval = setInterval("getStatus()", 2000);
      });

 
  </script>
  <?php } ?>
  </body>
</html>
