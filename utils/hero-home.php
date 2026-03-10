<style>
    /* ════════════════════ HERO OVERRIDES ════════════════════ */
    .hero-wrap {
        position: relative;
        overflow: hidden;
    }

    .hero-slide {
        min-height: 92vh;
        display: none;
        align-items: center;
        position: relative;
    }

    .hero-slide.active-slide {
        display: flex;
    }

    /* All slides keep the brand dark-ink palette */
    .hero-slide {
        background: linear-gradient(135deg, #0d0a06 0%, #1a1208 50%, #221508 100%);
    }

    /* Slide-specific accent glows */
    .slide-1::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 65% 80% at 80% 50%, rgba(181, 57, 15, .28), transparent),
            radial-gradient(ellipse 40% 60% at 5% 80%, rgba(201, 146, 10, .14), transparent);
    }

    .slide-2::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 60% 80% at 78% 45%, rgba(201, 146, 10, .22), transparent),
            radial-gradient(ellipse 35% 55% at 4% 85%, rgba(181, 57, 15, .12), transparent);
    }

    .slide-3::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 65% 80% at 80% 50%, rgba(181, 57, 15, .28), transparent),
            radial-gradient(ellipse 40% 55% at 5% 20%, rgba(201, 146, 10, .12), transparent);
    }

    .slide-4::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(ellipse 60% 80% at 78% 48%, rgba(201, 146, 10, .25), transparent),
            radial-gradient(ellipse 35% 55% at 4% 80%, rgba(181, 57, 15, .10), transparent);
    }

    /* Grid texture */
    .slide-grid {
        position: absolute;
        inset: 0;
        pointer-events: none;
        background-image:
            linear-gradient(rgba(255, 255, 255, .022) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, .022) 1px, transparent 1px);
        background-size: 48px 48px;
    }

    /* ── Inner layout ──────────────────────────── */
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

    @media(max-width:860px) {
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

    @media(max-width:860px) {
        .hero-text {
            padding: 64px 0 56px;
        }
    }

    /* Eyebrow */
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

    /* H1 */
    .hero-h1 {
        font-family: "Cormorant Garamond", Georgia, serif;
        font-size: clamp(36px, 5vw, 66px);
        font-weight: 700;
        line-height: 1.05;
        letter-spacing: -.6px;
        color: #faf7f2;
        margin-bottom: 14px;
    }

    .hero-h1 em {
        font-style: italic;
        color: rgba(250, 247, 242, .38);
    }

    /* Sub */
    .hero-p {
        font-size: 15px;
        color: rgba(250, 247, 242, .5);
        line-height: 1.72;
        max-width: 440px;
        margin-bottom: 28px;
    }

    /* Pill badges — replaces old bullet list */
    .hero-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 30px;
    }

    .hero-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 13px;
        background: rgba(255, 255, 255, .07);
        border: 1px solid rgba(255, 255, 255, .12);
        border-radius: 99px;
        font-size: 12px;
        font-weight: 600;
        color: rgba(250, 247, 242, .72);
        backdrop-filter: blur(4px);
        transition: all .22s var(--t);
    }

    .hero-pill i {
        font-size: 11px;
        color: var(--accent2);
    }

    .hero-pill:hover {
        background: rgba(255, 255, 255, .12);
        color: #faf7f2;
        border-color: rgba(201, 146, 10, .35);
    }

    /* Buttons */
    .hero-btns {
        display: flex;
        gap: 12px;
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

    /* ── Right visual ─────────────────────────── */
    .hero-visual {
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    /* Globe / illustration wrapper */
    .hero-globe-wrap {
        position: relative;
        width: 100%;
        max-width: 460px;
    }

    /* Glowing circle behind globe */
    .globe-ring {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 88%;
        padding-top: 88%;
        border-radius: 50%;
        /* background: radial-gradient(ellipse 70% 70% at 50% 50%,
                rgba(201, 146, 10, .18) 0%,
                rgba(181, 57, 15, .08) 55%,
                transparent 100%); */
        animation: globePulse 4s ease-in-out infinite;
    }

    @keyframes globePulse {

        0%,
        100% {
            transform: translate(-50%, -50%) scale(1);
            opacity: .6;
        }

        50% {
            transform: translate(-50%, -50%) scale(1.06);
            opacity: 1;
        }
    }

    .globe-ring-border {
        /* position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 92%;
        padding-top: 92%;
        border-radius: 50%;
        border: 1px solid rgba(201, 146, 10, .2);
        animation: globeRotate 18s linear infinite; */
    }

    @keyframes globeRotate {
        from {
            transform: translate(-50%, -50%) rotate(0deg);
        }

        to {
            transform: translate(-50%, -50%) rotate(360deg);
        }
    }

    .hero-globe-img {
        position: relative;
        z-index: 2;
        width: 100%;
        display: block;
        filter: drop-shadow(0 0 40px rgba(201, 146, 10, .35)) drop-shadow(0 0 80px rgba(181, 57, 15, .15));
        animation: globeFloat 6s ease-in-out infinite;
    }

    @keyframes globeFloat {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-12px);
        }
    }

    /* Stat cards floating around globe */
    .globe-stat {
        position: absolute;
        background: rgba(26, 18, 8, .88);
        border: 1px solid rgba(201, 146, 10, .25);
        border-radius: 10px;
        padding: 10px 14px;
        backdrop-filter: blur(8px);
        z-index: 3;
        white-space: nowrap;
    }



    .gs-num {
        font-family: "Cormorant Garamond", serif;
        font-size: 22px;
        font-weight: 700;
        color: #faf7f2;
        line-height: 1;
    }

    .gs-num sup {
        font-size: 12px;
        color: var(--accent2);
        vertical-align: super;
    }

    .gs-lbl {
        font-size: 10px;
        font-weight: 700;
        color: rgba(250, 247, 242, .4);
        text-transform: uppercase;
        letter-spacing: .6px;
        margin-top: 2px;
    }

    /* Slide-specific stat positions */
    .globe-stat-tl {
        top: 12%;
        left: -4%;
    }

    .globe-stat-tr {
        top: 8%;
        right: 0%;
    }

    .globe-stat-bl {
        bottom: 14%;
        left: -2%;
    }

    .globe-stat-br {
        bottom: 10%;
        right: 2%;
    }

    /* ── Carousel controls ────────────────────── */
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

    /* Bottom rule */
    .hero-wrap::after {
        content: '';
        display: block;
        height: 3px;
        background: linear-gradient(90deg, transparent, var(--accent), var(--accent2), transparent);
    }

    /* Slide progress bar */
    .hero-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        background: rgba(201, 146, 10, .5);
        z-index: 10;
        width: 0%;
        animation: heroProgress 5.2s linear infinite;
    }

    @keyframes heroProgress {
        from {
            width: 0%;
        }

        to {
            width: 100%;
        }
    }
</style>

<div class="hero-wrap" id="heroWrap">

    <!-- ── SLIDE 1 ─────────────────────────────────────────────── -->
    <div class="hero-slide slide-1 active-slide">
        <div class="slide-grid"></div>
        <div class="hero-inner">
            <div class="hero-text">
                <div class="hero-eyebrow">Since 2020</div>
                <h1 class="hero-h1">Professional<br><em>Publication</em><br>Services</h1>
                <p class="hero-p">End-to-End Research Writing &amp; Journal Publication Support — empowering doctors,
                    researchers, and academicians from first draft to final indexed journal.</p>
                <div class="hero-pills">
                    <span class="hero-pill"><i class="fas fa-award"></i> 6+ Years Experience</span>
                    <span class="hero-pill"><i class="fas fa-users"></i> 1 Lakh+ Authors Supported</span>
                    <span class="hero-pill"><i class="fas fa-headset"></i> 24/7 Research Support</span>
                    <span class="hero-pill"><i class="fas fa-lock"></i> 100% Confidentiality</span>
                </div>
                <div class="hero-btns">
                    <a href="books.php" class="btn-hp btn-hp-solid"><i class="fas fa-book-open"></i> Browse Books</a>
                    <a href="contact.php" class="btn-hp btn-hp-ghost">Get in Touch <i
                            class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-globe-wrap">
                    <div class="globe-ring"></div>
                    <div class="globe-ring-border"></div>
                    <img class="hero-globe-img" src="./uploads/assets/globe1.png" onerror="this.style.display='none'"
                        alt="Global Research Community">
                    <!-- Fallback SVG globe if no image -->
                    <svg class="hero-globe-img" style="display:none" id="globeSvg1" viewBox="0 0 400 400" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <circle cx="200" cy="200" r="170" stroke="rgba(201,146,10,0.4)" stroke-width="1.5" />
                        <ellipse cx="200" cy="200" rx="100" ry="170" stroke="rgba(201,146,10,0.25)" stroke-width="1" />
                        <ellipse cx="200" cy="200" rx="170" ry="70" stroke="rgba(201,146,10,0.25)" stroke-width="1" />
                        <ellipse cx="200" cy="200" rx="170" ry="30" stroke="rgba(201,146,10,0.15)" stroke-width="1" />
                        <circle cx="200" cy="200" r="170" fill="url(#g1)" opacity="0.6" />
                        <defs>
                            <radialGradient id="g1" cx="60%" cy="40%" r="60%">
                                <stop offset="0%" stop-color="#c9920a" stop-opacity="0.3" />
                                <stop offset="100%" stop-color="#b5390f" stop-opacity="0.05" />
                            </radialGradient>
                        </defs>
                    </svg>
                    <div class="globe-stat globe-stat-tl">
                        <div class="gs-num"><?= number_format($totalBooks) ?><sup>+</sup></div>
                        <div class="gs-lbl">Books</div>
                    </div>
                    <div class="globe-stat globe-stat-tr">
                        <div class="gs-num">1L<sup>+</sup></div>
                        <div class="gs-lbl">Authors</div>
                    </div>
                    <div class="globe-stat globe-stat-bl">
                        <div class="gs-num">6<sup>yr</sup></div>
                        <div class="gs-lbl">Experience</div>
                    </div>
                    <div class="globe-stat globe-stat-br">
                        <div class="gs-num">24<sup>h</sup></div>
                        <div class="gs-lbl">Support</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── SLIDE 2 ─────────────────────────────────────────────── -->
    <div class="hero-slide slide-2">
        <div class="slide-grid"></div>
        <div class="hero-inner">
            <div class="hero-text">
                <div class="hero-eyebrow">Academic Publishing</div>
                <h1 class="hero-h1">Academic Book<br>Writing &amp; <em>Publishing</em></h1>
                <p class="hero-p">Professional assistance in writing, editing, and publishing academic and scientific
                    books for researchers and academicians worldwide.</p>
                <div class="hero-pills">
                    <span class="hero-pill"><i class="fas fa-book"></i> 50+ Books Published</span>
                    <span class="hero-pill"><i class="fas fa-user-edit"></i> 12k+ Authors Assisted</span>
                    <span class="hero-pill"><i class="fas fa-file-alt"></i> 300+ Chapters Edited</span>
                    <span class="hero-pill"><i class="fas fa-star"></i> 95% Author Satisfaction</span>
                </div>
                <div class="hero-btns">
                    <a href="contact.php" class="btn-hp btn-hp-solid"><i class="fas fa-pen-nib"></i> Start Your Book</a>
                    <a href="books.php" class="btn-hp btn-hp-ghost">View Books <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-globe-wrap">
                    <div class="globe-ring"></div>
                    <div class="globe-ring-border"></div>
                    <img class="hero-globe-img" src="./uploads/assets/globe1.png" onerror=" this.style.display='none'"
                        alt=" Academic Publishing">
                    <!-- Fallback SVG globe if no image -->
                    <svg class="hero-globe-img" style="display:none" id="globeSvg1" viewBox="0 0 400 400" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <circle cx="200" cy="200" r="170" stroke="rgba(201,146,10,0.4)" stroke-width="1.5" />
                        <ellipse cx="200" cy="200" rx="100" ry="170" stroke="rgba(201,146,10,0.25)" stroke-width="1" />
                        <ellipse cx="200" cy="200" rx="170" ry="70" stroke="rgba(201,146,10,0.25)" stroke-width="1" />
                        <ellipse cx="200" cy="200" rx="170" ry="30" stroke="rgba(201,146,10,0.15)" stroke-width="1" />
                        <circle cx="200" cy="200" r="170" fill="url(#g1)" opacity="0.6" />
                        <defs>
                            <radialGradient id="g1" cx="60%" cy="40%" r="60%">
                                <stop offset="0%" stop-color="#c9920a" stop-opacity="0.3" />
                                <stop offset="100%" stop-color="#b5390f" stop-opacity="0.05" />
                            </radialGradient>
                        </defs>
                    </svg>
                    <div class="globe-stat globe-stat-tl">
                        <div class="gs-num">50<sup>+</sup></div>
                        <div class="gs-lbl">Books</div>
                    </div>
                    <div class="globe-stat globe-stat-tr">
                        <div class="gs-num">12k<sup>+</sup></div>
                        <div class="gs-lbl">Authors</div>
                    </div>
                    <div class="globe-stat globe-stat-bl">
                        <div class="gs-num">300<sup>+</sup></div>
                        <div class="gs-lbl">Chapters</div>
                    </div>
                    <div class="globe-stat globe-stat-br">
                        <div class="gs-num">95<sup>%</sup></div>
                        <div class="gs-lbl">Satisfaction</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── SLIDE 3 ─────────────────────────────────────────────── -->
    <div class="hero-slide slide-3">
        <div class="slide-grid"></div>
        <div class="hero-inner">
            <div class="hero-text">
                <div class="hero-eyebrow">End-to-End Support</div>
                <h1 class="hero-h1">Your Trusted <em>Partner</em><br>in Research Publication</h1>
                <p class="hero-p">From idea development and manuscript preparation to statistical analysis and journal
                    submission — we help researchers successfully publish their work.</p>
                <div class="hero-pills">
                    <span class="hero-pill"><i class="fas fa-star"></i> Rated 5★</span>
                    <span class="hero-pill"><i class="fas fa-journal-whills"></i> 100+ Journals</span>
                    <span class="hero-pill"><i class="fas fa-chart-line"></i> 99.5% Success Ratio</span>
                    <span class="hero-pill"><i class="fas fa-file-signature"></i> 3000+ Manuscripts</span>
                </div>
                <div class="hero-btns">
                    <a href="contact.php" class="btn-hp btn-hp-solid"><i class="fas fa-file-signature"></i> Contact
                        Us</a>
                    <a href="books.php" class="btn-hp btn-hp-ghost">Browse Books <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-globe-wrap">
                    <div class="globe-ring"></div>
                    <div class="globe-ring-border"></div>
                    <img class="hero-globe-img" src="./uploads/assets/globe1.png" onerror="this.style.display='none'"
                        alt="Research Publication">
                    <!-- Fallback SVG globe if no image -->
                    <svg class="hero-globe-img" style="display:none" id="globeSvg1" viewBox="0 0 400 400" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <circle cx="200" cy="200" r="170" stroke="rgba(201,146,10,0.4)" stroke-width="1.5" />
                        <ellipse cx="200" cy="200" rx="100" ry="170" stroke="rgba(201,146,10,0.25)" stroke-width="1" />
                        <ellipse cx="200" cy="200" rx="170" ry="70" stroke="rgba(201,146,10,0.25)" stroke-width="1" />
                        <ellipse cx="200" cy="200" rx="170" ry="30" stroke="rgba(201,146,10,0.15)" stroke-width="1" />
                        <circle cx="200" cy="200" r="170" fill="url(#g1)" opacity="0.6" />
                        <defs>
                            <radialGradient id="g1" cx="60%" cy="40%" r="60%">
                                <stop offset="0%" stop-color="#c9920a" stop-opacity="0.3" />
                                <stop offset="100%" stop-color="#b5390f" stop-opacity="0.05" />
                            </radialGradient>
                        </defs>
                    </svg>
                    <div class="globe-stat globe-stat-tl">
                        <div class="gs-num">5<sup>★</sup></div>
                        <div class="gs-lbl">Rated</div>
                    </div>
                    <div class="globe-stat globe-stat-tr">
                        <div class="gs-num">100<sup>+</sup></div>
                        <div class="gs-lbl">Journals</div>
                    </div>
                    <div class="globe-stat globe-stat-bl">
                        <div class="gs-num">99.5<sup>%</sup></div>
                        <div class="gs-lbl">Success</div>
                    </div>
                    <div class="globe-stat globe-stat-br">
                        <div class="gs-num">3K<sup>+</sup></div>
                        <div class="gs-lbl">Manuscripts</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── SLIDE 4 ─────────────────────────────────────────────── -->
    <div class="hero-slide slide-4">
        <div class="slide-grid"></div>
        <div class="hero-inner">
            <div class="hero-text">
                <div class="hero-eyebrow">Global Reach</div>
                <h1 class="hero-h1">Global <em>Research</em><br>Community</h1>
                <p class="hero-p">Supporting Researchers Worldwide — we proudly support doctors, scientists,
                    academicians, and students across the globe with professional research publication services.</p>
                <div class="hero-pills">
                    <span class="hero-pill"><i class="fas fa-university"></i> 200+ Institutes</span>
                    <span class="hero-pill"><i class="fas fa-file-alt"></i> 2500+ Synopses</span>
                    <span class="hero-pill"><i class="fas fa-graduation-cap"></i> 3000+ Thesis</span>
                    <span class="hero-pill"><i class="fas fa-globe"></i> 20+ Countries</span>
                </div>
                <div class="hero-btns">
                    <a href="contact.php" class="btn-hp btn-hp-solid"><i class="fas fa-paper-plane"></i> Join Our
                        Community</a>
                    <a href="about.php" class="btn-hp btn-hp-ghost">About Us <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-globe-wrap">
                    <div class="globe-ring"></div>
                    <div class="globe-ring-border"></div>
                    <img class="hero-globe-img" src="./uploads/assets/globe1.png" alt="Global Research Community">
                    <!-- Fallback SVG globe if no image -->
                    <svg class="hero-globe-img" style="display:none" id="globeSvg1" viewBox="0 0 400 400" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <circle cx="200" cy="200" r="170" stroke="rgba(201,146,10,0.4)" stroke-width="1.5" />
                        <ellipse cx="200" cy="200" rx="100" ry="170" stroke="rgba(201,146,10,0.25)" stroke-width="1" />
                        <ellipse cx="200" cy="200" rx="170" ry="70" stroke="rgba(201,146,10,0.25)" stroke-width="1" />
                        <ellipse cx="200" cy="200" rx="170" ry="30" stroke="rgba(201,146,10,0.15)" stroke-width="1" />
                        <circle cx="200" cy="200" r="170" fill="url(#g1)" opacity="0.6" />
                        <defs>
                            <radialGradient id="g1" cx="60%" cy="40%" r="60%">
                                <stop offset="0%" stop-color="#c9920a" stop-opacity="0.3" />
                                <stop offset="100%" stop-color="#b5390f" stop-opacity="0.05" />
                            </radialGradient>
                        </defs>
                    </svg>
                    <div class="globe-stat globe-stat-tl">
                        <div class="gs-num">200<sup>+</sup></div>
                        <div class="gs-lbl">Universities</div>
                    </div>
                    <div class="globe-stat globe-stat-tr">
                        <div class="gs-num">20<sup>+</sup></div>
                        <div class="gs-lbl">Countries</div>
                    </div>
                    <div class="globe-stat globe-stat-bl">
                        <div class="gs-num">10K<sup>+</sup></div>
                        <div class="gs-lbl">Consultations</div>
                    </div>
                    <div class="globe-stat globe-stat-br">
                        <div class="gs-num">3K<sup>+</sup></div>
                        <div class="gs-lbl">Thesis</div>
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
        <button class="hero-dot" onclick="heroGoto(3)"></button>
    </div>

    <div class="hero-progress" id="heroProgress"></div>
</div>

<script>
    /* ─── Hero carousel (4 slides) ──────────────────────────────── */
    const hSlides = document.querySelectorAll('.hero-slide');
    const hDots = document.querySelectorAll('.hero-dot');
    const hBar = document.getElementById('heroProgress');
    let hCur = 0, hTimer;

    function showSlide(n) {
        hSlides[hCur].classList.remove('active-slide');
        hDots[hCur].classList.remove('active');
        hCur = (n + hSlides.length) % hSlides.length;
        hSlides[hCur].classList.add('active-slide');
        hDots[hCur].classList.add('active');
        /* restart progress bar */
        if (hBar) { hBar.style.animation = 'none'; hBar.offsetHeight; hBar.style.animation = ''; }
    }
    function heroMove(d) { clearInterval(hTimer); showSlide(hCur + d); startAuto(); }
    function heroGoto(n) { clearInterval(hTimer); showSlide(n); startAuto(); }
    function startAuto() { hTimer = setInterval(() => showSlide(hCur + 1), 5200); }
    startAuto();

    /* Globe SVG fallback — show SVG if image fails */
    document.querySelectorAll('.hero-globe-img[src]').forEach(img => {
        img.addEventListener('error', function () {
            this.style.display = 'none';
            const svg = this.nextElementSibling;
            if (svg && svg.tagName === 'svg') svg.style.display = 'block';
        });
    });
</script>