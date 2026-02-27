<?php
// Ensure session is active before accessing session vars
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = htmlspecialchars($_SESSION['role'] ?? 'User', ENT_QUOTES);
$username = htmlspecialchars($_SESSION['username'] ?? 'Guest', ENT_QUOTES);

// Initials for avatar
$initials = strtoupper(substr($username, 0, 1));

// Current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);

/**
 * Renders a single nav item (with optional sub-links)
 */
function navItem(string $icon, string $label, array $subLinks = [], string $currentPage = ''): void
{
    $hasChildren = count($subLinks) > 1 || isset($subLinks[0]['label']);

    if (!$hasChildren) {
        // Flat link — sub-links[0] is the href
        $href = $subLinks[0]['href'] ?? '#';
        $isActive = ($currentPage === basename($href)) ? ' active' : '';
        echo '<li class="' . trim($isActive) . '">';
        echo '  <a href="' . htmlspecialchars($href) . '">';
        echo '    <i class="bx ' . htmlspecialchars($icon) . ' nav-icon"></i>';
        echo '    <span class="link_name">' . htmlspecialchars($label) . '</span>';
        echo '  </a>';
        // blank sub-menu for collapsed hover label
        echo '  <ul class="sub-menu blank">';
        echo '    <li><a class="link_name" href="' . htmlspecialchars($href) . '">' . htmlspecialchars($label) . '</a></li>';
        echo '  </ul>';
        echo '</li>';
        return;
    }

    // Check if any child is active
    $isActive = '';
    foreach ($subLinks as $link) {
        if ($currentPage === basename($link['href'])) {
            $isActive = ' active showMenu';
            break;
        }
    }

    echo '<li class="' . trim($isActive) . '">';
    echo '  <div class="iocn-link">';
    echo '    <a href="#">';
    echo '      <i class="bx ' . htmlspecialchars($icon) . ' nav-icon"></i>';
    echo '      <span class="link_name">' . htmlspecialchars($label) . '</span>';
    echo '    </a>';
    echo '    <i class="bx bxs-chevron-down arrow"></i>';
    echo '  </div>';
    echo '  <ul class="sub-menu">';
    // First item = header label for collapsed flyout
    echo '    <li><a class="link_name" href="#">' . htmlspecialchars($label) . '</a></li>';
    foreach ($subLinks as $link) {
        $activeCls = ($currentPage === basename($link['href'])) ? ' style="color:#63b3ed;opacity:1;"' : '';
        echo '    <li><a href="' . htmlspecialchars($link['href']) . '"' . $activeCls . '>' . htmlspecialchars($link['label']) . '</a></li>';
    }
    echo '  </ul>';
    echo '</li>';
}
?>

<div class="sidebar close">

    <!-- Logo -->
    <div class="logo-details">
        <div class="logo-icon">
            <i class='bx bx-book-heart'></i>
        </div>
        <span class="logo_name">BookAdmin</span>
    </div>

    <!-- Nav -->
    <ul class="nav-links">

        <li class="nav-section-label">Main</li>

        <?php navItem('bx-grid-alt', 'Dashboard', [['href' => './']], $currentPage); ?>

        <li class="nav-section-label">Catalog</li>

        <?php navItem('bx-collection', 'Categories', [
            ['href' => 'AllCategories.php', 'label' => 'All Categories'],
            ['href' => 'AddCategory.php', 'label' => 'Add Category'],
        ], $currentPage); ?>

        <?php navItem('bx-book-alt', 'Books', [
            ['href' => 'AllBooks.php', 'label' => 'All Books'],
            ['href' => 'AddBooks.php', 'label' => 'Add Book'],
            ['href' => 'add_book_images.php', 'label' => 'Add Images'],
            ['href' => 'manage_book_images.php', 'label' => 'Manage Images'],
        ], $currentPage); ?>

        <?php navItem('bx-printer', 'Publishing', [
            ['href' => 'add_publishing.php', 'label' => 'Add Publishing'],
        ], $currentPage); ?>

        <li class="nav-section-label">System</li>

        <?php navItem('bx-cog', 'Settings', [
            ['href' => 'add_logo.php', 'label' => 'Add Logo'],
            ['href' => 'change_password.php', 'label' => 'Change Password'],
        ], $currentPage); ?>

        <?php if ($role === 'SuperAdmin'): ?>

            <?php navItem('bx-user', 'Users', [
                ['href' => 'manage.php', 'label' => 'Manage Users'],
                ['href' => 'register.php', 'label' => 'Register User'],
                ['href' => 'Allcomments.php', 'label' => 'Comments'],
            ], $currentPage); ?>

            <?php navItem('bx-globe', 'Website Settings', [['href' => 'Settings.php']], $currentPage); ?>

        <?php endif; ?>

        <!-- Logout -->
        <li class="logout-item" style="margin-top: auto;">
            <a href="../../logout.php">
                <i class='bx bx-log-out nav-icon'></i>
                <span class="link_name">Logout</span>
            </a>
            <ul class="sub-menu blank">
                <li><a class="link_name" href="../../logout.php">Logout</a></li>
            </ul>
        </li>

    </ul>

    <!-- Profile Footer -->
    <div class="profile-details">
        <div class="profile-avatar"><?= $initials ?></div>
        <div class="profile-info">
            <div class="profile_name"><?= $username ?></div>
            <div class="job"><?= $role ?></div>
        </div>
        <a href="../../logout.php" class="profile-logout" title="Logout">
            <i class='bx bx-log-out'></i>
        </a>
    </div>
</div>

<!-- ── Sidebar JavaScript (runs on every page that includes sidebar.php) ── -->
<script>
    (function () {
        function initSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.getElementById('sidebarToggle');
            const topTitle = document.getElementById('topbarTitle');

            if (!sidebar || !toggle) return;

            /* ── Persist collapsed state across pages ── */
            if (localStorage.getItem('sidebarClosed') === '1') {
                sidebar.classList.add('close');
            } else {
                sidebar.classList.remove('close');
            }

            /* ── Toggle button ── */
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('close');
                localStorage.setItem('sidebarClosed',
                    sidebar.classList.contains('close') ? '1' : '0');
            });

            /* ── Arrow click — toggle sub-menu ── */
            sidebar.querySelectorAll('.arrow').forEach(arrow => {
                arrow.addEventListener('click', e => {
                    e.stopPropagation();
                    const li = e.currentTarget.closest('li');
                    if (li) li.classList.toggle('showMenu');
                });
            });

            /* ── iocn-link row click — also toggle sub-menu ── */
            sidebar.querySelectorAll('.iocn-link').forEach(row => {
                row.addEventListener('click', () => {
                    const li = row.closest('li');
                    if (li) li.classList.toggle('showMenu');
                });
            });

            /* ── Highlight active link by current filename ── */
            const currentFile = window.location.pathname.split('/').pop() || 'index.php';
            sidebar.querySelectorAll('a[href]').forEach(a => {
                const linkFile = (a.getAttribute('href') || '').split('/').pop();
                if (!linkFile || linkFile === '#') return;
                if (linkFile === currentFile) {
                    const li = a.closest('li');
                    const parent = a.closest('ul.sub-menu')?.closest('li');
                    if (li) li.classList.add('active');
                    if (parent) {
                        parent.classList.add('showMenu');
                        // update topbar title to the parent group name
                        const groupName = parent.querySelector('.iocn-link .link_name');
                        if (topTitle && groupName) topTitle.textContent = groupName.textContent.trim();
                    }
                    // also set title from the clicked link itself
                    const linkName = a.querySelector('.link_name');
                    const txt = linkName ? linkName.textContent.trim() : a.textContent.trim();
                    if (topTitle && txt && txt !== '#') topTitle.textContent = txt;
                }
            });

            /* ── Update topbar title on nav click ── */
            sidebar.querySelectorAll('a[href]:not([href="#"])').forEach(a => {
                a.addEventListener('click', () => {
                    const nameEl = a.querySelector('.link_name');
                    const name = nameEl ? nameEl.textContent.trim() : a.textContent.trim();
                    if (topTitle && name) topTitle.textContent = name;
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSidebar);
        } else {
            initSidebar();
        }
    })();
</script>

<!-- Topbar -->
<section class="home-section">
    <div class="home-content">
        <div class="topbar-left">
            <button class="menu-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class='bx bx-menu'></i>
            </button>
            <span class="topbar-title" id="topbarTitle">Dashboard</span>
        </div>
        <div class="topbar-right">
            <!-- <div class="topbar-badge">
                <i class='bx bx-bell'></i>
                <span class="dot"></span>
            </div> -->
            <!-- <div class="topbar-badge">
                <i class='bx bx-search'></i>
            </div> -->
            <div class="topbar-user">
                <div class="topbar-avatar"><?= $initials ?></div>
                <span class="topbar-username"><?= $username ?></span>
            </div>
        </div>
    </div>

    <!-- Page content injected here -->
    <div class="dash-content" id="pageContent">