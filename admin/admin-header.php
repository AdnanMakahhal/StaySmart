
<?php
$adminPage = basename($_SERVER['PHP_SELF']);
$adminNavigation = [
    'admin.php' => ['label' => 'Profile', 'icon' => 'fa-user'],
    'dashboard.php' => ['label' => 'Dashboard', 'icon' => 'fa-chart-line'],
    'addrooms.php' => ['label' => 'Manage rooms', 'icon' => 'fa-bed'],
    'users.php' => ['label' => 'Manage users', 'icon' => 'fa-users'],
    'status.php' => ['label' => 'Bookings', 'icon' => 'fa-calendar-check'],
    'checkedout.php' => ['label' => 'Checked out', 'icon' => 'fa-clock-rotate-left'],
    'content.php' => ['label' => 'Manage content', 'icon' => 'fa-pen-to-square', 'href' => 'content.php?page=home'],
];
$isContentPage = $adminPage === 'content.php';
?>
<nav class="admin-header" aria-label="Admin navigation">
    <button class="admin-menu-btn" type="button" aria-label="Open admin menu" aria-controls="admin-navigation" aria-expanded="false">
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
    </button>

    <div class="profile-links" id="admin-navigation">
        <?php foreach ($adminNavigation as $page => $item): ?>
            <a href="<?php echo htmlspecialchars($item['href'] ?? $page, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $adminPage === $page ? ' class="is-active" aria-current="page"' : ''; ?>>
                <i class="fa-solid <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <a class="admin-logout-link" href="../PHP/logout.php">
        <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
        <span>Log out</span>
    </a>
</nav>

<script>
    (() => {
        const adminHeader = document.querySelector(".admin-header");
        const menuButton = adminHeader?.querySelector(".admin-menu-btn");
        const navigationLinks = adminHeader?.querySelectorAll(".profile-links a");

        if (!adminHeader || !menuButton || !navigationLinks) {
            return;
        }

        const closeMenu = () => {
            adminHeader.classList.remove("menu-open");
            menuButton.setAttribute("aria-expanded", "false");
            menuButton.setAttribute("aria-label", "Open admin menu");
            menuButton.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
        };

        menuButton.addEventListener("click", () => {
            const isOpen = adminHeader.classList.toggle("menu-open");
            menuButton.setAttribute("aria-expanded", String(isOpen));
            menuButton.setAttribute("aria-label", isOpen ? "Close admin menu" : "Open admin menu");
            menuButton.innerHTML = isOpen
                ? '<i class="fa-solid fa-xmark" aria-hidden="true"></i>'
                : '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
        });

        navigationLinks.forEach((link) => link.addEventListener("click", closeMenu));
        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                closeMenu();
            }
        });
        document.addEventListener("click", (event) => {
            if (adminHeader.classList.contains("menu-open") && !adminHeader.contains(event.target)) {
                closeMenu();
            }
        });
        window.addEventListener("resize", () => {
            if (window.matchMedia("(min-width: 1181px)").matches) {
                closeMenu();
            }
        });
    })();
</script>
