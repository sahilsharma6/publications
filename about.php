<?php
include 'db.php';
session_start();

$totalBooks = (int) $conn->query("SELECT COUNT(*) FROM books_data")->fetch_row()[0];
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us — Professional Publication Services</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">

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
            --r-xl: 20px;
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
       PAGE HERO
    ════════════════════════════════════ */
        .page-hero {
            position: relative;
            background: linear-gradient(135deg, #0d0a06 0%, #1a1208 50%, #221508 100%);
            overflow: hidden;
            padding: 100px 0 86px;
        }

        .page-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 55% 80% at 85% 55%, rgba(181, 57, 15, .25), transparent),
                radial-gradient(ellipse 40% 60% at 5% 80%, rgba(201, 146, 10, .12), transparent);
        }

        .page-hero::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255, 255, 255, .02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .02) 1px, transparent 1px);
            background-size: 48px 48px;
        }

        .hero-ghost {
            position: absolute;
            right: -40px;
            top: 50%;
            transform: translateY(-50%);
            font-family: "Cormorant Garamond", serif;
            font-size: clamp(80px, 14vw, 200px);
            font-weight: 700;
            color: rgba(255, 255, 255, .028);
            line-height: 1;
            letter-spacing: -4px;
            pointer-events: none;
            white-space: nowrap;
            z-index: 1;
        }

        .hero-inner {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 28px;
            text-align: center;
        }

        .hero-breadcrumb {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 12.5px;
            color: rgba(250, 247, 242, .35);
            margin-bottom: 24px;
        }

        .hero-breadcrumb a {
            color: rgba(250, 247, 242, .45);
            text-decoration: none;
            transition: color var(--t);
        }

        .hero-breadcrumb a:hover {
            color: var(--accent2);
        }

        .hero-breadcrumb i {
            font-size: 10px;
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
            width: 24px;
            height: 1px;
            background: currentColor;
            opacity: .6;
        }

        .hero-h1 {
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: clamp(38px, 5.5vw, 70px);
            font-weight: 700;
            line-height: 1.05;
            letter-spacing: -.6px;
            color: #faf7f2;
            margin-bottom: 18px;
        }

        .hero-h1 em {
            font-style: italic;
            color: rgba(250, 247, 242, .38);
        }

        .hero-p {
            font-size: 16px;
            color: rgba(250, 247, 242, .5);
            line-height: 1.72;
            max-width: 560px;
            margin: 0 auto;
        }

        .page-hero-rule {
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--accent), var(--accent2), transparent);
        }

        /* ════════════════════════════════════
       SHARED
    ════════════════════════════════════ */
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
            font-size: clamp(28px, 4vw, 46px);
            font-weight: 700;
            color: var(--ink);
            line-height: 1.08;
            letter-spacing: -.3px;
            margin-bottom: 10px;
        }

        .sec-h2 em {
            font-style: italic;
            color: var(--muted);
        }

        /* ════════════════════════════════════
       STORY SECTION
    ════════════════════════════════════ */
        .story-sec {
            padding: 88px 0;
        }

        .story-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 28px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 72px;
            align-items: center;
        }

        @media(max-width:860px) {
            .story-inner {
                grid-template-columns: 1fr;
                gap: 40px;
            }
        }

        /* Image side */
        .story-img-wrap {
            position: relative;
        }

        .story-img-wrap img {
            width: 100%;
            border-radius: var(--r-xl);
            box-shadow: var(--shadow-lg);
            display: block;
        }

        .story-img-wrap::before {
            content: '';
            position: absolute;
            top: -14px;
            left: -14px;
            right: 14px;
            bottom: 14px;
            border: 2px solid rgba(201, 146, 10, .22);
            border-radius: var(--r-xl);
            pointer-events: none;
        }

        /* Founded badge */
        .story-badge {
            position: absolute;
            bottom: -18px;
            right: -18px;
            background: var(--ink);
            border-radius: var(--r-xl);
            padding: 18px 22px;
            text-align: center;
            box-shadow: 0 12px 36px rgba(0, 0, 0, .3);
            border: 1px solid rgba(255, 255, 255, .08);
        }

        .story-badge-num {
            font-family: "Cormorant Garamond", serif;
            font-size: 40px;
            font-weight: 700;
            color: #faf7f2;
            line-height: 1;
        }

        .story-badge-num sup {
            font-size: 16px;
            color: var(--accent2);
            vertical-align: super;
        }

        .story-badge-lbl {
            font-size: 10px;
            font-weight: 700;
            color: rgba(250, 247, 242, .38);
            text-transform: uppercase;
            letter-spacing: .8px;
            margin-top: 3px;
        }

        @media(max-width:560px) {
            .story-badge {
                bottom: 10px;
                right: 10px;
            }
        }

        /* Text side */
        .story-text p {
            font-size: 15px;
            color: var(--muted);
            line-height: 1.8;
            margin-bottom: 16px;
        }

        .story-text p:last-of-type {
            margin-bottom: 28px;
        }

        .story-mission {
            background: var(--cream);
            border: 1px solid var(--border);
            border-left: 4px solid var(--accent);
            border-radius: var(--r-lg);
            padding: 18px 20px;
            margin-bottom: 28px;
        }

        .story-mission-label {
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 6px;
        }

        .story-mission-text {
            font-family: "Cormorant Garamond", serif;
            font-size: 18px;
            font-weight: 600;
            color: var(--ink);
            line-height: 1.4;
            font-style: italic;
        }

        .btn-story {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 26px;
            background: var(--ink);
            color: #faf7f2;
            border-radius: var(--r);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all var(--t);
            font-family: "Outfit", sans-serif;
        }

        .btn-story:hover {
            background: var(--accent);
            color: #fff;
            transform: translateY(-2px);
        }

        /* ════════════════════════════════════
       STATS BAND
    ════════════════════════════════════ */
        .stats-band {
            background: var(--ink);
            position: relative;
            overflow: hidden;
        }

        .stats-band::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 50% 100% at 95% 50%, rgba(181, 57, 15, .18), transparent),
                radial-gradient(ellipse 35% 80% at 3% 50%, rgba(201, 146, 10, .1), transparent);
        }

        .stats-band-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 56px 28px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            position: relative;
            z-index: 1;
        }

        @media(max-width:720px) {
            .stats-band-inner {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media(max-width:400px) {
            .stats-band-inner {
                grid-template-columns: 1fr;
            }
        }

        .sband-item {
            text-align: center;
            padding: 24px 20px;
            border-right: 1px solid rgba(255, 255, 255, .07);
        }

        .sband-item:last-child {
            border-right: none;
        }

        @media(max-width:720px) {
            .sband-item:nth-child(2) {
                border-right: none;
            }

            .sband-item:nth-child(3) {
                border-top: 1px solid rgba(255, 255, 255, .07);
            }

            .sband-item:nth-child(4) {
                border-top: 1px solid rgba(255, 255, 255, .07);
                border-right: none;
            }
        }

        .sband-num {
            font-family: "Cormorant Garamond", serif;
            font-size: 52px;
            font-weight: 700;
            color: #faf7f2;
            line-height: 1;
            margin-bottom: 6px;
        }

        .sband-num sup {
            font-size: 22px;
            color: var(--accent2);
            vertical-align: super;
        }

        .sband-lbl {
            font-size: 11px;
            font-weight: 700;
            color: rgba(250, 247, 242, .38);
            text-transform: uppercase;
            letter-spacing: .8px;
        }

        /* ════════════════════════════════════
       WHAT WE OFFER (services list)
    ════════════════════════════════════ */
        .offer-sec {
            padding: 88px 0;
            background: var(--cream);
            position: relative;
        }

        .offer-sec::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--accent2), var(--accent), transparent);
        }

        .offer-grid {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 28px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 14px;
            margin-top: 48px;
        }

        .offer-item {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            padding: 20px 18px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            transition: all var(--t);
        }

        .offer-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--cream-dark);
        }

        .offer-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent);
            flex-shrink: 0;
            margin-top: 7px;
        }

        .offer-text {
            font-size: 14px;
            font-weight: 500;
            color: var(--ink);
            line-height: 1.4;
        }

        /* ════════════════════════════════════
       TEAM / DISCIPLINES
    ════════════════════════════════════ */
        .team-sec {
            padding: 88px 0;
        }

        .team-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 28px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 64px;
            align-items: center;
        }

        @media(max-width:860px) {
            .team-inner {
                grid-template-columns: 1fr;
                gap: 40px;
            }
        }

        .team-text p {
            font-size: 15px;
            color: var(--muted);
            line-height: 1.78;
            margin-bottom: 14px;
        }

        .disciplines {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 20px;
        }

        .disc-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            background: var(--cream);
            border: 1px solid var(--border);
            border-radius: 99px;
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            transition: all var(--t);
        }

        .disc-chip:hover {
            background: var(--ink);
            color: #faf7f2;
            border-color: var(--ink);
        }

        .disc-chip i {
            font-size: 12px;
            color: var(--accent);
        }

        .disc-chip:hover i {
            color: var(--accent2);
        }

        /* Values card panel */
        .values-panel {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .value-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-xl);
            padding: 22px 20px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            transition: all var(--t);
            position: relative;
            overflow: hidden;
        }

        .value-card::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(to bottom, var(--accent), var(--accent2));
            border-radius: 4px 0 0 4px;
            transform: scaleY(0);
            transform-origin: bottom;
            transition: transform .3s var(--t);
        }

        .value-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateX(4px);
            border-color: var(--cream-dark);
        }

        .value-card:hover::after {
            transform: scaleY(1);
        }

        .value-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--r);
            background: var(--cream);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
            transition: all var(--t);
        }

        .value-card:hover .value-icon {
            background: var(--ink);
            color: var(--accent2);
        }

        .value-title {
            font-family: "Cormorant Garamond", serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 4px;
        }

        .value-desc {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.6;
        }

        /* ════════════════════════════════════
       CTA STRIP
    ════════════════════════════════════ */
        .cta-strip {
            background: var(--accent);
            padding: 64px 0;
            position: relative;
            overflow: hidden;
        }

        .cta-strip::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 60% 100% at 92% 50%, rgba(0, 0, 0, .2), transparent);
        }

        .cta-strip::after {
            content: 'ABOUT';
            position: absolute;
            right: -30px;
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

        @media(max-width:720px) {
            .cta-inner {
                flex-direction: column;
                text-align: center;
            }
        }

        .cta-h2 {
            font-family: "Cormorant Garamond", serif;
            font-size: clamp(24px, 3.5vw, 40px);
            font-weight: 700;
            color: #fff;
            margin-bottom: 6px;
            line-height: 1.12;
        }

        .cta-p {
            font-size: 15px;
            color: rgba(255, 255, 255, .6);
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

        @media(max-width:480px) {
            .page-hero {
                padding: 72px 0 60px;
            }
        }
    </style>
</head>

<body>

    <?php include 'Header.php'; ?>

    <!-- ══════════════════════════════════════════════
     PAGE HERO
══════════════════════════════════════════════ -->
    <div class="page-hero">
        <div class="hero-ghost">ABOUT</div>
        <div class="hero-inner">
            <div class="hero-breadcrumb">
                <a href="./"><i class="fas fa-home"></i> Home</a>
                <i class="fas fa-chevron-right"></i>
                <span>About Us</span>
            </div>
            <div class="hero-eyebrow">Since 2020</div>
            <h1 class="hero-h1">About <em>Our</em><br>Publication House</h1>
            <p class="hero-p">A trusted academic support organization dedicated to helping researchers, doctors, and
                scientists successfully publish their work worldwide.</p>
        </div>
    </div>
    <div class="page-hero-rule"></div>


    <!-- ══════════════════════════════════════════════
     OUR STORY
══════════════════════════════════════════════ -->
    <section class="story-sec">
        <div class="story-inner">

            <div class="reveal-left">
                <div class="story-img-wrap">
                    <img src="./uploads/assets/about-img.png" alt="About Professional Publication Services"
                        loading="lazy">
                    <div class="story-badge">
                        <div class="story-badge-num">6<sup>yr</sup></div>
                        <div class="story-badge-lbl">Experience</div>
                    </div>
                </div>
            </div>

            <div class="reveal-right">
                <div class="sec-eyebrow">Our Story</div>
                <h2 class="sec-h2">Who <em>We</em> Are</h2>

                <div class="story-mission">
                    <!-- <div class="story-mission-label">Our Identity</div> -->
                    <div class="story-mission-text">"To simplify the research publication process and help scholars
                        successfully publish their work in reputed national and international journals."</div>
                </div>

                <p>Professional Publication Services, founded in 2020, is a trusted academic support organization
                    dedicated to providing high-quality research publication services to researchers, doctors,
                    academicians, and scientists worldwide.</p>

                <p>Over the years, we have built a strong reputation in the academic community supporting over 1 Lakh
                    clients from various disciplines and assisting in the publication of 50+ academic books along with
                    numerous research papers in peer-reviewed journals.</p>

                <p>Our expert team consists of experienced doctors, faculty members, researchers, and scientific writers
                    from diverse disciplines. With strong academic expertise and in-depth knowledge of scientific
                    writing and journal publication standards, we ensure every manuscript meets international publishing
                    requirements.</p>

                <a href="contact.php" class="btn-story">Get in Touch &nbsp;<i class="fas fa-arrow-right"></i></a>
            </div>

        </div>
    </section>


    <!-- ══════════════════════════════════════════════
     STATS BAND
══════════════════════════════════════════════ -->
    <div class="stats-band">
        <div class="stats-band-inner">
            <div class="sband-item reveal" style="transition-delay:.05s">
                <div class="sband-num">1L<sup>+</sup></div>
                <div class="sband-lbl">Clients Served</div>
            </div>
            <div class="sband-item reveal" style="transition-delay:.12s">
                <div class="sband-num">
                    <?= number_format($totalBooks) ?><sup>+</sup>
                </div>
                <div class="sband-lbl">Books Published</div>
            </div>
            <div class="sband-item reveal" style="transition-delay:.19s">
                <div class="sband-num">6<sup>yr</sup></div>
                <div class="sband-lbl">Years Experience</div>
            </div>
            <div class="sband-item reveal" style="transition-delay:.26s">
                <div class="sband-num">100<sup>%</sup></div>
                <div class="sband-lbl">Confidential</div>
            </div>
        </div>
    </div>


    <!-- ══════════════════════════════════════════════
     WHAT WE OFFER
══════════════════════════════════════════════ -->
    <section class="offer-sec">
        <div class="container">
            <div class="text-center reveal">
                <div class="sec-eyebrow">Comprehensive Support</div>
                <h2 class="sec-h2">What We <em>Offer</em></h2>
                <p style="font-size:15px;color:var(--muted);max-width:480px;margin:0 auto;line-height:1.65">End-to-end
                    publication services designed to improve quality, clarity, and acceptance chances of your scientific
                    manuscript</p>
            </div>
        </div>
        <div class="offer-grid">
            <?php
            $services = [
                'Research Paper Writing',
                'Manuscript Editing & Proofreading',
                'Plagiarism Checking & Report',
                'Journal Selection Assistance',
                'Formatting as per Journal Guidelines',
                'Thesis-to-Paper Conversion',
                'Case Report Writing',
                'Systematic Review Writing',
                'Meta-Analysis Support',
                'Statistical & Biostatistical Analysis',
                'Scopus Journal Submission Guidance',
                'Publication Guidance & Support',
            ];
            foreach ($services as $i => $s): ?>
                <div class="offer-item reveal" style="transition-delay:<?= $i * .04 ?>s">
                    <div class="offer-dot"></div>
                    <div class="offer-text">
                        <?= htmlspecialchars($s) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>


    <!-- ══════════════════════════════════════════════
     TEAM & VALUES
══════════════════════════════════════════════ -->
    <section class="team-sec">
        <div class="team-inner">

            <div class="reveal-left">
                <div class="sec-eyebrow">Our Team & Disciplines</div>
                <h2 class="sec-h2">Expert <em>Knowledge</em><br>Across Fields</h2>
                <p>Our expert team consists of experienced doctors, faculty members, researchers, and scientific writers
                    from diverse disciplines all with in-depth knowledge of scientific writing and journal publication
                    standards.</p>
                <p>We have successfully assisted over 1,000 doctors, researchers, and academicians in publishing their
                    work in reputed journals. Our commitment to quality, integrity, confidentiality, and timely delivery
                    has made us a reliable partner for the academic and research community.</p>

                <div class="disciplines">
                    <?php
                    $discs = [
                        ['icon' => 'fas fa-heartbeat', 'name' => 'Medical Sciences'],
                        ['icon' => 'fas fa-tooth', 'name' => 'Dental Sciences'],
                        ['icon' => 'fas fa-user-nurse', 'name' => 'Nursing'],
                        ['icon' => 'fas fa-stethoscope', 'name' => 'Paramedical Sciences'],
                        ['icon' => 'fas fa-dna', 'name' => 'Life Sciences'],
                        ['icon' => 'fas fa-clinic-medical', 'name' => 'Allied Health Sciences'],
                    ];
                    foreach ($discs as $d): ?>
                        <div class="disc-chip">
                            <i class="<?= $d['icon'] ?>"></i>
                            <?= $d['name'] ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="values-panel reveal-right">
                <div class="value-card">
                    <div class="value-icon"><i class="fas fa-medal"></i></div>
                    <div>
                        <div class="value-title">Quality</div>
                        <div class="value-desc">Every manuscript is handled with meticulous attention to scientific
                            accuracy, language quality, and journal standards.</div>
                    </div>
                </div>
                <div class="value-card">
                    <div class="value-icon"><i class="fas fa-balance-scale"></i></div>
                    <div>
                        <div class="value-title">Integrity</div>
                        <div class="value-desc">We uphold the highest standards of academic and publication ethics in
                            all our services.</div>
                    </div>
                </div>
                <div class="value-card">
                    <div class="value-icon"><i class="fas fa-lock"></i></div>
                    <div>
                        <div class="value-title">Confidentiality</div>
                        <div class="value-desc">Complete data security and confidentiality for all client manuscripts
                            and research work.</div>
                    </div>
                </div>
                <div class="value-card">
                    <div class="value-icon"><i class="fas fa-clock"></i></div>
                    <div>
                        <div class="value-title">Timely Delivery</div>
                        <div class="value-desc">Most projects completed within 7–21 days. We respect your deadlines and
                            submission timelines.</div>
                    </div>
                </div>
            </div>

        </div>
    </section>


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


    <?php include 'Footer.php'; ?>
    <?php if (file_exists('utils/whatsapp-icon.php'))
        include 'utils/whatsapp-icon.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ro = new IntersectionObserver(entries => {
            entries.forEach(e => {
                if (e.isIntersecting) { e.target.classList.add('in'); ro.unobserve(e.target); }
            });
        }, { threshold: 0.08 });
        document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(el => ro.observe(el));
    </script>
</body>

</html>