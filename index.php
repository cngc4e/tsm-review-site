<?php
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="site.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<?php include 'header.php' ?>
<body>
    <div class="container">
        <div class="row justify-content-center mt-3">
            <h1 class="text-center">Reviews</h1>
        </div>
                <?php foreach ($categories as $cat) { ?>
                    <a class="category-header text-decoration-none link" href="view_category.php?id=<?php echo $cat['category_id'] ?>">
                        <?php echo s($cat['category_name']) ?>
                    </a>
                    <hr />
                    <div class="row justify-content-center">
                        <?php 
                            if ($cat['category_status'] == "Opened" || true) {
                                $cmd = $db->prepare('SELECT * FROM reviews WHERE category_id = :cat_id ORDER BY date_submitted DESC');
                                $cmd->bindValue(':cat_id', $cat['category_id']);
                                $cmd->execute();
                                $reviews = $cmd->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($reviews as $r) {
                                    $cmd = $db->prepare('SELECT AVG(hm_liking), AVG(dm_liking), AVG(hm_diff), AVG(dm_diff) FROM user_reviews WHERE review_id = :rev_id');
                                    $cmd->bindValue(':rev_id', $r['review_id']);
                                    $cmd->execute();
                                    $res = $cmd->fetch(PDO::FETCH_NUM);
                                    $av_hm_liking = $res[0] ? number_format($res[0], 2) : null;
                                    $av_dm_liking = $res[1] ? number_format($res[1], 2) : null;
                                    $av_hm_diff = $res[2] ? number_format($res[2], 2) : "-";
                                    $av_dm_diff = $res[3] ? number_format($res[3], 2) : "-";
                                    
                                    $cmd = $db->prepare('SELECT COUNT(*) FROM user_reviews WHERE review_id = :rev_id AND user_id = :u_id');
                                    $cmd->bindValue(':rev_id', $r['review_id']);
                                    $cmd->bindValue(':u_id', $_SESSION['user_id']);
                                    $cmd->execute();
                                    $has_self_review = $cmd->fetch(PDO::FETCH_NUM)[0] > 0;
                        ?>
                            <div class="review-box col-lg-5 m-3">
                                <a class="stretched-link text-decoration-none link" href="review.php?id=<?php echo $r['review_id']?>">@<?php echo $r['mapcode'] ?></a>
                                <span style="float:right">by <span style="color:#BABD2F"><?php echo $r['mapauthor'] ? s($r['mapauthor']) : "-"; ?></span></span><br>
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
                                <?php if ($has_self_review) {?>
                                    <span class="badge badge-pill badge-success">Reviewed</span>
                                <?php }?>
                            </div>
                        <?php }}?>
                    </div>
                <?php }?>
    </div>
</body>