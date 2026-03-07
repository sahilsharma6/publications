<?php
/**
 * FAQ Component — Professional Publication Services
 * Usage: <?php include 'faq-component.php'; ?>
 * Drop anywhere on any page. Self-contained styles + JS (namespaced to avoid conflicts).
 */

$faqs = [
    [
        'q' => 'Do you help publish in Scopus indexed journals?',
        'a' => 'Yes. We assist researchers in preparing manuscripts and provide guidance for submission to Scopus indexed journals according to journal guidelines.',
        'icon' => 'fas fa-database',
    ],
    [
        'q' => 'Do you provide plagiarism reports?',
        'a' => 'Yes. We provide detailed plagiarism reports and similarity checks to ensure originality before journal submission.',
        'icon' => 'fas fa-shield-alt',
    ],
    [
        'q' => 'Do you convert thesis into research papers?',
        'a' => 'Yes. We specialize in thesis-to-research paper conversion suitable for journal publication.',
        'icon' => 'fas fa-file-alt',
    ],
    [
        'q' => 'Do you help in journal selection?',
        'a' => 'Yes. Our experts help identify suitable indexed journals based on your research topic and scope.',
        'icon' => 'fas fa-search',
    ],
    [
        'q' => 'Do you provide statistical analysis?',
        'a' => 'Yes. We provide biostatistical analysis, data interpretation, and result presentation.',
        'icon' => 'fas fa-chart-bar',
    ],
    [
        'q' => 'Do you help with case reports and systematic reviews?',
        'a' => 'Yes. We assist in preparing case reports, case series, systematic reviews, and meta-analyses.',
        'icon' => 'fas fa-microscope',
    ],
    [
        'q' => 'How long does manuscript preparation take?',
        'a' => 'The timeline depends on the complexity of the research, but most projects are completed within 7–21 days.',
        'icon' => 'fas fa-clock',
    ],
    [
        'q' => 'Is my research confidential?',
        'a' => 'Yes. We maintain complete confidentiality and data security for all client manuscripts.',
        'icon' => 'fas fa-lock',
    ],
];
?>

<section class="pps-faq-sec" id="faq">
    <div class="pps-faq-wrap">

        <div class="pps-faq-header">
            <div class="pps-faq-eyebrow">Got Questions?</div>
            <h2 class="pps-faq-h2">Frequently Asked <em>Questions</em></h2>
            <p class="pps-faq-sub">Everything you need to know about our publication services</p>
        </div>

        <div class="pps-faq-inner">

            <!-- Left decorative panel -->
            <div class="pps-faq-aside">
                <div class="pps-faq-aside-card">
                    <div class="pps-faq-aside-icon"><i class="fas fa-comment-dots"></i></div>
                    <div class="pps-faq-aside-title">Still have questions?</div>
                    <div class="pps-faq-aside-body">Can't find what you're looking for? Our team is happy to help with
                        any queries.</div>
                    <a href="contact.php" class="pps-faq-aside-btn">
                        <i class="fas fa-paper-plane"></i> Contact Us
                    </a>
                </div>

            </div>

            <!-- Accordion list -->
            <div class="pps-faq-list">
                <?php foreach ($faqs as $i => $faq): ?>
                    <div class="pps-faq-item" data-faq="<?= $i ?>">
                        <button class="pps-faq-q" aria-expanded="false" onclick="ppsFaqToggle(this, <?= $i ?>)">
                            <span class="pps-faq-q-icon"><i
                                    class="<?= htmlspecialchars($faq['icon'], ENT_QUOTES) ?>"></i></span>
                            <span class="pps-faq-q-text">
                                <?= htmlspecialchars($faq['q'], ENT_QUOTES) ?>
                            </span>
                            <span class="pps-faq-q-arrow"><i class="fas fa-chevron-down"></i></span>
                        </button>
                        <div class="pps-faq-a" id="pps-faq-a-<?= $i ?>">
                            <div class="pps-faq-a-inner">
                                <?= htmlspecialchars($faq['a'], ENT_QUOTES) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</section>

<style>
    /* ══════════════════════════════════════════
   FAQ COMPONENT — namespaced with pps-faq-
   Self-contained, no global style conflicts
══════════════════════════════════════════ */
    .pps-faq-sec {
        padding: 88px 0;
        background: var(--paper, #faf8f4);
        font-family: "Outfit", sans-serif;
    }

    .pps-faq-wrap {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 28px;
    }

    /* ── Header ──────────────────────────────── */
    .pps-faq-header {
        text-align: center;
        margin-bottom: 56px;
    }

    .pps-faq-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: var(--accent, #b5390f);
        margin-bottom: 12px;
    }

    .pps-faq-eyebrow::before,
    .pps-faq-eyebrow::after {
        content: '';
        display: block;
        width: 22px;
        height: 1px;
        background: currentColor;
        opacity: .5;
    }

    .pps-faq-h2 {
        font-family: "Cormorant Garamond", Georgia, serif;
        font-size: clamp(28px, 4vw, 46px);
        font-weight: 700;
        color: var(--ink, #1a1208);
        line-height: 1.08;
        letter-spacing: -.3px;
        margin-bottom: 10px;
    }

    .pps-faq-h2 em {
        font-style: italic;
        color: var(--muted, #7a6f62);
    }

    .pps-faq-sub {
        font-size: 15px;
        color: var(--muted, #7a6f62);
        line-height: 1.65;
    }

    /* ── Inner layout ────────────────────────── */
    .pps-faq-inner {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 40px;
        align-items: start;
    }

    @media (max-width: 920px) {
        .pps-faq-inner {
            grid-template-columns: 1fr;
        }

        .pps-faq-aside {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
    }

    @media (max-width: 560px) {
        .pps-faq-aside {
            grid-template-columns: 1fr;
        }

        .pps-faq-wrap {
            padding: 0 16px;
        }
    }

    /* ── Aside card ──────────────────────────── */
    .pps-faq-aside {
        display: flex;
        flex-direction: column;
        gap: 16px;
        position: sticky;
        top: 88px;
    }

    .pps-faq-aside-card {
        background: var(--ink, #1a1208);
        border-radius: 16px;
        padding: 28px 24px;
        position: relative;
        overflow: hidden;
    }

    .pps-faq-aside-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse 70% 80% at 90% 10%, rgba(201, 146, 10, .18), transparent);
        pointer-events: none;
    }

    .pps-faq-aside-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        background: rgba(201, 146, 10, .15);
        color: var(--accent2, #c9920a);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 16px;
        position: relative;
        z-index: 1;
    }

    .pps-faq-aside-title {
        font-family: "Cormorant Garamond", serif;
        font-size: 20px;
        font-weight: 700;
        color: #faf7f2;
        margin-bottom: 8px;
        position: relative;
        z-index: 1;
    }

    .pps-faq-aside-body {
        font-size: 13.5px;
        color: rgba(250, 247, 242, .5);
        line-height: 1.65;
        margin-bottom: 20px;
        position: relative;
        z-index: 1;
    }

    .pps-faq-aside-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 11px 22px;
        background: var(--accent, #b5390f);
        color: #fff;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 600;
        text-decoration: none;
        transition: all .22s cubic-bezier(.4, 0, .2, 1);
        font-family: "Outfit", sans-serif;
        position: relative;
        z-index: 1;
        box-shadow: 0 4px 16px rgba(181, 57, 15, .35);
    }

    .pps-faq-aside-btn:hover {
        background: #9b2e08;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(181, 57, 15, .5);
    }

    /* ── Aside stats ─────────────────────────── */
    .pps-faq-aside-stats {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 8px;
    }

    .pps-faq-stat {
        background: #fff;
        border: 1px solid var(--border, #e0d8cc);
        border-radius: 12px;
        padding: 14px 10px;
        text-align: center;
        transition: all .22s cubic-bezier(.4, 0, .2, 1);
    }

    .pps-faq-stat:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(26, 18, 8, .1);
    }

    .pps-faq-stat-num {
        font-family: "Cormorant Garamond", serif;
        font-size: 26px;
        font-weight: 700;
        color: var(--ink, #1a1208);
        line-height: 1;
        margin-bottom: 4px;
    }

    .pps-faq-stat-num sup {
        font-size: 13px;
        color: var(--accent2, #c9920a);
        vertical-align: super;
    }

    .pps-faq-stat-lbl {
        font-size: 10px;
        font-weight: 700;
        color: var(--muted, #7a6f62);
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    /* ── Accordion list ──────────────────────── */
    .pps-faq-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .pps-faq-item {
        background: #fff;
        border: 1px solid var(--border, #e0d8cc);
        border-radius: 14px;
        overflow: hidden;
        transition: border-color .22s cubic-bezier(.4, 0, .2, 1),
            box-shadow .22s cubic-bezier(.4, 0, .2, 1);
    }

    .pps-faq-item:hover {
        border-color: var(--cream-dark, #e6ddd0);
        box-shadow: 0 4px 20px rgba(26, 18, 8, .07);
    }

    .pps-faq-item.open {
        border-color: var(--ink, #1a1208);
        box-shadow: 0 8px 32px rgba(26, 18, 8, .1);
    }

    /* Question button */
    .pps-faq-q {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 18px 20px;
        background: transparent;
        border: none;
        cursor: pointer;
        text-align: left;
        font-family: "Outfit", sans-serif;
        transition: background .2s;
    }

    .pps-faq-q:hover {
        background: var(--cream, #f3ede2);
    }

    .pps-faq-item.open .pps-faq-q {
        background: var(--ink, #1a1208);
    }

    .pps-faq-q-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: var(--cream, #f3ede2);
        color: var(--accent, #b5390f);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
        transition: all .22s cubic-bezier(.4, 0, .2, 1);
    }

    .pps-faq-item.open .pps-faq-q-icon {
        background: rgba(255, 255, 255, .1);
        color: var(--accent2, #c9920a);
    }

    .pps-faq-q-text {
        flex: 1;
        font-size: 14.5px;
        font-weight: 600;
        color: var(--ink, #1a1208);
        line-height: 1.4;
        transition: color .2s;
    }

    .pps-faq-item.open .pps-faq-q-text {
        color: #faf7f2;
    }

    .pps-faq-q-arrow {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 1.5px solid var(--border, #e0d8cc);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 12px;
        color: var(--muted, #7a6f62);
        transition: all .3s cubic-bezier(.4, 0, .2, 1);
    }

    .pps-faq-item.open .pps-faq-q-arrow {
        border-color: rgba(255, 255, 255, .2);
        color: rgba(255, 255, 255, .7);
        transform: rotate(180deg);
    }

    /* Answer panel */
    .pps-faq-a {
        display: grid;
        grid-template-rows: 0fr;
        transition: grid-template-rows .35s cubic-bezier(.4, 0, .2, 1);
    }

    .pps-faq-item.open .pps-faq-a {
        grid-template-rows: 1fr;
    }

    .pps-faq-a-inner {
        overflow: hidden;
        font-size: 14px;
        color: var(--muted, #7a6f62);
        line-height: 1.72;
        padding: 0 20px 0 70px;
        transition: padding .35s cubic-bezier(.4, 0, .2, 1);
    }

    .pps-faq-item.open .pps-faq-a-inner {
        padding: 14px 20px 20px 70px;
    }

    @media (max-width: 480px) {

        .pps-faq-a-inner,
        .pps-faq-item.open .pps-faq-a-inner {
            padding-left: 20px;
        }
    }
</style>

<script>
    (function () {
        function ppsFaqToggle(btn, idx) {
            const item = btn.closest('.pps-faq-item');
            const isOpen = item.classList.contains('open');

            // Close all
            document.querySelectorAll('.pps-faq-item.open').forEach(el => {
                el.classList.remove('open');
                el.querySelector('.pps-faq-q').setAttribute('aria-expanded', 'false');
            });

            // Open clicked (if it wasn't already open)
            if (!isOpen) {
                item.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
            }
        }

        // Expose globally so inline onclick works
        window.ppsFaqToggle = ppsFaqToggle;
    })();
</script>