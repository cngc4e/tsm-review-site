<?php
include_once 'database.php';
include_once 'siteutils.php';

session_start();
SiteUtils::cookieSessionRenew();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = Database::getInstance();

$errors = array();
if (isset($_POST['submit'])) {
	$todo = array();
	if (!empty($_POST['pass'])){
		if (strlen($_POST['pass']) < 8) {
			$errors[] = "Password must be at least 8 characters long.";
		} else {
			$todo['pass'] = true;
		}
	}
	if (!empty($_POST['user'])) {
		$todo['user'] = true;
	}
	
	if (empty($errors) && count($todo) > 0) {
		if ($todo['pass']) {
			$cmd = $db->prepare("UPDATE users SET passhash = :passh WHERE user_id = :u_id");
			$cmd->bindValue(":u_id", $_SESSION['user_id']);
			$cmd->bindValue(":passh", password_hash($_POST['pass'], PASSWORD_DEFAULT));
			if (!$cmd->execute()) {
			    $errors[] = "Failed to update password.";
			}
		}
		if ($todo['user']) {
			$cmd = $db->prepare("UPDATE users SET username = :user WHERE user_id = :u_id");
			$cmd->bindValue(":u_id", $_SESSION['user_id']);
			$cmd->bindValue(":user", $_POST['user']);
			if (!$cmd->execute()) {
			    $errors[] = "Failed to update username.";
			}
		}
		$cmd = $db->prepare("UPDATE users SET email = :email, tfm_user = :tfm, discord_user = :disc WHERE user_id = :u_id");
		$cmd->bindValue(":u_id", $_SESSION['user_id']);
	    $cmd->bindValue(":email", !empty($_POST['email']) ? $_POST['email'] : null);
	    $cmd->bindValue(":tfm", !empty($_POST['tfm_user']) ? $_POST['tfm_user'] : null);
	    $cmd->bindValue(":disc", !empty($_POST['discord_user']) ? $_POST['discord_user'] : null);
	    if (!$cmd->execute()) {
		    $errors[] = "Failed to update email and TFM/Discord username.";
		}
	}
}

$cmd = $db->prepare("SELECT * from users WHERE user_id = :u_id");
$cmd->bindValue(":u_id", $_SESSION['user_id']);
$cmd->execute();
$user = $cmd->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="site.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<?php include 'header.php' ?>
<body>
    <div class="container">
        <div class="row justify-content-center mt-3">
            <h2 class="text-center">Update Profile</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-sm-12 col-md-8 col-lg-6 px-5 py-5">
                <div class="status-error">
					<?php print(implode("<br>",$errors));?>
                </div>
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="user" class="mt-3">Username</label><br>
                        <input type="text" name="user" class="form-control" value="<?php echo $user['username']?>">
                    </div>
                    <div class="form-group">
                        <label for="pass" class="mt-3">Password</label><br>
                        <input type="password" name="pass" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="email" class="mt-3">Email (optional)</label><br>
                        <input type="email" name="email" class="form-control" value="<?php echo $user['email']?>">
                    </div>
                    <div class="form-group">
                        <label for="tfm_user" class="mt-3">Transformice Username (optional)</label><br>
                        <input type="text" name="tfm_user" class="form-control" value="<?php echo $user['tfm_user']?>">
                    </div>
                    <div class="form-group">
                        <label for="discord_user" class="mt-3">Discord Username (optional)</label><br>
                        <input type="text" name="discord_user" class="form-control" value="<?php echo $user['discord_user']?>">
                    </div>
                    <input type="submit" name="submit" value="Update" class="btn btn btn-light mt-4">
                </form>
            </div>
        </div>
    </div>
</body>
