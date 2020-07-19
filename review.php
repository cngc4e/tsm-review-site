<?php
include_once 'siteutils.php';

session_start();
SiteUtils::cookieSessionRenew();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_REQUEST['id'])) {
    header("Location: index.php");
    exit;
}


$review_id = $_REQUEST['id'];

$db = Database::getInstance();

/* https://medium.com/@andycouch/php-auto-link-function-9aba197e95f7 */
function auto_link($text) {
  $pattern = '/(((http[s]?:\/\/(.+(:.+)?@)?)|(www\.))[a-z0-9](([-a-z0-9]+\.)*\.[a-z]{2,})?\/?[a-z0-9.,_\/~#&=:;%+!?-]+)/is';
  $text = preg_replace($pattern, ' <a href="$1">$1</a>', $text);
  $text = preg_replace('/href="www/', 'href="http://www', $text);
  return $text;
}

switch ($_POST['api_func']) {
    case "editreview": {
        $cmd = $db->prepare('SELECT COUNT(*) FROM user_reviews WHERE user_id = :u_id AND review_id = :r_id');
        $cmd->bindValue(":u_id", $_SESSION['user_id']);
        $cmd->bindValue(":r_id", $review_id);
        $cmd->execute();
        $cnt = $cmd->fetch(PDO::FETCH_NUM)[0];
        if ($cnt > 0) {
            $valid = true;
            foreach (['hm_diff', 'dm_diff', 'hm_liking', 'dm_liking'] as $key) {
                if (isset($_POST[$key]) && $_POST[$key] < 1 && $_POST[$key] > 5) {
                    $editreview_status = '<span style="color:red">Bad range! (1-5)</span>';
                    $valid = false;
                    break;
                }
            }
            if (!$valid)
                break;
                
            // existing record
            $cmd = $db->prepare("UPDATE user_reviews SET
                hm_diff = :hm_diff, dm_diff = :dm_diff, hm_liking = :hm_liking, dm_liking = :dm_liking, comments = :comments, date_updated = CURRENT_TIMESTAMP()
                WHERE user_id = :u_id AND review_id = :r_id");
            $cmd->bindValue(":u_id", $_SESSION['user_id']);
            $cmd->bindValue(":r_id", $review_id);
            $cmd->bindValue(":hm_diff", !empty($_POST['hm_diff']) ? $_POST['hm_diff'] : null);
            $cmd->bindValue(":dm_diff", !empty($_POST['dm_diff']) ? $_POST['dm_diff'] : null);
            $cmd->bindValue(":hm_liking", !empty($_POST['hm_liking']) ? $_POST['hm_liking'] : null);
            $cmd->bindValue(":dm_liking", !empty($_POST['dm_liking']) ? $_POST['dm_liking'] : null);
            $cmd->bindValue(":comments", !empty($_POST['comments']) ? strip_tags($_POST['comments'], "<b><i>") : null);
            if (!$cmd->execute()) {
                $editreview_status = '<span style="color:red">An error occured while editing your review. Check your inputs.</span>';
            } else {
                $editreview_status = '<span style="color:green">Review updated!</span>';
            }
        } else {
            // add new record
            $cmd = $db->prepare("INSERT INTO user_reviews (user_id, review_id, hm_diff, dm_diff, hm_liking, dm_liking, comments)
                VALUES (:u_id, :r_id, :hm_diff, :dm_diff, :hm_liking, :dm_liking, :comments)");
            $cmd->bindValue(":u_id", $_SESSION['user_id']);
            $cmd->bindValue(":r_id", $review_id);
            $cmd->bindValue(":hm_diff", !empty($_POST['hm_diff']) ? $_POST['hm_diff'] : null);
            $cmd->bindValue(":dm_diff", !empty($_POST['dm_diff']) ? $_POST['dm_diff'] : null);
            $cmd->bindValue(":hm_liking", !empty($_POST['hm_liking']) ? $_POST['hm_liking'] : null);
            $cmd->bindValue(":dm_liking", !empty($_POST['dm_liking']) ? $_POST['dm_liking'] : null);
            $cmd->bindValue(":comments", !empty($_POST['comments']) ? strip_tags($_POST['comments'], "<b><i>") : null);
            if (!$cmd->execute()) {
                $editreview_status = '<span style="color:red">An error occured while adding new review. Check your inputs.</span>';
            } else {
                $editreview_status = '<span style="color:green">Review added!</span>';
            }
        }
        DAL::setDiscordShouldSend(true);
        break;
    }
    case "delreview": {
        $cmd = $db->prepare('SELECT COUNT(*) FROM user_reviews WHERE user_id = :u_id AND review_id = :r_id');
        $cmd->bindValue(":u_id", $_SESSION['user_id']);
        $cmd->bindValue(":r_id", $review_id);
        $cmd->execute();
        $cnt = $cmd->fetch(PDO::FETCH_NUM)[0];
        if ($cnt > 0) {
            $cmd = $db->prepare('DELETE FROM user_reviews WHERE user_id = :u_id AND review_id = :r_id');
            $cmd->bindValue(":u_id", $_SESSION['user_id']);
            $cmd->bindValue(":r_id", $review_id);
            if ($cmd->execute())
                $editreview_status = '<span style="color:green">Review deleted!</span>';
            else
                $editreview_status = '<span style="color:red">Failed to delete review.</span>';
        }
        break;
    }
    default:
        break;
}

$cmd = $db->prepare('SELECT * FROM reviews r INNER JOIN categories c ON r.category_id = c.category_id WHERE review_id = :r_id LIMIT 1');
$cmd->bindValue(":r_id", $review_id);
$cmd->execute();
$review = $cmd->fetch(PDO::FETCH_ASSOC);

$cmd = $db->prepare('SELECT AVG(hm_liking), AVG(dm_liking), AVG(hm_diff), AVG(dm_diff) FROM user_reviews WHERE review_id = :rev_id');
$cmd->bindValue(':rev_id', $review_id);
$cmd->execute();
$res = $cmd->fetch(PDO::FETCH_NUM);
$av_hm_liking = $res[0] ? number_format($res[0], 2) : null;
$av_dm_liking = $res[1] ? number_format($res[1], 2) : null;
$av_hm_diff = $res[2] ? number_format($res[2], 2) : "-";
$av_dm_diff = $res[3] ? number_format($res[3], 2) : "-";
                                    
$cmd = $db->prepare('SELECT * FROM user_reviews ur INNER JOIN users u ON ur.user_id = u.user_id WHERE review_id = :r_id');
$cmd->bindValue(":r_id", $review_id);
$cmd->execute();
$user_reviews = $cmd->fetchAll(PDO::FETCH_ASSOC);

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
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
    <script>
        function changeMapcode(e) {
            e = e || event;
            e.preventDefault();
            let mapcode = prompt("Please enter the mapcode.");
            if (mapcode != null) {
                $.post("api_func.php", { api_func: "editcode", review_id: <?php echo $review_id?>, mapcode: mapcode }, function(data) {
                    if (data.status > 0) {
                        location.reload();
                    } else {
                        alert(data.msg);
                    }
                }, "json");
            }
        }
        
        function changeAuthor(e) {
            e = e || event;
            e.preventDefault();
            let author = prompt("Please enter the author.");
            if (author != null) {
                $.post("api_func.php", { api_func: "editauthor", review_id: <?php echo $review_id?>, mapauthor: author }, function(data) {
                    if (data.status > 0) {
                        location.reload();
                    } else {
                        alert(data.msg);
                    }
                }, "json");
            }
        }
        
        function changeCategory(e, id) {
            e = e || event;
            e.preventDefault();
            $.post("api_func.php", { api_func: "changecat", review_id: <?php echo $review_id?>, category_id: id }, function(data) {
                if (data.status > 0) {
                    location.reload();
                } else {
                    alert(data.msg);
                }
            }, "json");
        }
    </script>
</head>
<?php include 'header.php' ?>
<body>
    <div class="container">
        <div style="text-align:center" class="mt-3">
            <span style="font-size: 2.5rem" class="text-center">@<?php echo $review['mapcode'] ?></span> (<a class="link" href="" onclick="changeMapcode(event);">edit</a>)
            <div class="detail">Author: <?php echo $review['mapauthor'] ? s($review['mapauthor']) : "null"; ?> (<a class="link" href="" onclick="changeAuthor(event);">edit</a>) |
                Category: <a class="link" href="view_category.php?id=<?php echo $review['category_id'] ?>"><?php echo s($review['category_name']) ?></a>
                <span class="dropdown">
                    (<a class="link" href="" id="dropdownCategory" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">edit</a>)
                    <div class="dropdown-menu" aria-labelledby="dropdownCategory">
                        <?php
                        $cmd = $db->query('SELECT * FROM categories ORDER BY category_status != "Opened", category_id = 1, category_status');
                        $categories = $cmd->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($categories as $cat) {
                        ?>
                            <a class="dropdown-item<?php if ($cat['category_id']==$review['category_id']) echo ' active'?>" href="" onclick="changeCategory(event, <?php echo $cat['category_id']?>)"><?php echo s($cat['category_name'])?> (<?php echo $cat['category_status']?>)</a>
                        <?php }?>
                    </div>
                </span>
                | Submitted: <?php echo date("d/m/Y", strtotime($review['date_submitted']))?></div>
        </div>
        <div style="text-align: center;">
                HM Avg. Liking:
                <?php if ($av_hm_liking != null) {?>
                <span class="ratings hm-star">
                  <div class="empty-stars"></div>
                  <div class="full-stars" style="width:<?php echo $av_hm_liking/5*100 ?>%"></div>
                </span>
                (<?php echo $av_hm_liking ?>/5)
                <?php } else { echo "-"; } ?><br>
                DM Avg. Liking:
                <?php if ($av_dm_liking != null) {?>
                <span class="ratings dm-star">
                  <div class="empty-stars"></div>
                  <div class="full-stars" style="width:<?php echo $av_dm_liking/5*100 ?>%"></div>
                </span>
                (<?php echo $av_dm_liking ?>/5)
                <?php } else { echo "-"; } ?><br>
                HM Avg. Difficulty: <?php echo $av_hm_diff; ?><br>
                DM Avg. Difficulty: <?php echo $av_dm_diff; ?><br>
        </div>
        <div class="row justify-content-center mt-3">
            <table class="table table-bordered table-review">
                <tr>
                    <th>Reviewer</th>
                    <th>Liking (of 5)</th>
                    <th>Difficulty (of 5)</th>
                    <th width="40%">Comments</th>
                </tr>
                <?php
                foreach ($user_reviews as $r) {
                    $hm_liking = $r['hm_liking'] ? number_format($r['hm_liking'], 2) : "-";
                    $dm_liking = $r['dm_liking'] ? number_format($r['dm_liking'], 2) : "-";
                    $hm_diff = $r['hm_diff'] ? number_format($r['hm_diff'], 2) : "-";
                    $dm_diff = $r['dm_diff'] ? number_format($r['dm_diff'], 2) : "-";
                ?>
                    <tr>
                        <td><?php echo s($r['username']) ?></td>
                        <td>
                            HM: <?php echo $hm_liking ?><br>
                            DM: <?php echo $dm_liking ?>
                        </td>
                        <td>
                            HM: <?php echo $hm_diff ?><br>
                            DM: <?php echo $dm_diff ?>
                        </td>
                        <td><?php echo auto_link(s($r['comments'])) ?></td>
                    </tr>
                <?php }?>
            </table>
        </div>
        
        <?php
            $cmd = $db->prepare('SELECT * FROM user_reviews WHERE user_id = :u_id AND review_id = :r_id');
            $cmd->bindValue(":u_id", $_SESSION['user_id']);
            $cmd->bindValue(":r_id", $review_id);
            $cmd->execute();
            $my_r = $cmd->fetch(PDO::FETCH_ASSOC);
            
            function defVal($v) {
                if ($v != null) {
                    return 'value="'.$v.'"';
                }
            }
        ?>
        
        <div class="row justify-content-center mt-3">
            <h2 class="text-center">Add / Edit My Review</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-sm-12 col-md-8 col-lg-6 px-5 py-5">
                <div class="status-text" style="color:red"><?php echo $editreview_status ?></div>
                <form id="edit" action="" method="POST">
                    <div class="form-group">
                        <label class="mt-3">Liking (of 5)</label><br>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Hard</span>
                            </div>
                            <input type="number" name="hm_liking" class="form-control" min="1" max="5" step="0.1" <?php echo defVal($my_r['hm_liking']) ?>/>
                            <div class="input-group-prepend">
                                <span class="input-group-text">Divine</span>
                            </div>
                            <input type="number" name="dm_liking" class="form-control" min="1" max="5" step="0.1" <?php echo defVal($my_r['dm_liking']) ?>/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="mt-3">Difficulty (of 5)</label><br>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Hard</span>
                            </div>
                            <input type="number" name="hm_diff" class="form-control" min="1" max="5" step="0.1" <?php echo defVal($my_r['hm_diff']) ?>/>
                            <div class="input-group-prepend">
                                <span class="input-group-text">Divine</span>
                            </div>
                            <input type="number" name="dm_diff" class="form-control" min="1" max="5" step="0.1" <?php echo defVal($my_r['dm_diff']) ?>/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="comments" class="mt-3">Comments</label><br>
                        <textarea type="text" name="comments" class="form-control" rows="6"><?php echo $my_r['comments'] ? $my_r['comments'] : "" ?></textarea>
                    </div>
                    <input type="hidden" name="api_func" value="editreview">
                </form>
                <form id="del" action="" method="POST" onsubmit="return confirm('Are you sure you wish to delete your review?');">
                    <input type="hidden" name="api_func" value="delreview">
                </form>
                <input type="submit" value="Submit / Update" form="edit" class="btn btn-light">
                <input type="submit" value="Delete my review" form="del" class="btn btn-link float-right">
            </div>
        </div>
    </div>
</body>