<?php
include_once 'database.php';
include_once 'siteutils.php';

session_start();

if (isset($_SESSION['user_id'])) {
    SiteUtils::logOut();
}

$username = $_POST['user'];
$password = $_POST['pass'];

$login_status = "";

if (isset($username) && isset($password)) {
    $db = Database::getInstance();
    $cmd = $db->prepare('SELECT user_id, username, passhash FROM users WHERE username = :user');
    $cmd->bindParam(':user', $username);
    $cmd->execute();
    
    $row = $cmd->fetch(PDO::FETCH_ASSOC);

    if (password_verify($password, $row['passhash'])) {
        $_SESSION['user_id'] = $row['user_id'];
        SiteUtils::createSetCookieToken();
        header("Location: index.php");
        exit;
	} else {
        $login_status = "Invalid username and/or password.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="site.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-3">
            <h1 class="text-center">Team Shaman Review Site</h1>
        </div>
        <div class="row justify-content-center">
            <div class="col-sm-12 col-md-8 col-lg-6 px-5 py-5">
                <div class="status-text" style="color:red"><?php echo $login_status ?></div>
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="user">Username</label><br>
                        <input type="text" name="user" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="pass" class="mt-3">Password</label><br>
                        <input type="password" name="pass" class="form-control">
                    </div>
                    <input type="submit" value="Login" class="btn btn btn-light mt-4">
                </form>
            </div>
        </div>
    </div>
</body>