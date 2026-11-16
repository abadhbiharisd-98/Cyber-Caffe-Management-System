<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
if (empty($_SESSION['ccmsaid'])) {
  header('location:logout.php');
  exit;
} else{
    if(isset($_POST['submit']))
  {
    $adminid=$_SESSION['ccmsaid'];
    $AName=$_POST['adminname'];
    $mobno=$_POST['mobilenumber'];
    $email=$_POST['email'];
     $query=mysqli_query($con, "update tbladmin set AdminName='$AName', MobileNumber ='$mobno', Email= '$email' where ID='$adminid'");
    if ($query) {
    echo '<script>alert("Admin profile has been updated.")</script>';
  }
  else
    {
      echo '<script>alert("Something Went Wrong. Please try again.")</script>';
    }
  }
  ?>

<!doctype html>
<html class="no-js" lang="en">
<head>
    
    <title>CCMS Admin Profile</title>
   

    <link rel="apple-touch-icon" href="apple-icon.png">


    <link rel="stylesheet" href="vendors/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="vendors/themify-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/selectFX/css/cs-skin-elastic.css">

    <link rel="stylesheet" href="assets/css/style.css">

    <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,600,700,800' rel='stylesheet' type='text/css'>



</head>

<body>
    <!-- Left Panel -->

    <?php include_once('includes/sidebar.php');?>

    <div id="right-panel" class="right-panel">

        <!-- Header-->
        <?php include_once('includes/header.php');?>

        <div class="breadcrumbs">
            <div class="col-sm-4">
                <div class="page-header float-left">
                    <div class="page-title">
                        <h1>Admin Profile</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="dashboard.php">Dashboard</a></li>
                            <li><a href="adminprofile.php">Admin Profile</a></li>
                            <li class="active">Update</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="content mt-3">
            <div class="animated fadeIn">


                <div class="row">
          
                                            <div class="col-lg-12">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <strong>Admin</strong> Profile
                                                    </div>
                                                    <div class="card-body card-block">
                                                        <form action="" method="post" enctype="multipart/form-data" class="form-horizontal">
                                                            <p style="font-size:16px; color:red" align="center"> <?php if($msg){
    echo $msg;
  }  ?> </p>
                                                           
                                                            <?php
$adminid=$_SESSION['ccmsaid'];
if($con){
    $ret=mysqli_query($con,"select * from tbladmin where ID='$adminid'");
    if($ret && $ret instanceof mysqli_result){
        while ($row=mysqli_fetch_array($ret)) {
?>
                                                            <div class="row form-group">
                                                                <div class="col col-md-3"><label for="text-input" class=" form-control-label">Admin Name</label></div>
                                                                <div class="col-12 col-md-9"><input type="text" id="adminname" name="adminname" class="form-control" value="<?php  echo htmlspecialchars($row['AdminName']);?>"></div>
                                                            </div>
                                                            <div class="row form-group">
                                                                <div class="col col-md-3"><label for="email-input" class=" form-control-label">User Name</label></div>
                                                                <div class="col-12 col-md-9"><input type="text" id="username" name="username" class="form-control" value="<?php  echo htmlspecialchars($row['UserName']);?>" readonly="true"></div>
                                                            </div>
                                                            <div class="row form-group">
                                                                <div class="col col-md-3"><label for="password-input" class=" form-control-label">Contact Number</label></div>
                                                                <div class="col-12 col-md-9"><input type="text" id="mobilenumber" name="mobilenumber" class="form-control" value="<?php  echo htmlspecialchars($row['MobileNumber']);?>"></div>
                                                            </div>
                                                            <div class="row form-group">
                                                                <div class="col col-md-3"><label for="disabled-input" class=" form-control-label">Email</label></div>
                                                                <div class="col-12 col-md-9"><input type="email" id="email" name="email" class="form-control" value="<?php  echo htmlspecialchars($row['Email']);?>"></div>
                                                            </div>
                                                          
                                                            <div class="row form-group">
                                                                <div class="col col-md-3"><label for="disabled-input" class=" form-control-label">Registration Date</label></div>
                                                                <div class="col-12 col-md-9"><input type="text" id="adminregdate" name="adminregdate" class="form-control" value="<?php  echo htmlspecialchars($row['AdminRegdate']);?>" readonly="true"></div>
                                                            </div>
                                                          <?php }}} ?>
                                                     <div class="card-footer">
                                                       <p style="text-align: center;"><button type="submit" name="submit" class="btn btn-primary btn-sm">
                                                            <i class="fa fa-dot-circle-o"></i> Update
                                                        </button></p>
                                                        
                                                    </div>
                                                        </form>
                                                    </div>
                                                    
                                                </div>
                                               
                                            </div>

                                           

                                            </div>
                                        </div><!-- .animated -->
                                    </div><!-- .content -->
                                </div><!-- /#right-panel -->
                                <!-- Right Panel -->


                            <script src="vendors/jquery/dist/jquery.min.js"></script>
                            <script src="vendors/popper.js/dist/umd/popper.min.js"></script>
                            <script src="vendors/bootstrap/dist/js/bootstrap.min.js"></script>
                            <script src="assets/js/main.js"></script>
</body>
</html>
<?php }  ?>
