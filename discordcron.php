<?php

require_once "siteutils.php";
require_once "DiscordConn.php";

if (php_sapi_name() != 'cli') {
    //exit;
}

$time_since = DAL::getDiscordShouldSend();

if ($time_since === false) {
    exit;
}

$since_beginning = $time_since === true;

$disc = new DiscordConn(
    Config::$discord_webhook_url,
    Config::$discord_webhook_username,
    Config::$discord_webhook_avatar);

$db = Database::getInstance();

/* new submissions */
$q = 'SELECT COUNT(*) FROM reviews';
if (!$since_beginning) {
    $q .= " WHERE date_submitted > :d_since";
}
$cmd = $db->prepare($q);
$cmd->bindParam(':d_since', $time_since);
$cmd->execute();
$new_submit = $cmd->fetch(PDO::FETCH_NUM)[0];

/* new user reviews */
$q = 'SELECT COUNT(*) FROM user_reviews';
if (!$since_beginning) {
    $q .= " WHERE date_submitted > :d_since";
}
$cmd = $db->prepare($q);
$cmd->bindParam(':d_since', $time_since);
$cmd->execute();
$new_review = $cmd->fetch(PDO::FETCH_NUM)[0];

/* new user reviews updates */
$cmd = $db->prepare('SELECT COUNT(*) FROM user_reviews WHERE date_updated > :d_since');
$cmd->bindParam(':d_since', $time_since);
$cmd->execute();
$updated_review = $cmd->fetch(PDO::FETCH_NUM)[0];

/* require more reviews */
$q = 'SELECT COUNT(*) FROM reviews r WHERE (SELECT COUNT(*) FROM user_reviews ur WHERE r.review_id = ur.review_id) < 3';
$cmd = $db->prepare($q);
$cmd->bindParam(':d_since', $time_since);
$cmd->execute();
$require_review = $cmd->fetch(PDO::FETCH_NUM)[0];

$msg = $disc->DMBuilder()
    ->addEmbed([
        "title" => "New changes since the last time",
        "url" => "https://bagueatt.spr.io/tsmreview/",
        "description" => "",
        /*"author" => [
            "name" => "{$user->getDisplayName()}",
            "url" => "https://bagueatt.spr.io/tsmreview/view-profile.php?id={$u_id}",
            "icon_url" => "https://cdn.discordapp.com/icons/720126472186101761/17622162a493b57be885180d603c1233.png"
        ],*/
        "thumbnail" => [
            "url" => ""
        ],
        "fields" => [
            [
                "name" => "New submissions",
                "value" => "{$new_submit}",
                "inline" => false
            ],
            [
                "name" => "New reviews",
                "value" => "{$new_review}",
                "inline" => false
            ],
            [
                "name" => "Updated reviews",
                "value" => "{$updated_review}",
                "inline" => false
            ],
            [
                "name" => "Require more reviews",
                "value" => "{$require_review}",
                "inline" => false
            ],
        ],
        "color" => 0xe8eb34,
    ]);
$disc->sendMessage($msg);

DAL::setDiscordShouldSend(false);  // updated!
