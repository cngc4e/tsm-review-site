<?php
include_once 'database.php';
include_once 'siteutils.php';

session_start();
SiteUtils::cookieSessionRenew();

function returnError($msg = null) {
    print(json_encode(array("status" => 0,"msg" => $msg ? $msg : "")));
}

if (!isset($_SESSION['user_id'])) {
    returnError();
    exit;
}

$db = Database::getInstance();

switch ($_POST['api_func']) {
    case "editcode": {
        if (empty($_POST['review_id'])) {
            returnError("No review id!");
            break;
        }
        if (empty($_POST['mapcode'])) {
            returnError("No mapcode!");
            break;
        }
        $review_id = $_POST['review_id'];
        $mapcode = preg_replace('/[^0-9]/', '', $_POST['mapcode']);  // only extract digits
        if (empty($mapcode)) {
            returnError("Invalid mapcode!");
            break;
        }
        $cmd = $db->prepare('UPDATE reviews SET mapcode = :code WHERE review_id = :rev_id');
        $cmd->bindValue(':rev_id', $review_id);
        $cmd->bindValue(':code', $mapcode);
        if (!$cmd->execute()) {
            returnError("Error occured while updating mapcode!");
            break;
        }
        print(json_encode(array("status" => 1, "msg" => "Updated!")));
        break;
    }
    case "editauthor": {
        if (empty($_POST['review_id'])) {
            returnError("No review id!");
            break;
        }
        $author = empty($_POST['mapauthor']) ? null : $_POST['mapauthor'];
        $review_id = $_POST['review_id'];
        $cmd = $db->prepare('UPDATE reviews SET mapauthor = :aut WHERE review_id = :rev_id');
        $cmd->bindValue(':rev_id', $review_id);
        $cmd->bindValue(':aut', $author);
        if (!$cmd->execute()) {
            returnError("Error occured while updating author!");
            break;
        }
        print(json_encode(array("status" => 1, "msg" => "Updated!")));
        break;
    }
    case "changecat": {
        if (empty($_POST['review_id'])) {
            returnError("No review id!");
            break;
        }
        if (empty($_POST['category_id'])) {
            returnError("No review id!");
            break;
        }
        $cmd = $db->prepare('UPDATE reviews SET category_id = :aut WHERE review_id = :rev_id');
        $cmd->bindValue(':rev_id', $_POST['review_id']);
        $cmd->bindValue(':cat_id', $_POST['category_id']);
        if (!$cmd->execute()) {
            returnError("Error occured while updating category!");
            break;
        }
        print(json_encode(array("status" => 1, "msg" => "Updated!")));
        break;
    }
    default:
        returnError("No such function.");
        break;
}
