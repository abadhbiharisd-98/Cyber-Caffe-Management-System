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

    $entryid=mt_rand(100000000, 999999999);
    $cname=$_POST['cname'];
    $uadd=$_POST['uadd'];
    $username=$_POST['username'];
    $mobilenumber=$_POST['mobilenumber'];
    $email=$_POST['email'];
    $idproof=$_POST['idproof'];
   
    $query=mysqli_query($con,"insert into tblusers(EntryID,UserName,UserAddress,MobileNumber,Email,ComputerName,IDProof) value('$entryid','$username','$uadd','$mobilenumber','$email','$cname','$idproof')");
    if ($query) {
    echo "<script>alert('User Detail has been added.');</script>"; 
    echo "<script>window.location.href ='add-users.php'</script>";
  }
  else
    {
       echo "<script>alert('Something Went Wrong. Please try again.');</script>";
    }
}
?>

<!doctype html>
<html class="no-js" lang="en">
<head>
    
    <title>CCMS Add Users</title>
  

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
                        <h1>User Details</h1>
                    </div>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="page-header float-right">
                    <div class="page-title">
                        <ol class="breadcrumb text-right">
                            <li><a href="dashboard.php">Dashboard</a></li>
                            <li><a href="add-users.php">User Details</a></li>
                            <li class="active">Add</li>
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
                                                        <strong>User</strong> Details
                                                    </div>
                                                    <div class="card-body card-block">
                                                        <form action="" method="post" enctype="multipart/form-data" class="form-horizontal">
                                                            <p style="font-size:16px; color:red" align="center"> <?php if($msg){
    echo $msg;
  }  ?> </p>
   
                                                            <div class="row form-group">
                                                                <div class="col col-md-3"><label for="text-input" class=" form-control-label">User Name</label></div>
                                                                <div class="col-12 col-md-9"><input type="text" id="username" name="username" class="form-control" placeholder="User Name" required="true"></div>
                                                            </div>
                                                            <div class="row form-group">
                                                                <div class="col col-md-3"><label for="email-input" class=" form-control-label">User Address</label></div>
                                                                <div class="col-12 col-md-9"><textarea type="text" id="uadd" name="uadd" class="form-control" rows="4" cols="12" required="true"></textarea></div>
                                                            </div>
                                                            <div class="row form-group">
                                                                <div class="col col-md-3"><label for="password-input" class=" form-control-label">Mobile Number</label></div>
                                                                <div class="col-12 col-md-9"><input type="text" id="mobilenumber" name="mobilenumber" class="form-control" maxlength="10" pattern="[0-9]{10}" placeholder="Mobile Number" required="true"></div>
                                                            </div>
                                                            <div class="row form-group">
                                                                <div class="col col-md-3"><label for="password-input" class=" form-control-label">Email Address</label></div>
                                                                <div class="col-12 col-md-9"><input type="email" id="email" name="email" class="form-control" placeholder="Email Address" required="true"></div>
                                                            </div>
                                                            <div class="row form-group">
                                                                <div class="col col-md-3"><label for="password-input" class=" form-control-label">Computer Name</label></div>
                                                                <div class="col-12 col-md-9">
                                                                    <select name="cname" id="cname" class="form-control" required="true">
                                                                        <option value="">Select Computer</option>
                                <?php 
                                if($con){
                                    $query=mysqli_query($con,"select * from tblcomputers");
                                    if($query && $query instanceof mysqli_result){
                                        while($row=mysqli_fetch_array($query)) {
                                ?>
                                <option value="<?php echo htmlspecialchars($row['ComputerName']);?>"><?php echo htmlspecialchars($row['ComputerName']);?></option>
                                <?php }}} ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="row form-group">
                                                                <div class="col col-md-3"><label for="password-input" class=" form-control-label">ID Proof</label></div>
                                                                <div class="col-12 col-md-9"><input type="text" id="idproof" name="idproof" class="form-control" placeholder="ID Proof" required="true"></div>
                                                            </div>
                                                     <div class="card-footer">
                                                       <p style="text-align: center;"><button type="submit" name="submit" class="btn btn-primary btn-sm">
                                                            <i class="fa fa-dot-circle-o"></i> Add
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
