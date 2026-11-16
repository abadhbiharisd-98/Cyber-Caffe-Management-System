<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');

$msg = "";
if(isset($_POST['login']))
  {
    $adminuser=trim($_POST['username']);
    $password=md5($_POST['password']);
    if ($con) {
        $query=mysqli_query($con,"select ID from tbladmin where UserName='$adminuser' && Password='$password' ");
        
        // If query failed, trigger self-heal schema creation and retry
        if (!$query && isset($db_instance) && method_exists($db_instance, 'selfHealSchema')) {
            $db_instance->selfHealSchema();
            $query=mysqli_query($con,"select ID from tbladmin where UserName='$adminuser' && Password='$password' ");
        }

        if ($query && $query instanceof mysqli_result) {
            $ret=mysqli_fetch_array($query);
            if($ret && isset($ret['ID'])){
                $_SESSION['ccmsaid']=$ret['ID'];
                header('location:dashboard.php');
                exit;
            }
            else{
                $msg="Invalid Details. Default: admin / admin";
            }
        } else {
            $err = mysqli_error($con);
            $msg="Database query error: " . ($err ?: "Please run db_setup.php to verify tables.");
        }
    } else {
        $msg="Database connection not available.";
    }
  }
  ?>
<!doctype html>
<html class="no-js" lang="en">
<head>
    
    <title>CCMS Admin Login</title>
    

    <link rel="apple-touch-icon" href="apple-icon.png">
   


    <link rel="stylesheet" href="vendors/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="vendors/themify-icons/css/themify-icons.css">
    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css">
    <link rel="stylesheet" href="vendors/selectFX/css/cs-skin-elastic.css">

    <link rel="stylesheet" href="assets/css/style.css">

    <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,600,700,800' rel='stylesheet' type='text/css'>



</head>

<body class="bg-dark" style=" background-image: url('images/bg.jpg');">


    <div class="sufee-login d-flex align-content-center flex-wrap" >
        <div class="container">
            <div class="login-content">
                <div class="login-logo">
                    <h3 style="color: white">Cyber Cafe Management System </h3>
                    <hr  color="red"/>
                </div>
                <div class="login-form">
                    <form action="" method="post" name="login">
                        <p style="font-size:16px; color:red" align="center"> <?php if(!empty($msg)){
    echo htmlspecialchars($msg);
  }  ?> </p>
                        <div class="form-group">
                            <label>User Name</label>
                            <input type="text" class="form-control" placeholder="User Name" required="true" name="username">
                        </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" class="form-control" placeholder="Password" name="password" required="true">
                        </div>
                                <div class="checkbox">
                                    <label class="pull-right">
                                <a href="forgot-password.php">Forgot Password?</a>
                            </label>

                                </div>
                                <button type="submit" class="btn btn-success btn-flat m-b-30 m-t-30" name="login">Sign in</button>
                                
                            
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script src="vendors/jquery/dist/jquery.min.js"></script>
    <script src="vendors/popper.js/dist/umd/popper.min.js"></script>
    <script src="vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="assets/js/main.js"></script>


</body>

</html>
