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
    <?php include './PHP/head.php'; ?>
    <title>About StaySmart</title>
    <meta name="description" content="Learn about StaySmart and how we make finding and booking a comfortable stay simple.">
    <link rel="stylesheet" href="./CSS/about.css">
</head>

<body class="about-page">
    <?php include './PHP/header.php'; ?>

    <main>
        <section class="about-hero" aria-labelledby="about-title">
            <div class="about-hero-content">
                <p class="about-eyebrow"><?php echo $escapeContent($siteContent['about_eyebrow']); ?></p>
                <h1 id="about-title"><?php echo $escapeContent($siteContent['about_title']); ?></h1>
                <p><?php echo $escapeContent($siteContent['about_description']); ?></p>
                <a class="about-primary-link" href="./rooms.php">Explore our rooms <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="about-hero-image">
                <img src="./<?php echo $escapeContent(staySmartContentImage($siteContent['about_image'], 'Assets/mainabout.png')); ?>" alt="StaySmart hospitality" width="1200" height="800" fetchpriority="high">
                <div class="about-image-note">
                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                    <span><?php echo $escapeContent($siteContent['about_image_note']); ?></span>
                </div>
            </div>
        </section>

        <section class="about-story" aria-labelledby="story-title">
            <div class="about-story-mark" aria-hidden="true"><i class="fa-solid fa-heart"></i></div>
            <div class="about-story-copy">
                <p class="about-eyebrow"><?php echo $escapeContent($siteContent['about_story_eyebrow']); ?></p>
                <h2 id="story-title"><?php echo $escapeContent($siteContent['about_story_title']); ?></h2>
                <p><?php echo $escapeContent($siteContent['about_story_paragraph_1']); ?></p>
                <p><?php echo $escapeContent($siteContent['about_story_paragraph_2']); ?></p>
                <a class="about-text-link" href="./contact.php">Have a question? Get in touch <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        </section>

        <section class="about-values" aria-labelledby="values-title">
            <div class="about-section-heading">
                <p class="about-eyebrow"><?php echo $escapeContent($siteContent['about_values_eyebrow']); ?></p>
                <h2 id="values-title"><?php echo $escapeContent($siteContent['about_values_title']); ?></h2>
            </div>
            <div class="about-value-grid">
                <article class="about-value-card">
                    <span class="about-value-icon"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span>
                    <h3><?php echo $escapeContent($siteContent['about_value_1_title']); ?></h3>
                    <p><?php echo $escapeContent($siteContent['about_value_1_description']); ?></p>
                </article>
                <article class="about-value-card">
                    <span class="about-value-icon"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i></span>
                    <h3><?php echo $escapeContent($siteContent['about_value_2_title']); ?></h3>
                    <p><?php echo $escapeContent($siteContent['about_value_2_description']); ?></p>
                </article>
                <article class="about-value-card">
                    <span class="about-value-icon"><i class="fa-solid fa-headset" aria-hidden="true"></i></span>
                    <h3><?php echo $escapeContent($siteContent['about_value_3_title']); ?></h3>
                    <p><?php echo $escapeContent($siteContent['about_value_3_description']); ?></p>
                </article>
            </div>
        </section>

        <section class="about-cta" aria-labelledby="about-cta-title">
            <div>
                <p class="about-eyebrow"><?php echo $escapeContent($siteContent['about_cta_eyebrow']); ?></p>
                <h2 id="about-cta-title"><?php echo $escapeContent($siteContent['about_cta_title']); ?></h2>
            </div>
            <a class="about-primary-link" href="./rooms.php">Browse rooms <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </section>
    </main>

    <?php include './PHP/footer.php'; ?>
</body>

</html>
