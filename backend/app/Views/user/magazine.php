<?php
$stories = [
    ['category' => 'gardening', 'number' => '01', 'title' => 'A greener corner, one plant at a time.', 'image' => 'growbag.png', 'intro' => 'Make room for a small growing space, whether you have a balcony, a patio, or a sunny garden.', 'tips' => ['Use coir grow bags for vegetables and herbs in a space that suits their light needs.', 'Place mulch mats around plants to help retain soil moisture and suppress weeds.', 'Check moisture regularly and choose a container with suitable drainage.']],
    ['category' => 'home_living', 'number' => '02', 'title' => 'Natural texture. Everyday purpose.', 'image' => 'coirdoormat.png', 'intro' => 'Bring the texture of coconut fiber into the places you use every day.', 'tips' => ['Place a coir doormat at a sheltered entrance to help catch dirt from shoes.', 'Use lined hanging baskets to create a planted corner indoors or outdoors.', 'Shake loose dirt from mats and let damp coir dry in a ventilated area.']],
    ['category' => 'construction', 'number' => '03', 'title' => 'Give growing landscapes a helping hand.', 'image' => 'geotextitle.png', 'intro' => 'Coir products can support erosion-control and revegetation projects while plants become established.', 'tips' => ['Explore geotextile mats and erosion netting for exposed soil and planted slopes.', 'Consider coir logs for suitable streambank or shoreline restoration projects.', 'Have a qualified project professional confirm the product, anchoring, and coverage your site requires.']],
    ['category' => 'craft_utility', 'number' => '04', 'title' => 'Small projects, a natural finish.', 'image' => 'coirrope.jpg', 'intro' => 'A simple length of coir rope can add a tactile detail to your next creative project.', 'tips' => ['Use coir rope for decorative wraps, rustic accents, and garden crafts.', 'Measure your project and confirm the supplied rope length before ordering.', 'Use products only within their specified purpose and load limits.']],
];
?>
<!DOCTYPE html>
<html lang="en">
<?= $this->include('components/head', ['title' => 'The Coir Journal — COCOIR']) ?>
<body class="site-store">
<?= $this->include('components/header') ?>
<main class="journal-page">
    <header class="journal-heading"><p class="store-eyebrow">The COCOIR magazine · Ideas for everyday living</p><h1>The Coir Journal</h1><p>Thoughtful ways to bring natural fiber into your home, garden, and next project.</p><a href="#journal-stories" class="category-link">Explore the stories ↓</a></header>
    <div id="journal-stories">
    <?php foreach ($stories as $story): ?>
        <article class="journal-story">
            <div class="journal-story-image"><img src="<?= base_url('images/' . $story['image']) ?>" alt="<?= esc(ucwords(str_replace('_', ' ', $story['category']))) ?> coir inspiration" loading="lazy"><span><?= esc($story['number']) ?></span></div>
            <div class="journal-story-copy"><p class="store-eyebrow"><?= esc(ucwords(str_replace('_', ' ', $story['category']))) ?></p><h2><?= esc($story['title']) ?></h2><p><?= esc($story['intro']) ?></p><ul><?php foreach ($story['tips'] as $tip): ?><li><?= esc($tip) ?></li><?php endforeach; ?></ul>
                <div class="journal-shop"><h3>Shop this story</h3>
                <?php $featured = array_slice(array_values(array_filter($products, static fn($item) => $item['category'] === $story['category'])), 0, 2); ?>
                <?php if (!$featured): ?><p>Products for this story are coming soon.</p><a class="category-link" href="<?= site_url('products') ?>">Browse all products</a><?php endif; ?>
                <?php foreach ($featured as $featuredProduct): ?>
                    <div class="journal-shop-item"><a href="<?= site_url('products/' . (int) $featuredProduct['id']) ?>"><strong><?= esc($featuredProduct['name']) ?></strong><span>₱<?= number_format((float) $featuredProduct['price'], 2) ?></span><span class="category-link">View product &amp; package details →</span></a><?= view('components/product_purchase', ['purchaseProduct' => $featuredProduct]) ?></div>
                <?php endforeach; ?>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
    <section class="journal-end"><p class="store-eyebrow">Made with purpose</p><h2>Find a little inspiration.<br>Make it part of your everyday.</h2><a href="<?= site_url('products') ?>" class="store-button">Shop all products</a></section>
</main>
<?= $this->include('components/footer') ?>
<script src="<?= base_url('js/product-purchase.js') ?>"></script>
</body>
</html>
