<?php
session_start();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal Development Services — Professional Publication Services</title>

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

        /* subtle grid */
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

        /* giant ghost text */
        .hero-ghost {
            position: absolute;
            right: -40px;
            top: 50%;
            transform: translateY(-50%);
            font-family: "Cormorant Garamond", serif;
            font-size: clamp(80px, 12vw, 180px);
            font-weight: 700;
            color: rgba(255, 255, 255, .032);
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
            color: rgba(250, 247, 242, .40);
        }

        .hero-p {
            font-size: 16px;
            color: rgba(250, 247, 242, .50);
            line-height: 1.72;
            max-width: 580px;
            margin: 0 auto 32px;
        }

        .hero-btns {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-hp {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 26px;
            border-radius: var(--r);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all var(--t);
            font-family: "Outfit", sans-serif;
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
        }

        .btn-hp-ghost {
            border: 1.5px solid rgba(255, 255, 255, .18);
            color: rgba(250, 247, 242, .75);
        }

        .btn-hp-ghost:hover {
            border-color: rgba(255, 255, 255, .42);
            color: #fff;
            background: rgba(255, 255, 255, .06);
        }

        /* Bottom gradient rule */
        .page-hero-rule {
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--accent), var(--accent2), transparent);
        }

        /* Breadcrumb in hero */
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

        /* ════════════════════════════════════
       SHARED SECTION STYLES
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

        .sec-sub {
            font-size: 15px;
            color: var(--muted);
            max-width: 480px;
            margin: 0 auto;
            line-height: 1.65;
        }

        /* ════════════════════════════════════
       INTRO SECTION
    ════════════════════════════════════ */
        .intro-sec {
            padding: 80px 0 60px;
        }

        .intro-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 28px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 64px;
            align-items: center;
        }

        @media(max-width:860px) {
            .intro-inner {
                grid-template-columns: 1fr;
                gap: 36px;
            }
        }

        .intro-text .sec-h2 {
            text-align: left;
        }

        .intro-text .sec-eyebrow {
            display: inline-flex;
        }

        .intro-p {
            font-size: 15px;
            color: var(--muted);
            line-height: 1.78;
            margin-bottom: 14px;
        }

        .intro-highlights {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-top: 24px;
        }

        @media(max-width:500px) {
            .intro-highlights {
                grid-template-columns: 1fr;
            }
        }

        .ih-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px;
            background: var(--cream);
            border-radius: var(--r-lg);
            border: 1px solid var(--border);
            transition: all var(--t);
        }

        .ih-item:hover {
            background: #fff;
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }

        .ih-icon {
            width: 36px;
            height: 36px;
            border-radius: var(--r);
            background: var(--ink);
            color: var(--accent2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .ih-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            line-height: 1.3;
            margin-top: 2px;
        }

        /* Right stat cluster */
        .intro-visual {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .iv-card {
            background: var(--ink);
            border-radius: var(--r-xl);
            padding: 28px 24px;
            position: relative;
            overflow: hidden;
        }

        .iv-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 70% 80% at 90% 20%, rgba(201, 146, 10, .18), transparent);
        }

        .iv-card-inner {
            position: relative;
            z-index: 1;
        }

        .iv-card-tag {
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--accent2);
            margin-bottom: 8px;
        }

        .iv-card-title {
            font-family: "Cormorant Garamond", serif;
            font-size: 22px;
            font-weight: 700;
            color: #faf7f2;
            margin-bottom: 10px;
            line-height: 1.2;
        }

        .iv-card-body {
            font-size: 13.5px;
            color: rgba(250, 247, 242, .5);
            line-height: 1.65;
        }

        .iv-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .iv-stat {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            padding: 16px;
            text-align: center;
            transition: all var(--t);
        }

        .iv-stat:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .iv-stat-num {
            font-family: "Cormorant Garamond", serif;
            font-size: 36px;
            font-weight: 700;
            color: var(--ink);
            line-height: 1;
            margin-bottom: 4px;
        }

        .iv-stat-num sup {
            font-size: 16px;
            color: var(--accent2);
            vertical-align: super;
        }

        .iv-stat-lbl {
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        /* ════════════════════════════════════
       SERVICES GRID
    ════════════════════════════════════ */
        .services-sec {
            padding: 80px 0;
            background: var(--cream);
            position: relative;
        }

        .services-sec::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--accent2), var(--accent), transparent);
        }

        .services-sec::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--accent), var(--accent2), transparent);
        }

        .sec-header {
            text-align: center;
            margin-bottom: 52px;
        }

        .svc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 28px;
        }

        .svc-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-xl);
            padding: 28px 24px 24px;
            position: relative;
            overflow: hidden;
            transition: all .28s var(--t);
        }

        .svc-card::after {
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

        .svc-card:hover::after {
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
            transform: translate(2px, -2px);
        }

        .svc-num {
            position: absolute;
            top: 16px;
            right: 20px;
            font-family: "DM Mono", monospace;
            font-size: 11px;
            font-weight: 500;
            color: var(--cream-dark);
            z-index: 1;
            transition: color var(--t);
        }

        .svc-card:hover .svc-num {
            color: rgba(255, 255, 255, .15);
        }

        .svc-arr {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 14px;
            color: var(--cream-dark);
            opacity: 0;
            transition: all var(--t);
            z-index: 1;
        }

        .svc-icon {
            width: 50px;
            height: 50px;
            border-radius: var(--r);
            background: var(--cream);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 18px;
            transition: all var(--t);
            position: relative;
            z-index: 1;
        }

        .svc-title {
            font-family: "Cormorant Garamond", serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 9px;
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

        /* ════════════════════════════════════
       INDEXING SECTION
    ════════════════════════════════════ */
        .indexing-sec {
            padding: 80px 0;
            background: var(--ink);
            position: relative;
            overflow: hidden;
        }

        .indexing-sec::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 55% 80% at 95% 50%, rgba(181, 57, 15, .18), transparent),
                radial-gradient(ellipse 35% 55% at 3% 20%, rgba(201, 146, 10, .1), transparent);
        }

        .indexing-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 28px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 64px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        @media(max-width:860px) {
            .indexing-inner {
                grid-template-columns: 1fr;
                gap: 40px;
            }
        }

        .idx-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--accent2);
            margin-bottom: 14px;
        }

        .idx-eyebrow::before {
            content: '';
            display: block;
            width: 20px;
            height: 1px;
            background: currentColor;
            opacity: .6;
        }

        .idx-h2 {
            font-family: "Cormorant Garamond", serif;
            font-size: clamp(28px, 3.8vw, 48px);
            font-weight: 700;
            color: #faf7f2;
            line-height: 1.08;
            letter-spacing: -.4px;
            margin-bottom: 18px;
        }

        .idx-h2 em {
            font-style: italic;
            color: rgba(250, 247, 242, .35);
        }

        .idx-p {
            font-size: 15px;
            color: rgba(250, 247, 242, .5);
            line-height: 1.75;
            margin-bottom: 28px;
        }

        .idx-platforms {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .idx-platform {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: var(--r-lg);
            transition: all var(--t);
        }

        .idx-platform:hover {
            background: rgba(255, 255, 255, .09);
            border-color: rgba(201, 146, 10, .25);
            transform: translateX(4px);
        }

        .idx-platform-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--r);
            background: rgba(201, 146, 10, .15);
            color: var(--accent2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .idx-platform-name {
            font-size: 14px;
            font-weight: 600;
            color: rgba(250, 247, 242, .85);
        }

        .idx-platform-sub {
            font-size: 12px;
            color: rgba(250, 247, 242, .38);
            margin-top: 1px;
        }

        .idx-platform-badge {
            margin-left: auto;
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 99px;
            background: rgba(201, 146, 10, .18);
            color: var(--accent2);
            flex-shrink: 0;
        }

        /* Right visual: why choose us */
        .idx-reasons {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .idx-reason {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 18px 18px;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(255, 255, 255, .07);
            border-radius: var(--r-lg);
            transition: all var(--t);
        }

        .idx-reason:hover {
            background: rgba(255, 255, 255, .08);
            border-color: rgba(181, 57, 15, .25);
        }

        .idx-check {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(181, 57, 15, .2);
            color: #e07055;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .idx-reason-text {
            font-size: 14px;
            color: rgba(250, 247, 242, .65);
            line-height: 1.6;
        }

        .idx-reason-text strong {
            color: rgba(250, 247, 242, .9);
            font-weight: 600;
            display: block;
            margin-bottom: 2px;
        }

        /* ════════════════════════════════════
       PROCESS SECTION
    ════════════════════════════════════ */
        .process-sec {
            padding: 88px 0;
        }

        .process-timeline {
            max-width: 820px;
            margin: 52px auto 0;
            padding: 0 28px;
            position: relative;
        }

        /* Vertical line */
        .process-timeline::before {
            content: '';
            position: absolute;
            left: calc(28px + 22px);
            top: 12px;
            bottom: 12px;
            width: 2px;
            background: linear-gradient(to bottom, var(--accent), var(--accent2), transparent);
            border-radius: 99px;
        }

        @media(max-width:600px) {
            .process-timeline::before {
                display: none;
            }
        }

        .process-step {
            display: flex;
            gap: 28px;
            align-items: flex-start;
            margin-bottom: 36px;
            position: relative;
        }

        .process-step:last-child {
            margin-bottom: 0;
        }

        .ps-num {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: var(--ink);
            color: #faf7f2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: "DM Mono", monospace;
            font-size: 14px;
            font-weight: 500;
            flex-shrink: 0;
            box-shadow: 0 0 0 4px var(--paper), 0 0 0 6px var(--accent2);
            position: relative;
            z-index: 1;
            transition: all var(--t);
        }

        .process-step:hover .ps-num {
            background: var(--accent);
            box-shadow: 0 0 0 4px var(--paper), 0 0 0 6px var(--accent), 0 8px 20px rgba(181, 57, 15, .35);
        }

        .ps-body {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-xl);
            padding: 22px 24px;
            flex: 1;
            transition: all var(--t);
        }

        .process-step:hover .ps-body {
            box-shadow: var(--shadow-lg);
            border-color: var(--cream-dark);
            transform: translateX(4px);
        }

        .ps-title {
            font-family: "Cormorant Garamond", serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 6px;
        }

        .ps-desc {
            font-size: 14px;
            color: var(--muted);
            line-height: 1.65;
        }

        /* ════════════════════════════════════
       EXPERTISE / DISCIPLINES
    ════════════════════════════════════ */
        .expertise-sec {
            padding: 80px 0;
            background: var(--cream);
        }

        .disciplines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 14px;
            max-width: 900px;
            margin: 44px auto 0;
            padding: 0 28px;
        }

        .disc-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            padding: 20px 16px;
            text-align: center;
            transition: all var(--t);
            position: relative;
            overflow: hidden;
        }

        .disc-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), var(--accent2));
            transform: scaleX(0);
            transition: transform var(--t);
        }

        .disc-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--cream-dark);
        }

        .disc-card:hover::after {
            transform: scaleX(1);
        }

        .disc-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--cream);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin: 0 auto 12px;
            transition: all var(--t);
        }

        .disc-card:hover .disc-icon {
            background: var(--ink);
            color: var(--accent2);
        }

        .disc-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--ink);
            line-height: 1.3;
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
            content: 'JOURNAL';
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

        /* ════════════════════════════════════
       RESPONSIVE
    ════════════════════════════════════ */
        @media(max-width:560px) {
            .svc-grid {
                grid-template-columns: 1fr;
            }

            .disciplines-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .process-timeline::before {
                display: none;
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
        <div class="hero-ghost">JOURNAL</div>
        <div class="hero-inner">
            <div class="hero-breadcrumb">
                <a href="./"><i class="fas fa-home"></i> Home</a>
                <i class="fas fa-chevron-right"></i>
                <a href="#">Services</a>
                <i class="fas fa-chevron-right"></i>
                <span>Journal Development</span>
            </div>
            <div class="hero-eyebrow">Academic Publishing</div>
            <h1 class="hero-h1">Journal<br><em>Development</em><br>Services</h1>
            <p class="hero-p">Build and develop high-quality, peer-reviewed academic journals that meet international
                publishing standards with full expert support.</p>
            <div class="hero-btns">
                <a href="contact.php" class="btn-hp btn-hp-solid"><i class="fas fa-paper-plane"></i> Start Your
                    Journal</a>
                <a href="#services" class="btn-hp btn-hp-ghost">Explore Services <i class="fas fa-arrow-down"></i></a>
            </div>
        </div>
    </div>
    <div class="page-hero-rule"></div>


    <!-- ══════════════════════════════════════════════
     INTRO
══════════════════════════════════════════════ -->
    <section class="intro-sec">
        <div class="intro-inner">

            <div class="reveal-left">
                <div class="sec-eyebrow">Who We Serve</div>
                <h2 class="sec-h2">Build <em>Academic</em><br>Journals That Last</h2>
                <p class="intro-p">At Professional Publication Services, we provide comprehensive Journal Development
                    and Management Services for universities, research institutions, academic societies, and independent
                    publishers.</p>
                <p class="intro-p">Our goal is to help organizations establish professional, peer-reviewed academic
                    journals that follow international publishing standards — with high academic integrity and global
                    visibility.</p>

                <div class="intro-highlights">
                    <div class="ih-item">
                        <div class="ih-icon"><i class="fas fa-university"></i></div>
                        <div class="ih-label">Universities & Research Institutions</div>
                    </div>
                    <div class="ih-item">
                        <div class="ih-icon"><i class="fas fa-users"></i></div>
                        <div class="ih-label">Academic Societies & Publishers</div>
                    </div>
                    <div class="ih-item">
                        <div class="ih-icon"><i class="fas fa-globe"></i></div>
                        <div class="ih-label">International Standards Compliant</div>
                    </div>
                    <div class="ih-item">
                        <div class="ih-icon"><i class="fas fa-shield-alt"></i></div>
                        <div class="ih-label">Peer-Review Integrity</div>
                    </div>
                </div>
            </div>

            <div class="reveal-right">
                <div class="iv-card" style="margin-bottom:16px">
                    <div class="iv-card-inner">
                        <div class="iv-card-tag">Our Idea </div>
                        <div class="iv-card-title">From Concept to Indexed Academic Journal</div>
                        <div class="iv-card-body">With our expertise in scientific publishing, editorial workflows, and
                            indexing preparation, we assist in creating journals that maintain high academic integrity
                            and global visibility.</div>
                    </div>
                </div>
                <div class="iv-stats">
                    <div class="iv-stat">
                        <div class="iv-stat-num">6<sup>yr</sup></div>
                        <div class="iv-stat-lbl">Experience</div>
                    </div>
                    <div class="iv-stat">
                        <div class="iv-stat-num">1K<sup>+</sup></div>
                        <div class="iv-stat-lbl">Clients</div>
                    </div>
                    <div class="iv-stat">
                        <div class="iv-stat-num">3000<sup>+</sup></div>
                        <div class="iv-stat-lbl">Journals</div>
                    </div>
                    <div class="iv-stat">
                        <div class="iv-stat-num">5<sup>★</sup></div>
                        <div class="iv-stat-lbl">Rated</div>
                    </div>
                </div>
            </div>

        </div>
    </section>


    <!-- ══════════════════════════════════════════════
     SERVICES GRID
══════════════════════════════════════════════ -->
    <section class="services-sec" id="services">
        <div class="sec-header reveal">
            <div class="sec-eyebrow">What We Offer</div>
            <h2 class="sec-h2">Our Journal <em>Development</em> Services</h2>
            <p class="sec-sub">End-to-end support for building, launching, and growing your academic journal</p>
        </div>

        <div class="svc-grid">

            <div class="svc-card reveal" style="transition-delay:.05s">
                <span class="svc-num">01</span>
                <i class="fas fa-arrow-up-right svc-arr"></i>
                <div class="svc-icon"><i class="fas fa-globe"></i></div>
                <div class="svc-title">Journal Website Development</div>
                <div class="svc-desc">Modern, user-friendly journal websites with manuscript submission systems,
                    editorial workflows, and complete archive management.</div>
            </div>

            <div class="svc-card reveal" style="transition-delay:.10s">
                <span class="svc-num">02</span>
                <i class="fas fa-arrow-up-right svc-arr"></i>
                <div class="svc-icon"><i class="fas fa-barcode"></i></div>
                <div class="svc-title">ISSN Registration Support</div>
                <div class="svc-desc">Guidance and assistance in obtaining ISSN (International Standard Serial Number)
                    for newly established academic journals.</div>
            </div>

            <div class="svc-card reveal" style="transition-delay:.15s">
                <span class="svc-num">03</span>
                <i class="fas fa-arrow-up-right svc-arr"></i>
                <div class="svc-icon"><i class="fas fa-users-cog"></i></div>
                <div class="svc-title">Editorial Board Development</div>
                <div class="svc-desc">Support in forming a strong editorial board and reviewer panel consisting of
                    experienced academicians and researchers.</div>
            </div>

            <div class="svc-card reveal" style="transition-delay:.20s">
                <span class="svc-num">04</span>
                <i class="fas fa-arrow-up-right svc-arr"></i>
                <div class="svc-icon"><i class="fas fa-search"></i></div>
                <div class="svc-title">Peer-Review System Setup</div>
                <div class="svc-desc">Implementation of a structured peer-review process ensuring transparency, academic
                    quality, and ethical publication standards.</div>
            </div>

            <div class="svc-card reveal" style="transition-delay:.25s">
                <span class="svc-num">05</span>
                <i class="fas fa-arrow-up-right svc-arr"></i>
                <div class="svc-icon"><i class="fas fa-upload"></i></div>
                <div class="svc-title">Manuscript Submission System</div>
                <div class="svc-desc">Setup of online manuscript submission and tracking systems to streamline the
                    review and editorial process end-to-end.</div>
            </div>

            <div class="svc-card reveal" style="transition-delay:.30s">
                <span class="svc-num">06</span>
                <i class="fas fa-arrow-up-right svc-arr"></i>
                <div class="svc-icon"><i class="fas fa-balance-scale"></i></div>
                <div class="svc-title">Publication Policies & Ethics</div>
                <div class="svc-desc">Development of publication ethics, author guidelines, reviewer policies, and
                    editorial standards per international academic publishing norms.</div>
            </div>

            <div class="svc-card reveal" style="transition-delay:.35s">
                <span class="svc-num">07</span>
                <i class="fas fa-arrow-up-right svc-arr"></i>
                <div class="svc-icon"><i class="fas fa-database"></i></div>
                <div class="svc-title">Journal Indexing Preparation</div>
                <div class="svc-desc">Assistance in preparing journals for major indexing databases including Google
                    Scholar, DOAJ, CrossRef DOI, Scopus, and other academic platforms.</div>
            </div>

            <div class="svc-card reveal" style="transition-delay:.40s">
                <span class="svc-num">08</span>
                <i class="fas fa-arrow-up-right svc-arr"></i>
                <div class="svc-icon"><i class="fas fa-chart-line"></i></div>
                <div class="svc-title">Journal Promotion & Visibility</div>
                <div class="svc-desc">Strategic approaches to improve journal visibility, citation impact, and global
                    readership across academic communities.</div>
            </div>

        </div>
    </section>


    <!-- ══════════════════════════════════════════════
     INDEXING + WHY CHOOSE US
══════════════════════════════════════════════ -->
    <section class="indexing-sec">
        <div class="indexing-inner">

            <!-- Left: Indexing platforms -->
            <div class="reveal-left">
                <div class="idx-eyebrow">Global Visibility</div>
                <h2 class="idx-h2">Journal Indexing <em>Preparation</em></h2>
                <p class="idx-p">We prepare your journal for submission to the world's most respected academic indexing
                    platforms — increasing discoverability and credibility.</p>

                <div class="idx-platforms">
                    <div class="idx-platform">
                        <div class="idx-platform-icon"><i class="fab fa-google"></i></div>
                        <div>
                            <div class="idx-platform-name">Google Scholar</div>
                            <div class="idx-platform-sub">Worldwide open academic search</div>
                        </div>
                        <div class="idx-platform-badge">Free</div>
                    </div>
                    <div class="idx-platform">
                        <div class="idx-platform-icon"><i class="fas fa-book-open"></i></div>
                        <div>
                            <div class="idx-platform-name">DOAJ</div>
                            <div class="idx-platform-sub">Directory of Open Access Journals</div>
                        </div>
                        <div class="idx-platform-badge">Open Access</div>
                    </div>
                    <div class="idx-platform">
                        <div class="idx-platform-icon"><i class="fas fa-fingerprint"></i></div>
                        <div>
                            <div class="idx-platform-name">CrossRef DOI Registration</div>
                            <div class="idx-platform-sub">Persistent digital identifiers</div>
                        </div>
                        <div class="idx-platform-badge">DOI</div>
                    </div>
                    <div class="idx-platform">
                        <div class="idx-platform-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <div class="idx-platform-name">Scopus Preparation</div>
                            <div class="idx-platform-sub">Elsevier's citation database</div>
                        </div>
                        <div class="idx-platform-badge">Premium</div>
                    </div>
                    <div class="idx-platform">
                        <div class="idx-platform-icon"><i class="fas fa-layer-group"></i></div>
                        <div>
                            <div class="idx-platform-name">Other Platforms</div>
                            <div class="idx-platform-sub">Additional academic indexing services</div>
                        </div>
                        <div class="idx-platform-badge">+More</div>
                    </div>
                </div>
            </div>

            <!-- Right: Why choose us -->
            <div class="reveal-right">
                <div class="idx-eyebrow">Why Choose Us</div>
                <h2 class="idx-h2" style="color:#faf7f2; font-size:clamp(24px,2.8vw,38px); margin-bottom:24px">Why Our
                    <em>Journal Development</em> Services
                </h2>

                <div class="idx-reasons">
                    <div class="idx-reason">
                        <div class="idx-check"><i class="fas fa-check"></i></div>
                        <div class="idx-reason-text">
                            <strong>Experienced Publishing Team</strong>
                            Seasoned professionals in academic publishing and research communication.
                        </div>
                    </div>
                    <div class="idx-reason">
                        <div class="idx-check"><i class="fas fa-check"></i></div>
                        <div class="idx-reason-text">
                            <strong>International Standards</strong>
                            Full support for COPE, ICMJE, and global publishing ethics frameworks.
                        </div>
                    </div>
                    <div class="idx-reason">
                        <div class="idx-check"><i class="fas fa-check"></i></div>
                        <div class="idx-reason-text">
                            <strong>Credible Peer-Reviewed Journals</strong>
                            We assist in building journals with rigorous review processes from day one.
                        </div>
                    </div>
                    <div class="idx-reason">
                        <div class="idx-check"><i class="fas fa-check"></i></div>
                        <div class="idx-reason-text">
                            <strong>Indexing & Visibility Guidance</strong>
                            Structured roadmap for getting indexed in major academic databases.
                        </div>
                    </div>
                    <div class="idx-reason">
                        <div class="idx-check"><i class="fas fa-check"></i></div>
                        <div class="idx-reason-text">
                            <strong>Long-Term Growth Support</strong>
                            Ongoing professional support beyond launch for sustainable journal growth.
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>


    <!-- ══════════════════════════════════════════════
     PROCESS
══════════════════════════════════════════════ -->
    <section class="process-sec">
        <div class="container">
            <div class="sec-header reveal">
                <div class="sec-eyebrow">How It Works</div>
                <h2 class="sec-h2">Our Journal <em>Development</em> Process</h2>
                <p class="sec-sub">A structured, five-stage journey from initial consultation to a fully indexed
                    academic journal</p>
            </div>
        </div>

        <div class="process-timeline">

            <div class="process-step reveal" style="transition-delay:.05s">
                <div class="ps-num">01</div>
                <div class="ps-body">
                    <div class="ps-title">Consultation &amp; Planning</div>
                    <div class="ps-desc">Understanding the scope, discipline, and objectives of your journal. We define
                        the target audience, editorial scope, and publication model together.</div>
                </div>
            </div>

            <div class="process-step reveal" style="transition-delay:.12s">
                <div class="ps-num">02</div>
                <div class="ps-body">
                    <div class="ps-title">Technical Setup</div>
                    <div class="ps-desc">Development of the journal website, manuscript submission system, and full
                        editorial workflow — built for authors, reviewers, and editors.</div>
                </div>
            </div>

            <div class="process-step reveal" style="transition-delay:.19s">
                <div class="ps-num">03</div>
                <div class="ps-body">
                    <div class="ps-title">Editorial Structure</div>
                    <div class="ps-desc">Formation of the editorial board, reviewer panel, peer-review guidelines, and
                        publication ethics policies aligned with international standards.</div>
                </div>
            </div>

            <div class="process-step reveal" style="transition-delay:.26s">
                <div class="ps-num">04</div>
                <div class="ps-body">
                    <div class="ps-title">Journal Launch</div>
                    <div class="ps-desc">Publication of initial issues, author submission management, and coordination
                        of the first review cycles to establish your journal's reputation.</div>
                </div>
            </div>

            <div class="process-step reveal" style="transition-delay:.33s">
                <div class="ps-num">05</div>
                <div class="ps-body">
                    <div class="ps-title">Indexing Preparation</div>
                    <div class="ps-desc">Comprehensive guidance for applying to indexing databases, improving citation
                        metrics, and growing global academic visibility over time.</div>
                </div>
            </div>

        </div>
    </section>


    <!-- ══════════════════════════════════════════════
     DISCIPLINES / EXPERTISE
══════════════════════════════════════════════ -->
    <section class="expertise-sec">
        <div class="container">
            <div class="sec-header reveal">
                <div class="sec-eyebrow">Our Expertise</div>
                <h2 class="sec-h2">Disciplines We <em>Support</em></h2>
                <p class="sec-sub">Hands-on experience supporting journals across a wide range of academic and
                    scientific fields</p>
            </div>
        </div>

        <div class="disciplines-grid">

            <div class="disc-card reveal" style="transition-delay:.05s">
                <div class="disc-icon"><i class="fas fa-heartbeat"></i></div>
                <div class="disc-name">Medical Sciences</div>
            </div>

            <div class="disc-card reveal" style="transition-delay:.10s">
                <div class="disc-icon"><i class="fas fa-tooth"></i></div>
                <div class="disc-name">Dental Sciences</div>
            </div>

            <div class="disc-card reveal" style="transition-delay:.15s">
                <div class="disc-icon"><i class="fas fa-user-nurse"></i></div>
                <div class="disc-name">Nursing</div>
            </div>

            <div class="disc-card reveal" style="transition-delay:.20s">
                <div class="disc-icon"><i class="fas fa-stethoscope"></i></div>
                <div class="disc-name">Paramedical Sciences</div>
            </div>

            <div class="disc-card reveal" style="transition-delay:.25s">
                <div class="disc-icon"><i class="fas fa-dna"></i></div>
                <div class="disc-name">Life Sciences</div>
            </div>

            <div class="disc-card reveal" style="transition-delay:.30s">
                <div class="disc-icon"><i class="fas fa-clinic-medical"></i></div>
                <div class="disc-name">Allied Health Sciences</div>
            </div>

            <div class="disc-card reveal" style="transition-delay:.35s">
                <div class="disc-icon"><i class="fas fa-atom"></i></div>
                <div class="disc-name">Multidisciplinary Research</div>
            </div>

        </div>
    </section>


    <!-- ══════════════════════════════════════════════
     CTA STRIP
══════════════════════════════════════════════ -->
    <div class="cta-strip">
        <div class="cta-inner">
            <div>
                <div class="cta-h2">Start Your Academic Journal Today</div>
                <p class="cta-p">Whether you are a university, research institute, or independent publisher — we can
                    help.</p>
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
        /* ── Scroll reveal ─────────────────────────────────────────── */
        const ro = new IntersectionObserver(entries => {
            entries.forEach(e => {
                if (e.isIntersecting) { e.target.classList.add('in'); ro.unobserve(e.target); }
            });
        }, { threshold: 0.08 });

        document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(el => ro.observe(el));

        /* ── Smooth scroll for anchor links ───────────────────────── */
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                const target = document.querySelector(a.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>

</html>