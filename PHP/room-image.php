<?php

function staySmartRoomImage(string $storedImage): string
{
    $legacyImages = [
        '204622896.jpg' => 'marina-pool-at-night.png',
        '430868253.jpg' => 'palm-resort-pool.png',
        '517844446.jpg' => 'downtown-marina-night.png',
        '576753858.jpg' => 'waterfront-skyline.png',
        'adam-winger-JByeWNMdfoY-unsplash.jpg' => 'bright-lounge-room.png',
        'christopher-jolly-GqbU78bdJFM-unsplash.jpg' => 'coastal-blue-bedroom.png',
        'collov-home-design-H-1j_s0dhCw-unsplash.jpg' => 'garden-view-living-suite.png',
        'dada_design-0K7CzDUEtKA-unsplash.jpg' => 'contemporary-lounge-suite.png',
        'dada_design-9LpUDXYP5-4-unsplash.jpg' => 'neutral-double-bedroom.png',
        'dada_design-KDPmuv6yFDI-unsplash.jpg' => 'modern-king-bedroom.png',
        'dada_design-Zt2cY3LhoBw-unsplash.jpg' => 'sunlit-deluxe-bedroom.png',
        'faisal-manga-4NeuZU-ynnw-unsplash.jpg' => 'island-resort-exterior.png',
        'frames-for-your-heart-FqqiAvJejto-unsplash (1).jpg' => 'bright-garden-bedroom.png',
        'h1.png' => 'warm-accent-bedroom.png',
        'iwood-R5v8Xtc0ecg-unsplash.jpg' => 'minimalist-bedroom-suite.png',
        'manuel-moreno-DGa0LQ0yDPc-unsplash.jpg' => 'mountain-pool-retreat.png',
        'masaaki-komori-iMbXscxYIFE-unsplash.jpg' => 'twin-comfort-bedroom.png',
        'max-harlynking-zLMlEl0_vXI-unsplash.jpg' => 'open-living-suite.png',
        'nikolay-hristov-Azho3flgdQU-unsplash.jpg' => 'glass-dome-retreat.png',
        'olexandr-ignatov-w72a24brINI-unsplash.jpg' => 'city-view-king-bedroom.png',
        'patrick-robert-doyle-pZ6W7HuuY6w-unsplash.jpg' => 'compact-city-bedroom.png',
        'rio-lecatompessy-p77qNCTFEJQ-unsplash.jpg' => 'palm-villa-pool.png',
        'rktkn-ssOtyGE8CyE-unsplash.jpg' => 'waterfront-resort-pool.png',
        'roberto-nickson-emqnSQwQQDo-unsplash.jpg' => 'contemporary-bedroom-suite.png',
        'sasha-kaunas-TAgGZWz6Qg8-unsplash.jpg' => 'tropical-beach-resort.png',
        'suite.jpg' => 'warm-classic-suite.png',
        'tony-yakovlenko-lDxxeAJrWpE-unsplash.jpg' => 'hotel-corridor.png',
        'visualsofdana-T5pL6ciEn-I-unsplash.jpg' => 'woodland-cabin-bedroom.png',
        'waldemar-57Bs4BoKtn0-unsplash.jpg' => 'hotel-cafe.png',
    ];

    $filename = basename($storedImage);
    if (preg_match('/^[A-Za-z0-9_-]+\.(?:png|jpe?g|webp)$/i', $filename)
        && is_file(__DIR__ . '/../Assets/our rooms/' . $filename)) {
        return $filename;
    }
    if (isset($legacyImages[$filename])) {
        return $legacyImages[$filename];
    }

    if (preg_match('/^[A-Za-z0-9_-]+\.png$/', $filename)) {
        return $filename;
    }

    return 'bright-garden-bedroom.png';
}
