<?php
$isAdminArea = basename(dirname($_SERVER['PHP_SELF'])) === 'admin';
$sitePrefix = $isAdminArea ? '../' : './';
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$isHomePage = $currentPage === 'index.php';
$isRoomsPage = in_array($currentPage, ['rooms.php', 'room-details.php'], true);
$isAboutPage = $currentPage === 'about.php';
$isContactPage = $currentPage === 'contact.php';
?>
<header class="my-header" id="top">
    <nav class="navbar" aria-label="Main navigation">
        <a class="brand-title" href="<?php echo $sitePrefix; ?>index.php" aria-label="StaySmart home">
            <span class="brand-stay">Stay</span><span class="brand-smart">Smart</span>
        </a>

        <button class="icon menu-btn" type="button" aria-label="Open navigation menu" aria-controls="main-navigation" aria-expanded="false">
            <i class="fas fa-bars" aria-hidden="true"></i>
        </button>

        <ul class="div-links" id="main-navigation">
            <li><a href="<?php echo $sitePrefix; ?>index.php"<?php echo $isHomePage ? ' class="is-active" aria-current="page"' : ''; ?>>Home</a></li>
            <li><a href="<?php echo $sitePrefix; ?>rooms.php"<?php echo $isRoomsPage ? ' class="is-active" aria-current="page"' : ''; ?>>Rooms</a></li>
            <li><a href="<?php echo $sitePrefix; ?>about.php"<?php echo $isAboutPage ? ' class="is-active" aria-current="page"' : ''; ?>>About Us</a></li>
            <li><a href="<?php echo $sitePrefix; ?>contact.php"<?php echo $isContactPage ? ' class="is-active" aria-current="page"' : ''; ?>>Contact</a></li>
        </ul>

        <div class="nav-btns-container">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php
                    $headerUserId = (int) $_SESSION['user_id'];
                    $headerAvatarFile = dirname(__DIR__) . '/Assets/profile-photos/user-' . $headerUserId . '.png';
                    $headerAvatarUrl = is_file($headerAvatarFile)
                        ? $sitePrefix . 'Assets/profile-photos/user-' . $headerUserId . '.png?v=' . filemtime($headerAvatarFile)
                        : $sitePrefix . 'Assets/guest-avatar-default.png';
                ?>
                <a class="user-icon" href="<?php echo $sitePrefix; ?>profile.php">
                    <img class="header-user-avatar" src="<?php echo htmlspecialchars($headerAvatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="" width="28" height="28">
                    <span class="hide-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Account', ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
            <?php elseif (isset($_SESSION['admin_id'])): ?>
                <a class="admin-icon" href="<?php echo $sitePrefix; ?>admin/admin.php">
                    <i class="fa-solid fa-crown" aria-hidden="true"></i>
                    <span class="hide-name"><?php echo htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
            <?php else: ?>
                <div class="div-user">
                    <a class="nav-login" href="<?php echo $sitePrefix; ?>login.php">Login</a>
                    <a class="nav-register" href="<?php echo $sitePrefix; ?>register.php">Register</a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <script>
        const navbar = document.querySelector(".navbar");
        const menuButton = document.querySelector(".menu-btn");
        const navigationLinks = document.querySelectorAll("#main-navigation a");

        if (navbar && menuButton) {
            const closeMenu = () => {
                navbar.classList.remove("show");
                menuButton.setAttribute("aria-expanded", "false");
                menuButton.setAttribute("aria-label", "Open navigation menu");
            };

            menuButton.addEventListener("click", () => {
                const isOpen = navbar.classList.toggle("show");
                menuButton.setAttribute("aria-expanded", String(isOpen));
                menuButton.setAttribute("aria-label", isOpen ? "Close navigation menu" : "Open navigation menu");
                menuButton.innerHTML = isOpen
                    ? '<i class="fas fa-times" aria-hidden="true"></i>'
                    : '<i class="fas fa-bars" aria-hidden="true"></i>';
            });

            navigationLinks.forEach((link) => link.addEventListener("click", closeMenu));
            document.addEventListener("keydown", (event) => {
                if (event.key === "Escape") {
                    closeMenu();
                }
            });
            window.addEventListener("scroll", () => {
                navbar.classList.toggle("sticky", window.scrollY > 10);
            });
        }
    </script>
</header>
