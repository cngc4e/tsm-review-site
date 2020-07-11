<header>
    <div class="container">
    <?php
    include_once 'siteutils.php';
    
    if (isset($_SESSION['user_id'])) {
        $db = Database::getInstance();
        $cmd = $db->prepare('SELECT username, tfm_user FROM users WHERE user_id = :id');
        $cmd->bindParam(':id', $_SESSION['user_id']);
        $cmd->execute();
        
        $row = $cmd->fetch(PDO::FETCH_ASSOC);
    ?>
        
        <div class="text-center text-sm-right">
            Logged in as <?php echo s($row['username']) ?>
            <?php if ($row['tfm_user']) echo "(".s($row['tfm_user']).")" ?>
            <br>
            <a href="index.php">All Ongoing Reviews</a> |
            <a href="addmap.php">Add Maps</a> |
            <a href="profile.php">Profile</a> |
            <a href="logout.php">Log out</a>
        </div>
    
    <?php }?>

    </div>
</header>