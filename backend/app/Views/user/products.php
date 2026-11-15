<?php
/**
 * View: user/products.php
 * Controller: Users::products()
 * Route: /products
 */
$categoryLabels = [
    'home_living'   => 'Home & Living',
    'gardening'     => 'Gardening',
    'construction'  => 'Construction',
    'craft_utility' => 'Craft & Utility',
    'agriculture'   => 'Agriculture',
];
$badgeClass = [
    'New'         => 'badge-new',
    'Trending'    => 'badge-trending',
    'Best Seller' => 'badge-bestseller',
    'Sale'        => 'badge-sale',
];
$products = $products ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<?= $this->include('components/head', ['title' => 'Products — COCOIR Coconut Coir Co.']) ?>
<style>
.card-img{transition:transform .3s ease}
.filter-pill, .page-btn{cursor:pointer;transition:all .2s ease}
.filter-pill:hover,.page-btn:hover{background:#252a24;color:#fff;border-color:#252a24}
select{appearance:none;cursor:pointer}
/* Modal */
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(20,10,5,.6);backdrop-filter:blur(6px);z-index:9997;align-items:center;justify-content:center;padding:16px}
        .modal-overlay.open{display:flex}
        .modal-img-wrap{position:relative;overflow:hidden;border-radius:24px 0 0 24px}
        @media(max-width:640px){.modal-img-wrap{border-radius:24px 24px 0 0}}

</style>

<body class="site-store bg-coco-cream font-body text-coco-brown">

<!-- CSRF token for cart AJAX -->
<input type="hidden" id="csrf-name"  value="<?= csrf_token() ?>">
<input type="hidden" id="csrf-value" value="<?= csrf_hash() ?>">

<?= $this->include('components/header') ?>

<!-- Flash messages -->
<?php $flashSuccess = session()->getFlashdata('success'); $flashError = session()->getFlashdata('error'); ?>
<?php if ($flashSuccess): ?>
<div id="flash-toast" class="fixed top-20 left-1/2 -translate-x-1/2 z-[9998] flex items-center gap-3 bg-coco-green shadow-2xl px-6 py-3 rounded-2xl font-semibold text-white text-sm transition-all duration-500">
    <i class="fas fa-check-circle text-coco-sage"></i><?= esc($flashSuccess) ?>
    <button onclick="this.parentElement.remove()" class="opacity-60 hover:opacity-100 ml-2"><i class="fas fa-times text-xs"></i></button>
</div>
<?php elseif ($flashError): ?>
<div id="flash-toast" class="fixed top-20 left-1/2 -translate-x-1/2 z-[9998] flex items-center gap-3 bg-red-500 shadow-2xl px-6 py-3 rounded-2xl font-semibold text-white text-sm">
    <i class="fas fa-exclamation-circle"></i><?= esc($flashError) ?>
    <button onclick="this.parentElement.remove()" class="opacity-60 hover:opacity-100 ml-2"><i class="fas fa-times text-xs"></i></button>
</div>
<?php endif; ?>

<!-- Hero banner -->
<section class="relative bg-gradient-to-br from-coco-cream via-coco-sand to-coco-cream pt-28 pb-14 overflow-hidden">
    <div class="absolute top-0 right-0 bg-coco-orange/8 rounded-full w-72 h-72 -translate-y-1/2 translate-x-1/3 pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 bg-coco-green/8 rounded-full w-48 h-48 -translate-x-1/3 translate-y-1/2 pointer-events-none"></div>
    <div class="relative z-10 mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
        <nav class="flex items-center gap-2 mb-6 text-coco-mid text-xs">
            <a href="<?= site_url('/') ?>" class="hover:text-coco-orange transition-colors">Home</a>
            <i class="fas fa-chevron-right text-[9px] text-coco-tan"></i>
            <span class="font-semibold text-coco-orange">Products</span>
        </nav>
        <div class="flex lg:flex-row flex-col lg:justify-between lg:items-end gap-6">
            <div>
                <p class="mb-2 font-bold text-coco-orange text-xs uppercase tracking-[0.3em]">Full Catalog</p>
                <h1 class="font-display font-black text-coco-brown text-5xl sm:text-6xl leading-tight">All Products</h1>
                <p class="mt-3 max-w-lg font-light text-coco-mid text-base">Browse our full range of premium coconut coir products.</p>
            </div>
            <div class="relative w-full lg:w-80">
                <input id="search-input" type="text" placeholder="Search products..."
                    class="bg-white px-5 py-3 pr-12 border-2 border-coco-sand rounded-full w-full text-coco-brown text-sm transition-all search-input placeholder-coco-tan/60">
                <i class="fas fa-search absolute right-4 top-1/2 -translate-y-1/2 text-coco-tan text-sm pointer-events-none"></i>
            </div>
        </div>
    </div>
</section>

<!-- Filter + Grid -->
<section class="bg-coco-cream py-10">
    <div class="mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">

        <!-- Filter bar -->
        <div class="flex sm:flex-row flex-col sm:justify-between sm:items-center gap-4 mb-8">
            <div class="flex flex-wrap gap-2" id="filter-pills">
                <?php
                $filterCats = ['All', 'Home & Living', 'Gardening', 'Construction', 'Craft & Utility', 'Agriculture'];
                foreach ($filterCats as $i => $cat):
                ?>
                <button class="filter-pill <?= $i===0?'active':'' ?> font-body text-xs font-semibold px-4 py-2 rounded-full border-2 border-coco-sand text-coco-dark tracking-wide"
                    data-category="<?= $cat ?>"><?= $cat ?></button>
                <?php endforeach; ?>
            </div>
            <div class="flex flex-shrink-0 items-center gap-3">
                <span class="text-coco-mid text-xs whitespace-nowrap">
                    Showing <strong class="text-coco-brown" id="count-num"><?= count($products) ?></strong> products
                </span>
                <div class="relative">
                    <select id="sort-select" class="py-2.5 pr-8 pl-4 border-2 border-coco-sand focus:border-coco-orange rounded-full focus:outline-none font-semibold text-coco-dark text-xs transition-colors fiber-bg">
                        <option value="default">Sort: Featured</option>
                        <option value="price-asc">Price: Low to High</option>
                        <option value="price-desc">Price: High to Low</option>
                        <option value="name-asc">Name: A–Z</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-coco-tan pointer-events-none"></i>
                </div>
            </div>
        </div>

        <!-- Product grid -->
        <div id="product-grid" class="gap-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <?php if (empty($products)): ?>
            <div class="col-span-4 py-24 text-center">
                <div class="text-5xl mb-4 text-gray-300"><i class="fas fa-box-open"></i></div>
                <p class="text-coco-mid font-semibold">No products available yet.</p>
            </div>
            <?php endif; ?>

            <?php foreach ($products as $idx => $p):
                $catDisplay = $categoryLabels[$p['category']] ?? ucfirst(str_replace('_', ' ', $p['category'] ?? ''));
                $badge      = $p['badge'] ?? '';
                // Auto-assign badge if DB has none
                if (empty($badge)) {
                    if (isset($p['stock']) && $p['stock'] <= 5 && $p['stock'] > 0) {
                        $badge = 'Sale';
                    } elseif ($p['id'] % 4 === 0) {
                        $badge = 'Best Seller';
                    } elseif ($p['id'] % 4 === 1) {
                        $badge = 'New';
                    } elseif ($p['id'] % 4 === 2) {
                        $badge = 'Trending';
                    }
                }
                $delay = ($idx % 4) * 80;
                $desc  = $p['description'] ?? $p['desc'] ?? '';
            ?>
            <div class="flex flex-col bg-white shadow-sm border border-coco-sand/60 rounded-3xl overflow-hidden product-card fade-card"
                data-category="<?= esc($catDisplay) ?>"
                data-price="<?= $p['price'] ?>"
                data-name="<?= strtolower(esc($p['name'])) ?>"
                style="animation-delay:<?= $delay ?>ms">

                <!-- Image -->
                <div class="relative bg-gradient-to-br from-coco-sand/60 to-coco-cream aspect-[4/3] overflow-hidden">
                    <a class="product-image-link" href="<?= site_url('products/' . (int) $p['id']) ?>"><img src="/images/<?= esc($p['image'] ?? 'coco.png') ?>" alt="<?= esc($p['name']) ?>" class="w-full h-full object-cover card-img"></a>
                    <?php if (!empty($badge)): ?>
                    <span class="absolute top-3 left-3 <?= $badgeClass[$badge] ?? '' ?> text-[10px] font-black tracking-widest uppercase rounded-full px-3 py-1">
                        <?= esc($badge) ?>
                    </span>
                    <?php endif; ?>
                    <button class="wishlist-btn absolute top-3 right-3 flex justify-center items-center bg-white/90 hover:bg-coco-orange shadow-sm backdrop-blur-sm rounded-full w-8 h-8 text-coco-tan hover:text-white transition-all duration-200">
                        <i class="fas fa-heart text-xs"></i>
                    </button>
                </div>

                <!-- Info -->
                <div class="flex flex-col flex-1 p-5">
                    <p class="mb-1 font-bold text-[10px] text-coco-green uppercase tracking-widest"><?= esc($catDisplay) ?></p>
                    <h3 class="mb-1 font-display font-black text-coco-brown text-lg leading-snug"><a href="<?= site_url('products/' . (int) $p['id']) ?>"><?= esc($p['name']) ?></a></h3>
                    <p class="flex-1 mb-4 text-coco-mid text-xs leading-relaxed"><?= esc(substr($desc, 0, 80)) ?><?= strlen($desc) > 80 ? '...' : '' ?></p>

                    <?php if (isset($p['stock']) && $p['stock'] <= 5 && $p['stock'] > 0): ?>
                    <p class="text-yellow-600 text-[10px] font-bold mb-2"><i class="fas fa-exclamation-triangle mr-1"></i>Only <?= $p['stock'] ?> left!</p>
                    <?php elseif (isset($p['stock']) && $p['stock'] == 0): ?>
                    <p class="text-red-500 text-[10px] font-bold mb-2"><i class="fas fa-times-circle mr-1"></i>Out of Stock</p>
                    <?php endif; ?>

                    <div class="flex justify-between items-center mt-auto">
                        <span class="font-display font-black text-coco-orange text-xl">₱<?= number_format($p['price']) ?></span>
                        <div class="flex gap-2">
                            <!-- Eye: opens modal -->
                            <button onclick="openProductModal(<?= (int)$p['id'] ?>)"
                                class="flex justify-center items-center border-2 border-coco-sand hover:border-coco-orange rounded-full w-9 h-9 text-coco-mid hover:text-coco-orange text-xs transition-all"
                                title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <!-- Add to cart -->
                            <button class="add-to-cart bg-coco-brown hover:bg-coco-orange px-4 py-2 rounded-full font-bold text-coco-cream text-xs whitespace-nowrap transition-colors duration-200"
                                data-id="<?= $p['id'] ?>"
                                data-name="<?= esc($p['name']) ?>"
                                data-price="<?= $p['price'] ?>"
                                data-image="<?= esc($p['image'] ?? 'default.png') ?>"
                                <?= (isset($p['stock']) && $p['stock'] == 0) ? 'disabled style="opacity:0.5;cursor:not-allowed"' : '' ?>>
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Empty state -->
        <div id="empty-state" class="hidden py-24 text-center">
            <div class="mb-4 text-5xl text-gray-300"><i class="fas fa-search"></i></div>
            <h3 class="mb-2 font-display font-black text-coco-brown text-2xl">No products found</h3>
            <p class="text-coco-mid text-sm">Try a different category or search term.</p>
            <button onclick="resetFilters()" class="bg-coco-orange hover:bg-coco-dark mt-6 px-8 py-3 rounded-full font-bold text-white text-sm transition-colors">
                Clear Filters
            </button>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center items-center gap-2 mt-14" id="pagination">
            <button class="flex justify-center items-center border-2 border-coco-sand rounded-full w-10 h-10 font-bold text-coco-mid text-sm page-btn">
                <i class="fas fa-chevron-left text-xs"></i>
            </button>
            <button class="flex justify-center items-center border-2 rounded-full w-10 h-10 font-bold text-sm page-btn active">1</button>
            <button class="flex justify-center items-center border-2 border-coco-sand rounded-full w-10 h-10 font-bold text-coco-mid text-sm page-btn">2</button>
            <button class="flex justify-center items-center border-2 border-coco-sand rounded-full w-10 h-10 font-bold text-coco-mid text-sm page-btn">3</button>
            <button class="flex justify-center items-center border-2 border-coco-sand rounded-full w-10 h-10 font-bold text-coco-mid text-sm page-btn">
                <i class="fas fa-chevron-right text-xs"></i>
            </button>
        </div>
    </div>
</section>

<?= $this->include('components/footer') ?>

<!-- Toast -->
<div id="toast" class="fixed right-6 bottom-6 z-[9998] opacity-0 transition-all translate-y-24 duration-300 pointer-events-none">
    <div class="flex items-center gap-3 bg-coco-brown shadow-2xl px-6 py-3 rounded-2xl font-semibold text-coco-cream text-sm">
        <i class="fas fa-check-circle text-coco-leaf"></i>
        <span id="toast-msg">Added to cart!</span>
    </div>
</div>

<!-- ══ PRODUCT DETAIL MODAL ══ -->
<div id="product-modal" class="modal-overlay" onclick="if(event.target===this)closeProductModal()">
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

        <!-- Close button -->
        <button onclick="closeProductModal()"
            class="absolute top-4 right-4 z-20 w-9 h-9 rounded-full bg-white shadow-md flex items-center justify-center hover:bg-gray-100 transition-colors">
            <i class="fas fa-times text-coco-mid text-xs"></i>
        </button>

        <div class="grid grid-cols-1 sm:grid-cols-2 min-h-[360px]">
            <!-- Image side -->
            <div class="modal-img-wrap relative bg-gradient-to-br from-coco-sand/60 to-coco-cream min-h-[240px] sm:min-h-[360px]">
                <img id="modal-img" src="" alt=""
                    class="w-full h-full object-cover absolute inset-0"
                    onerror="this.src='/images/default.png'">
                <div id="modal-badge-wrap" class="absolute top-4 left-4"></div>
            </div>

            <!-- Info side -->
            <div class="p-7 flex flex-col gap-4">
                <div>
                    <p id="modal-category" class="text-coco-green text-[10px] font-bold tracking-widest uppercase mb-1"></p>
                    <h2 id="modal-name" class="font-display font-black text-coco-brown text-2xl leading-tight"></h2>
                </div>

                <p id="modal-desc" class="text-coco-mid text-sm leading-relaxed"></p>
                <div class="quick-package"><strong>What you receive</strong><p id="modal-package"></p><strong>Specifications</strong><p id="modal-specifications"></p></div>
                <a id="modal-details-link" class="category-link" href="#">View full product details →</a>

                <div id="modal-stock" class="flex items-center gap-2 text-xs font-semibold"></div>

                <div class="border-t border-coco-sand/40 pt-4">
                    <div id="modal-price" class="font-display font-black text-3xl text-coco-orange mb-4"></div>
                    <div class="flex gap-3">
                        <button id="modal-atc"
                            class="flex-1 bg-coco-orange hover:bg-coco-dark text-white font-bold py-3 rounded-full transition-all hover:scale-105 shadow-lg text-sm flex items-center justify-center gap-2">
                            <i class="fas fa-shopping-bag text-xs"></i> Add to Cart
                        </button>
                        <button id="modal-wishlist"
                            class="w-11 h-11 border-2 border-coco-sand hover:border-coco-orange rounded-full flex items-center justify-center text-coco-tan hover:text-coco-orange transition-all">
                            <i class="fas fa-heart text-sm"></i>
                        </button>
                    </div>
                </div>

                <!-- Feature pills -->
                <div class="border-t border-coco-sand/40 pt-4 space-y-2">
                    <div class="flex items-center gap-2 text-xs text-coco-mid">
                        <i class="fas fa-leaf text-coco-green w-4 text-xs"></i>
                        100% biodegradable &amp; natural fiber
                    </div>
                    <div class="flex items-center gap-2 text-xs text-coco-mid">
                        <i class="fas fa-truck text-coco-orange w-4 text-xs"></i>
                        Nationwide delivery available
                    </div>
                    <div class="flex items-center gap-2 text-xs text-coco-mid">
                        <i class="fas fa-shield-alt text-coco-brown w-4 text-xs"></i>
                        Quality tested &amp; eco-certified
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Products JSON for modal -->
<script>
const productsData  = <?= json_encode(array_values($products), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const categoryLabels = <?= json_encode($categoryLabels) ?>;
const badgeClassMap  = {
    'New':'badge-new','Trending':'badge-trending',
    'Best Seller':'badge-bestseller','Sale':'badge-sale'
};

// ── Flash toast auto-dismiss ──
const flashToast = document.getElementById('flash-toast');
if (flashToast) setTimeout(() => {
    flashToast.style.opacity = '0';
    flashToast.style.transform = 'translateX(-50%) translateY(-10px)';
    setTimeout(() => flashToast.remove(), 500);
}, 3500);

// ── Nav scroll ──
// ── Filters ──
let activeCategory = 'All';
function getVisibleCards() {
    const q = document.getElementById('search-input').value.toLowerCase().trim();
    const cards = [...document.querySelectorAll('#product-grid .product-card')];
    let visible = 0;
    cards.forEach(card => {
        const catMatch  = activeCategory === 'All' || card.dataset.category === activeCategory;
        const nameMatch = !q || card.dataset.name.includes(q);
        const show = catMatch && nameMatch;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('count-num').textContent = visible;
    document.getElementById('empty-state').classList.toggle('hidden', visible > 0);
    document.getElementById('pagination').classList.toggle('hidden', visible === 0);
}

document.getElementById('filter-pills').addEventListener('click', e => {
    const btn = e.target.closest('.filter-pill');
    if (!btn) return;
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    activeCategory = btn.dataset.category;
    getVisibleCards();
});
const requestedCategory = new URLSearchParams(window.location.search).get('category');
const initialFilter = [...document.querySelectorAll('#filter-pills .filter-pill')].find(button => button.dataset.category === requestedCategory);
if (initialFilter) initialFilter.click();
document.getElementById('search-input').addEventListener('input', getVisibleCards);
document.getElementById('sort-select').addEventListener('change', function() {
    const grid  = document.getElementById('product-grid');
    const cards = [...grid.querySelectorAll('.product-card')];
    cards.sort((a,b) => {
        if (this.value === 'price-asc')  return +a.dataset.price - +b.dataset.price;
        if (this.value === 'price-desc') return +b.dataset.price - +a.dataset.price;
        if (this.value === 'name-asc')   return a.dataset.name.localeCompare(b.dataset.name);
        return 0;
    });
    cards.forEach(c => grid.appendChild(c));
});
function resetFilters() {
    activeCategory = 'All';
    document.getElementById('search-input').value = '';
    document.querySelectorAll('.filter-pill').forEach((p,i) => p.classList.toggle('active', i===0));
    getVisibleCards();
}

// ── Toast ──
function showToast(msg) {
    const toast = document.getElementById('toast');
    document.getElementById('toast-msg').textContent = msg;
    toast.classList.remove('translate-y-24','opacity-0');
    toast.classList.add('translate-y-0','opacity-100');
    setTimeout(() => {
        toast.classList.add('translate-y-24','opacity-0');
        toast.classList.remove('translate-y-0','opacity-100');
    }, 2800);
}

// ── AJAX add to cart ──
async function doAddToCart(id, name, price, image, btn) {
    const origHTML = btn.innerHTML;
    btn.disabled   = true;
    btn.innerHTML  = '<i class="fas fa-spinner fa-spin text-xs"></i> Adding...';

    const csrfName  = document.getElementById('csrf-name').value;
    const csrfValue = document.getElementById('csrf-value').value;
    const fd = new FormData();
    fd.append('id', id); fd.append('name', name);
    fd.append('price', price); fd.append('image', image);
    fd.append(csrfName, csrfValue);

    try {
        const res    = await fetch('<?= site_url('cart/add') ?>', { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} });
        const result = await res.json();
        if (result.success) {
            btn.innerHTML = '<i class="fas fa-check text-xs"></i> Added!';
            btn.classList.replace('bg-coco-orange','bg-coco-green');
            btn.classList.replace('bg-coco-brown','bg-coco-green');
            document.getElementById('csrf-value').value = result.csrf_hash;
            window.dispatchEvent(new CustomEvent('cartUpdated', { detail:{ count: result.totalItems } }));
            showToast('"' + name + '" added to cart!');
            setTimeout(() => { btn.innerHTML = origHTML; btn.classList.replace('bg-coco-green','bg-coco-brown'); btn.disabled = false; }, 2000);
        } else {
            btn.innerHTML = origHTML; btn.disabled = false;
            showToast(result.message || 'Could not add to cart.');
        }
    } catch(e) { btn.innerHTML = origHTML; btn.disabled = false; showToast('Something went wrong.'); }
}

// Card buttons
document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', function() {
        doAddToCart(this.dataset.id, this.dataset.name, this.dataset.price, this.dataset.image, this);
    });
});

// Wishlist
document.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const icon = this.querySelector('i');
        const wished = icon.classList.contains('text-white');
        this.classList.toggle('bg-coco-orange', !wished);
        this.classList.toggle('bg-white/90', wished);
        icon.classList.toggle('text-white', !wished);
        icon.classList.toggle('text-coco-tan', wished);
        showToast(wished ? 'Removed from wishlist' : 'Added to wishlist!');
    });
});

// Pagination
document.querySelectorAll('.page-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.page-btn').forEach(b => b.classList.remove('active'));
        if (!this.querySelector('i')) this.classList.add('active');
        window.scrollTo({ top:0, behavior:'smooth' });
    });
});

// ══ PRODUCT DETAIL MODAL ══
function openProductModal(id) {
    const p = productsData.find(x => x.id == id);
    if (!p) return;

    const catDisplay = categoryLabels[p.category] || p.category || '';
    const badge      = p.badge || '';
    const stock      = Number(p.stock ?? 0);
    const desc       = p.description || p.desc || '';

    document.getElementById('modal-img').src          = '/images/' + (p.image || 'default.png');
    document.getElementById('modal-img').alt          = p.name;
    document.getElementById('modal-category').textContent = catDisplay;
    document.getElementById('modal-name').textContent     = p.name;
    document.getElementById('modal-desc').textContent     = desc;
    document.getElementById('modal-package').textContent = p.package_contents || 'Package quantity not specified. Contact us to confirm before ordering.';
    document.getElementById('modal-specifications').textContent = p.specifications || 'Measurements and weight not specified.';
    document.getElementById('modal-details-link').href = <?= json_encode(site_url('products')) ?> + '/' + Number(p.id);
    document.getElementById('modal-price').textContent    = '₱' + Number(p.price).toLocaleString('en-PH');

    // Badge
    const badgeWrap = document.getElementById('modal-badge-wrap');
    badgeWrap.innerHTML = badge
        ? `<span class="${badgeClassMap[badge]||''} text-[10px] font-black tracking-widest uppercase rounded-full px-3 py-1">${badge}</span>`
        : '';

    // Stock
    const stockEl = document.getElementById('modal-stock');
    if (stock === 0)       stockEl.innerHTML = '<i class="fas fa-times-circle text-red-500"></i><span class="text-red-500">Out of Stock</span>';
    else if (stock <= 5)   stockEl.innerHTML = `<i class="fas fa-exclamation-triangle text-yellow-500"></i><span class="text-yellow-600">Only ${stock} left!</span>`;
    else                   stockEl.innerHTML = '<i class="fas fa-check-circle text-coco-green"></i><span class="text-coco-green">In Stock</span>';

    // ATC button
    const atcBtn = document.getElementById('modal-atc');
    atcBtn.disabled = stock === 0;
    atcBtn.className = atcBtn.className
        .replace('bg-coco-orange hover:bg-coco-dark','')
        .replace('bg-gray-300 cursor-not-allowed','')
        .trim();
    if (stock === 0) {
        atcBtn.classList.add('bg-gray-300','cursor-not-allowed');
        atcBtn.innerHTML = '<i class="fas fa-ban text-xs"></i> Out of Stock';
    } else {
        atcBtn.classList.add('bg-coco-orange','hover:bg-coco-dark');
        atcBtn.innerHTML = '<i class="fas fa-shopping-bag text-xs"></i> Add to Cart';
        atcBtn.onclick   = () => doAddToCart(p.id, p.name, p.price, p.image||'', atcBtn);
    }

    const modal = document.getElementById('product-modal');
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeProductModal() {
    document.getElementById('product-modal').classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeProductModal(); });
</script>
</body>
</html>