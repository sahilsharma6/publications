<?php
include 'db.php';
session_start();

/* ── Best books categories ───────────────────────────────────── */
$catRes = $conn->query("
    SELECT DISTINCT c.id, c.name
    FROM   categories c
    INNER JOIN best_books bb ON bb.category_id = c.id
    INNER JOIN books_data b  ON b.id = bb.book_id
    ORDER  BY c.name ASC
");
$categories = $catRes ? $catRes->fetch_all(MYSQLI_ASSOC) : [];

/* ── Active services ─────────────────────────────────────────── */
$svcRes = $conn->query("SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
$svcList = $svcRes ? $svcRes->fetch_all(MYSQLI_ASSOC) : [];

/* ── Quick stats ─────────────────────────────────────────────── */
$totalBooks = (int) $conn->query("SELECT COUNT(*) FROM books_data")->fetch_row()[0];
$totalAuthors = (int) $conn->query("SELECT COUNT(*) FROM authors")->fetch_row()[0];
$totalCats = (int) $conn->query("SELECT COUNT(*) FROM categories")->fetch_row()[0];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Professional Publication Services</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="style.css">

  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    :root {
      --ink: #1a1208;
      --paper: #faf8f4;
      --cream: #f3ede2;
      --cream-dark: #e6ddd0;
      --accent: #b5390f;
      --accent2: #c9920a;
      --muted: #7a6f62;
      --border: #e0d8cc;
      --r: 6px;
      --r-lg: 14px;
      --t: .22s cubic-bezier(.4, 0, .2, 1);
      --shadow: 0 4px 24px rgba(26, 18, 8, .08);
      --shadow-lg: 0 16px 48px rgba(26, 18, 8, .14);
    }

    body {
      background: var(--paper);
      color: var(--ink);
      font-family: "Outfit", sans-serif;
      -webkit-font-smoothing: antialiased;
      overflow-x: hidden;
    }

    /* ════════════════════════════════════
       HERO
    ════════════════════════════════════ */
    .hero-wrap {
      position: relative;
      overflow: hidden;
    }

    .hero-slide {
      min-height: 90vh;
      display: none;
      align-items: center;
      position: relative;
    }

    .hero-slide.active-slide {
      display: flex;
    }

    .slide-1 {
      background: linear-gradient(135deg, #0d0a06 0%, #1a1208 45%, #2a1a0a 100%);
    }

    .slide-2 {
      background: linear-gradient(135deg, #0a0d12 0%, #121a24 45%, #0f1e2e 100%);
    }

    .slide-3 {
      background: linear-gradient(135deg, #0a0d08 0%, #12180e 45%, #1a2412 100%);
    }

    .slide-1::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(ellipse 65% 80% at 80% 50%, rgba(181, 57, 15, .28), transparent), radial-gradient(ellipse 40% 60% at 5% 80%, rgba(201, 146, 10, .14), transparent);
    }

    .slide-2::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(ellipse 60% 80% at 75% 40%, rgba(59, 130, 246, .2), transparent), radial-gradient(ellipse 40% 60% at 5% 90%, rgba(139, 92, 246, .1), transparent);
    }

    .slide-3::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(ellipse 60% 80% at 72% 45%, rgba(16, 185, 129, .18), transparent), radial-gradient(ellipse 35% 55% at 5% 85%, rgba(201, 146, 10, .12), transparent);
    }

    /* subtle grid */
    .slide-grid {
      position: absolute;
      inset: 0;
      pointer-events: none;
      background-image:
        linear-gradient(rgba(255, 255, 255, .022) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, .022) 1px, transparent 1px);
      background-size: 48px 48px;
    }

    .hero-inner {
      position: relative;
      z-index: 2;
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 28px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 56px;
      align-items: center;
    }

    @media (max-width: 860px) {
      .hero-inner {
        grid-template-columns: 1fr;
        gap: 0;
      }

      .hero-visual {
        display: none;
      }
    }

    .hero-text {
      padding: 90px 0;
    }

    @media (max-width: 860px) {
      .hero-text {
        padding: 64px 0 56px;
      }
    }

    .hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 2.5px;
      text-transform: uppercase;
      color: var(--accent2);
      margin-bottom: 18px;
    }

    .hero-eyebrow::before,
    .hero-eyebrow::after {
      content: '';
      display: block;
      width: 20px;
      height: 1px;
      background: currentColor;
      opacity: .6;
    }

    .hero-h1 {
      font-family: "Cormorant Garamond", Georgia, serif;
      font-size: clamp(40px, 5.5vw, 72px);
      font-weight: 700;
      line-height: 1.04;
      letter-spacing: -.8px;
      color: #faf7f2;
      margin-bottom: 18px;
    }

    .hero-h1 em {
      font-style: italic;
      color: rgba(250, 247, 242, .42);
    }

    .hero-p {
      font-size: 16px;
      color: rgba(250, 247, 242, .48);
      line-height: 1.72;
      max-width: 420px;
      margin-bottom: 34px;
    }

    .hero-btns {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .btn-hp {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 14px 28px;
      border-radius: var(--r);
      font-size: 14px;
      font-weight: 600;
      letter-spacing: .2px;
      text-decoration: none;
      transition: all var(--t);
      font-family: "Outfit", sans-serif;
      white-space: nowrap;
    }

    .btn-hp-solid {
      background: var(--accent);
      color: #fff;
      box-shadow: 0 4px 20px rgba(181, 57, 15, .4);
    }

    .btn-hp-solid:hover {
      background: #9b2e08;
      color: #fff;
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(181, 57, 15, .5);
    }

    .btn-hp-ghost {
      border: 1.5px solid rgba(255, 255, 255, .18);
      color: rgba(250, 247, 242, .78);
      background: transparent;
    }

    .btn-hp-ghost:hover {
      border-color: rgba(255, 255, 255, .45);
      color: #fff;
      background: rgba(255, 255, 255, .06);
    }

    /* Stats cluster on hero right */
    .hero-visual {
      display: flex;
      justify-content: flex-end;
      align-items: center;
    }

    .stat-cluster {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    .stat-card {
      background: rgba(255, 255, 255, .07);
      border: 1px solid rgba(255, 255, 255, .1);
      border-radius: var(--r-lg);
      padding: 22px 20px;
      backdrop-filter: blur(8px);
      text-align: center;
      transition: all var(--t);
    }

    .stat-card:hover {
      background: rgba(255, 255, 255, .11);
      transform: translateY(-3px);
    }

    .stat-card:nth-child(2) {
      transform: translateY(16px);
    }

    .stat-card:nth-child(2):hover {
      transform: translateY(12px);
    }

    .stat-card:nth-child(4) {
      transform: translateY(-12px);
    }

    .stat-card:nth-child(4):hover {
      transform: translateY(-16px);
    }

    .stat-num {
      font-family: "Cormorant Garamond", serif;
      font-size: 44px;
      font-weight: 700;
      color: #faf7f2;
      line-height: 1;
      margin-bottom: 5px;
    }

    .stat-num sup {
      font-size: 20px;
      color: var(--accent2);
      vertical-align: super;
    }

    .stat-lbl {
      font-size: 11px;
      font-weight: 700;
      color: rgba(250, 247, 242, .38);
      text-transform: uppercase;
      letter-spacing: .8px;
    }

    /* Carousel controls */
    .hero-ctrl {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 44px;
      height: 44px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .1);
      backdrop-filter: blur(6px);
      border: 1px solid rgba(255, 255, 255, .16);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 15px;
      z-index: 10;
      cursor: pointer;
      transition: all var(--t);
    }

    .hero-ctrl:hover {
      background: rgba(255, 255, 255, .2);
    }

    .hero-ctrl-prev {
      left: 20px;
    }

    .hero-ctrl-next {
      right: 20px;
    }

    /* Dots */
    .hero-dots {
      position: absolute;
      bottom: 26px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 8px;
      z-index: 10;
    }

    .hero-dot {
      width: 6px;
      height: 6px;
      border-radius: 99px;
      background: rgba(255, 255, 255, .28);
      border: none;
      cursor: pointer;
      transition: all .4s var(--t);
    }

    .hero-dot.active {
      width: 28px;
      background: var(--accent2);
    }

    /* bottom accent rule */
    .hero-wrap::after {
      content: '';
      display: block;
      height: 3px;
      background: linear-gradient(90deg, transparent, var(--accent), var(--accent2), transparent);
    }

    /* ════════════════════════════════════
       SHARED SECTION STYLES
    ════════════════════════════════════ */
    .sec-header {
      text-align: center;
      margin-bottom: 48px;
    }

    .sec-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--accent);
      margin-bottom: 12px;
    }

    .sec-eyebrow::before,
    .sec-eyebrow::after {
      content: '';
      display: block;
      width: 22px;
      height: 1px;
      background: currentColor;
      opacity: .5;
    }

    .sec-h2 {
      font-family: "Cormorant Garamond", serif;
      font-size: clamp(30px, 4vw, 50px);
      font-weight: 700;
      color: var(--ink);
      line-height: 1.08;
      letter-spacing: -.4px;
      margin-bottom: 10px;
    }

    .sec-h2 em {
      font-style: italic;
      color: var(--muted);
    }

    .sec-sub {
      font-size: 15px;
      color: var(--muted);
      max-width: 440px;
      margin: 0 auto;
      line-height: 1.65;
    }

    /* ════════════════════════════════════
       BEST BOOKS
    ════════════════════════════════════ */
    .books-sec {
      padding: 84px 0;
    }

    /* Category tabs */
    .cat-tabs {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 6px;
      margin-bottom: 44px;
    }

    .cat-tab {
      padding: 9px 20px;
      border: 1.5px solid var(--border);
      background: #fff;
      color: var(--muted);
      font-size: 13px;
      font-weight: 600;
      border-radius: 99px;
      cursor: pointer;
      font-family: "Outfit", sans-serif;
      transition: all var(--t);
      white-space: nowrap;
    }

    .cat-tab:hover {
      border-color: var(--ink);
      color: var(--ink);
      background: var(--cream);
    }

    .cat-tab.active {
      background: var(--ink);
      border-color: var(--ink);
      color: #faf7f2;
      box-shadow: 0 4px 14px rgba(26, 18, 8, .2);
    }

    /* Book cards */
    .books-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
      gap: 20px;
    }

    @media (max-width: 560px) {
      .books-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
      }
    }

    .book-card {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: var(--r-lg);
      overflow: hidden;
      text-decoration: none;
      color: inherit;
      display: flex;
      flex-direction: column;
      transition: all .25s var(--t);
      position: relative;
    }

    .book-card::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 0;
      background: linear-gradient(90deg, var(--accent), var(--accent2));
      transition: height .25s var(--t);
    }

    .book-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 18px 44px rgba(26, 18, 8, .14);
      color: inherit;
      border-color: var(--cream-dark);
    }

    .book-card:hover::after {
      height: 3px;
    }

    .bc-rank {
      position: absolute;
      top: 10px;
      left: 10px;
      width: 26px;
      height: 26px;
      border-radius: 50%;
      background: var(--ink);
      color: #faf7f2;
      font-size: 10.5px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: "DM Mono", monospace;
      box-shadow: 0 2px 8px rgba(26, 18, 8, .3);
      z-index: 1;
    }

    .bc-cover {
      aspect-ratio: 3/4;
      overflow: hidden;
      background: var(--cream);
      position: relative;
    }

    .bc-cover img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform .5s var(--t);
    }

    .book-card:hover .bc-cover img {
      transform: scale(1.06);
    }

    .bc-cover-ph {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 38px;
      color: var(--cream-dark);
      background: linear-gradient(135deg, var(--cream), #ede6d8);
    }

    .bc-overlay {
      position: absolute;
      inset: 0;
      background: rgba(26, 18, 8, .52);
      backdrop-filter: blur(2px);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity var(--t);
    }

    .book-card:hover .bc-overlay {
      opacity: 1;
    }

    .bc-overlay span {
      background: #fff;
      color: var(--ink);
      font-size: 12px;
      font-weight: 700;
      padding: 8px 16px;
      border-radius: var(--r);
      transform: translateY(6px);
      transition: transform var(--t);
      font-family: "Outfit", sans-serif;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .book-card:hover .bc-overlay span {
      transform: translateY(0);
    }

    .bc-body {
      padding: 12px 14px 15px;
      display: flex;
      flex-direction: column;
      gap: 4px;
      flex: 1;
    }

    .bc-title {
      font-family: "Cormorant Garamond", serif;
      font-size: 15px;
      font-weight: 600;
      color: var(--ink);
      line-height: 1.25;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .bc-price {
      margin-top: auto;
      padding-top: 8px;
      font-family: "Cormorant Garamond", serif;
      font-size: 18px;
      font-weight: 700;
      color: var(--accent);
    }

    .bc-price small {
      font-family: "Outfit", sans-serif;
      font-size: 11px;
      color: var(--muted);
      font-weight: 400;
      margin-right: 2px;
    }

    .tab-panel {
      display: none;
    }

    .tab-panel.active {
      display: block;
    }

    .books-cta {
      text-align: center;
      margin-top: 46px;
    }

    .btn-outline-ink {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 13px 30px;
      border: 2px solid var(--ink);
      color: var(--ink);
      border-radius: var(--r);
      font-size: 14px;
      font-weight: 700;
      text-decoration: none;
      transition: all var(--t);
      font-family: "Outfit", sans-serif;
    }

    .btn-outline-ink:hover {
      background: var(--ink);
      color: var(--paper);
    }

    /* ════════════════════════════════════
       ABOUT
    ════════════════════════════════════ */
    .about-sec {
      background: var(--ink);
      padding: 96px 0;
      position: relative;
      overflow: hidden;
    }

    .about-sec::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse 50% 80% at 92% 50%, rgba(181, 57, 15, .2), transparent),
        radial-gradient(ellipse 35% 60% at 5% 20%, rgba(201, 146, 10, .1), transparent);
    }

    .about-sec::after {
      content: '';
      position: absolute;
      left: 0;
      right: 0;
      top: 0;
      height: 3px;
      background: linear-gradient(90deg, transparent, var(--accent2), var(--accent), transparent);
    }

    .about-inner {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 28px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 76px;
      align-items: center;
      position: relative;
      z-index: 1;
    }

    @media (max-width: 860px) {
      .about-inner {
        grid-template-columns: 1fr;
        gap: 36px;
      }
    }

    .about-img-wrap {
      position: relative;
    }

    .about-img-wrap img {
      width: 100%;
      border-radius: var(--r-lg);
      box-shadow: 0 24px 60px rgba(0, 0, 0, .5);
      display: block;
    }

    .about-img-wrap::before {
      content: '';
      position: absolute;
      top: -16px;
      left: -16px;
      right: 16px;
      bottom: 16px;
      border: 2px solid rgba(201, 146, 10, .28);
      border-radius: var(--r-lg);
      pointer-events: none;
    }

    .about-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--accent2);
      margin-bottom: 16px;
    }

    .about-eyebrow::before {
      content: '';
      display: block;
      width: 20px;
      height: 1px;
      background: currentColor;
      opacity: .6;
    }

    .about-h2 {
      font-family: "Cormorant Garamond", serif;
      font-size: clamp(32px, 4vw, 52px);
      font-weight: 700;
      color: #faf7f2;
      line-height: 1.08;
      letter-spacing: -.4px;
      margin-bottom: 20px;
    }

    .about-h2 em {
      font-style: italic;
      color: rgba(250, 247, 242, .38);
    }

    .about-p {
      font-size: 15px;
      color: rgba(250, 247, 242, .52);
      line-height: 1.78;
      margin-bottom: 14px;
    }

    .about-stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      border: 1px solid rgba(255, 255, 255, .08);
      border-radius: var(--r);
      overflow: hidden;
      margin-top: 30px;
    }

    .as-item {
      padding: 20px 14px;
      text-align: center;
      background: rgba(255, 255, 255, .04);
      border-right: 1px solid rgba(255, 255, 255, .06);
    }

    .as-item:last-child {
      border-right: none;
    }

    .as-num {
      font-family: "Cormorant Garamond", serif;
      font-size: 36px;
      font-weight: 700;
      color: #faf7f2;
      line-height: 1;
    }

    .as-num sup {
      font-size: 15px;
      color: var(--accent2);
      vertical-align: super;
    }

    .as-lbl {
      font-size: 10.5px;
      font-weight: 700;
      color: rgba(250, 247, 242, .32);
      text-transform: uppercase;
      letter-spacing: .8px;
      margin-top: 5px;
    }

    .btn-about {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 28px;
      padding: 13px 26px;
      border: 1.5px solid rgba(255, 255, 255, .18);
      color: rgba(250, 247, 242, .82);
      border-radius: var(--r);
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      transition: all var(--t);
      font-family: "Outfit", sans-serif;
    }

    .btn-about:hover {
      border-color: rgba(255, 255, 255, .5);
      color: #fff;
      background: rgba(255, 255, 255, .06);
    }

    /* ════════════════════════════════════
       SERVICES
    ════════════════════════════════════ */
    .services-sec {
      padding: 92px 0;
    }

    .svc-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 20px;
      margin-top: 50px;
    }

    .svc-card {
      background: #fff;
      border: 1px solid var(--border);
      border-radius: var(--r-lg);
      padding: 32px 26px 28px;
      transition: all .28s var(--t);
      position: relative;
      overflow: hidden;
    }

    .svc-card::before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 0;
      background: var(--ink);
      transition: height .35s var(--t);
      z-index: 0;
    }

    .svc-card:hover {
      transform: translateY(-6px);
      box-shadow: var(--shadow-lg);
      border-color: var(--ink);
    }

    .svc-card:hover::before {
      height: 100%;
    }

    .svc-card:hover .svc-icon {
      background: rgba(255, 255, 255, .1);
      color: var(--accent2);
    }

    .svc-card:hover .svc-title {
      color: #faf7f2;
    }

    .svc-card:hover .svc-desc {
      color: rgba(250, 247, 242, .5);
    }

    .svc-card:hover .svc-arr {
      opacity: 1;
      color: var(--accent2);
      transform: translate(3px, -3px);
    }

    .svc-icon {
      width: 52px;
      height: 52px;
      border-radius: var(--r);
      background: var(--cream);
      color: var(--accent);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      margin-bottom: 20px;
      transition: all var(--t);
      position: relative;
      z-index: 1;
    }

    .svc-title {
      font-family: "Cormorant Garamond", serif;
      font-size: 20px;
      font-weight: 700;
      color: var(--ink);
      margin-bottom: 10px;
      line-height: 1.2;
      position: relative;
      z-index: 1;
      transition: color var(--t);
    }

    .svc-desc {
      font-size: 13.5px;
      color: var(--muted);
      line-height: 1.65;
      position: relative;
      z-index: 1;
      transition: color var(--t);
    }

    .svc-arr {
      position: absolute;
      top: 22px;
      right: 20px;
      font-size: 15px;
      color: var(--cream-dark);
      opacity: 0;
      transition: all var(--t);
      z-index: 1;
    }

    /* ════════════════════════════════════
       CTA STRIP
    ════════════════════════════════════ */
    .cta-strip {
      background: var(--accent);
      padding: 60px 0;
      position: relative;
      overflow: hidden;
    }

    .cta-strip::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(ellipse 60% 100% at 92% 50%, rgba(0, 0, 0, .22), transparent);
    }

    /* Decorative large text */
    .cta-strip::after {
      content: 'PUBLISH';
      position: absolute;
      right: -20px;
      top: 50%;
      transform: translateY(-50%);
      font-family: "Cormorant Garamond", serif;
      font-size: 160px;
      font-weight: 700;
      color: rgba(255, 255, 255, .05);
      line-height: 1;
      pointer-events: none;
      letter-spacing: -4px;
    }

    .cta-inner {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 28px;
      position: relative;
      z-index: 1;
    }

    @media (max-width: 720px) {
      .cta-inner {
        flex-direction: column;
        text-align: center;
      }
    }

    .cta-h2 {
      font-family: "Cormorant Garamond", serif;
      font-size: clamp(26px, 3.5vw, 42px);
      font-weight: 700;
      color: #fff;
      margin-bottom: 6px;
      line-height: 1.12;
    }

    .cta-p {
      font-size: 15px;
      color: rgba(255, 255, 255, .62);
    }

    .btn-cta {
      display: inline-flex;
      align-items: center;
      gap: 9px;
      padding: 15px 32px;
      background: #fff;
      color: var(--accent);
      border-radius: var(--r);
      font-size: 14.5px;
      font-weight: 700;
      text-decoration: none;
      white-space: nowrap;
      transition: all var(--t);
      font-family: "Outfit", sans-serif;
      box-shadow: 0 4px 20px rgba(0, 0, 0, .2);
      flex-shrink: 0;
    }

    .btn-cta:hover {
      background: var(--ink);
      color: #fff;
      box-shadow: 0 8px 28px rgba(0, 0, 0, .35);
      transform: translateY(-2px);
    }

    /* ════════════════════════════════════
       SCROLL REVEAL
    ════════════════════════════════════ */
    .reveal {
      opacity: 0;
      transform: translateY(26px);
      transition: opacity .6s var(--t), transform .6s var(--t);
    }

    .reveal-left {
      opacity: 0;
      transform: translateX(-28px);
      transition: opacity .65s var(--t), transform .65s var(--t);
    }

    .reveal-right {
      opacity: 0;
      transform: translateX(28px);
      transition: opacity .65s var(--t), transform .65s var(--t);
    }

    .reveal.in,
    .reveal-left.in,
    .reveal-right.in {
      opacity: 1;
      transform: none;
    }
  </style>
</head>

<body>

  <?php include 'header.php'; ?>

  <!-- ══════════════════════════════════════════════
     HERO
══════════════════════════════════════════════ -->
  <div class="hero-wrap" id="heroWrap">

    <!-- Slide 1 -->
    <div class="hero-slide slide-1 active-slide">
      <div class="slide-grid"></div>
      <div class="hero-inner">
        <div class="hero-text">
          <div class="hero-eyebrow">Since 2020</div>
          <h1 class="hero-h1">Professional<br><em>Publication</em><br>Services</h1>
          <p class="hero-p">Trusted academic and scientific publication support — from first draft to final
            journal.</p>
          <div class="hero-btns">
            <a href="books.php" class="btn-hp btn-hp-solid"><i class="fas fa-book-open"></i> Browse
              Books</a>
            <a href="contact.php" class="btn-hp btn-hp-ghost">Get in Touch <i class="fas fa-arrow-right"></i></a>
          </div>
        </div>
        <div class="hero-visual">
          <div class="stat-cluster">
            <div class="stat-card">
              <div class="stat-num"><?= number_format($totalBooks) ?><sup>+</sup></div>
              <div class="stat-lbl">Books</div>
            </div>
            <div class="stat-card">
              <div class="stat-num"><?= number_format($totalAuthors) ?><sup>+</sup></div>
              <div class="stat-lbl">Authors</div>
            </div>
            <div class="stat-card">
              <div class="stat-num">1K<sup>+</sup></div>
              <div class="stat-lbl">Clients</div>
            </div>
            <div class="stat-card">
              <div class="stat-num"><?= number_format($totalCats) ?><sup>+</sup></div>
              <div class="stat-lbl">Categories</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Slide 2 -->
    <div class="hero-slide slide-2">
      <div class="slide-grid"></div>
      <div class="hero-inner">
        <div class="hero-text">
          <div class="hero-eyebrow">Expertise</div>
          <h1 class="hero-h1">Expert<br><em>Medical</em><br>Writing</h1>
          <p class="hero-p">Manuscripts, journals and language editing handled by experienced professionals.
          </p>
          <div class="hero-btns">
            <a href="contact.php" class="btn-hp btn-hp-solid"><i class="fas fa-pen-nib"></i> Our
              Services</a>
            <a href="books.php" class="btn-hp btn-hp-ghost">View Books <i class="fas fa-arrow-right"></i></a>
          </div>
        </div>
        <div class="hero-visual">
          <div class="stat-cluster">
            <div class="stat-card">
              <div class="stat-num">500<sup>+</sup></div>
              <div class="stat-lbl">Manuscripts</div>
            </div>
            <div class="stat-card">
              <div class="stat-num">50<sup>+</sup></div>
              <div class="stat-lbl">Journals</div>
            </div>
            <div class="stat-card">
              <div class="stat-num">98<sup>%</sup></div>
              <div class="stat-lbl">Acceptance</div>
            </div>
            <div class="stat-card">
              <div class="stat-num">5<sup>★</sup></div>
              <div class="stat-lbl">Rated</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Slide 3 -->
    <div class="hero-slide slide-3">
      <div class="slide-grid"></div>
      <div class="hero-inner">
        <div class="hero-text">
          <div class="hero-eyebrow">End-to-End</div>
          <h1 class="hero-h1">Complete<br><em>Publication</em><br>Support</h1>
          <p class="hero-p">From first draft to final publication — every step managed for you.</p>
          <div class="hero-btns">
            <a href="contact.php" class="btn-hp btn-hp-solid"><i class="fas fa-file-signature"></i> Contact
              Us</a>
            <a href="books.php" class="btn-hp btn-hp-ghost">Browse Books <i class="fas fa-arrow-right"></i></a>
          </div>
        </div>
        <div class="hero-visual">
          <div class="stat-cluster">
            <div class="stat-card">
              <div class="stat-num">4<sup>yr</sup></div>
              <div class="stat-lbl">Experience</div>
            </div>
            <div class="stat-card">
              <div class="stat-num">24<sup>h</sup></div>
              <div class="stat-lbl">Support</div>
            </div>
            <div class="stat-card">
              <div class="stat-num">100<sup>%</sup></div>
              <div class="stat-lbl">Confidential</div>
            </div>
            <div class="stat-card">
              <div class="stat-num">1K<sup>+</sup></div>
              <div class="stat-lbl">Clients</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Controls -->
    <div class="hero-ctrl hero-ctrl-prev" onclick="heroMove(-1)"><i class="fas fa-chevron-left"></i></div>
    <div class="hero-ctrl hero-ctrl-next" onclick="heroMove(1)"><i class="fas fa-chevron-right"></i></div>

    <div class="hero-dots" id="heroDots">
      <button class="hero-dot active" onclick="heroGoto(0)"></button>
      <button class="hero-dot" onclick="heroGoto(1)"></button>
      <button class="hero-dot" onclick="heroGoto(2)"></button>
    </div>
  </div>


  <!-- ══════════════════════════════════════════════
     BEST BOOKS
══════════════════════════════════════════════ -->
  <section class="books-sec">
    <div class="container">

      <div class="sec-header reveal">
        <div class="sec-eyebrow">Curated Collection</div>
        <h2 class="sec-h2">Best <em>Books</em></h2>
        <p class="sec-sub">Handpicked books from our library — curated by category</p>
      </div>

      <?php if (empty($categories)): ?>
        <p class="text-center py-4" style="color:var(--muted)">No featured books yet. Check back soon.</p>
      <?php else: ?>

        <!-- Tabs -->
        <div class="cat-tabs reveal">
          <?php foreach ($categories as $i => $cat): ?>
            <button class="cat-tab <?= $i === 0 ? 'active' : '' ?>"
              onclick="switchTab(this,'panel-<?= (int) $cat['id'] ?>')">
              <?= htmlspecialchars(ucfirst($cat['name']), ENT_QUOTES) ?>
            </button>
          <?php endforeach; ?>
        </div>

        <!-- Panels -->
        <?php
        include 'db.php';
        foreach ($categories as $i => $cat):
          $cid = (int) $cat['id'];
          $bSt = $conn->prepare("
                SELECT b.id, b.title, b.img, b.price
                FROM   books_data b
                INNER JOIN best_books bb ON bb.book_id = b.id
                WHERE  bb.category_id = ?
                ORDER  BY bb.sort_order ASC
            ");
          $bSt->bind_param("i", $cid);
          $bSt->execute();
          $bRes = $bSt->get_result();
          $catBooks = $bRes ? $bRes->fetch_all(MYSQLI_ASSOC) : [];
          $bSt->close();
          ?>
          <div class="tab-panel <?= $i === 0 ? 'active' : '' ?>" id="panel-<?= $cid ?>">
            <?php if (!empty($catBooks)): ?>
              <div class="books-grid">
                <?php foreach ($catBooks as $bi => $book):
                  $imgPath = "../" . ($book['img'] ?? '');
                  $hasImg = !empty($book['img']) && file_exists("../" . $book['img']);
                  ?>
                  <a href="book_details.php?id=<?= (int) $book['id'] ?>" class="book-card reveal"
                    style="transition-delay:<?= $bi * .05 ?>s">
                    <div class="bc-rank"><?= $bi + 1 ?></div>
                    <div class="bc-cover">
                      <?php if ($hasImg): ?>
                        <img src="<?= htmlspecialchars($imgPath, ENT_QUOTES) ?>"
                          alt="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>" loading="lazy">
                      <?php else: ?>
                        <div class="bc-cover-ph"><i class="fas fa-book"></i></div>
                      <?php endif; ?>
                      <div class="bc-overlay"><span><i class="fas fa-eye"></i> View Book</span></div>
                    </div>
                    <div class="bc-body">
                      <div class="bc-title"><?= htmlspecialchars($book['title'], ENT_QUOTES) ?></div>
                      <?php if (!empty($book['price'])): ?>
                        <div class="bc-price"><small>₹</small><?= htmlspecialchars($book['price'], ENT_QUOTES) ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="text-center py-4" style="color:var(--muted)">No featured books in this category yet.</p>
            <?php endif; ?>
          </div>
        <?php endforeach;
        $conn->close(); ?>

        <div class="books-cta reveal">
          <a href="books.php" class="btn-outline-ink">View All Books &nbsp;<i class="fas fa-arrow-right"></i></a>
        </div>

      <?php endif; ?>
    </div>
  </section>


  <!-- ══════════════════════════════════════════════
     ABOUT
══════════════════════════════════════════════ -->
  <section class="about-sec">
    <div class="about-inner">
      <div class="about-img-wrap reveal-left">
        <img src="../uploads/assets/about-img.png" alt="About us" height="500px" loading="lazy">
      </div>
      <div class="reveal-right">
        <div class="about-eyebrow">Who We Are</div>
        <h2 class="about-h2">About <em>Our</em><br>Publication House</h2>
        <p class="about-p">Founded in 2020, Professional Publication Services has rapidly established itself as
          a trusted provider in the academic and scientific writing industry. Our team includes esteemed
          doctors, faculty members and experienced scientists from a wide range of fields, including Medical
          Sciences, Dental Sciences, Nursing, Paramedical, and Life Sciences. We aim to deliver top-quality,
          comprehensive publication services to meet the diverse needs of researchers, academicians, and
          professionals.

        </p>
        <p class="about-p">Since our inception, we have had the privilege of serving over 1,000 doctors,
          scientists, and
          researchers from various domains. At Professional Publication Services, we are committed to
          supporting the academic and research community by providing reliable, high-quality, and professional
          publication services.</p>
        <div class="about-stats">
          <div class="as-item">
            <div class="as-num"><?= number_format($totalBooks) ?><sup>+</sup></div>
            <div class="as-lbl">Books</div>
          </div>
          <div class="as-item">
            <div class="as-num">1K<sup>+</sup></div>
            <div class="as-lbl">Clients</div>
          </div>
          <div class="as-item">
            <div class="as-num">4<sup>yr</sup></div>
            <div class="as-lbl">Experience</div>
          </div>
        </div>
        <a href="contact.php" class="btn-about">Get in Touch &nbsp;<i class="fas fa-arrow-right"></i></a>
      </div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════════
     SERVICES
══════════════════════════════════════════════ -->
  <?php if (!empty($svcList)): ?>
    <section class="services-sec">
      <div class="container">
        <div class="sec-header reveal">
          <div class="sec-eyebrow">What We Offer</div>
          <h2 class="sec-h2">Our <em>Services</em></h2>
          <p class="sec-sub">Expert solutions for your academic and scientific publication needs</p>
        </div>
        <div class="svc-grid">
          <?php foreach ($svcList as $si => $svc): ?>
            <div class="svc-card reveal" style="transition-delay:<?= $si * .07 ?>s">
              <i class="fas fa-arrow-up-right svc-arr"></i>
              <div class="svc-icon">
                <i class="<?= htmlspecialchars($svc['icon'], ENT_QUOTES) ?>"></i>
              </div>
              <div class="svc-title"><?= htmlspecialchars($svc['title'], ENT_QUOTES) ?></div>
              <?php if (!empty($svc['description'])): ?>
                <div class="svc-desc"><?= htmlspecialchars($svc['description'], ENT_QUOTES) ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>


  <!-- ══════════════════════════════════════════════
     CTA STRIP
══════════════════════════════════════════════ -->
  <div class="cta-strip">
    <div class="cta-inner">
      <div>
        <div class="cta-h2">Ready to Publish Your Work?</div>
        <p class="cta-p">Let our experts handle everything from writing to submission.</p>
      </div>
      <a href="contact.php" class="btn-cta">
        <i class="fas fa-paper-plane"></i> Contact Us Today
      </a>
    </div>
  </div>


  <?php include 'footer.php'; ?>
  <?php if (file_exists('utils/whatsapp-icon.php'))
    include 'utils/whatsapp-icon.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    /* ─── Hero carousel ─────────────────────────────────────────── */
    const hSlides = document.querySelectorAll('.hero-slide');
    const hDots = document.querySelectorAll('.hero-dot');
    let hCur = 0, hTimer;

    function showSlide(n) {
      hSlides[hCur].classList.remove('active-slide');
      hDots[hCur].classList.remove('active');
      hCur = (n + hSlides.length) % hSlides.length;
      hSlides[hCur].classList.add('active-slide');
      hDots[hCur].classList.add('active');
    }
    function heroMove(d) { clearInterval(hTimer); showSlide(hCur + d); startAuto(); }
    function heroGoto(n) { clearInterval(hTimer); showSlide(n); startAuto(); }
    function startAuto() { hTimer = setInterval(() => showSlide(hCur + 1), 5200); }
    startAuto();

    /* ─── Book tabs ─────────────────────────────────────────────── */
    function switchTab(btn, panelId) {
      document.querySelectorAll('.cat-tab').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const panel = document.getElementById(panelId);
      if (panel) {
        panel.classList.add('active');
        // Trigger reveals for newly visible cards
        panel.querySelectorAll('.reveal:not(.in)').forEach(el => {
          requestAnimationFrame(() => el.classList.add('in'));
        });
      }
    }

    /* ─── Scroll reveal ─────────────────────────────────────────── */
    const ro = new IntersectionObserver(entries => {
      entries.forEach(e => {
        if (e.isIntersecting) { e.target.classList.add('in'); ro.unobserve(e.target); }
      });
    }, { threshold: 0.08 });

    document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(el => ro.observe(el));
  </script>
</body>

</html>