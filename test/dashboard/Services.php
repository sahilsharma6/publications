<?php
/**
 * Services.php — Admin: Manage Homepage Services
 *
 * ── REQUIRED TABLE ─────────────────────────────────────────────
 * CREATE TABLE IF NOT EXISTS services (
 *   id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *   icon        VARCHAR(100) NOT NULL DEFAULT 'fa-solid fa-star',
 *   title       VARCHAR(150) NOT NULL,
 *   description TEXT,
 *   sort_order  SMALLINT     NOT NULL DEFAULT 0,
 *   is_active   TINYINT(1)   NOT NULL DEFAULT 1,
 *   created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 * ───────────────────────────────────────────────────────────────
 */

session_start();
include '../../db.php';

/* ── Auth ─────────────────────────────────────────────────────── */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'SuperAdmin'], true)) {
    header('Location: login.php');
    exit();
}

$toast = null;
$errors = [];
$editService = null;

/* ══════════════════════════════════════════════════════════════
   HANDLE POST ACTIONS
══════════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ── REORDER (AJAX) ── */
    if ($action === 'reorder') {
        $order = json_decode($_POST['order'] ?? '[]', true);
        if (is_array($order)) {
            foreach ($order as $i => $sid) {
                $pos = (int) $i;
                $sid = (int) $sid;
                $conn->query("UPDATE services SET sort_order = $pos WHERE id = $sid");
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        $conn->close();
        exit();
    }

    /* ── ADD ── */
    if ($action === 'add') {
        $icon = trim($_POST['icon'] ?? 'fa-solid fa-star');
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $active = isset($_POST['is_active']) ? 1 : 0;

        if ($title === '')
            $errors['title'] = 'Title is required.';
        elseif (mb_strlen($title) > 150)
            $errors['title'] = 'Title must be 150 chars or fewer.';

        if (empty($errors)) {
            $so = (int) $conn->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM services")->fetch_row()[0];
            $ins = $conn->prepare("INSERT INTO services (icon, title, description, sort_order, is_active) VALUES (?,?,?,?,?)");
            $ins->bind_param("sssii", $icon, $title, $desc, $so, $active);
            if ($ins->execute()) {
                $toast = ['type' => 'success', 'msg' => "Service \"{$title}\" added."];
            } else {
                $toast = ['type' => 'error', 'msg' => 'Database error: ' . $conn->error];
            }
            $ins->close();
        }
    }

    /* ── EDIT ── */
    if ($action === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $icon = trim($_POST['icon'] ?? 'fa-solid fa-star');
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $active = isset($_POST['is_active']) ? 1 : 0;

        if ($id <= 0)
            $errors['id'] = 'Invalid service.';
        if ($title === '')
            $errors['title'] = 'Title is required.';
        elseif (mb_strlen($title) > 150)
            $errors['title'] = 'Title must be 150 chars or fewer.';

        if (empty($errors)) {
            $upd = $conn->prepare("UPDATE services SET icon=?, title=?, description=?, is_active=? WHERE id=?");
            $upd->bind_param("sssii", $icon, $title, $desc, $active, $id);
            if ($upd->execute()) {
                $toast = ['type' => 'success', 'msg' => 'Service updated successfully.'];
            } else {
                $toast = ['type' => 'error', 'msg' => 'Database error: ' . $conn->error];
            }
            $upd->close();
        } else {
            $editService = ['id' => $id, 'icon' => $icon, 'title' => $title, 'description' => $desc, 'is_active' => $active];
        }
    }

    /* ── DELETE ── */
    if ($action === 'delete') {
        $id = (int) ($_POST['delete_id'] ?? 0);
        if ($id > 0) {
            $del = $conn->prepare("DELETE FROM services WHERE id=?");
            $del->bind_param("i", $id);
            $toast = $del->execute()
                ? ['type' => 'success', 'msg' => 'Service deleted.']
                : ['type' => 'error', 'msg' => 'Failed to delete.'];
            $del->close();
        }
    }

    /* ── TOGGLE ── */
    if ($action === 'toggle') {
        $id = (int) ($_POST['toggle_id'] ?? 0);
        if ($id > 0)
            $conn->query("UPDATE services SET is_active = 1 - is_active WHERE id = $id");
    }
}

/* ── Load edit service from GET ──────────────────────────────── */
if (!$editService && isset($_GET['edit'])) {
    $eid = (int) $_GET['edit'];
    $es = $conn->prepare("SELECT * FROM services WHERE id=?");
    $es->bind_param("i", $eid);
    $es->execute();
    $editService = $es->get_result()->fetch_assoc();
    $es->close();
}

/* ── Fetch all services ──────────────────────────────────────── */
$svcRes = $conn->query("SELECT * FROM services ORDER BY sort_order ASC, id ASC");
$services = $svcRes ? $svcRes->fetch_all(MYSQLI_ASSOC) : [];
$totalActive = count(array_filter($services, fn($s) => $s['is_active']));
$totalInactive = count($services) - $totalActive;

$conn->close();

/* ── Icon library ────────────────────────────────────────────── */
$iconOptions = [
    'fa-solid fa-book-open' => 'Book Open',
    'fa-solid fa-magnifying-glass' => 'Search',
    'fa-solid fa-pen-nib' => 'Pen Nib',
    'fa-solid fa-file-signature' => 'File Signature',
    'fa-solid fa-star' => 'Star',
    'fa-solid fa-award' => 'Award',
    'fa-solid fa-flask' => 'Flask',
    'fa-solid fa-microscope' => 'Microscope',
    'fa-solid fa-stethoscope' => 'Stethoscope',
    'fa-solid fa-graduation-cap' => 'Graduation Cap',
    'fa-solid fa-globe' => 'Globe',
    'fa-solid fa-chart-line' => 'Chart Line',
    'fa-solid fa-lightbulb' => 'Lightbulb',
    'fa-solid fa-handshake' => 'Handshake',
    'fa-solid fa-shield-halved' => 'Shield',
    'fa-solid fa-users' => 'Users',
    'fa-solid fa-clipboard-check' => 'Clipboard Check',
    'fa-solid fa-rocket' => 'Rocket',
    'fa-solid fa-gear' => 'Gear',
    'fa-solid fa-envelope' => 'Envelope',
    'fa-solid fa-phone' => 'Phone',
    'fa-solid fa-comments' => 'Comments',
    'fa-solid fa-bullhorn' => 'Bullhorn',
    'fa-solid fa-trophy' => 'Trophy',
];

$currentIcon = $editService['icon'] ?? 'fa-solid fa-star';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Manager — BookAdmin</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.1/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <style>
        @keyframes shake {

            0%,
            100% {
                transform: translateX(0)
            }

            20% {
                transform: translateX(-5px)
            }

            40% {
                transform: translateX(5px)
            }

            60% {
                transform: translateX(-3px)
            }

            80% {
                transform: translateX(3px)
            }
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--gray-900);
            letter-spacing: -.3px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12.5px;
            color: var(--gray-400);
            margin-top: 6px;
        }

        .breadcrumb a {
            color: var(--gray-400);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: color var(--t);
        }

        .breadcrumb a:hover {
            color: var(--accent);
        }

        /* SQL banner */
        .sql-banner {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: var(--r);
            padding: 12px 16px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .sql-banner i {
            color: #d97706;
            font-size: 17px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .sql-banner code {
            display: block;
            margin-top: 6px;
            font-size: 11px;
            background: rgba(0, 0, 0, .06);
            padding: 6px 10px;
            border-radius: 4px;
            font-family: "DM Mono", monospace;
            color: #78350f;
            white-space: pre;
            overflow-x: auto;
        }

        .sql-dismiss {
            margin-left: auto;
            font-size: 16px;
            color: #d97706;
            cursor: pointer;
            background: none;
            border: none;
            flex-shrink: 0;
        }

        /* Summary */
        .sum-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 22px;
        }

        .sum-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-lg);
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-sm);
            transition: transform var(--t), box-shadow var(--t);
        }

        .sum-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .sum-icon {
            width: 42px;
            height: 42px;
            border-radius: var(--r);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
        }

        .si-teal {
            background: #ecfdf5;
            color: #10b981;
        }

        .si-blue {
            background: var(--accent-light);
            color: var(--accent);
        }

        .si-red {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .sum-val {
            font-size: 26px;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1;
        }

        .sum-label {
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 2px;
        }

        /* Layout */
        .page-grid {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 20px;
            align-items: start;
        }

        @media(max-width:920px) {
            .page-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Main card */
        .main-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-toolbar {
            padding: 14px 20px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .card-toolbar-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-800);
            flex: 1;
        }

        .drag-hint {
            font-size: 12px;
            color: var(--gray-400);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Service rows */
        .svc-list {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .svc-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r-lg);
            background: #fff;
            transition: all var(--t);
            position: relative;
        }

        .svc-row:hover {
            border-color: var(--gray-300);
            box-shadow: var(--shadow-sm);
        }

        .svc-row.inactive {
            opacity: .55;
            background: var(--gray-50);
        }

        .svc-row.dragging {
            opacity: .35;
        }

        .svc-row.drag-over {
            border-color: var(--accent);
            background: var(--accent-light);
        }

        .drag-handle {
            color: var(--gray-300);
            font-size: 18px;
            cursor: grab;
            flex-shrink: 0;
            transition: color var(--t);
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        .svc-row:hover .drag-handle {
            color: var(--gray-500);
        }

        .svc-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--gray-100);
            color: var(--gray-500);
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-family: "DM Mono", monospace;
        }

        .svc-icon-bubble {
            width: 44px;
            height: 44px;
            border-radius: var(--r);
            background: var(--accent-light);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
            transition: all var(--t);
        }

        .svc-row.inactive .svc-icon-bubble {
            background: var(--gray-100);
            color: var(--gray-400);
        }

        .svc-body {
            flex: 1;
            min-width: 0;
        }

        .svc-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .svc-desc {
            font-size: 12.5px;
            color: var(--gray-500);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .status-pill {
            padding: 3px 10px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .status-active {
            background: #ecfdf5;
            color: #065f46;
        }

        .status-inactive {
            background: var(--gray-100);
            color: var(--gray-500);
        }

        .svc-actions {
            display: flex;
            gap: 6px;
            flex-shrink: 0;
        }

        .act-btn {
            width: 32px;
            height: 32px;
            border-radius: var(--r-sm);
            border: 1.5px solid transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            cursor: pointer;
            transition: all var(--t);
            background: transparent;
            text-decoration: none;
        }

        .act-edit {
            background: var(--accent-light);
            color: var(--accent);
            border-color: rgba(59, 130, 246, .2);
        }

        .act-edit:hover {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
            transform: scale(1.08);
        }

        .act-toggle {
            background: #ecfdf5;
            color: #10b981;
            border-color: rgba(16, 185, 129, .2);
        }

        .act-toggle:hover {
            background: #10b981;
            color: #fff;
            border-color: #10b981;
            transform: scale(1.08);
        }

        .act-toggle.off {
            background: var(--gray-100);
            color: var(--gray-400);
            border-color: var(--gray-200);
        }

        .act-toggle.off:hover {
            background: var(--gray-400);
            color: #fff;
        }

        .act-delete {
            background: var(--danger-bg);
            color: var(--danger);
            border-color: rgba(239, 68, 68, .15);
        }

        .act-delete:hover {
            background: var(--danger);
            color: #fff;
            border-color: var(--danger);
            transform: scale(1.08);
        }

        .empty-state {
            text-align: center;
            padding: 52px 24px;
        }

        .empty-icon {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            background: var(--gray-100);
            color: var(--gray-300);
            font-size: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
        }

        .empty-state h3 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 6px;
        }

        .empty-state p {
            font-size: 13px;
            color: var(--gray-400);
        }

        .save-order-bar {
            padding: 12px 16px;
            border-top: 1px solid var(--gray-100);
            display: none;
        }

        .save-order-bar.dirty {
            display: block;
        }

        .btn-save-order {
            width: 100%;
            padding: 10px;
            background: #10b981;
            color: #fff;
            border: none;
            border-radius: var(--r);
            font-size: 13.5px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: all var(--t);
        }

        .btn-save-order:hover {
            background: #059669;
        }

        /* Form card */
        .form-card {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow);
            overflow: hidden;
            position: sticky;
            top: 80px;
        }

        .form-card-accent {
            height: 4px;
            background: linear-gradient(90deg, #10b981, #3b82f6, #8b5cf6);
        }

        .form-card-header {
            padding: 16px 22px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .fch-icon {
            width: 38px;
            height: 38px;
            border-radius: var(--r);
            background: #ecfdf5;
            color: #10b981;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .form-card-header h2 {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-800);
            margin: 0;
        }

        .form-card-header p {
            font-size: 12px;
            color: var(--gray-400);
            margin: 2px 0 0;
        }

        .form-card-body {
            padding: 22px;
        }

        /* Preview box */
        .preview-box {
            border: 1px solid var(--gray-200);
            border-radius: var(--r-lg);
            padding: 18px;
            text-align: center;
            background: linear-gradient(135deg, var(--gray-50), #fff);
            margin-bottom: 18px;
        }

        .preview-icon-wrap {
            width: 56px;
            height: 56px;
            border-radius: var(--r-lg);
            background: var(--accent-light);
            color: var(--accent);
            font-size: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
        }

        .preview-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 5px;
        }

        .preview-desc {
            font-size: 12.5px;
            color: var(--gray-500);
            line-height: 1.5;
        }

        /* Form inputs */
        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 6px;
        }

        .form-label .req {
            color: var(--danger);
            margin-left: 2px;
        }

        .form-label .hint {
            font-size: 11px;
            font-weight: 400;
            color: var(--gray-400);
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            color: var(--gray-400);
            pointer-events: none;
            border-right: 1px solid var(--gray-200);
            transition: all var(--t);
            z-index: 1;
        }

        .form-input {
            width: 100%;
            padding: 10px 12px 10px 52px;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r);
            font-size: 13.5px;
            font-family: inherit;
            background: var(--gray-50);
            color: var(--gray-800);
            outline: none;
            transition: all var(--t);
        }

        .form-input::placeholder {
            color: var(--gray-300);
        }

        .form-input:focus {
            background: #fff;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .12);
        }

        .input-wrap:focus-within .input-icon {
            color: var(--accent);
            border-color: rgba(59, 130, 246, .3);
        }

        .form-input.no-icon {
            padding-left: 12px;
        }

        .form-input.has-error {
            border-color: var(--danger);
            animation: shake .35s;
        }

        textarea.form-input {
            resize: vertical;
            min-height: 85px;
            line-height: 1.55;
            padding-top: 10px;
        }

        .field-error {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 5px;
            font-size: 12px;
            color: var(--danger);
        }

        .field-error i {
            font-size: 13px;
        }

        .char-count {
            display: block;
            text-align: right;
            margin-top: 3px;
            font-size: 11px;
            color: var(--gray-400);
            font-family: "DM Mono", monospace;
        }

        .char-count.warn {
            color: var(--warning);
        }

        .char-count.over {
            color: var(--danger);
            font-weight: 600;
        }

        /* Icon picker */
        .icon-picker-wrap {
            max-height: 210px;
            overflow-y: auto;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r);
            padding: 10px;
            background: var(--gray-50);
        }

        .icon-picker-wrap::-webkit-scrollbar {
            width: 4px;
        }

        .icon-picker-wrap::-webkit-scrollbar-thumb {
            background: var(--gray-200);
            border-radius: 99px;
        }

        .icon-picker {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 6px;
        }

        .icon-opt {
            aspect-ratio: 1;
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--gray-500);
            cursor: pointer;
            transition: all var(--t);
            background: #fff;
            position: relative;
        }

        .icon-opt:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--accent-light);
        }

        .icon-opt.selected {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--accent-light);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .18);
        }

        .icon-opt[title]:hover::after {
            content: attr(title);
            position: absolute;
            bottom: calc(100% + 5px);
            left: 50%;
            transform: translateX(-50%);
            background: var(--gray-900);
            color: #fff;
            font-size: 10px;
            padding: 3px 7px;
            border-radius: 4px;
            white-space: nowrap;
            z-index: 20;
        }

        /* Toggle */
        .toggle-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .toggle-switch {
            position: relative;
            width: 42px;
            height: 24px;
            flex-shrink: 0;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            inset: 0;
            border-radius: 99px;
            background: var(--gray-200);
            cursor: pointer;
            transition: .3s;
        }

        .toggle-slider::before {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            left: 3px;
            bottom: 3px;
            background: #fff;
            border-radius: 50%;
            transition: .3s;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .2);
        }

        .toggle-switch input:checked+.toggle-slider {
            background: #10b981;
        }

        .toggle-switch input:checked+.toggle-slider::before {
            transform: translateX(18px);
        }

        .toggle-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
        }

        /* Actions */
        .form-divider {
            height: 1px;
            background: var(--gray-100);
            margin: 18px -22px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
        }

        .btn-submit {
            flex: 1;
            padding: 11px 20px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: var(--r);
            font-size: 13.5px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: all var(--t);
            box-shadow: 0 2px 10px rgba(59, 130, 246, .28);
        }

        .btn-submit:hover {
            background: var(--accent-dark);
            transform: translateY(-1px);
        }

        .btn-cancel {
            padding: 11px 18px;
            background: var(--gray-100);
            color: var(--gray-600);
            border: 1.5px solid var(--gray-200);
            border-radius: var(--r);
            font-size: 13.5px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all var(--t);
        }

        .btn-cancel:hover {
            background: var(--gray-200);
            color: var(--gray-800);
        }

        /* Homepage preview strip */
        .hp-strip {
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: var(--r-xl);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            margin-top: 18px;
        }

        .hp-strip-header {
            padding: 13px 18px;
            border-bottom: 1px solid var(--gray-100);
            font-size: 13px;
            font-weight: 700;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .hp-strip-header i {
            color: var(--accent);
        }

        .hp-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            padding: 14px;
        }

        .hp-card {
            border: 1px solid var(--gray-200);
            border-radius: var(--r);
            padding: 12px;
            text-align: center;
            transition: all var(--t);
        }

        .hp-card:hover {
            border-color: var(--accent);
            box-shadow: 0 3px 12px rgba(59, 130, 246, .1);
        }

        .hp-card i {
            display: block;
            font-size: 20px;
            color: var(--accent);
            margin-bottom: 6px;
        }

        .hp-card .hp-t {
            font-size: 12px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 3px;
        }

        .hp-card .hp-d {
            font-size: 11px;
            color: var(--gray-500);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        @media(max-width:600px) {
            .sum-strip {
                grid-template-columns: 1fr 1fr;
            }

            .sum-strip .sum-card:last-child {
                display: none;
            }
        }
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <?php if ($toast): ?>
        <div class="toast <?= $toast['type'] ?>" id="toast">
            <i class='bx <?= $toast['type'] === 'success' ? 'bx-check-circle' : 'bx-x-circle' ?>'></i>
            <?= htmlspecialchars($toast['msg'], ENT_QUOTES) ?>
            <button class="toast-close" onclick="dismissToast()">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Delete Modal -->
    <div class="overlay" id="deleteOverlay">
        <div class="modal">
            <div class="modal-icon-wrap"><i class='bx bx-trash'></i></div>
            <h3>Delete Service?</h3>
            <p>You are about to permanently delete <strong id="delSvcName"></strong>.<br>It will be removed from the
                homepage immediately.</p>
            <form method="POST" action="Services.php" class="modal-btns">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="delete_id" id="deleteIdFld">
                <button type="button" class="btn-cancel-modal" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-del-confirm">
                    <span class="spinner" id="delSpinner"></span>
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- ── Page header ────────────────────────────────────────── -->
    <div class="page-header">
        <nav class="breadcrumb">
            <a href="<?= $root_url ?>"><i class='bx bx-home-alt'></i> Dashboard</a>
            <i class='bx bx-chevron-right'></i>
            <span>Services</span>
        </nav>
        <h1>Services Manager</h1>
    </div>


    <!-- ── Summary strip ─────────────────────────────────────── -->
    <div class="sum-strip">
        <div class="sum-card">
            <div class="sum-icon si-teal"><i class='bx bx-list-check'></i></div>
            <div>
                <div class="sum-val"><?= count($services) ?></div>
                <div class="sum-label">Total Services</div>
            </div>
        </div>
        <div class="sum-card">
            <div class="sum-icon si-blue"><i class='bx bx-show'></i></div>
            <div>
                <div class="sum-val"><?= $totalActive ?></div>
                <div class="sum-label">Visible on Site</div>
            </div>
        </div>
        <div class="sum-card">
            <div class="sum-icon si-red"><i class='bx bx-hide'></i></div>
            <div>
                <div class="sum-val"><?= $totalInactive ?></div>
                <div class="sum-label">Hidden</div>
            </div>
        </div>
    </div>

    <!-- ── Main layout ───────────────────────────────────────── -->
    <div class="page-grid">

        <!-- LEFT: list ──────────────────────────────────────── -->
        <div>
            <div class="main-card">
                <div class="card-toolbar">
                    <div class="card-toolbar-title">All Services</div>
                    <div class="drag-hint"><i class='bx bx-move'></i> Drag to reorder</div>
                </div>

                <?php if (empty($services)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class='bx bx-layer'></i></div>
                        <h3>No Services Yet</h3>
                        <p>Add your first service using the form on the right.</p>
                    </div>
                <?php else: ?>

                    <div class="svc-list" id="svcList">
                        <?php foreach ($services as $i => $svc): ?>
                            <div class="svc-row <?= !$svc['is_active'] ? 'inactive' : '' ?>" draggable="true"
                                data-id="<?= (int) $svc['id'] ?>">

                                <i class='bx bx-grid-vertical drag-handle'></i>
                                <span class="svc-num"><?= $i + 1 ?></span>

                                <div class="svc-icon-bubble">
                                    <i class="<?= htmlspecialchars($svc['icon'], ENT_QUOTES) ?>"></i>
                                </div>

                                <div class="svc-body">
                                    <div class="svc-title"><?= htmlspecialchars($svc['title'], ENT_QUOTES) ?></div>
                                    <?php if (!empty($svc['description'])): ?>
                                        <div class="svc-desc"><?= htmlspecialchars($svc['description'], ENT_QUOTES) ?></div>
                                    <?php endif; ?>
                                </div>

                                <span class="status-pill <?= $svc['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                    <?= $svc['is_active'] ? 'Live' : 'Hidden' ?>
                                </span>

                                <div class="svc-actions">
                                    <a href="Services.php?edit=<?= (int) $svc['id'] ?>" class="act-btn act-edit" title="Edit">
                                        <i class='bx bx-edit-alt'></i>
                                    </a>

                                    <form method="POST" action="Services.php" style="display:contents">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="toggle_id" value="<?= (int) $svc['id'] ?>">
                                        <button type="submit" class="act-btn act-toggle <?= !$svc['is_active'] ? 'off' : '' ?>"
                                            title="<?= $svc['is_active'] ? 'Hide from site' : 'Show on site' ?>">
                                            <i class='bx <?= $svc['is_active'] ? 'bx-hide' : 'bx-show' ?>'></i>
                                        </button>
                                    </form>

                                    <button class="act-btn act-delete" title="Delete"
                                        onclick="openDelete(<?= (int) $svc['id'] ?>, <?= json_encode($svc['title']) ?>)">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="save-order-bar" id="saveOrderBar">
                        <button class="btn-save-order" onclick="saveOrder()">
                            <i class='bx bx-save'></i> Save New Order
                        </button>
                    </div>

                <?php endif; ?>
            </div>

            <!-- Homepage preview -->
            <?php
            $liveServices = array_filter($services, fn($s) => $s['is_active']);
            if (!empty($liveServices)):
                ?>
                <div class="hp-strip">
                    <div class="hp-strip-header"><i class='bx bx-desktop'></i> Homepage Preview (Live Services)</div>
                    <div class="hp-grid">
                        <?php foreach ($liveServices as $svc): ?>
                            <div class="hp-card">
                                <i class="<?= htmlspecialchars($svc['icon'], ENT_QUOTES) ?>"></i>
                                <div class="hp-t"><?= htmlspecialchars($svc['title'], ENT_QUOTES) ?></div>
                                <?php if (!empty($svc['description'])): ?>
                                    <div class="hp-d"><?= htmlspecialchars($svc['description'], ENT_QUOTES) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: form ─────────────────────────────────────── -->
        <div class="form-card">
            <div class="form-card-accent"></div>
            <div class="form-card-header">
                <div class="fch-icon">
                    <i class='bx <?= $editService ? 'bx-edit' : 'bx-plus-circle' ?>'></i>
                </div>
                <div>
                    <h2><?= $editService ? 'Edit Service' : 'Add New Service' ?></h2>
                    <p><?= $editService ? 'Update the details below' : 'Fill in the form to add a service' ?></p>
                </div>
            </div>
            <div class="form-card-body">

                <!-- Live card preview -->
                <div class="preview-box">
                    <div class="preview-icon-wrap" id="previewIconWrap">
                        <i class="<?= htmlspecialchars($currentIcon, ENT_QUOTES) ?>" id="previewIcon"></i>
                    </div>
                    <div class="preview-title" id="previewTitle">
                        <?= $editService ? htmlspecialchars($editService['title'], ENT_QUOTES) : 'Service Title' ?>
                    </div>
                    <div class="preview-desc" id="previewDesc">
                        <?= $editService && !empty($editService['description'])
                            ? htmlspecialchars($editService['description'], ENT_QUOTES)
                            : 'Your service description will appear here.' ?>
                    </div>
                </div>

                <form method="POST" action="Services.php" id="svcForm" novalidate>
                    <input type="hidden" name="action" value="<?= $editService ? 'edit' : 'add' ?>">
                    <?php if ($editService): ?>
                        <input type="hidden" name="id" value="<?= (int) $editService['id'] ?>">
                    <?php endif; ?>

                    <!-- Title -->
                    <div class="form-group">
                        <label class="form-label">
                            <span>Title <span class="req">*</span></span>
                            <span class="hint">max 150</span>
                        </label>
                        <div class="input-wrap">
                            <div class="input-icon"><i class='bx bx-text'></i></div>
                            <input type="text" name="title" id="titleInput"
                                class="form-input <?= !empty($errors['title']) ? 'has-error' : '' ?>"
                                value="<?= htmlspecialchars($editService['title'] ?? '', ENT_QUOTES) ?>"
                                placeholder="e.g. Manuscript Writing" maxlength="150" autocomplete="off" autofocus>
                        </div>
                        <?php if (!empty($errors['title'])): ?>
                            <div class="field-error"><i
                                    class='bx bx-error-circle'></i><?= htmlspecialchars($errors['title'], ENT_QUOTES) ?>
                            </div>
                        <?php endif; ?>
                        <span class="char-count" id="titleCount"><?= mb_strlen($editService['title'] ?? '') ?> /
                            150</span>
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label class="form-label">
                            <span>Description</span>
                            <span class="hint">shown on homepage</span>
                        </label>
                        <textarea name="description" id="descInput" class="form-input no-icon" rows="3" maxlength="300"
                            placeholder="Brief description of this service…"><?= htmlspecialchars($editService['description'] ?? '', ENT_QUOTES) ?></textarea>
                        <span class="char-count" id="descCount"><?= mb_strlen($editService['description'] ?? '') ?> /
                            300</span>
                    </div>

                    <!-- Icon picker -->
                    <div class="form-group">
                        <label class="form-label">
                            <span>Icon</span>
                            <span class="hint">click to choose</span>
                        </label>
                        <input type="hidden" name="icon" id="iconInput"
                            value="<?= htmlspecialchars($currentIcon, ENT_QUOTES) ?>">
                        <div class="icon-picker-wrap">
                            <div class="icon-picker" id="iconPicker">
                                <?php foreach ($iconOptions as $cls => $label): ?>
                                    <div class="icon-opt <?= $currentIcon === $cls ? 'selected' : '' ?>"
                                        data-icon="<?= htmlspecialchars($cls, ENT_QUOTES) ?>"
                                        title="<?= htmlspecialchars($label, ENT_QUOTES) ?>"
                                        onclick="selectIcon('<?= htmlspecialchars($cls, ENT_QUOTES) ?>', this)">
                                        <i class="<?= htmlspecialchars($cls, ENT_QUOTES) ?>"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Active toggle -->
                    <div class="toggle-row">
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_active" id="activeToggle" <?= ($editService['is_active'] ?? 1) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="toggle-label">Show on homepage</span>
                    </div>

                    <div class="form-divider"></div>

                    <div class="form-actions">
                        <?php if ($editService): ?>
                            <a href="Services.php" class="btn-cancel">
                                <i class='bx bx-x'></i> Cancel
                            </a>
                        <?php endif; ?>
                        <button type="submit" class="btn-submit">
                            <i class='bx <?= $editService ? 'bx-check' : 'bx-plus' ?>'></i>
                            <?= $editService ? 'Save Changes' : 'Add Service' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div><!-- /.page-grid -->

    </div>
    </section>

    <script>
        /* ── Char counters + live preview ──────────────────────────── */
        function syncCounter(inputId, countId, max) {
            const el = document.getElementById(inputId);
            const ct = document.getElementById(countId);
            if (!el || !ct) return;
            const n = el.value.length;
            ct.textContent = `${n} / ${max}`;
            ct.className = 'char-count' + (n >= max ? ' over' : n >= max * .8 ? ' warn' : '');
        }

        document.getElementById('titleInput')?.addEventListener('input', function () {
            document.getElementById('previewTitle').textContent = this.value.trim() || 'Service Title';
            syncCounter('titleInput', 'titleCount', 150);
        });
        document.getElementById('descInput')?.addEventListener('input', function () {
            document.getElementById('previewDesc').textContent = this.value.trim() || 'Your service description will appear here.';
            syncCounter('descInput', 'descCount', 300);
        });

        /* ── Icon picker ───────────────────────────────────────────── */
        function selectIcon(cls, el) {
            document.querySelectorAll('.icon-opt').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');
            document.getElementById('iconInput').value = cls;
            document.getElementById('previewIcon').className = cls;
        }

        /* ── Drag reorder ──────────────────────────────────────────── */
        let dragSrc = null;
        let orderDirty = false;

        document.querySelectorAll('.svc-row').forEach(row => {
            row.addEventListener('dragstart', e => { dragSrc = row; row.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; });
            row.addEventListener('dragend', () => { row.classList.remove('dragging'); document.querySelectorAll('.svc-row').forEach(r => r.classList.remove('drag-over')); renumber(); markDirty(); });
            row.addEventListener('dragover', e => { e.preventDefault(); if (dragSrc && dragSrc !== row) { document.querySelectorAll('.svc-row').forEach(r => r.classList.remove('drag-over')); row.classList.add('drag-over'); } });
            row.addEventListener('drop', e => { e.preventDefault(); if (dragSrc && dragSrc !== row) { const list = row.parentNode; const rows = [...list.querySelectorAll('.svc-row')]; rows.indexOf(dragSrc) < rows.indexOf(row) ? list.insertBefore(dragSrc, row.nextSibling) : list.insertBefore(dragSrc, row); } });
        });

        function renumber() {
            document.querySelectorAll('.svc-row').forEach((r, i) => {
                const n = r.querySelector('.svc-num');
                if (n) n.textContent = i + 1;
            });
        }

        function markDirty() {
            if (!orderDirty) {
                orderDirty = true;
                document.getElementById('saveOrderBar')?.classList.add('dirty');
            }
        }

        function saveOrder() {
            const ids = [...document.querySelectorAll('.svc-row')].map(r => r.dataset.id);
            fetch('Services.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=reorder&order=' + encodeURIComponent(JSON.stringify(ids))
            })
                .then(r => r.json())
                .then(d => {
                    if (d.ok) {
                        orderDirty = false;
                        document.getElementById('saveOrderBar')?.classList.remove('dirty');
                        showToast('Order saved!');
                    }
                });
        }

        /* ── Delete modal ──────────────────────────────────────────── */
        const overlay = document.getElementById('deleteOverlay');
        function openDelete(id, name) {
            document.getElementById('deleteIdFld').value = id;
            document.getElementById('delSvcName').textContent = name;
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeModal() { overlay.classList.remove('open'); document.body.style.overflow = ''; }
        overlay?.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

        /* ── Toast ─────────────────────────────────────────────────── */
        const toastEl = document.getElementById('toast');
        if (toastEl) {
            const t = setTimeout(dismissToast, 4500);
            toastEl.addEventListener('click', () => { clearTimeout(t); dismissToast(); });
        }
        function dismissToast() {
            const t = document.getElementById('toast');
            if (!t) return;
            t.classList.add('hiding');
            setTimeout(() => { t.style.display = 'none'; t.classList.remove('hiding'); }, 320);
        }
        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast') || document.createElement('div');
            t.id = 'toast';
            t.className = `toast ${type}`;
            t.innerHTML = `<i class='bx bx-check-circle'></i> ${msg} <button class='toast-close' onclick='dismissToast()'>&times;</button>`;
            if (!document.getElementById('toast')) document.body.appendChild(t);
            t.style.display = 'flex';
            setTimeout(dismissToast, 4000);
        }
    </script>
</body>

</html>