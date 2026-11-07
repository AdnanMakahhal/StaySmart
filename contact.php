<?php
session_start();
require_once __DIR__ . '/PHP/auth.php';
require_once './PHP/connect.php';
require_once './PHP/site-content.php';
$siteContent = staySmartLoadContent($con);
$escapeContent = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <?php include './PHP/head.php'; ?>
    <link rel="stylesheet" href="./CSS/contact.css">
</head>

<body class="contact-page">
    <?php include './PHP/header.php'; ?>

    <main>
        <section class="contact-hero container" aria-labelledby="contact-title">
            <img class="contact-hero-image" src="./<?php echo $escapeContent(staySmartContentImage($siteContent['contact_image'], 'Assets/maincontact.png')); ?>" alt="" aria-hidden="true">
            <div class="contact-hero-content">
                <p class="contact-eyebrow"><?php echo $escapeContent($siteContent['contact_eyebrow']); ?></p>
                <h1 id="contact-title"><?php echo $escapeContent($siteContent['contact_title']); ?></h1>
                <p><?php echo $escapeContent($siteContent['contact_description']); ?></p>
            </div>
        </section>

        <section class="container contact-content py-5" aria-label="Contact StaySmart">
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-5">
                    <div class="contact-info-card h-100">
                        <p class="contact-card-kicker"><?php echo $escapeContent($siteContent['contact_details_kicker']); ?></p>
                        <h2><?php echo $escapeContent($siteContent['contact_details_title']); ?></h2>
                        <p class="contact-info-intro"><?php echo $escapeContent($siteContent['contact_details_description']); ?></p>

                        <a class="contact-method" href="mailto:<?php echo $escapeContent($siteContent['contact_email']); ?>">
                            <span class="contact-method-icon"><i class="fa-regular fa-envelope" aria-hidden="true"></i></span>
                            <span><small>Email us</small><strong><?php echo $escapeContent($siteContent['contact_email']); ?></strong></span>
                        </a>
                        <a class="contact-method" href="./rooms.php">
                            <span class="contact-method-icon"><i class="fa-solid fa-bed" aria-hidden="true"></i></span>
                            <span><small>Planning a visit?</small><strong>Explore available rooms</strong></span>
                        </a>

                        <div class="contact-note">
                            <i class="fa-regular fa-circle-check" aria-hidden="true"></i>
                            <p><?php echo $escapeContent($siteContent['contact_note']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="contact-form-card h-100">
                        <p class="contact-card-kicker"><?php echo $escapeContent($siteContent['contact_form_kicker']); ?></p>
                        <h2><?php echo $escapeContent($siteContent['contact_form_title']); ?></h2>
                        <p class="contact-form-intro"><?php echo $escapeContent($siteContent['contact_form_description']); ?></p>

                        <form class="row g-3" id="contact-form">
                            <div class="col-md-6">
                                <label for="contact-name" class="form-label">Your name</label>
                                <input type="text" class="form-control" id="contact-name" name="name" placeholder="How should we address you?" autocomplete="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="contact-email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="contact-email" name="email" placeholder="you@example.com" autocomplete="email" required>
                            </div>
                            <div class="col-12">
                                <label for="contact-subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="contact-subject" name="subject" placeholder="What can we help with?" required>
                            </div>
                            <div class="col-12">
                                <label for="contact-message" class="form-label">Message</label>
                                <textarea class="form-control" id="contact-message" name="message" rows="5" placeholder="Tell us a little more..." required></textarea>
                            </div>
                            <div class="col-12 d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                                <button class="btn contact-submit" type="submit">
                                    Open email to send <i class="fa-solid fa-arrow-right ms-2" aria-hidden="true"></i>
                                </button>
                                <span class="contact-form-hint"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i>Your details are used only to reply.</span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        const contactForm = document.getElementById("contact-form");

        contactForm.addEventListener("submit", (event) => {
            event.preventDefault();

            const formData = new FormData(contactForm);
            const subject = `[StaySmart] ${formData.get("subject")}`;
            const body = [
                `Name: ${formData.get("name")}`,
                `Email: ${formData.get("email")}`,
                "",
                formData.get("message"),
            ].join("\n");

            window.location.href = `mailto:<?php echo $escapeContent($siteContent['contact_email']); ?>?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        });
    </script>

    <?php include './PHP/footer.php'; ?>
