<?php

function staySmartContentFields(): array
{
    return [
        'home' => [
            'label' => 'Home page',
            'groups' => [
                'Hero' => [
                    'home_hero_eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'A better stay starts here'],
                    'home_hero_title' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Find a place to feel at home on your next vacation'],
                    'home_hero_description' => ['label' => 'Description', 'type' => 'textarea', 'default' => 'Thoughtful rooms, warm hospitality, and stays worth remembering.'],
                    'home_hero_image' => ['label' => 'Hero image', 'type' => 'image', 'default' => 'Assets/mainhome.png'],
                    'home_hero_link' => ['label' => 'Button text', 'type' => 'text', 'default' => 'Explore our rooms'],
                ],
                'Ideas for your next getaway' => [
                    'home_ideas_eyebrow' => ['label' => 'Section eyebrow', 'type' => 'text', 'default' => 'Ideas for your next getaway'],
                    'home_ideas_title' => ['label' => 'Section title', 'type' => 'text', 'default' => 'Recent posts'],
                    'home_idea_1_category' => ['label' => 'Idea 1 category', 'type' => 'text', 'default' => 'Travel inspiration'],
                    'home_idea_1_title' => ['label' => 'Idea 1 title', 'type' => 'text', 'default' => 'Make the most of a weekend away'],
                    'home_idea_1_description' => ['label' => 'Idea 1 text', 'type' => 'textarea', 'default' => 'Slow down, settle in, and leave a little room in your plans for discovery.'],
                    'home_idea_1_image' => ['label' => 'Idea 1 image', 'type' => 'image', 'default' => 'Assets/mainabout.png'],
                    'home_idea_2_category' => ['label' => 'Idea 2 category', 'type' => 'text', 'default' => 'Stay ideas'],
                    'home_idea_2_title' => ['label' => 'Idea 2 title', 'type' => 'text', 'default' => 'Find a stay that fits your trip'],
                    'home_idea_2_description' => ['label' => 'Idea 2 text', 'type' => 'textarea', 'default' => 'A few simple things to consider when choosing a room for your next visit.'],
                    'home_idea_2_image' => ['label' => 'Idea 2 image', 'type' => 'image', 'default' => 'Assets/view-1.png'],
                    'home_idea_3_category' => ['label' => 'Idea 3 category', 'type' => 'text', 'default' => 'Destinations'],
                    'home_idea_3_title' => ['label' => 'Idea 3 title', 'type' => 'text', 'default' => 'Wake up somewhere wonderful'],
                    'home_idea_3_description' => ['label' => 'Idea 3 text', 'type' => 'textarea', 'default' => 'Explore a new view, enjoy a change of pace, and make the journey your own.'],
                    'home_idea_3_image' => ['label' => 'Idea 3 image', 'type' => 'image', 'default' => 'Assets/mainhome.png'],
                ],
            ],
        ],
        'contact' => [
            'label' => 'Contact page',
            'groups' => [
                'Contact introduction' => [
                    'contact_eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'We’re happy to help'],
                    'contact_title' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Let’s make your stay a little easier.'],
                    'contact_description' => ['label' => 'Description', 'type' => 'textarea', 'default' => 'Questions about a room or your reservation? Send us a note and our team will be glad to hear from you.'],
                    'contact_image' => ['label' => 'Contact hero image', 'type' => 'image', 'default' => 'Assets/maincontact.png'],
                ],
                'Contact details' => [
                    'contact_details_kicker' => ['label' => 'Section eyebrow', 'type' => 'text', 'default' => 'Get in touch'],
                    'contact_details_title' => ['label' => 'Section title', 'type' => 'text', 'default' => 'How can we help?'],
                    'contact_details_description' => ['label' => 'Intro text', 'type' => 'textarea', 'default' => 'Choose the option that works best for you. We’ll be happy to help with your StaySmart questions.'],
                    'contact_email' => ['label' => 'Contact email', 'type' => 'email', 'default' => 'adnan.pls2003@gmail.com'],
                    'contact_note' => ['label' => 'Booking note', 'type' => 'textarea', 'default' => 'For a quicker reply, include your booking details if your question is about a reservation.'],
                ],
                'Message form' => [
                    'contact_form_kicker' => ['label' => 'Form eyebrow', 'type' => 'text', 'default' => 'Send us a message'],
                    'contact_form_title' => ['label' => 'Form title', 'type' => 'text', 'default' => 'We’re listening.'],
                    'contact_form_description' => ['label' => 'Form intro text', 'type' => 'textarea', 'default' => 'Fill in the details below. Submitting opens a draft in your email app so you can send it directly to our team.'],
                ],
            ],
        ],
        'about' => [
            'label' => 'About page',
            'groups' => [
                'About introduction' => [
                    'about_eyebrow' => ['label' => 'Eyebrow', 'type' => 'text', 'default' => 'A better stay starts here'],
                    'about_title' => ['label' => 'Headline', 'type' => 'text', 'default' => 'Feel at home, wherever you’re going.'],
                    'about_description' => ['label' => 'Description', 'type' => 'textarea', 'default' => 'StaySmart makes it easier to find a welcoming room, plan your visit, and focus on enjoying the journey.'],
                    'about_image' => ['label' => 'About hero image', 'type' => 'image', 'default' => 'Assets/mainabout.png'],
                    'about_image_note' => ['label' => 'Image caption', 'type' => 'text', 'default' => 'Make room for a little more comfort.'],
                ],
                'Our story' => [
                    'about_story_eyebrow' => ['label' => 'Story eyebrow', 'type' => 'text', 'default' => 'About StaySmart'],
                    'about_story_title' => ['label' => 'Story title', 'type' => 'text', 'default' => 'The little details make a stay feel special.'],
                    'about_story_paragraph_1' => ['label' => 'Story paragraph 1', 'type' => 'textarea', 'default' => 'Travel planning should feel exciting, not complicated. StaySmart brings room discovery and booking details together in one place, so you can choose a stay that suits your trip and keep your reservation easy to find.'],
                    'about_story_paragraph_2' => ['label' => 'Story paragraph 2', 'type' => 'textarea', 'default' => 'Whether you’re planning a quick visit or a longer getaway, we’re here to help make the process feel straightforward from the first search to check-in.'],
                ],
                'What makes us different' => [
                    'about_values_eyebrow' => ['label' => 'Section eyebrow', 'type' => 'text', 'default' => 'The StaySmart difference'],
                    'about_values_title' => ['label' => 'Section title', 'type' => 'text', 'default' => 'A simpler way to plan your stay.'],
                    'about_value_1_title' => ['label' => 'Value 1 title', 'type' => 'text', 'default' => 'Find your fit'],
                    'about_value_1_description' => ['label' => 'Value 1 text', 'type' => 'textarea', 'default' => 'Explore available rooms and compare the details that matter for your visit.'],
                    'about_value_2_title' => ['label' => 'Value 2 title', 'type' => 'text', 'default' => 'Book with ease'],
                    'about_value_2_description' => ['label' => 'Value 2 text', 'type' => 'textarea', 'default' => 'Move through a clear booking process and keep your reservation details together.'],
                    'about_value_3_title' => ['label' => 'Value 3 title', 'type' => 'text', 'default' => 'Here to help'],
                    'about_value_3_description' => ['label' => 'Value 3 text', 'type' => 'textarea', 'default' => 'Reach out to us with questions about rooms or the details of your stay.'],
                ],
                'Closing message' => [
                    'about_cta_eyebrow' => ['label' => 'Closing eyebrow', 'type' => 'text', 'default' => 'Your next trip is waiting'],
                    'about_cta_title' => ['label' => 'Closing title', 'type' => 'text', 'default' => 'Let’s find a place that feels right.'],
                ],
            ],
        ],
    ];
}

function staySmartContentDefaults(): array
{
    $defaults = [];
    foreach (staySmartContentFields() as $page) {
        foreach ($page['groups'] as $fields) {
            foreach ($fields as $key => $field) {
                $defaults[$key] = $field['default'];
            }
        }
    }

    return $defaults;
}

function staySmartLoadContent(mysqli $connection): array
{
    $tableExists = $connection->query(
        "SELECT COUNT(*) AS table_count
        FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = 'site_content'"
    );
    if ((int) $tableExists->fetch_assoc()['table_count'] === 0) {
        $connection->query(
            'CREATE TABLE site_content (
                content_key VARCHAR(100) NOT NULL PRIMARY KEY,
                content_value TEXT NOT NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
    }

    $content = staySmartContentDefaults();
    $storedFieldCount = (int) $connection->query('SELECT COUNT(*) AS field_count FROM site_content')->fetch_assoc()['field_count'];
    if ($storedFieldCount < count($content)) {
        $seedContent = $connection->prepare(
            'INSERT IGNORE INTO site_content (content_key, content_value) VALUES (?, ?)'
        );
        foreach ($content as $key => $value) {
            $seedContent->bind_param('ss', $key, $value);
            $seedContent->execute();
        }
        $seedContent->close();
    }

    $result = $connection->query('SELECT content_key, content_value FROM site_content');
    while ($row = $result->fetch_assoc()) {
        if (array_key_exists($row['content_key'], $content)) {
            $content[$row['content_key']] = $row['content_value'];
        }
    }

    return $content;
}

function staySmartContentImage(string $image, string $fallback): string
{
    if (preg_match('/^Assets\/site-content\/[a-f0-9]{32}\.(?:png|jpe?g|webp)$/', $image)) {
        return $image;
    }

    if (preg_match('/^Assets\/[A-Za-z0-9_-]+\.(?:png|jpe?g|webp)$/', $image)) {
        return $image;
    }

    return $fallback;
}
