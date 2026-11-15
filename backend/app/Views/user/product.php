<!DOCTYPE html>
<html lang="en">
<?= $this->include('components/head', ['title' => esc($product['name']) . ' — COCOIR']) ?>
<body class="site-store">
<?= $this->include('components/header') ?>
<main class="product-detail-page">
    <nav class="detail-breadcrumb" aria-label="Breadcrumb"><a href="<?= site_url('products') ?>">All products</a> <span aria-hidden="true">/</span> <?= esc($product['name']) ?></nav>
    <div class="product-detail-layout">
        <div class="product-detail-image"><img src="<?= base_url('images/' . ($product['image'] ?: 'coco.png')) ?>" alt="<?= esc($product['name']) ?>"></div>
        <div class="product-detail-copy">
            <p class="store-eyebrow"><?= esc(ucwords(str_replace('_', ' ', $product['category']))) ?></p>
            <h1><?= esc($product['name']) ?></h1>
            <p class="detail-price">₱<?= number_format((float) $product['price'], 2) ?> <small>per order unit</small></p>
            <p><?= nl2br(esc($product['description'] ?? '')) ?></p>
            <dl class="product-specs">
                <div><dt>What you receive</dt><dd><?= nl2br(esc(trim($product['package_contents'] ?? '') ?: 'Package quantity has not been specified. Please contact us to confirm the contents before ordering.')) ?></dd></div>
                <div><dt>Size, weight &amp; specifications</dt><dd><?= nl2br(esc(trim($product['specifications'] ?? '') ?: 'Exact measurements and weight have not been specified. Please contact us if your project requires a particular size.')) ?></dd></div>
                <div><dt>Availability</dt><dd><?= (int) $product['stock'] > 0 ? (int) $product['stock'] . ' order units in stock' : 'Out of stock' ?></dd></div>
            </dl>
            <p class="detail-note">Each click adds one order unit. The contents of that unit are listed above.</p>
            <?= view('components/product_purchase', ['purchaseProduct' => $product]) ?>
            <div class="store-actions"><a href="<?= site_url('cart') ?>" class="category-link">View cart</a><a href="<?= site_url('magazine') ?>" class="category-link">Explore product uses</a><a href="<?= site_url('/') ?>#contact" class="category-link">Ask about this product</a></div>
        </div>
    </div>
</main>
<?= $this->include('components/footer') ?>
<script src="<?= base_url('js/product-purchase.js') ?>"></script>
</body>
</html>
