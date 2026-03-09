<?php
include 'db.php';
session_start();

$book_id = isset($_GET['id']) ? (int) $_GET['id'] : null;
if (!$book_id) {
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT b.*, b.img AS book_image, c.name AS category_name
                        FROM books_data b
                        JOIN categories c ON b.category_id = c.id
                        WHERE b.id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
if (!$book) {
    header("Location: index.php");
    exit();
}
$stmt->close();

$stmt = $conn->prepare("SELECT image_path FROM book_images WHERE book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// $stmt = $conn->prepare("SELECT a.id, a.name, a.title, a.description, a.image
//                         FROM authors a
//                         JOIN book_authors ba ON ba.author_id = a.id
//                         WHERE ba.book_id = ?
//                         ORDER BY a.name ASC");
// Fetch only 2 authors for preview
$stmt = $conn->prepare("SELECT a.id, a.name, a.title, a.description, a.image
                        FROM authors a
                        JOIN book_authors ba ON ba.author_id = a.id
                        WHERE ba.book_id = ?
                        ORDER BY a.name ASC LIMIT 2");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$authors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total author count separately
$stmt2 = $conn->prepare("SELECT COUNT(*) FROM book_authors WHERE book_id = ?");
$stmt2->bind_param("i", $book_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
$totalAuthors = $result2->fetch_row()[0] ?? 0;
$stmt2->close();
// 

$whatsapp_number = '+919752747384';
$wa_msg = "Hi, I'm interested in the book: *" . $book['title'] . "*\n"
    . "Price: INR " . $book['price'] . "\n"
    . "Publisher: " . $book['publishers'] . "\n"
    . "Please share more details.";
$whatsapp_url = "https://wa.me/$whatsapp_number?text=" . urlencode($wa_msg);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($book['title'], ENT_QUOTES) ?> — Book Details</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,600&family=Cabinet+Grotesk:wght@300;400;500;700;800&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --ink: #18130e;
            --paper: #faf7f2;
            --cream: #f3ede3;
            --cream-dark: #e8dfd0;
            --accent: #b5390f;
            --accent-warm: #d4501a;
            --accent-light: #fdf0eb;
            --gold: #c9920a;
            --muted: #776b5d;
            --border: #e2d9cc;
            --shadow-warm: rgba(24, 19, 14, .1);
            --r: 6px;
            --r-lg: 14px;
            --t: .22s cubic-bezier(.4, 0, .2, 1);
        }


        html {
            scroll-behavior: smooth;
        }

        body {
            background: var(--paper);
            color: var(--ink);
            font-family: "Cabinet Grotesk", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* ── HERO BAND ─────────────────────────────────────────────── */
        .hero-band {
            background: var(--ink);
            padding: 56px 0 0;
            position: relative;
            overflow: hidden;
        }

        .hero-band::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 80% at 70% 50%, rgba(181, 57, 15, .22), transparent),
                radial-gradient(ellipse 40% 60% at 10% 80%, rgba(201, 146, 10, .1), transparent);
            pointer-events: none;
        }

        /* Subtle noise texture */
        .hero-band::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.03'/%3E%3C/svg%3E");
            pointer-events: none;
            opacity: .4;
        }

        .hero-inner {
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 32px;
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 56px;
            align-items: end;
            position: relative;
            z-index: 1;
        }

        @media (max-width: 900px) {
            .hero-inner {
                grid-template-columns: 1fr;
                gap: 32px;
                align-items: start;
            }
        }

        /* ── Cover column ──────────────────────────────────────────── */
        .cover-column {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .cover-frame {
            position: relative;
            border-radius: var(--r-lg) var(--r-lg) 0 0;
            overflow: hidden;
            aspect-ratio: 3/4;
            cursor: zoom-in;
            background: #2a2015;
            box-shadow:
                0 -2px 0 rgba(255, 255, 255, .06) inset,
                0 32px 80px rgba(0, 0, 0, .7),
                0 0 0 1px rgba(255, 255, 255, .06);
        }

        .cover-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .7s var(--t);
            display: block;
        }

        .cover-frame:hover img {
            transform: scale(1.04);
        }

        .cover-zoom-hint {
            position: absolute;
            bottom: 14px;
            right: 14px;
            background: rgba(0, 0, 0, .55);
            backdrop-filter: blur(6px);
            color: rgba(255, 255, 255, .8);
            font-size: 12px;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 99px;
            display: flex;
            align-items: center;
            gap: 5px;
            opacity: 0;
            transition: opacity var(--t);
            pointer-events: none;
        }

        .cover-frame:hover .cover-zoom-hint {
            opacity: 1;
        }

        /* Thumbnails */
        .thumb-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding-bottom: 8px;
        }

        .thumb {
            width: 58px;
            height: 58px;
            border-radius: var(--r);
            overflow: hidden;
            cursor: pointer;
            flex-shrink: 0;
            border: 2px solid rgba(255, 255, 255, .08);
            transition: all var(--t);
            background: #2a2015;
        }

        .thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .3s var(--t);
            display: block;
        }

        .thumb:hover {
            border-color: rgba(255, 255, 255, .35);
        }

        .thumb:hover img {
            transform: scale(1.1);
        }

        .thumb.active {
            border-color: var(--accent-warm);
            box-shadow: 0 0 0 3px rgba(212, 80, 26, .3);
        }

        /* ── Info column ────────────────────────────────────────────── */
        .info-column {
            padding: 12px 0 40px;
            color: #f5f0e8;
        }

        .cat-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 14px;
            border-radius: 99px;
            border: 1px solid rgba(201, 146, 10, .4);
            background: rgba(201, 146, 10, .1);
            color: var(--gold);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .book-title {
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: clamp(32px, 4.5vw, 56px);
            font-weight: 600;
            line-height: 1.1;
            letter-spacing: -.5px;
            color: #faf7f2;
            margin-bottom: 14px;
        }

        .book-title em {
            font-style: italic;
            color: rgba(250, 247, 242, .65);
        }

        .by-line {
            font-size: 15px;
            color: rgba(245, 240, 232, .55);
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .by-line i {
            font-size: 12px;
            color: var(--accent-warm);
        }

        .by-line span {
            color: rgba(245, 240, 232, .8);
            font-weight: 500;
        }

        .price-block {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin-bottom: 32px;
        }

        .price-currency {
            font-family: "Cabinet Grotesk", sans-serif;
            font-size: 18px;
            font-weight: 500;
            color: rgba(245, 240, 232, .5);
            letter-spacing: .5px;
        }

        .price-amount {
            font-family: "Cormorant Garamond", serif;
            font-size: 52px;
            font-weight: 700;
            line-height: 1;
            color: #faf7f2;
            letter-spacing: -2px;
        }

        /* Meta pills in hero */
        .meta-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 36px;
        }

        .meta-pill {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            background: rgba(255, 255, 255, .06);
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: var(--r);
            font-size: 13px;
            color: rgba(245, 240, 232, .8);
            backdrop-filter: blur(4px);
        }

        .meta-pill i {
            font-size: 13px;
            color: rgba(245, 240, 232, .4);
        }

        .meta-pill strong {
            font-weight: 600;
            color: rgba(245, 240, 232, .95);
        }

        /* Buy button */
        .btn-whatsapp {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            padding: 16px 32px;
            background: #25d366;
            color: #fff;
            font-family: "Cabinet Grotesk", sans-serif;
            font-size: 15px;
            font-weight: 700;
            border-radius: var(--r);
            text-decoration: none;
            transition: all var(--t);
            box-shadow: 0 4px 24px rgba(37, 211, 102, .35);
            letter-spacing: .3px;
            position: relative;
            overflow: hidden;
        }

        .btn-whatsapp::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, .1), transparent);
        }

        .btn-whatsapp:hover {
            background: #20ba5a;
            color: #fff;
            box-shadow: 0 8px 32px rgba(37, 211, 102, .5);
            transform: translateY(-2px);
        }

        .btn-whatsapp i {
            font-size: 22px;
        }

        /* ── BODY CONTENT ──────────────────────────────────────────── */
        .body-wrap {
            max-width: 1180px;
            margin: 0 auto;
            padding: 0 32px 100px;
        }

        /* ── DETAILS STRIP ──────────────────────────────────────────── */
        .details-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            overflow: hidden;
            margin: 52px 0 48px;
            background: #fff;
            box-shadow: 0 2px 16px var(--shadow-warm);
        }

        @media (max-width: 720px) {
            .details-strip {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .details-strip {
                grid-template-columns: 1fr;
            }
        }

        .detail-cell {
            padding: 22px 24px;
            border-right: 1px solid var(--border);
            position: relative;
            transition: background var(--t);
        }

        .detail-cell:last-child {
            border-right: none;
        }

        .detail-cell:hover {
            background: var(--cream);
        }

        .detail-cell-icon {
            width: 36px;
            height: 36px;
            background: var(--cream);
            border-radius: var(--r);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--accent);
            margin-bottom: 10px;
            transition: background var(--t);
        }

        .detail-cell:hover .detail-cell-icon {
            background: var(--accent-light);
        }

        .detail-cell-label {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            margin-bottom: 5px;
        }

        .detail-cell-val {
            font-size: 14px;
            font-weight: 600;
            color: var(--ink);
            line-height: 1.3;
            word-break: break-word;
        }

        .detail-cell-val.mono {
            font-family: "DM Mono", monospace;
            font-size: 13px;
            font-weight: 400;
        }

        /* ── DESCRIPTION SECTION ─────────────────────────────────── */
        .desc-wrap {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 56px;
            margin-bottom: 72px;
        }

        @media (max-width: 900px) {
            .desc-wrap {
                grid-template-columns: 1fr;
            }
        }

        .section-eyebrow {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--accent);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-eyebrow::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
            max-width: 60px;
        }

        .section-heading-serif {
            font-family: "Cormorant Garamond", serif;
            font-size: 32px;
            font-weight: 600;
            color: var(--ink);
            line-height: 1.2;
            margin-bottom: 20px;
            letter-spacing: -.3px;
        }

        .desc-text {
            font-size: 15px;
            line-height: 1.8;
            color: #4a4035;
            display: -webkit-box;
            -webkit-line-clamp: 6;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: all var(--t);
        }

        .desc-text.open {
            -webkit-line-clamp: unset;
            display: block;
        }

        .read-more-btn {
            margin-top: 14px;
            background: none;
            border: none;
            font-family: inherit;
            font-size: 13.5px;
            font-weight: 700;
            color: var(--accent);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0;
            transition: gap var(--t);
            letter-spacing: .2px;
        }

        .read-more-btn:hover {
            gap: 10px;
        }

        /* Side: subjects & contributors */
        .side-box {
            background: var(--cream);
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            padding: 24px;
            height: fit-content;
        }

        .side-box-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--muted);
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .side-box-row {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
        }

        .side-box-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .side-box-row i {
            font-size: 13px;
            color: var(--muted);
            margin-top: 2px;
            flex-shrink: 0;
        }

        .side-box-row-label {
            font-size: 10.5px;
            color: var(--muted);
            margin-bottom: 1px;
        }

        .side-box-row-val {
            font-size: 13.5px;
            color: var(--ink);
            font-weight: 500;
            line-height: 1.4;
        }

        .tag-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 4px;
        }

        .tag {
            display: inline-block;
            padding: 3px 10px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 99px;
            font-size: 12px;
            color: var(--ink);
            transition: all var(--t);
        }

        .tag:hover {
            background: var(--accent-light);
            border-color: var(--accent);
            color: var(--accent);
        }

        /* ── GALLERY SECTION ─────────────────────────────────────── */
        .gallery-section {
            margin-bottom: 72px;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px;
            margin-top: 24px;
        }

        .gallery-item {
            aspect-ratio: 3/4;
            border-radius: var(--r);
            overflow: hidden;
            cursor: pointer;
            border: 1px solid var(--border);
            background: var(--cream);
            transition: all var(--t);
        }

        .gallery-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 32px var(--shadow-warm);
            border-color: var(--cream-dark);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* ── AUTHORS SECTION ─────────────────────────────────────── */
        .authors-section {
            margin-bottom: 72px;
        }

        .authors-section-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 36px;
        }

        .authors-section-header .line {
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .authors-section-header .label {
            font-family: "Cormorant Garamond", serif;
            font-size: 26px;
            font-weight: 600;
            color: var(--ink);
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .authors-section-header .label i {
            font-size: 20px;
            color: var(--accent);
        }

        .authors-list {
            display: flex;
            flex-direction: column;
            gap: 1px;
        }

        .author-row {
            display: flex;
            align-items: center;
            gap: 24px;
            padding: 24px 28px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            text-decoration: none;
            color: inherit;
            transition: all var(--t);
            position: relative;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .author-row::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--accent), var(--gold));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform var(--t);
        }

        .author-row:hover {
            box-shadow: 0 8px 32px var(--shadow-warm);
            transform: translateY(-2px);
            border-color: var(--cream-dark);
            color: inherit;
        }

        .author-row:hover::after {
            transform: scaleX(1);
        }

        .author-num {
            font-family: "DM Mono", monospace;
            font-size: 12px;
            color: var(--muted);
            min-width: 26px;
            flex-shrink: 0;
        }

        .author-pic {
            width: 66px;
            height: 66px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
            border: 2px solid var(--border);
            transition: border-color var(--t);
        }

        .author-initials-big {
            width: 66px;
            height: 66px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--cream), var(--cream-dark));
            color: var(--accent);
            font-family: "Cormorant Garamond", serif;
            font-size: 26px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 2px solid var(--border);
            transition: all var(--t);
        }

        .author-row:hover .author-pic,
        .author-row:hover .author-initials-big {
            border-color: var(--accent);
        }

        .author-body {
            flex: 1;
            min-width: 0;
        }

        .author-row-name {
            font-family: "Cormorant Garamond", serif;
            font-size: 20px;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 2px;
            transition: color var(--t);
        }

        .author-row:hover .author-row-name {
            color: var(--accent);
        }

        .author-row-title {
            font-size: 12.5px;
            color: var(--muted);
            font-style: italic;
            margin-bottom: 8px;
        }

        .author-row-bio {
            font-size: 13.5px;
            color: #5a544e;
            line-height: 1.55;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .author-row-arrow {
            font-size: 18px;
            color: var(--accent);
            flex-shrink: 0;
            opacity: 0;
            transform: translateX(-6px);
            transition: all var(--t);
        }

        .author-row:hover .author-row-arrow {
            opacity: 1;
            transform: translateX(0);
        }

        .no-authors {
            text-align: center;
            padding: 48px 24px;
            border: 2px dashed var(--border);
            border-radius: var(--r-lg);
            color: var(--muted);
        }

        .no-authors i {
            font-size: 40px;
            display: block;
            margin-bottom: 12px;
            opacity: .3;
        }

        .no-authors p {
            font-size: 14px;
            margin: 0;
        }

        .all-authors-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
            padding: 11px 22px;
            border: 1.5px solid var(--ink);
            color: var(--ink);
            font-size: 13.5px;
            font-weight: 700;
            border-radius: var(--r);
            text-decoration: none;
            transition: all var(--t);
            letter-spacing: .2px;
        }

        .all-authors-link:hover {
            background: var(--ink);
            color: var(--paper);
        }

        /* ── LIGHTBOX ─────────────────────────────────────────────── */
        .lightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(10, 7, 4, .92);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }

        .lightbox.open {
            display: flex;
        }

        .lightbox img {
            max-width: 90vw;
            max-height: 88vh;
            border-radius: var(--r-lg);
            box-shadow: 0 32px 80px rgba(0, 0, 0, .7);
            animation: lbIn .22s ease both;
        }

        @keyframes lbIn {
            from {
                opacity: 0;
                transform: scale(.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .lb-close {
            position: fixed;
            top: 20px;
            right: 22px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .2);
            color: #fff;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--t);
        }

        .lb-close:hover {
            background: rgba(255, 255, 255, .22);
        }

        /* ── SCROLL REVEAL ───────────────────────────────────────── */
        .reveal {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity .6s var(--t), transform .6s var(--t);
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* ── RESPONSIVE ─────────────────────────────────────────── */
        @media (max-width: 640px) {
            .hero-inner {
                padding: 0 18px;
            }

            .body-wrap {
                padding: 0 18px 80px;
            }

            .author-row {
                flex-wrap: wrap;
                gap: 14px;
                padding: 18px;
            }

            .author-row-arrow {
                display: none;
            }

            .details-strip .detail-cell:nth-child(even) {
                border-right: none;
            }

            @media (max-width: 480px) {
                .details-strip .detail-cell {
                    border-right: none;
                    border-bottom: 1px solid var(--border);
                }

                .details-strip .detail-cell:last-child {
                    border-bottom: none;
                }
            }
        }


        /*  */
        .lb-nav {
            position: fixed;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .2);
            color: #fff;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .2s;
            z-index: 10000;
        }

        .lb-nav:hover {
            background: rgba(255, 255, 255, .25);
        }

        .lb-prev {
            left: 20px;
        }

        .lb-next {
            right: 72px;
        }

        .lb-counter {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            color: rgba(255, 255, 255, .6);
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 1px;
        }

        /*  */
    </style>
</head>

<body>
    <?php include 'Header.php'; ?>

    <!-- ── LIGHTBOX ────────────────────────────────────────────────── -->
    <!-- <div class="lightbox" id="lightbox">
        <button class="lb-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
        <img src="" id="lbImg" alt="">
    </div> -->

    <div class="lightbox" id="lightbox">
        <button class="lb-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
        <button class="lb-nav lb-prev" onclick="lbNav(-1)"><i class="fas fa-chevron-left"></i></button>
        <img src="" id="lbImg" alt="">
        <button class="lb-nav lb-next" onclick="lbNav(1)"><i class="fas fa-chevron-right"></i></button>
        <div class="lb-counter" id="lbCounter"></div>
    </div>
    <!-- ═══════════════════════════════════════════════════════════════
     HERO BAND
═══════════════════════════════════════════════════════════════ -->
    <div class="hero-band">
        <div class="hero-inner">

            <!-- Cover column -->
            <div class="cover-column">
                <div class="cover-frame" id="mainCoverFrame">
                    <img src="./test/dashboard/<?= htmlspecialchars($book['book_image'], ENT_QUOTES) ?>"
                        alt="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>" id="mainCoverImg">
                    <div class="cover-zoom-hint">
                        <i class="fas fa-search-plus"></i> Click to zoom
                    </div>
                </div>


            </div>

            <!-- Info column -->
            <div class="info-column">

                <div class="cat-chip">
                    <i class="fas fa-bookmark"></i>
                    <?= htmlspecialchars($book['category_name'], ENT_QUOTES) ?>
                </div>

                <h1 class="book-title">
                    <?= htmlspecialchars($book['title'], ENT_QUOTES) ?>
                </h1>

                <?php if (!empty($book['authors'])): ?>
                    <div class="by-line">
                        <i class="fas fa-pen-nib"></i>
                        <span>By <?= htmlspecialchars($book['authors'], ENT_QUOTES) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($book['price'])): ?>
                    <div class="price-block">
                        <span class="price-currency">₹</span>
                        <span class="price-amount"><?= htmlspecialchars($book['price'], ENT_QUOTES) ?></span>
                    </div>
                <?php endif; ?>

                <div class="meta-pills">
                    <?php if (!empty($book['publishers'])): ?>
                        <div class="meta-pill">
                            <i class="fas fa-building"></i>
                            <span><?= htmlspecialchars($book['publishers'], ENT_QUOTES) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($book['isbn'])): ?>
                        <div class="meta-pill">
                            <i class="fas fa-barcode"></i>
                            <strong><?= htmlspecialchars($book['isbn'], ENT_QUOTES) ?></strong>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($book['length'])): ?>
                        <div class="meta-pill">
                            <i class="fas fa-file-alt"></i>
                            <span><?= htmlspecialchars($book['length'], ENT_QUOTES) ?> pages</span>
                        </div>
                    <?php endif; ?>
                </div>

                <a href="<?= $whatsapp_url ?>" class="btn-whatsapp" target="_blank">
                    <i class="fab fa-whatsapp"></i>
                    Order via WhatsApp
                </a>

            </div>
        </div>
    </div>
    <!-- /hero-band -->

    <!-- ═══════════════════════════════════════════════════════════════
     BODY
═══════════════════════════════════════════════════════════════ -->
    <div class="body-wrap">

        <!-- ── Details strip ──────────────────────────────────────── -->
        <?php
        $details = [];
        if (!empty($book['publishers']))
            $details[] = ['fas fa-building', 'Publisher', $book['publishers'], false];
        if (!empty($book['isbn']))
            $details[] = ['fas fa-barcode', 'ISBN', $book['isbn'], true];
        if (!empty($book['length']))
            $details[] = ['fas fa-file-alt', 'Pages', $book['length'], false];
        if (!empty($book['price']))
            $details[] = ['fas fa-tag', 'Price', 'INR ' . $book['price'], false];
        if (empty($details)):
        else: ?>
            <div class="details-strip reveal">
                <?php foreach ($details as [$ico, $lbl, $val, $mono]): ?>
                    <div class="detail-cell">
                        <div class="detail-cell-icon"><i class="<?= $ico ?>"></i></div>
                        <div class="detail-cell-label"><?= $lbl ?></div>
                        <div class="detail-cell-val <?= $mono ? 'mono' : '' ?>"><?= htmlspecialchars($val, ENT_QUOTES) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ── Description + side ─────────────────────────────────── -->
        <?php if (!empty($book['description']) || !empty($book['subjects']) || !empty($book['contributors'])): ?>
            <div class="desc-wrap reveal">

                <?php if (!empty($book['description'])): ?>
                    <div class="desc-col">
                        <div class="section-eyebrow">About this book</div>
                        <h2 class="section-heading-serif">Overview</h2>
                        <p class="desc-text" id="descText">
                            <?= nl2br(htmlspecialchars($book['description'], ENT_QUOTES)) ?>
                        </p>
                        <button class="read-more-btn" id="readMoreBtn" onclick="toggleDesc()">
                            Read more <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>

                <!-- Side metadata -->
                <div class="side-box">
                    <?php if (!empty($book['subjects'])): ?>
                        <div class="side-box-title">Subjects</div>
                        <div class="side-box-row"
                            style="border-bottom: <?= !empty($book['contributors']) ? '' : 'none' ?>; padding-bottom: <?= !empty($book['contributors']) ? '' : '0' ?>">
                            <i class="fas fa-tag"></i>
                            <div>
                                <div class="tag-wrap">
                                    <?php foreach (explode(',', $book['subjects']) as $subj): ?>
                                        <span class="tag"><?= htmlspecialchars(trim($subj), ENT_QUOTES) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($book['contributors'])): ?>
                        <div style="margin-top: <?= !empty($book['subjects']) ? '16px' : '0' ?>">
                            <div class="side-box-title">Contributors</div>
                            <div class="side-box-row" style="border:none;padding-bottom:0">
                                <i class="fas fa-users"></i>
                                <div>
                                    <div class="side-box-row-val"><?= htmlspecialchars($book['contributors'], ENT_QUOTES) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>

        <!-- ── Image gallery ──────────────────────────────────────── -->
        <?php if (!empty($images)): ?>
            <div class="gallery-section reveal">
                <div class="section-eyebrow">Inside the book</div>
                <h2 class="section-heading-serif">Gallery</h2>
                <div class="gallery-grid">
                    <?php foreach ($images as $gi): ?>
                        <div class="gallery-item"
                            onclick="openLightbox('../<?= htmlspecialchars($gi['image_path'], ENT_QUOTES) ?>')">
                            <img src="../<?= htmlspecialchars($gi['image_path'], ENT_QUOTES) ?>" alt="">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ── Authors ────────────────────────────────────────────── -->
        <div class="authors-section reveal">
            <div class="authors-section-header">
                <div class="line"></div>
                <div class="label">
                    <i class="fas fa-feather-alt"></i>
                    <?= count($authors) === 1 ? 'About the Author' : (empty($authors) ? 'About the Author' : 'About the Authors') ?>
                </div>
                <div class="line"></div>
            </div>

            <?php if (empty($authors)): ?>
                <div class="no-authors">
                    <i class="fas fa-user-pen"></i>
                    <p>No author profiles have been linked to this book yet.</p>
                </div>
            <?php else: ?>
                <div class="authors-list">
                    <?php foreach ($authors as $i => $author):
                        if (empty($author['name']))
                            continue; // skip broken rows
                        $imgPath = "../uploads/authors/" . ($author['image'] ?? '');
                        $hasImg = !empty($author['image']) && file_exists($imgPath);
                        $initial = strtoupper(substr($author['name'] ?? 'A', 0, 1));
                        ?>
                        <a href="author_detail.php?id=<?= (int) $author['id'] ?>" class="author-row"
                            style="animation-delay: <?= $i * .08 ?>s">
                            <span class="author-num">0<?= $i + 1 ?></span>

                            <?php if ($hasImg): ?>
                                <img src="<?= htmlspecialchars($imgPath, ENT_QUOTES) ?>"
                                    alt="<?= htmlspecialchars($author['name'], ENT_QUOTES) ?>" class="author-pic">
                            <?php else: ?>
                                <div class="author-initials-big"><?= $initial ?></div>
                            <?php endif; ?>

                            <div class="author-body">
                                <div class="author-row-name"><?= htmlspecialchars($author['name'], ENT_QUOTES) ?></div>
                                <?php if (!empty($author['title'])): ?>
                                    <div class="author-row-title"><?= htmlspecialchars($author['title'], ENT_QUOTES) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($author['description'])): ?>
                                    <div class="author-row-bio"><?= htmlspecialchars($author['description'], ENT_QUOTES) ?></div>
                                <?php endif; ?>
                            </div>

                            <i class="fas fa-arrow-right author-row-arrow"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- <a href="all_authors.php" class="all-authors-link">
                <i class="fas fa-users"></i> Browse All Authors
            </a> -->
            <?php if ($totalAuthors > 2): ?>
                <a href="book_authors.php?id=<?= $book_id ?>" class="all-authors-link">
                    <i class="fas fa-users"></i> View All <?= $totalAuthors ?> Authors
                </a>
            <?php elseif ($totalAuthors == 2): ?>
                <a href="book_authors.php?id=<?= $book_id ?>" class="all-authors-link">
                    <i class="fas fa-users"></i> View Both Authors
                </a>
            <?php endif; ?>
        </div>

    </div><!-- /body-wrap -->

    <?php include 'Footer.php'; ?>

    <script>
        /* ── Cover switcher ── */
        function switchCover(src, thumbEl) {
            document.getElementById('mainCoverImg').src = src;
            document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
            thumbEl.classList.add('active');
        }

        /* ── Lightbox ── */
        document.getElementById('mainCoverFrame').addEventListener('click', () => {
            openLightbox(document.getElementById('mainCoverImg').src);
        });

        function openLightbox(src) {
            document.getElementById('lbImg').src = src;
            document.getElementById('lightbox').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('open');
            document.body.style.overflow = '';
        }
        document.getElementById('lightbox').addEventListener('click', function (e) {
            if (e.target === this) closeLightbox();
        });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

        /* ── Description toggle ── */
        function toggleDesc() {
            const txt = document.getElementById('descText');
            const btn = document.getElementById('readMoreBtn');
            txt.classList.toggle('open');
            btn.innerHTML = txt.classList.contains('open')
                ? 'Read less <i class="fas fa-arrow-up"></i>'
                : 'Read more <i class="fas fa-arrow-right"></i>';
        }

        /* ── Scroll reveal ── */
        const obs = new IntersectionObserver(entries => {
            entries.forEach((e, i) => {
                if (e.isIntersecting) {
                    e.target.style.transitionDelay = (i * .07) + 's';
                    e.target.classList.add('visible');
                    obs.unobserve(e.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.reveal').forEach(el => obs.observe(el));

        // 
        /* ── Lightbox ── */
        let lbImages = [];
        let lbIndex = 0;

        // Collect all gallery images on page load
        function buildLbImages() {
            lbImages = [];
            document.querySelectorAll('.gallery-item img').forEach(img => {
                lbImages.push(img.src);
            });
        }

        document.getElementById('mainCoverFrame').addEventListener('click', () => {
            buildLbImages();
            // Also include cover as first image
            const coverSrc = document.getElementById('mainCoverImg').src;
            const allImgs = [coverSrc, ...lbImages];
            lbImages = allImgs;
            openLightboxAt(0);
        });

        document.querySelectorAll('.gallery-item').forEach((item, i) => {
            item.addEventListener('click', () => {
                buildLbImages();
                openLightboxAt(i);
            });
        });

        function openLightbox(src) {
            buildLbImages();
            const i = lbImages.indexOf(src);
            openLightboxAt(i >= 0 ? i : 0);
        }

        function openLightboxAt(index) {
            lbIndex = index;
            updateLb();
            document.getElementById('lightbox').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function updateLb() {
            document.getElementById('lbImg').src = lbImages[lbIndex];
            document.getElementById('lbCounter').textContent = (lbIndex + 1) + ' / ' + lbImages.length;
        }

        function lbNav(dir) {
            lbIndex = (lbIndex + dir + lbImages.length) % lbImages.length;
            updateLb();
        }

        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('open');
            document.body.style.overflow = '';
        }

        document.getElementById('lightbox').addEventListener('click', function (e) {
            if (e.target === this) closeLightbox();
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') lbNav(-1);
            if (e.key === 'ArrowRight') lbNav(1);
        });
    </script>
</body>

</html>