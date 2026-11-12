<!DOCTYPE html>
<html lang="en">
<?php 
session_start();
include('./db_connect.php');
ob_start();

if (isset($conn) && $conn instanceof mysqli) {
	$system_qry = $conn->query("SELECT * FROM system_settings limit 1");
	if ($system_qry && $system_qry instanceof mysqli_result && $system_qry->num_rows > 0) {
		$system = $system_qry->fetch_array();
		foreach ($system as $k => $v) {
			$_SESSION['system'][$k] = $v;
		}
	}
}

if (!isset($_SESSION['system']['name']) || empty($_SESSION['system']['name'])) {
	$_SESSION['system']['name'] = 'Train Station Ticketing System';
}
ob_end_flush();
?>
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title><?php echo htmlspecialchars($_SESSION['system']['name']); ?></title>

<?php include('./header.php'); ?>
<?php 
if (isset($_SESSION['login_id'])) {
	header("location:index.php?page=home");
	exit;
}
?>

</head>
<style>
	body{
		width: 100%;
	    height: calc(100%);
	    position: fixed;
	    top:0;
	    left: 0;
	}
	main#main{
		width:100%;
		height: calc(100%);
		display: flex;
	}
	.setup-link {
		position: absolute;
		bottom: 15px;
		right: 20px;
		color: rgba(255,255,255,0.6);
		font-size: 13px;
		text-decoration: none;
	}
	.setup-link:hover {
		color: #fff;
		text-decoration: underline;
	}
</style>

<body class="bg-dark">

  <main id="main" >
  		<div class="align-self-center w-100">
		<h4 class="text-white text-center"><b><?php echo htmlspecialchars($_SESSION['system']['name']); ?></b></h4>
  		<div id="login-center" class="bg-dark row justify-content-center">
  			<div class="card col-md-4">
  				<div class="card-body">
  					<form id="login-form" >
  						<div class="form-group">
  							<label for="username" class="control-label">Username</label>
  							<input type="text" id="username" name="username" class="form-control" required autocomplete="username">
  						</div>
  						<div class="form-group">
  							<label for="password" class="control-label">Password</label>
  							<input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
  						</div>
  						<center><button type="submit" class="btn-sm btn-block btn-wave col-md-4 btn-primary">Login</button></center>
  					</form>
  				</div>
  			</div>
  		</div>
  		</div>
  </main>

  <a href="db_setup.php" class="setup-link"><i class="fa fa-database"></i> Database Installer</a>
  <a href="#" class="back-to-top"><i class="icofont-simple-up"></i></a>

</body>
<script>
	$('#login-form').submit(function(e){
		e.preventDefault()
		$('#login-form button[type="submit"]').attr('disabled',true).html('Logging in...');
		if($(this).find('.alert-danger').length > 0 )
			$(this).find('.alert-danger').remove();
		$.ajax({
			url:'ajax.php?action=login',
			method:'POST',
			data:$(this).serialize(),
			error:err=>{
				console.log(err)
				$('#login-form button[type="submit"]').removeAttr('disabled').html('Login');
				$('#login-form').prepend('<div class="alert alert-danger">An error occurred while connecting. Please try again.</div>')
			},
			success:function(resp){
				if(resp > -1){
					if(resp == 0)
						location.href ='index.php?page=home';
					else
						location.href ='ticketing/index.php';
				}else{
					$('#login-form').prepend('<div class="alert alert-danger">Username or password is incorrect.</div>')
					$('#login-form button[type="submit"]').removeAttr('disabled').html('Login');
				}
			}
		})
	})
</script>
</html>
