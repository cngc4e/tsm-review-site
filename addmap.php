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

$cmd = $db->query('SELECT * FROM categories ORDER BY category_status != "Opened", category_id = 1, category_status');
$categories = $cmd->fetchAll(PDO::FETCH_ASSOC);

switch ($_POST['api_func']) {
    case "addreview": {
        if (empty($_POST['category_id']) || empty($_POST['mapcode'])) {
            $addreview_status = '<span class="status-error">Bad request.</span>';
            break;
        }
        $mapcode = preg_replace('/[^0-9]/', '', $_POST['mapcode']);  // only extract digits
        if (empty($mapcode)) {
            $addreview_status = '<span class="status-error">Invalid mapcode!</span>';
            break;
        }
        $cmd = $db->prepare('SELECT COUNT(*) FROM reviews WHERE mapcode = :code AND category_id = :c_id');
        $cmd->bindValue(":c_id", $_POST['category_id']);
        $cmd->bindValue(":code", $mapcode);
        $cmd->execute();
        $cnt = $cmd->fetch(PDO::FETCH_NUM)[0];
        if ($cnt > 0) {
            $addreview_status = '<span class="status-success">A map under this category of review already exists.</span>';
        } else {
            $cmd = $db->prepare("INSERT INTO reviews (mapcode, category_id, user_id)
                VALUES (:code, :c_id, :u_id)");
            $cmd->bindValue(":u_id", $_SESSION['user_id']);
            $cmd->bindValue(":c_id", $_POST['category_id']);
            $cmd->bindValue(":code", $mapcode);
            if (!$cmd->execute()) {
                $addreview_status = '<span class="status-error">An error occured while adding new map. Check your inputs.</span>';
            } else {
                $addreview_status = '<span class="status-success">Map added!</span>';
            }
        }
        break;
    }
    default:
        break;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review: </title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="site.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
</head>
<?php include 'header.php' ?>
<body>
    <div class="container">
        <div class="row justify-content-center mt-3">
            <h2 class="text-center">Add Maps</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-sm-12 col-md-8 col-lg-6 px-5 py-5">
                <div class="status-text"><?php echo $addreview_status ?></div>
                <form id="edit" action="" method="POST">
                    <div class="form-group">
                        <label for="category_id">Select category</label>
                            <select class="form-control" name="category_id">
                            <?php
                            foreach ($categories as $cat) {
                            ?>
                                <option value="<?php echo $cat['category_id']?>"><?php echo $cat['category_name']?> (<?php echo $cat['category_status']?>)</option>
                            <?php }?>
                            </select>
                    </div>
                    <div class="form-group">
                        <label class="mt-3">Mapcode</label><br>
                        <input type="text" name="mapcode" class="form-control" required />
                    </div>
                    <input type="hidden" name="api_func" value="addreview">
                    <input type="submit" value="Add map" class="btn btn-light">
                </form>
            </div>
        </div>
    </div>
</body>