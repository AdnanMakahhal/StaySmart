<?php
$isAdminArea = basename(dirname($_SERVER['PHP_SELF'])) === 'admin';
$sitePrefix = $isAdminArea ? '../' : './';
?>
<footer class="my-footer">
    <div class="footer-main">
        <section class="footer-brand" aria-label="About StaySmart">
            <a class="footer-logo" href="<?php echo $sitePrefix; ?>index.php" aria-label="StaySmart home">
                <span>Stay</span><strong>Smart</strong>
            </a>
            <p>Find a comfortable place to stay and make your next trip feel a little more like home.</p>
            <a class="footer-cta" href="<?php echo $sitePrefix; ?>rooms.php">Explore our rooms <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </section>

        <nav class="footer-column" aria-label="Explore">
            <h2>Explore</h2>
            <ul>
                <li><a href="<?php echo $sitePrefix; ?>index.php">Home</a></li>
                <li><a href="<?php echo $sitePrefix; ?>rooms.php">Rooms</a></li>
                <li><a href="<?php echo $sitePrefix; ?>about.php">About us</a></li>
            </ul>
        </nav>

        <nav class="footer-column" aria-label="Guest services">
            <h2>Guest services</h2>
            <ul>
                <li><a href="<?php echo $sitePrefix; ?>contact.php">Contact us</a></li>
                <li><a href="<?php echo $sitePrefix; ?>login.php">Sign in</a></li>
                <li><a href="<?php echo $sitePrefix; ?>register.php">Create an account</a></li>
            </ul>
        </nav>

        <section class="footer-column footer-contact" aria-labelledby="footer-contact-title">
            <h2 id="footer-contact-title">Need a hand?</h2>
            <p>Have a question about your stay? Get in touch and we’ll be happy to help.</p>
            <a href="<?php echo $sitePrefix; ?>contact.php"><i class="fa-regular fa-envelope" aria-hidden="true"></i> Contact our team</a>
        </section>
    </div>

    <div class="footer-bottom">
        <a class="footer-logo footer-logo-small" href="<?php echo $sitePrefix; ?>index.php" aria-label="StaySmart home">
            <span>Stay</span><strong>Smart</strong>
        </a>
        <p>&copy; <?php echo date('Y'); ?> StaySmart. All rights reserved.</p>
        <a class="back-to-top" href="#top" aria-label="Back to top"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></a>
    </div>
</footer>

<script src="<?php echo $sitePrefix; ?>JS/script.js"></script>
<?php include __DIR__ . '/alerts.php'; ?>

</body>

</html>
