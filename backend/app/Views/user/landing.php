<?php
?>
<!DOCTYPE html>
<html lang="en">

<?= $this->include('components/head') ?>

<body class="site-store home-store bg-coco-cream font-body text-coco-brown">


    <?= $this->include('components/header') ?>


    <!-- ══════════════════════════ HERO ══════════════════════════ -->
    <section id="home" class="collection-hero">
        <div class="collection-hero-inner">
            <div class="collection-copy">
                <p class="store-eyebrow">Natural by nature</p>
                <h1>Everyday living.<br>Thoughtfully crafted.</h1>
                <p>Discover Philippine-made coconut coir essentials for your home, your garden, and a greener tomorrow.</p>
                <div class="store-actions">
                    <a class="store-button" href="<?= site_url('products') ?>">Shop collection</a>
                    <a class="store-button store-button-outline" href="<?= site_url('about') ?>">Our story</a>
                </div>
            </div>
            <div class="collection-visual"><img src="<?= base_url('images/coco.png') ?>" alt="Natural coconut and coir fiber"></div>
        </div>
    </section>
    <nav class="collection-categories" aria-label="Shop collections">
        <?php foreach ([['Home & Living','coirdoormat.png','Home & Living'],['Gardening','growbag.png','Gardening'],['Construction','geotextitle.png','Construction'],['Natural Essentials','coirrope.jpg','']] as [$label,$image,$category]): ?>
        <a class="collection-category" href="<?= site_url('products') . ($category ? '?category=' . rawurlencode($category) : '') ?>">
            <img src="<?= base_url('images/' . $image) ?>" alt="<?= esc($label) ?>" loading="lazy">
            <div><span class="store-eyebrow"><?= esc($label) ?></span><span class="category-link">Shop now</span></div>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- ══════════════════════════ ABOUT ══════════════════════════ -->
<!-- ══════════════════════════ FEATURED PRODUCTS ══════════════════════════ -->
<section id="products" class="bg-coco-cream py-24">
    <div class="mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
        <div class="space-y-4 mb-16 text-center">
            <p class="font-bold text-coco-orange text-xs uppercase tracking-[0.3em]">Our Range</p>
            <h2 class="font-display font-black text-coco-brown text-4xl sm:text-5xl">Latest Collection</h2>
            <p class="mx-auto max-w-2xl font-light text-coco-mid text-lg">Eco-friendly coir products handcrafted for gardening, construction, and sustainable living.</p>
        </div>
 
        <!-- CSRF token for cart AJAX -->
        <input type="hidden" id="landing-csrf-name"  value="<?= csrf_token() ?>">
        <input type="hidden" id="landing-csrf-value" value="<?= csrf_hash() ?>">
 
        <div class="gap-7 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach (($products ?? []) as $featured): ?>
            <article class="product-card flex flex-col">
                <div class="relative"><a class="product-image-link" href="<?= site_url('products/' . (int) $featured['id']) ?>"><img class="w-full h-full" src="<?= base_url('images/' . ($featured['image'] ?: 'coco.png')) ?>" alt="<?= esc($featured['name']) ?>" loading="lazy"></a></div>
                <div class="p-5"><p class="store-eyebrow"><?= esc(ucwords(str_replace('_', ' ', $featured['category']))) ?></p><h3><a href="<?= site_url('products/' . (int) $featured['id']) ?>"><?= esc($featured['name']) ?></a></h3><p class="text-coco-mid text-xs my-3"><?= esc(($featured['package_contents'] ?? '') ?: 'View product for package details') ?></p>
                    <div class="flex justify-between items-center gap-2"><span>₱<?= number_format((float) $featured['price'], 2) ?></span><button class="landing-atc store-button" data-id="<?= (int) $featured['id'] ?>" data-name="<?= esc($featured['name']) ?>" data-price="<?= esc($featured['price']) ?>" data-image="<?= esc($featured['image'] ?? '') ?>" <?= (int) $featured['stock'] <= 0 ? 'disabled' : '' ?>><?= (int) $featured['stock'] > 0 ? 'Add to cart' : 'Out of stock' ?></button></div>
                </div>
            </article>
            <?php endforeach; ?>
            <?php if (empty($products)): ?><p>Our collection is being prepared. Check back soon.</p><?php endif; ?>
        </div>
        <div class="text-center mt-10"><a class="store-button store-button-outline" href="<?= site_url('magazine') ?>">Explore the Coir Journal →</a></div>
    </div>
</section>
 


    <!-- ══════════════════════════ WHY COCOIR ══════════════════════════ -->
    <section id="about" class="bg-coco-sand py-24">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
            <div class="items-center gap-16 grid grid-cols-1 lg:grid-cols-2">

                <!-- Story -->
                <div class="space-y-6">
                    <p class="font-bold text-coco-orange text-xs uppercase tracking-[0.3em]">Our Story</p>
                    <h2 class="font-display font-black text-coco-brown text-4xl sm:text-5xl leading-tight">
                        The Fiber That<br><em>Changes Everything</em>
                    </h2>
                    <p class="font-light text-coco-dark text-lg leading-relaxed">
                        Coconut coir — the tough fibrous material between the hard inner shell and outer husk of the coconut — has long been discarded as waste. COCOIR is changing that story.
                    </p>
                    <p class="text-coco-mid text-sm leading-relaxed">
                        In the Philippines, where coconuts are abundant and coir utilization is still in its infancy, we lead the charge: turning agricultural byproduct into durable mats, grow bags, erosion control nets, and construction liners. Every product supports local farmers, reduces waste, and brings world-class sustainable materials to your doorstep.
                    </p>
                    <div class="flex gap-6 pt-2">
                        <div class="flex items-start gap-3">
                            <div class="flex flex-shrink-0 justify-center items-center bg-coco-green/15 rounded-full w-10 h-10">
                                <i class="text-coco-green text-sm fas fa-seedling"></i>
                            </div>
                            <div>
                                <div class="font-bold text-coco-brown text-sm">Zero Waste</div>
                                <div class="text-coco-mid text-xs">Every husk has purpose</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="flex flex-shrink-0 justify-center items-center bg-coco-orange/15 rounded-full w-10 h-10">
                                <i class="text-coco-orange text-sm fas fa-hands-helping"></i>
                            </div>
                            <div>
                                <div class="font-bold text-coco-brown text-sm">Farmer First</div>
                                <div class="text-coco-mid text-xs">Fair trade, local sourcing</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats grid -->
                <div class="gap-5 grid grid-cols-2">
                    <div class="shadow-sm p-7 border border-coco-tan/20 rounded-3xl fiber-bg">
                        <div class="mb-1 font-display font-black text-coco-orange text-5xl">15+</div>
                        <div class="mb-1 font-bold text-coco-brown text-sm">Product Categories</div>
                        <div class="text-coco-mid text-xs leading-relaxed">Gardening, construction, craft, and more</div>
                    </div>
                    <div class="bg-coco-green shadow-sm p-7 rounded-3xl">
                        <div class="mb-1 font-display font-black text-coco-cream text-5xl">5+</div>
                        <div class="mb-1 font-bold text-coco-cream text-sm">Years Expertise</div>
                        <div class="text-coco-sage text-xs leading-relaxed">Pioneering coir craft in the Philippines</div>
                    </div>
                    <div class="bg-coco-brown shadow-sm p-7 rounded-3xl">
                        <div class="mb-1 font-display font-black text-coco-amber text-5xl">100%</div>
                        <div class="mb-1 font-bold text-coco-cream text-sm">Biodegradable</div>
                        <div class="text-coco-tan text-xs leading-relaxed">No synthetics, no compromise</div>
                    </div>
                    <div class="flex flex-col justify-center items-center shadow-sm p-7 border border-coco-tan/20 rounded-3xl text-center fiber-bg">
                        <div class="mb-2 text-5xl">🌴</div>
                        <div class="font-bold text-coco-brown text-sm">PH Sourced</div>
                        <div class="text-coco-mid text-xs">Proudly Philippine farmers</div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <section id="why-us" class="bg-coco-sand py-24">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
            <div class="space-y-4 mb-16 text-center">
                <p class="font-bold text-coco-orange text-xs uppercase tracking-[0.3em]">Why COCOIR</p>
                <h2 class="font-display font-black text-coco-brown text-4xl sm:text-5xl">Rooted in Nature,<br><em>Built for You</em></h2>
                <p class="mx-auto max-w-2xl font-light text-coco-mid text-lg">Experience eco-excellence with our commitment to quality, sustainability, and Filipino craftsmanship.</p>
            </div>

            <div class="gap-7 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                <?php
                $features = [
                    ['icon' => 'leaf',               'color' => 'coco-green',  'title' => 'Fully Biodegradable',     'desc' => 'Every COCOIR product breaks down naturally — no synthetic residue in your soil, water, or air.'],
                    ['icon' => 'truck',              'color' => 'coco-orange', 'title' => 'Nationwide Delivery',     'desc' => 'Fast, secure shipping across Metro Manila and all major Philippine provinces.'],
                    ['icon' => 'shield-alt',         'color' => 'coco-brown',  'title' => 'Quality Assured',         'desc' => 'Each batch is tested for tensile strength, moisture resistance, and durability before shipping.'],
                    ['icon' => 'hand-holding-heart', 'color' => 'coco-orange', 'title' => 'Farmer-Sourced',          'desc' => 'We source directly from Philippine coconut farmers, ensuring fair prices and sustainable livelihoods.'],
                    ['icon' => 'headset',            'color' => 'coco-green',  'title' => 'Expert Support',          'desc' => 'Free consultations for construction and gardening projects. Our team guides you every step.'],
                    ['icon' => 'recycle',            'color' => 'coco-brown',  'title' => 'Circular Economy',        'desc' => 'From husk to product to compost — our supply chain creates zero waste at every stage.'],
                ];
                foreach ($features as $f): ?>
                    <div class="group hover:shadow-xl p-8 border border-coco-tan/20 rounded-3xl transition-all hover:-translate-y-1 duration-300 fiber-bg">
                        <div class="w-14 h-14 bg-<?= $f['color'] ?>/10 rounded-2xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                            <i class="fas fa-<?= $f['icon'] ?> text-<?= $f['color'] ?> text-xl"></i>
                        </div>
                        <h3 class="mb-2 font-display font-bold text-coco-brown text-xl"><?= $f['title'] ?></h3>
                        <p class="text-coco-mid text-sm leading-relaxed"><?= $f['desc'] ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>


    <!-- ══════════════════════════ CTA / NEWSLETTER ══════════════════════════ -->
    <section id="contact" class="relative bg-coco-brown py-24 overflow-hidden">
        <!-- Decorative -->
        <div class="absolute inset-0 pointer-events-none" style="background:repeating-linear-gradient(90deg,transparent,transparent 60px,rgba(255,255,255,0.015) 60px,rgba(255,255,255,0.015) 61px);"></div>

        <div class="z-10 relative space-y-8 mx-auto px-4 max-w-4xl text-center">
            <p class="font-bold text-coco-amber text-xs uppercase tracking-[0.3em]">Stay Connected</p>
            <h2 class="font-display font-black text-coco-cream text-4xl sm:text-5xl leading-tight">
                Join the Coir Revolution.<br>
                <span class="text-coco-amber italic">Get 10% Off Your First Order.</span>
            </h2>
            <p class="mx-auto max-w-xl font-light text-coco-tan text-lg">Subscribe for exclusive deals, new product launches, and tips on sustainable living with coconut coir.</p>

            <form class="flex sm:flex-row flex-col gap-3 mx-auto max-w-lg" onsubmit="return false;">
                <input type="ilil" placeholder="cocoirph@gmail.com"
                    class="flex-1 bg-white/10 px-6 py-4 border border-coco-tan/30 focus:border-coco-amber rounded-full focus:outline-none text-coco-cream text-sm transition-colors placeholder-coco-tan/50">
                <button type="submit" class="bg-coco-orange hover:bg-coco-amber shadow-lg px-8 py-4 rounded-full font-bold text-white hover:text-coco-brown whitespace-nowrap transition-all duration-300">
                    Subscribe
                </button>
            </form>
            <p class="text-coco-tan/40 text-xs">No spam. Unsubscribe anytime. 🥥</p>

            <!-- Contact strip -->
            <div class="flex flex-wrap justify-center gap-8 pt-8 border-white/10 border-t">
                <a href="mailto:cocoirph@gmail.com" class="flex items-center gap-2 text-coco-tan hover:text-coco-amber text-sm transition-colors">
                    <i class="text-coco-orange fas fa-envelope"></i> cocoirph@gmail.com
                </a>
                <a href="tel:+639XXXXXXXXX" class="flex items-center gap-2 text-coco-tan hover:text-coco-amber text-sm transition-colors">
                    <i class="text-coco-orange fas fa-phone"></i> +63 945 872 2774
                </a>
                <span class="flex items-center gap-2 text-coco-tan text-sm">
                    <i class="text-coco-orange fas fa-map-marker-alt"></i> Philippines
                </span>
            </div>
        </div>
    </section>


    <!-- ══════════════════════════ FOOTER ══════════════════════════ -->
    <?= $this->include('components/footer') ?>


    <script src="<?= base_url('js/main.js') ?>"></script>
<script>
(function() {
    // ── Add to Cart ──
    document.querySelectorAll('.landing-atc').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id    = this.dataset.id;
            const name  = this.dataset.name;
            const price = this.dataset.price;
            const image = this.dataset.image;
 
            // Show loading state
            const original = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin text-[10px]"></i> Adding...';
            this.disabled  = true;
 
            const csrfName  = document.getElementById('landing-csrf-name').value;
            const csrfValue = document.getElementById('landing-csrf-value').value;
 
            const fd = new FormData();
            fd.append('id',    id);
            fd.append('name',  name);
            fd.append('price', price);
            fd.append('image', image);
            fd.append(csrfName, csrfValue);
 
            try {
                const res    = await fetch('<?= site_url('cart/add') ?>', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await res.json();
 
                if (result.success) {
                    // Update CSRF token
                    document.getElementById('landing-csrf-value').value = result.csrf_hash;
 
                    // Update cart badge in header
                    window.dispatchEvent(new CustomEvent('cartUpdated', {
                        detail: { count: result.totalItems }
                    }));
 
                    // Show success state on button
                    this.innerHTML = '<i class="fas fa-check text-[10px]"></i> Added!';
                    this.classList.remove('bg-coco-brown');
                    this.classList.add('bg-coco-green');
 
                    showLandingToast('"' + name + '" added to cart!');
 
                    // Reset button after 2s
                    setTimeout(() => {
                        this.innerHTML = original;
                        this.classList.add('bg-coco-brown');
                        this.classList.remove('bg-coco-green');
                        this.disabled = false;
                    }, 2000);
                } else {
                    this.innerHTML = original;
                    this.disabled  = false;
                    showLandingToast(result.message || 'Could not add to cart.', true);
                }
            } catch (e) {
                this.innerHTML = original;
                this.disabled  = false;
                showLandingToast('Error — please try again.', true);
            }
        });
    });
 
    // ── Toast ──
    function showLandingToast(msg, isError = false) {
        const toast = document.getElementById('landing-toast');
        const inner = toast.querySelector('div');
        document.getElementById('landing-toast-msg').textContent = msg;
        inner.className = inner.className.replace(/bg-\S+/, isError ? 'bg-red-600' : 'bg-coco-brown');
        toast.classList.remove('translate-y-24', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
        setTimeout(() => {
            toast.classList.add('translate-y-24', 'opacity-0');
            toast.classList.remove('translate-y-0', 'opacity-100');
        }, 3000);
    }
})();
</script>
</body>

</html>