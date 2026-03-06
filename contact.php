<?php
include 'db.php';
session_start();

$contactInfo = [
    'address' => '7895+7GR, Bhopal-Indore Highway Bhainsakhedi, Bairagarh, Bhopal, Madhya Pradesh 462030',
    'phone' => '+91-8708299825, +91-9752747384',
    'email' => 'info@professionalpublicationservice.com',
    'whatsapp' => '+919752747384',
];

$toast = null;

/* ── Handle form submission ──────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $errors = [];
    if ($name === '')
        $errors[] = 'Name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'A valid email is required.';
    if ($message === '')
        $errors[] = 'Message cannot be empty.';

    if (empty($errors)) {
        // Save to DB if you have a contact_messages table, or just mail()
        // mail($contactInfo['email'], "Contact: $subject", "From: $name <$email>\n\n$message");
        $toast = ['type' => 'success', 'msg' => 'Thank you! Your message has been sent. We\'ll be in touch soon.'];
        $name = $email = $subject = $message = '';
    } else {
        $toast = ['type' => 'error', 'msg' => implode(' ', $errors)];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us — Professional Publication Services</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Outfit:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
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
            --success: #065f46;
            --success-bg: #ecfdf5;
            --danger: #b91c1c;
            --danger-bg: #fef2f2;
            --r: 6px;
            --r-lg: 14px;
            --t: .22s cubic-bezier(.4, 0, .2, 1);
            --shadow: 0 4px 24px rgba(26, 18, 8, .08);
            --shadow-lg: 0 16px 48px rgba(26, 18, 8, .13);
        }

        body {
            background: var(--paper);
            color: var(--ink);
            font-family: "Outfit", sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* ══════════════════════════════════════════
       HERO BAND
    ══════════════════════════════════════════ */
        .page-hero {
            background: var(--ink);
            padding: 64px 0 56px;
            position: relative;
            overflow: hidden;
        }

        .page-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 60% 100% at 75% 50%, rgba(181, 57, 15, .22), transparent),
                radial-gradient(ellipse 40% 80% at 5% 70%, rgba(201, 146, 10, .12), transparent);
            pointer-events: none;
        }

        .page-hero::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--accent), var(--accent2), transparent);
        }

        /* Subtle grid pattern */
        .hero-grid-overlay {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            background-image:
                linear-gradient(rgba(255, 255, 255, .03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, .03) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        .hero-inner {
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 28px;
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 32px;
        }

        @media (max-width: 640px) {
            .hero-inner {
                grid-template-columns: 1fr;
            }
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--accent2);
            margin-bottom: 14px;
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

        .hero-title {
            font-family: "Cormorant Garamond", Georgia, serif;
            font-size: clamp(38px, 5vw, 64px);
            font-weight: 700;
            line-height: 1.05;
            color: #faf7f2;
            letter-spacing: -.5px;
            margin-bottom: 12px;
        }

        .hero-title em {
            font-style: italic;
            color: rgba(250, 247, 242, .5);
        }

        .hero-sub {
            font-size: 15px;
            color: rgba(250, 247, 242, .5);
            line-height: 1.6;
            max-width: 440px;
        }

        /* Quick contact links in hero */
        .hero-quick {
            display: flex;
            flex-direction: column;
            gap: 10px;
            flex-shrink: 0;
        }

        .hq-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            background: rgba(255, 255, 255, .07);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: var(--r);
            text-decoration: none;
            color: rgba(250, 247, 242, .8);
            font-size: 13px;
            transition: all var(--t);
            white-space: nowrap;
            backdrop-filter: blur(4px);
        }

        .hq-link:hover {
            background: rgba(255, 255, 255, .13);
            color: #faf7f2;
            border-color: rgba(255, 255, 255, .25);
        }

        .hq-link i {
            font-size: 15px;
            color: var(--accent2);
            flex-shrink: 0;
        }

        /* ══════════════════════════════════════════
       MAIN BODY
    ══════════════════════════════════════════ */
        .page-body {
            max-width: 1140px;
            margin: 0 auto;
            padding: 56px 28px 90px;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 36px;
            align-items: start;
        }

        @media (max-width: 960px) {
            .page-body {
                grid-template-columns: 1fr;
            }
        }

        /* ══════════════════════════════════════════
       CONTACT FORM
    ══════════════════════════════════════════ */
        .form-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .form-card-top {
            height: 4px;
            background: linear-gradient(90deg, var(--accent), var(--accent2), #d97706);
        }

        .form-card-header {
            padding: 28px 32px 22px;
            border-bottom: 1px solid var(--border);
        }

        .form-card-header h2 {
            font-family: "Cormorant Garamond", serif;
            font-size: 28px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 5px;
        }

        .form-card-header p {
            font-size: 14px;
            color: var(--muted);
        }

        .form-body {
            padding: 28px 32px 32px;
        }

        .form-row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        @media (max-width: 560px) {
            .form-row-2 {
                grid-template-columns: 1fr;
            }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #4a3f33;
            margin-bottom: 7px;
        }

        .form-label .req {
            color: var(--accent);
            margin-left: 2px;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--muted);
            pointer-events: none;
            border-right: 1px solid var(--border);
            transition: all var(--t);
            z-index: 1;
        }

        .form-input {
            width: 100%;
            padding: 12px 14px 12px 54px;
            border: 1.5px solid var(--border);
            border-radius: var(--r);
            font-size: 14px;
            font-family: "Outfit", sans-serif;
            background: var(--cream);
            color: var(--ink);
            outline: none;
            transition: all var(--t);
        }

        .form-input::placeholder {
            color: #b5a898;
        }

        .form-input:focus {
            background: #fff;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(181, 57, 15, .1);
        }

        .input-wrap:focus-within .input-icon {
            color: var(--accent);
            border-color: rgba(181, 57, 15, .3);
        }

        .form-input.no-icon {
            padding-left: 14px;
        }

        textarea.form-input {
            resize: vertical;
            min-height: 130px;
            line-height: 1.6;
            padding-top: 13px;
        }

        .char-count {
            display: block;
            text-align: right;
            margin-top: 4px;
            font-size: 11.5px;
            color: var(--muted);
            font-family: "DM Mono", monospace;
            transition: color var(--t);
        }

        .char-count.warn {
            color: var(--accent2);
        }

        .char-count.over {
            color: var(--accent);
            font-weight: 600;
        }

        /* Submit button */
        .btn-submit {
            width: 100%;
            padding: 14px 24px;
            background: var(--ink);
            color: #faf7f2;
            border: none;
            border-radius: var(--r);
            font-family: "Outfit", sans-serif;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: .3px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            transition: all var(--t);
            box-shadow: 0 4px 18px rgba(26, 18, 8, .22);
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, .05), transparent);
        }

        .btn-submit:hover {
            background: #2d2010;
            box-shadow: 0 8px 28px rgba(26, 18, 8, .32);
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit.loading {
            pointer-events: none;
            opacity: .7;
        }

        .btn-whatsapp-alt {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 12px 24px;
            margin-top: 10px;
            background: #25d366;
            color: #fff;
            border: none;
            border-radius: var(--r);
            font-family: "Outfit", sans-serif;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all var(--t);
            box-shadow: 0 4px 18px rgba(37, 211, 102, .3);
        }

        .btn-whatsapp-alt:hover {
            background: #1fb358;
            color: #fff;
            box-shadow: 0 8px 28px rgba(37, 211, 102, .4);
            transform: translateY(-1px);
        }

        .btn-whatsapp-alt i {
            font-size: 18px;
        }

        .form-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 16px 0;
            color: var(--muted);
            font-size: 12px;
        }

        .form-divider::before,
        .form-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* Toast */
        .page-toast {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 16px;
            border-radius: var(--r);
            font-size: 13.5px;
            margin-bottom: 20px;
            animation: toastIn .3s var(--t);
        }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateY(-8px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .page-toast.success {
            background: var(--success-bg);
            border: 1px solid #a7f3d0;
            color: var(--success);
        }

        .page-toast.error {
            background: var(--danger-bg);
            border: 1px solid #fecaca;
            color: var(--danger);
        }

        .page-toast i {
            font-size: 17px;
            flex-shrink: 0;
        }

        /* ══════════════════════════════════════════
       RIGHT PANEL
    ══════════════════════════════════════════ */
        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        /* Info card */
        .info-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .info-card-header {
            background: var(--ink);
            padding: 22px 24px;
            position: relative;
            overflow: hidden;
        }

        .info-card-header::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse 70% 100% at 80% 50%, rgba(181, 57, 15, .25), transparent);
        }

        .info-card-header h3 {
            font-family: "Cormorant Garamond", serif;
            font-size: 22px;
            font-weight: 700;
            color: #faf7f2;
            position: relative;
            z-index: 1;
            margin-bottom: 3px;
        }

        .info-card-header p {
            font-size: 13px;
            color: rgba(250, 247, 242, .45);
            position: relative;
            z-index: 1;
        }

        .info-rows {
            padding: 6px 0;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 16px 22px;
            border-bottom: 1px solid var(--border);
            transition: background var(--t);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-row:hover {
            background: var(--cream);
        }

        .info-row-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--r);
            background: var(--cream);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
            transition: all var(--t);
        }

        .info-row:hover .info-row-icon {
            background: var(--accent);
            color: #fff;
        }

        .info-row-body {
            flex: 1;
            min-width: 0;
        }

        .info-row-label {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .info-row-val {
            font-size: 13.5px;
            color: var(--ink);
            font-weight: 500;
            line-height: 1.5;
            word-break: break-word;
        }

        .info-row-val a {
            color: inherit;
            text-decoration: none;
            transition: color var(--t);
        }

        .info-row-val a:hover {
            color: var(--accent);
        }

        /* Map card */
        .map-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .map-card-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            font-weight: 700;
            color: var(--ink);
        }

        .map-card-header i {
            color: var(--accent);
            font-size: 16px;
        }

        .map-frame {
            display: block;
            width: 100%;
            height: 220px;
            border: none;
        }

        /* Hours card */
        .hours-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .hours-card-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13.5px;
            font-weight: 700;
            color: var(--ink);
        }

        .hours-card-header i {
            color: var(--accent2);
            font-size: 16px;
        }

        .hours-list {
            padding: 8px 0;
        }

        .hours-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 20px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }

        .hours-row:last-child {
            border-bottom: none;
        }

        .hours-day {
            color: var(--muted);
            font-weight: 500;
        }

        .hours-time {
            font-weight: 600;
            color: var(--ink);
            font-family: "DM Mono", monospace;
            font-size: 12.5px;
        }

        .hours-closed {
            color: var(--accent);
            font-weight: 600;
        }

        .today-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            background: var(--accent);
            color: #fff;
            border-radius: 99px;
            margin-left: 8px;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        /* ══════════════════════════════════════════
       REVEAL ANIMATION
    ══════════════════════════════════════════ */
        .reveal {
            opacity: 0;
            transform: translateY(22px);
            transition: opacity .55s var(--t), transform .55s var(--t);
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>

<body>

    <?php include 'Header.php'; ?>

    <!-- ══════════════════════════════════════
     HERO
══════════════════════════════════════ -->
    <div class="page-hero">
        <div class="hero-grid-overlay"></div>
        <div class="hero-inner">
            <div>
                <div class="hero-eyebrow">Get in Touch</div>
                <h1 class="hero-title">Contact <em>Us</em></h1>
                <p class="hero-sub">We'd love to hear from you. Send us a message and our team will respond within 24
                    hours.</p>
            </div>
            <div class="hero-quick">
                <a href="tel:+918708299825" class="hq-link">
                    <i class="fas fa-phone"></i> +91-8708299825
                </a>
                <a href="mailto:<?= htmlspecialchars($contactInfo['email'], ENT_QUOTES) ?>" class="hq-link">
                    <i class="fas fa-envelope"></i> Email Us
                </a>
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $contactInfo['whatsapp']) ?>" target="_blank"
                    class="hq-link">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════
     BODY
══════════════════════════════════════ -->
    <div class="page-body">

        <!-- LEFT: Form ──────────────────────────────────────────── -->
        <div class="reveal">
            <div class="form-card">
                <div class="form-card-top"></div>
                <div class="form-card-header">
                    <h2>Send a Message</h2>
                    <p>Fill out the form below and we'll get back to you as soon as possible.</p>
                </div>
                <div class="form-body">

                    <?php if ($toast): ?>
                        <div class="page-toast <?= $toast['type'] ?>">
                            <i
                                class="fas <?= $toast['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                            <?= htmlspecialchars($toast['msg'], ENT_QUOTES) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="contact.php" id="contactForm" novalidate>

                        <div class="form-row-2">
                            <!-- Name -->
                            <div class="form-group">
                                <label class="form-label">Your Name <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <div class="input-icon"><i class="fas fa-user"></i></div>
                                    <input type="text" name="name" class="form-input"
                                        value="<?= htmlspecialchars($name ?? '', ENT_QUOTES) ?>"
                                        placeholder="Dr. John Smith" maxlength="100" autocomplete="name" required>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="form-group">
                                <label class="form-label">Email Address <span class="req">*</span></label>
                                <div class="input-wrap">
                                    <div class="input-icon"><i class="fas fa-envelope"></i></div>
                                    <input type="email" name="email" class="form-input"
                                        value="<?= htmlspecialchars($email ?? '', ENT_QUOTES) ?>"
                                        placeholder="you@example.com" maxlength="150" autocomplete="email" required>
                                </div>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="form-group">
                            <label class="form-label">Phone Number <span
                                    style="color:var(--muted);font-weight:400;font-size:12px">(optional)</span></label>
                            <div class="input-wrap">
                                <div class="input-icon"><i class="fas fa-phone"></i></div>
                                <input type="tel" name="phone" class="form-input"
                                    value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES) ?>"
                                    placeholder="+91 98765 43210" maxlength="20" autocomplete="tel">
                            </div>
                        </div>

                        <!-- Subject -->
                        <!-- <div class="form-group">
                            <label class="form-label">Subject</label>
                            <div class="input-wrap">
                                <div class="input-icon"><i class="fas fa-tag"></i></div>
                                <input type="text" name="subject" class="form-input"
                                    value="<?= htmlspecialchars($subject ?? '', ENT_QUOTES) ?>"
                                    placeholder="e.g. Manuscript submission enquiry" maxlength="200" id="subjectInput">
                            </div>
                        </div> -->

                        <!-- Message -->
                        <div class="form-group">
                            <label class="form-label">Message <span class="req">*</span></label>
                            <textarea name="message" class="form-input no-icon" rows="5"
                                placeholder="Describe how we can help you…" maxlength="1500" id="messageInput"
                                required><?= htmlspecialchars($message ?? '', ENT_QUOTES) ?></textarea>
                            <span class="char-count" id="msgCount">0 / 1500</span>
                        </div>

                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-paper-plane"></i>
                            Send Message
                        </button>

                        <div class="form-divider">or reach us on</div>

                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $contactInfo['whatsapp']) ?>?text=<?= urlencode("Hi, I'd like to know more about your services.") ?>"
                            target="_blank" class="btn-whatsapp-alt">
                            <i class="fab fa-whatsapp"></i>
                            Chat on WhatsApp
                        </a>

                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT: Info + Map + Hours ───────────────────────────── -->
        <div class="right-panel">

            <!-- Contact info card -->
            <div class="info-card reveal" style="transition-delay:.1s">
                <div class="info-card-header">
                    <h3>Get in Touch</h3>
                    <p>Multiple ways to reach our team</p>
                </div>
                <div class="info-rows">
                    <div class="info-row">
                        <div class="info-row-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="info-row-body">
                            <div class="info-row-label">Address</div>
                            <div class="info-row-val">
                                <?= htmlspecialchars($contactInfo['address'], ENT_QUOTES) ?>
                            </div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-row-icon"><i class="fas fa-phone-alt"></i></div>
                        <div class="info-row-body">
                            <div class="info-row-label">Phone</div>
                            <div class="info-row-val">
                                <?php foreach (explode(',', $contactInfo['phone']) as $ph):
                                    $ph = trim($ph); ?>
                                    <a href="tel:<?= preg_replace('/[^0-9+]/', '', $ph) ?>">
                                        <?= htmlspecialchars($ph, ENT_QUOTES) ?>
                                    </a><br>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-row-icon"><i class="fas fa-envelope"></i></div>
                        <div class="info-row-body">
                            <div class="info-row-label">Email</div>
                            <div class="info-row-val">
                                <a href="mailto:<?= htmlspecialchars($contactInfo['email'], ENT_QUOTES) ?>">
                                    <?= htmlspecialchars($contactInfo['email'], ENT_QUOTES) ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-row-icon" style="background:#ecfdf5;color:#25d366"><i
                                class="fab fa-whatsapp"></i></div>
                        <div class="info-row-body">
                            <div class="info-row-label">WhatsApp</div>
                            <div class="info-row-val">
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $contactInfo['whatsapp']) ?>"
                                    target="_blank">
                                    <?= htmlspecialchars($contactInfo['whatsapp'], ENT_QUOTES) ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Map -->
            <div class="map-card reveal" style="transition-delay:.18s">
                <div class="map-card-header">
                    <i class="fas fa-location-dot"></i> Office Location
                </div>
                <iframe class="map-frame"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3532.445923066085!2d77.307172!3d23.268722!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjPCsDE2JzAwLjAiTiA3N8KwMTMnMzMuOCJF!5e0!3m2!1sen!2sin!4v1695117890123"
                    allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>

            <!-- Hours -->
            <div class="hours-card reveal" style="transition-delay:.24s">
                <div class="hours-card-header">
                    <i class="fas fa-clock"></i> Office Hours
                </div>
                <div class="hours-list">
                    <?php
                    $today = strtolower(date('l'));
                    $hours = [
                        'Monday' => '9:00 AM – 6:00 PM',
                        'Tuesday' => '9:00 AM – 6:00 PM',
                        'Wednesday' => '9:00 AM – 6:00 PM',
                        'Thursday' => '9:00 AM – 6:00 PM',
                        'Friday' => '9:00 AM – 6:00 PM',
                        'Saturday' => '10:00 AM – 3:00 PM',
                        'Sunday' => 'Closed',
                    ];
                    foreach ($hours as $day => $time):
                        $isToday = strtolower($day) === $today;
                        ?>
                        <div class="hours-row">
                            <span class="hours-day">
                                <?= $day ?>
                                <?php if ($isToday): ?><span class="today-badge">Today</span>
                                <?php endif; ?>
                            </span>
                            <span class="<?= $time === 'Closed' ? 'hours-closed' : 'hours-time' ?>">
                                <?= $time ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

    </div><!-- /page-body -->

    <?php include 'Footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /* ── Char counter ───────────────────────────────────────────── */
        const msgInput = document.getElementById('messageInput');
        const msgCount = document.getElementById('msgCount');
        if (msgInput && msgCount) {
            const update = () => {
                const n = msgInput.value.length;
                msgCount.textContent = `${n} / 1500`;
                msgCount.className = 'char-count' + (n >= 1500 ? ' over' : n >= 1200 ? ' warn' : '');
            };
            msgInput.addEventListener('input', update);
            update();
        }

        /* ── Submit loading state ───────────────────────────────────── */
        document.getElementById('contactForm')?.addEventListener('submit', () => {
            const btn = document.getElementById('submitBtn');
            if (btn) {
                btn.classList.add('loading');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
            }
        });

        /* ── Scroll reveal ──────────────────────────────────────────── */
        const obs = new IntersectionObserver(entries => {
            entries.forEach(e => {
                if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); }
            });
        }, { threshold: 0.08 });
        document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
    </script>
</body>

</html>