<!-- FOOTER -->
<footer style="
    background: #18130e;
    border-top: 1px solid rgba(255,255,255,.07);
    font-family: 'Cabinet Grotesk', sans-serif;
    color: rgba(250,247,242,.55);
    padding: 56px 0 28px;
    position: relative;
    overflow: hidden;
">
    <!-- Warm glow -->
    <div style="
        position: absolute; inset: 0; pointer-events: none;
        background: radial-gradient(ellipse 60% 80% at 20% 100%, rgba(181,57,15,.1), transparent),
                    radial-gradient(ellipse 40% 60% at 80% 0%, rgba(201,146,10,.07), transparent);
    "></div>

    <div class="container" style="position:relative; z-index:1;">

        <div class="row gy-4 mb-5">

            <!-- Brand col -->
            <div class="col-lg-4 col-md-12">
                <img src="./uploads/logos/logotest.png" alt="Logo"
                    style="height:52px; width:auto; margin-bottom:16px; filter:brightness(1.05);">
                <p style="font-size:14px; line-height:1.75; color:rgba(250,247,242,.45); max-width:300px;">
                    Dedicated to publishing excellence connecting authors, ideas, and readers worldwide.
                </p>
                <!-- Social icons -->
                <div style="display:flex; gap:10px; margin-top:20px;">
                    <?php
                    $socials = [
                        ['fab fa-facebook-f', 'https://www.facebook.com/share/1DNGiWUmuM/'],
                        ['fab fa-instagram', 'https://www.instagram.com/professional_publications_serv?igsh=YXI2ZGd6aHluZzhy'],
                        // ['fab fa-linkedin-in', '#'],
                    ];
                    foreach ($socials as [$ico, $url]): ?>
                        <a href="<?= $url ?>" style="
                            width:36px; height:36px; border-radius:8px;
                            background:rgba(255,255,255,.06);
                            border:1px solid rgba(255,255,255,.09);
                            color:rgba(250,247,242,.55);
                            display:flex; align-items:center; justify-content:center;
                            font-size:14px; text-decoration:none;
                            transition:all .22s ease;
                        "
                            onmouseover="this.style.background='rgba(201,146,10,.15)';this.style.color='#c9920a';this.style.borderColor='rgba(201,146,10,.3)'"
                            onmouseout="this.style.background='rgba(255,255,255,.06)';this.style.color='rgba(250,247,242,.55)';this.style.borderColor='rgba(255,255,255,.09)'">
                            <i class="<?= $ico ?>"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick links -->
            <div class="col-lg-2 col-sm-6">
                <div
                    style="font-size:11px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:rgba(250,247,242,.3); margin-bottom:16px;">
                    Links
                </div>
                <?php
                $links = [
                    ['fas fa-home', 'Home', './'],
                    ['fas fa-book-open', 'Books', './books.php'],
                    ['fas fa-journal-whills', 'Journal Development', './journal-development.php'],
                    ['fas fa-envelope', 'Contact Us', './contact.php'],
                    ['fas fa-info-circle', 'About Us', './about.php'],
                    ['fas fa-question-circle', 'FAQ', './faq.php'],
                ];
                foreach ($links as [$ico, $lbl, $url]): ?>
                    <a href="<?= $url ?>" style="
                        display:flex; align-items:center; gap:8px;
                        font-size:14px; color:rgba(250,247,242,.5);
                        text-decoration:none; padding:5px 0;
                        transition:color .2s;
                    " onmouseover="this.style.color='rgba(250,247,242,.9)'"
                        onmouseout="this.style.color='rgba(250,247,242,.5)'">
                        <i class="<?= $ico ?>" style="font-size:12px; color:#c9920a; width:14px;"></i>
                        <?= $lbl ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Contact info -->
            <div class="col-lg-3 col-sm-6">
                <div
                    style="font-size:11px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:rgba(250,247,242,.3); margin-bottom:16px;">
                    Contact
                </div>
                <?php
                $contacts = [
                    ['fas fa-phone', '+91 97527 47384, +91 87082 99825,'],
                    ['fas fa-envelope', 'info@professionalpublicationservice.com'],
                    ['fas fa-map-marker-alt', '7895+7GR, Bhopal-Indore Highway Bhainsakhedi, Bairagarh, Bhopal, Madhya Pradesh 462030'],
                ];
                foreach ($contacts as [$ico, $val]): ?>
                    <div style="display:flex; align-items:flex-start; gap:10px; margin-bottom:12px;">
                        <i class="<?= $ico ?>" style="font-size:13px; color:#c9920a; margin-top:2px; flex-shrink:0;"></i>
                        <span style="font-size:14px; color:rgba(250,247,242,.5); line-height:1.5;">
                            <?= $val ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- WhatsApp CTA -->
            <div class="col-lg-3 col-md-12">
                <div style="
                    background:rgba(255,255,255,.04);
                    border:1px solid rgba(255,255,255,.08);
                    border-radius:14px;
                    padding:24px;
                ">
                    <div style="font-size:13px; font-weight:600; color:rgba(250,247,242,.8); margin-bottom:8px;">
                        Order a Book
                    </div>
                    <p style="font-size:13px; color:rgba(250,247,242,.4); margin-bottom:16px; line-height:1.6;">
                        Reach us directly on WhatsApp for quick orders and enquiries.
                    </p>
                    <a href="https://wa.me/919752747384" target="_blank" style="
                        display:inline-flex; align-items:center; gap:8px;
                        padding:10px 20px;
                        background:#25d366;
                        color:#fff;
                        border-radius:8px;
                        font-size:13.5px; font-weight:700;
                        text-decoration:none;
                        box-shadow:0 4px 16px rgba(37,211,102,.3);
                        transition:all .22s ease;
                    " onmouseover="this.style.background='#20ba5a';this.style.transform='translateY(-1px)'"
                        onmouseout="this.style.background='#25d366';this.style.transform='translateY(0)'">
                        <i class="fab fa-whatsapp" style="font-size:18px;"></i>
                        Chat on WhatsApp
                    </a>
                </div>
            </div>

        </div>

        <!-- Divider -->
        <div style="height:1px; background:rgba(255,255,255,.07); margin-bottom:24px;"></div>

        <!-- Bottom bar -->
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <p style="font-size:13px; color:rgba(250,247,242,.3); margin:0;">
                ©
                <?= date('Y') ?> <span style="color:rgba(250,247,242,.5); font-weight:600;">Professional Publication
                    Services</span>. All rights reserved.
            </p>
            <div style="display:flex; gap:20px;">
                <a href="#"
                    style="font-size:12px; color:rgba(250,247,242,.3); text-decoration:none; transition:color .2s;"
                    onmouseover="this.style.color='rgba(250,247,242,.7)'"
                    onmouseout="this.style.color='rgba(250,247,242,.3)'">
                    Privacy Policy
                </a>
                <a href="#"
                    style="font-size:12px; color:rgba(250,247,242,.3); text-decoration:none; transition:color .2s;"
                    onmouseover="this.style.color='rgba(250,247,242,.7)'"
                    onmouseout="this.style.color='rgba(250,247,242,.3)'">
                    Terms of Use
                </a>
            </div>
        </div>

    </div>
</footer>