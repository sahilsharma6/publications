<?php
session_start();

// Auth guards
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Allow both admin and SuperAdmin
$allowedRoles = ['admin', 'SuperAdmin'];
if (!in_array($_SESSION['role'] ?? '', $allowedRoles, true)) {
    header("Location: ./");
    exit();
}

$role = htmlspecialchars($_SESSION['role'] ?? 'User', ENT_QUOTES);
$username = htmlspecialchars($_SESSION['username'] ?? 'Guest', ENT_QUOTES);

// ── Fetch quick stats (replace with real DB queries) ──────────────────────────
// Example: $totalBooks = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
$stats = [
    ['label' => 'Total Books', 'value' => '1,284', 'change' => '+12 this week', 'icon' => 'bx-book-alt', 'color' => 'blue'],
    ['label' => 'Categories', 'value' => '38', 'change' => '+2 new', 'icon' => 'bx-collection', 'color' => 'green'],
    ['label' => 'Publishers', 'value' => '56', 'change' => '+1 this month', 'icon' => 'bx-printer', 'color' => 'orange'],
    ['label' => 'Pending Comments', 'value' => '14', 'change' => 'Need review', 'icon' => 'bx-chat', 'color' => 'red', 'changeClass' => 'down'],
];

$quickLinks = [
    ['href' => 'AddBooks.php', 'label' => 'Add Book', 'icon' => 'bx-plus-circle'],
    ['href' => 'AddCategories.php', 'label' => 'Add Category', 'icon' => 'bx-folder-plus'],
    ['href' => 'add_publishing.php', 'label' => 'Add Publisher', 'icon' => 'bx-printer'],
    ['href' => 'AllBooks.php', 'label' => 'View Books', 'icon' => 'bx-list-ul'],
];

$recentActivity = [
    ['color' => 'blue', 'text' => 'New book "Design Patterns" added', 'time' => '2m ago'],
    ['color' => 'green', 'text' => 'Category "Science" updated', 'time' => '18m ago'],
    ['color' => 'orange', 'text' => 'Publisher "O\'Reilly" registered', 'time' => '1h ago'],
    ['color' => 'blue', 'text' => '3 new book images uploaded', 'time' => '3h ago'],
    ['color' => 'green', 'text' => 'Comment approved on "Clean Code"', 'time' => '5h ago'],
];

$hour = (int) date('H');
$greeting = match (true) {
    $hour < 12 => 'Good morning',
    $hour < 17 => 'Good afternoon',
    default => 'Good evening',
};
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — BookAdmin</title>
    <link rel="stylesheet" href="./sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css">
    <!-- Bootstrap only where truly needed (grid/utilities) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>

    <?php include './sidebar.php'; ?>

    <!-- ── Welcome Banner ─────────────────────────------- -->
    <div class="welcome-banner">
        <div class="welcome-text">
            <h2>
                <?= $greeting ?>,
                <?= $username ?> 👋
            </h2>
            <p>Here's what's happening in your Publisher today.</p>
        </div>
        <span class="welcome-role">
            <?= $role ?>
        </span>
    </div>

    <!-- ── Stats Grid ────────────────────────────── -->
    <div class="stats-grid">
        <?php foreach ($stats as $stat): ?>
            <div class="stat-card">
                <div class="stat-icon <?= htmlspecialchars($stat['color']) ?>">
                    <i class="bx <?= htmlspecialchars($stat['icon']) ?>"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-label">
                        <?= htmlspecialchars($stat['label']) ?>
                    </div>
                    <div class="stat-value">
                        <?= htmlspecialchars($stat['value']) ?>
                    </div>
                    <div class="stat-change <?= htmlspecialchars($stat['changeClass'] ?? '') ?>">
                        <?= htmlspecialchars($stat['change']) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Content Grid ──────────────────────────── -->
    <div class="content-grid">

        <!-- Quick Actions -->
        <div class="content-card">
            <div class="content-card-header">
                <span class="content-card-title">Quick Actions</span>
            </div>
            <div class="quick-links">
                <?php foreach ($quickLinks as $link): ?>
                    <a href="<?= htmlspecialchars($link['href']) ?>" class="quick-link">
                        <i class="bx <?= htmlspecialchars($link['icon']) ?>"></i>
                        <?= htmlspecialchars($link['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="content-card">
            <div class="content-card-header">
                <span class="content-card-title">Recent Activity</span>
                <a href="activity.php" class="view-all">View all</a>
            </div>
            <div class="activity-list">
                <?php foreach ($recentActivity as $item): ?>
                    <div class="activity-item">
                        <span class="activity-dot <?= htmlspecialchars($item['color']) ?>"></span>
                        <span class="activity-text">
                            <?= htmlspecialchars($item['text']) ?>
                        </span>
                        <span class="activity-time">
                            <?= htmlspecialchars($item['time']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <?php
    // Close the dash-content + home-section divs opened in sidebar.php
    echo '    </div><!-- /.dash-content -->';
    echo '</section><!-- /.home-section -->';
    ?>



</body>

</html>