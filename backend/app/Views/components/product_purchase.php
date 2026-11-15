<form class="product-purchase" method="post" action="<?= site_url('cart/add') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $purchaseProduct['id'] ?>">
    <input type="hidden" name="name" value="<?= esc($purchaseProduct['name']) ?>">
    <input type="hidden" name="price" value="<?= esc($purchaseProduct['price']) ?>">
    <input type="hidden" name="image" value="<?= esc($purchaseProduct['image'] ?? '') ?>">
    <button class="store-button" type="submit" <?= (int) $purchaseProduct['stock'] <= 0 ? 'disabled' : '' ?>><?= (int) $purchaseProduct['stock'] > 0 ? 'Add to cart' : 'Out of stock' ?></button>
    <p class="purchase-feedback" role="status" aria-live="polite"></p>
</form>
