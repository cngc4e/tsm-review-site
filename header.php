<header>
    <div class="container">
    <?php
    include_once 'siteutils.php';
    
    if (isset($_SESSION['user_id'])) {
        $user = DAL::getUser($_SESSION['user_id']);
    ?>
        
        <div class="text-center text-sm-right">
            Logged in as <?php echo $user->getDisplayName() ?>
            <br>
            <a href="index.php">All Ongoing Reviews</a> |
            <a href="addmap.php">Add Maps</a> |
            <a href="profile.php">Profile</a> |
            <a href="logout.php">Log out</a>
        </div>
    
    <?php }?>

    </div>
</header>