<?php
include_once 'database.php';
include_once 'siteutils.php';
include_once 'config.php';

session_start();

if (isset($_SESSION['user_id'])) {
    SiteUtils::logOut();
}

$username = $_POST['user'];
$password = $_POST['pass'];
$sec_password = $_POST['secpass'];

$login_status = "";

if (isset($_POST['submit'])) {
    if (isset($sec_password) && $sec_password != Config::$secretpass) {
        $register_status = "Wrong secret code. Hacker???";
    } elseif (empty($username) || empty($password)) {
        $register_status = "Empty username or password.";
    } else {
        $db = Database::getInstance();
        $cmd = $db->prepare('SELECT COUNT(*) FROM users WHERE username = :user');
        $cmd->bindValue(':user', $username);
        $cmd->execute();
        $cnt = $cmd->fetch(PDO::FETCH_NUM)[0];
        if ($cnt > 0)
            $register_status = "Username already exists D: Try another.";
        else {
            $cmd = $db->prepare('INSERT INTO users (username, email, passhash, tfm_user, discord_user)
                VALUES (:user, :email, :passh, :tfm, :disc)');
            $cmd->bindValue(':user', $username);
            $cmd->bindValue(':email', !empty($_POST['email']) ? $_POST['email'] : null);
            $cmd->bindValue(':passh', password_hash($password, PASSWORD_DEFAULT));
            $cmd->bindValue(':tfm', !empty($_POST['tfm_user']) ? $_POST['tfm_user'] : null);
            $cmd->bindValue(':disc', !empty($_POST['discord_user']) ? $_POST['discord_user'] : null);
            
            if ($cmd->execute()) {
                $register_status = "Succeed registration.";
            } else {
                $register_status = "Registration failed noob";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="site.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-3">
            <h1 class="text-center">Totally secret staff register page</h1>
        </div>
        <div class="row justify-content-center">
            <div class="col-sm-12 col-md-8 col-lg-6 px-5 py-5">
                <div class="status-text" style="color:red"><?php echo $register_status ?></div>
                <form action="" method="POST">
                    <div class="form-group">
                        <label for="secpass">Secret Pass</label><br>
                        <input type="password" name="secpass" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="user" class="mt-3">Username</label><br>
                        <input type="text" name="user" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="pass" class="mt-3">Password</label><br>
                        <input type="password" name="pass" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email" class="mt-3">Email (optional)</label><br>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="tfm_user" class="mt-3">Transformice Username (optional)</label><br>
                        <input type="text" name="tfm_user" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="discord_user" class="mt-3">Discord Username (optional)</label><br>
                        <input type="text" name="discord_user" class="form-control">
                    </div>
                    <input type="submit" name="submit" value="Register" class="btn btn btn-light mt-4">
                </form>
            </div>
        </div>
    </div>
</body>