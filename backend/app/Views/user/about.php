<?php
/**
 * View: user/about.php
 * Controller: Users::about()
 * Route: GET /about
 */
?>
<!DOCTYPE html>
<html lang="en">
<?= $this->include('components/head', ['title' => 'About Us — COCOIR Coconut Coir Co.']) ?>

<style>


    /* Scroll reveal */
    .reveal        { opacity:0; transform:translateY(28px); transition:opacity 0.7s ease, transform 0.7s ease; }
    .reveal.visible{ opacity:1; transform:translateY(0); }
    .reveal-left        { opacity:0; transform:translateX(-28px); transition:opacity 0.7s ease, transform 0.7s ease; }
    .reveal-left.visible{ opacity:1; transform:translateX(0); }
    .reveal-right        { opacity:0; transform:translateX(28px); transition:opacity 0.7s ease, transform 0.7s ease; }
    .reveal-right.visible{ opacity:1; transform:translateX(0); }

    /* Mission section image */
    .mission-img {
        position: relative;
        overflow: hidden;
        border-radius: 24px;
    }
    .mission-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s ease;
    }
    .mission-img:hover img { transform: scale(1.04); }

    .member-photo { transition: transform .3s ease; }

    /* Timeline */
    .tl-line { position:absolute; left:19px; top:44px; bottom:-24px; width:2px;
        background: linear-gradient(to bottom, #E87722, #EDE0CC); }

    /* Marquee strip */
    @keyframes marquee { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }
    .marquee-track { display:flex; animation:marquee 20s linear infinite; width:max-content; }
    .marquee-track:hover { animation-play-state:paused; }

    /* Stat counter */
    .stat-num { font-variant-numeric: tabular-nums; }
</style>

<body class="site-store flex flex-col bg-coco-cream min-h-screen font-body text-coco-brown">
<?= $this->include('components/header') ?>


<!-- ══════════════════════ FULL-WIDTH BANNER HERO ══════════════════════ -->
<section class="page-banner">
    <div class="page-banner-inner">
        <div><p class="store-eyebrow">Our story · Philippine made</p><h1>Rooted in nature.<br>Made with purpose.</h1><p>Meet the people turning coconut husks into thoughtful essentials for everyday living.</p><a href="#team" class="store-button store-button-outline">Meet our team</a></div>
        <img src="<?= base_url('images/crafting.png') ?>" alt="Crafting with natural coconut coir">
    </div>
</section>


<!-- ══════════════════════ MARQUEE STRIP ══════════════════════ -->
<div class="bg-coco-orange py-3 overflow-hidden">
    <div class="marquee-track">
        <?php
        $tags = ['100% Biodegradable','Philippine-Made','Zero Waste','Farmer-Sourced','Eco-Certified','Sustainable','Natural Fiber','Coconut Coir'];
        // repeat twice for seamless loop
        for ($r=0; $r<2; $r++) foreach ($tags as $tag):
        ?>
        <span class="flex items-center gap-3 px-6 text-white text-xs font-black tracking-[0.2em] uppercase whitespace-nowrap">
            <span class="w-1.5 h-1.5 bg-white/50 rounded-full"></span>
            <?= $tag ?>
        </span>
        <?php endforeach; ?>
    </div>
</div>


<!-- ══════════════════════ MISSION SECTION ══════════════════════ -->
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

            <!-- Text -->
            <div class="space-y-6 reveal-left">
                <p class="text-coco-orange font-bold text-xs tracking-[0.3em] uppercase">Who We Are</p>
                <h2 class="font-display font-black text-coco-brown leading-tight"
                    style="font-size: clamp(2.2rem, 5vw, 3.5rem);">
                    Our Mission<br>Since 2019
                </h2>
                <p class="text-coco-dark text-lg leading-relaxed font-light">
                    COCOIR was born from a simple belief — the rough, discarded husk of the Philippine coconut is not waste. It is raw material for something extraordinary.
                </p>
                <p class="text-coco-mid text-sm leading-relaxed">
                    We partner directly with Filipino coconut farmers to transform this abundant byproduct into premium, eco-certified products: geotextile mats for civil engineering, grow bags for urban farming, coir ropes for marine use, and natural home goods. Every product is fully biodegradable, carbon-neutral in production, and created with the welfare of our farming communities at the center.
                </p>
                <p class="text-coco-mid text-sm leading-relaxed">
                    We believe that sustainability and quality are not a compromise — they are the same thing.
                </p>
                <a href="<?= site_url('products') ?>"
                   class="inline-flex items-center gap-2 bg-coco-brown text-coco-cream font-bold px-8 py-3.5 rounded-full hover:bg-coco-orange transition-all duration-300 shadow-md hover:scale-105 transform mt-2">
                    <i class="fas fa-leaf text-sm"></i> Shop Our Products
                </a>
            </div>

            <!-- Image -->
            <div class="reveal-right">
                <div class="mission-img h-[480px] shadow-2xl">
                    <!-- Put your mission image at /images/about-mission.jpg -->
                    <img src="/images/crafting.png" alt="COCOIR Mission"
                         onerror="this.parentElement.style.background='linear-gradient(135deg,#5C3317,#4A7C59)'; this.style.display='none';">
                </div>

                <!-- Floating label -->
                <div class="relative -mt-6 ml-6">
                    <div class="inline-flex items-center gap-3 bg-white rounded-2xl shadow-xl px-5 py-3 border border-coco-sand/60">
                        <div class="w-10 h-10 bg-coco-orange rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-leaf text-white text-sm"></i>
                        </div>
                        <div>
                            <div class="font-display font-black text-coco-brown text-sm">100% Eco-Certified</div>
                            <div class="text-coco-mid text-xs">Zero synthetic materials</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ══════════════════════ STATS BAND ══════════════════════ -->
<section class="py-16 bg-coco-brown">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
            <?php
            $stats = [
                ['num'=>'1,500+', 'label'=>'Happy Customers'],
                ['num'=>'15+',    'label'=>'Product Lines'],
                ['num'=>'5+',     'label'=>'Years Expertise'],
                ['num'=>'0',      'label'=>'Waste Generated'],
            ];
            foreach ($stats as $i => $s):
            ?>
            <div class="reveal" style="transition-delay:<?= $i*80 ?>ms">
                <div class="font-display font-black text-4xl sm:text-5xl text-coco-orange stat-num mb-1"><?= $s['num'] ?></div>
                <div class="text-coco-tan text-xs font-semibold tracking-widest uppercase"><?= $s['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<!-- ══════════════════════ MISSION / VISION / VALUES ══════════════════════ -->
<section class="py-24 bg-coco-cream">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 reveal">
            <p class="text-coco-orange font-bold text-xs tracking-[0.3em] uppercase mb-3">What Guides Us</p>
            <h2 class="font-display font-black text-4xl sm:text-5xl text-coco-brown">Mission · Vision · Values</h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-7">
            <!-- Mission -->
            <div class="value-card bg-coco-orange rounded-3xl p-9 text-white reveal" style="transition-delay:0ms">
                <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center mb-6">
                    <i class="fas fa-bullseye text-xl"></i>
                </div>
                <h3 class="font-display font-black text-2xl mb-4">Our Mission</h3>
                <p class="text-white/85 text-sm leading-relaxed">
                    To pioneer the coconut coir industry in the Philippines by transforming agricultural waste into world-class eco-products — empowering farmers, protecting the environment, and delivering quality without compromise.
                </p>
            </div>

            <!-- Vision -->
            <div class="value-card bg-coco-brown rounded-3xl p-9 text-white reveal" style="transition-delay:100ms">
                <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center mb-6">
                    <i class="fas fa-eye text-coco-amber text-xl"></i>
                </div>
                <h3 class="font-display font-black text-2xl mb-4 text-coco-amber">Our Vision</h3>
                <p class="text-coco-tan text-sm leading-relaxed">
                    A Philippines where every coconut husk finds purpose — where coir is recognized globally as the sustainable material of the future, and where Filipino craftsmanship leads the world in natural fiber innovation.
                </p>
            </div>

            <!-- Values -->
            <div class="value-card fiber-bg border border-coco-tan/20 rounded-3xl p-9 reveal" style="transition-delay:200ms">
                <div class="w-12 h-12 bg-coco-green/10 rounded-2xl flex items-center justify-center mb-6">
                    <i class="fas fa-heart text-coco-green text-xl"></i>
                </div>
                <h3 class="font-display font-black text-2xl text-coco-brown mb-4">Our Values</h3>
                <div class="space-y-2.5">
                    <?php foreach (['Sustainability','Integrity','Community','Innovation','Quality','Transparency'] as $v): ?>
                    <div class="flex items-center gap-3 text-sm text-coco-dark">
                        <span class="w-5 h-5 bg-coco-orange/10 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="w-1.5 h-1.5 bg-coco-orange rounded-full"></span>
                        </span>
                        <?= $v ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- ══════════════════════ JOURNEY TIMELINE ══════════════════════ -->
<section class="py-24 bg-coco-sand">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 reveal">
            <p class="text-coco-orange font-bold text-xs tracking-[0.3em] uppercase mb-3">How We Got Here</p>
            <h2 class="font-display font-black text-4xl sm:text-5xl text-coco-brown">Our Journey</h2>
        </div>

        <div class="space-y-0">
            <?php
            $milestones = [
                ['year'=>'2019','color'=>'coco-orange','title'=>'The Spark',           'desc'=>'Founded in a small workshop in Quezon City with one coir mat loom and a dream to reduce Philippine agricultural waste.'],
                ['year'=>'2020','color'=>'coco-green', 'title'=>'First Harvest',        'desc'=>'Partnered with our first community of coconut farmers in Quezon Province. Door mats and rope launched online.'],
                ['year'=>'2021','color'=>'coco-orange','title'=>'Going Green Official', 'desc'=>'Received eco-certification. Zero-waste production achieved across all product lines.'],
                ['year'=>'2022','color'=>'coco-brown', 'title'=>'Construction Entry',   'desc'=>'Expanded into geotextile mats and erosion control products for civil engineering projects nationwide.'],
                ['year'=>'2023','color'=>'coco-green', 'title'=>'1,000 Customers',      'desc'=>'Reached 1,000 customers. Launched nationwide delivery across all Philippine provinces.'],
                ['year'=>'2024+','color'=>'coco-orange','title'=>'Growing Worldwide',   'desc'=>'Exploring export to Japan, South Korea, and EU. Building the future of Filipino coir craftsmanship.'],
            ];
            foreach ($milestones as $i => $m):
            ?>
            <div class="relative flex gap-8 reveal" style="transition-delay:<?= $i*80 ?>ms; padding-bottom: <?= $i < count($milestones)-1 ? '40px' : '0' ?>;">
                <!-- Line -->
                <?php if ($i < count($milestones)-1): ?>
                <div class="tl-line"></div>
                <?php endif; ?>

                <!-- Dot -->
                <div class="flex-shrink-0 z-10">
                    <div class="w-10 h-10 bg-<?= $m['color'] ?> rounded-full flex items-center justify-center shadow-lg border-4 border-coco-sand">
                        <span class="w-2 h-2 bg-white rounded-full"></span>
                    </div>
                </div>

                <!-- Content -->
                <div class="flex-1 pb-2">
                    <span class="inline-block bg-<?= $m['color'] ?>/10 text-<?= $m['color'] ?> text-[10px] font-black tracking-widest px-3 py-1 rounded-full mb-2 uppercase">
                        <?= $m['year'] ?>
                    </span>
                    <h3 class="font-display font-bold text-xl text-coco-brown mb-1"><?= $m['title'] ?></h3>
                    <p class="text-coco-mid text-sm leading-relaxed"><?= $m['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════════════════ TEAM ══════════════════════ -->
<section id="team" class="py-24 bg-coco-cream">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-16 reveal">
            <p class="text-coco-orange font-bold text-xs tracking-[0.3em] uppercase mb-3">The People Behind the Husk</p>
            <h2 class="font-display font-black text-4xl sm:text-5xl text-coco-brown mb-4">Meet the Team</h2>
            <p class="text-coco-mid text-lg font-light max-w-lg mx-auto">
                Four passionate Filipinos on a mission to make coconut coir the sustainable material of the future.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php
            $team = [
                [
                    'name'    => 'Daniel Ling',
                    'role'    => 'Co-Founder & COO',
                    'photo'   => 'daniel pic.jpg',          
                    'initial' => 'M',
                    'color'   => 'coco-orange',
                    'bio'     => 'Operations expert with a background in supply chain management. Oversees production quality, logistics, and our nationwide delivery network.',
                    'social'  => ['linkedin'=>'#','facebook'=>'#','instagram'=>'#'],
                ],
                [
                    'name'    => 'Hilary Pagadora',
                    'role'    => 'Co-Founder & CEO',
                    'photo'   => 'ashey.jpg',         
                    'initial' => 'M',
                    'color'   => 'coco-green',
                    'bio'     => 'Passionate about sustainable agriculture and circular economies. Leads COCOIR\'s vision and farmer partnership programs across the Philippines.',
                    'social'  => ['linkedin'=>'#','facebook'=>'#','instagram'=>'#'],
                ],
                [
                    'name'    => 'Amilia Tañajura',
                    'role'    => 'Head of Product & Design',
                    'photo'   => 'amilia.png',        
                    'initial' => 'M',
                    'color'   => 'coco-brown',
                    'bio'     => 'Industrial designer who transforms raw coir fiber into beautiful, functional products. Leads R&D for new COCOIR product lines.',
                    'social'  => ['linkedin'=>'#','facebook'=>'#','instagram'=>'#'],
                ],
                [
                    'name' => 'Steven Ballesteros',
                    'role' => 'Logistics Coordinator',
                    'photo' => '',
                    'initial' => 'SB',
                    'color' => 'coco-green',
                    'bio' => 'Coordinates order fulfillment, shipping, and delivery schedules to help bring COCOIR products from our makers to your doorstep with care.',
                    'social' => [],
                ],
            ];
            foreach ($team as $i => $member):
            ?>
            <div class="team-card bg-white rounded-3xl overflow-hidden border border-coco-sand/60 shadow-sm reveal" style="transition-delay:<?= $i*120 ?>ms;">

                <!-- Photo -->
                <div class="relative h-72 overflow-hidden bg-coco-sand/30">
                    <?php if (!empty($member['photo'])): ?>
                        <img src="/images/<?= esc($member['photo']) ?>"
                             alt="<?= esc($member['name']) ?>"
                             class="member-photo w-full h-full object-cover object-top">
                    <?php else: ?>
                        <!-- Placeholder until you add real photos -->
                        <div class="w-full h-full flex flex-col items-center justify-center gap-3 bg-gradient-to-br from-<?= $member['color'] ?>/10 to-coco-sand">
                            <div class="w-28 h-28 rounded-full border-4 border-<?= $member['color'] ?>/30 bg-white flex items-center justify-center shadow-inner">
                                <span class="font-display font-black text-5xl text-<?= $member['color'] ?>">
                                    <?= $member['initial'] ?>
                                </span>
                            </div>
                            <span class="text-coco-mid text-[10px] font-semibold tracking-wide opacity-50">
                                COCOIR
                            </span>
                        </div>
                    <?php endif; ?>
                    <!-- Color bar -->
                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-<?= $member['color'] ?>"></div>
                </div>

                <!-- Info -->
                <div class="p-7">
                    <h3 class="font-display font-black text-2xl text-coco-brown leading-tight"><?= esc($member['name']) ?></h3>
                    <div class="inline-flex items-center gap-1.5 mt-2 mb-4 bg-<?= $member['color'] ?>/10 text-<?= $member['color'] ?> text-[10px] font-black px-3 py-1 rounded-full tracking-widest uppercase">
                        <?= esc($member['role']) ?>
                    </div>
                    <p class="text-coco-mid text-sm leading-relaxed mb-5"><?= esc($member['bio']) ?></p>

                    <!-- Socials -->
                    <div class="flex gap-2 pt-4 border-t border-coco-sand/50">
                        <?php foreach ($member['social'] as $platform => $url): ?>
                        <a href="<?= $url ?>"
                           class="w-9 h-9 bg-coco-sand/40 hover:bg-<?= $member['color'] ?> rounded-full flex items-center justify-center text-coco-mid hover:text-white transition-all duration-200">
                            <i class="fab fa-<?= $platform ?> text-xs"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Join the team -->
        <div class="mt-14 text-center reveal">
            <div class="inline-block bg-white border border-coco-sand/60 shadow-sm rounded-3xl px-10 py-8 max-w-lg">
                <div class="text-4xl mb-3">🌴</div>
                <h3 class="font-display font-bold text-xl text-coco-brown mb-2">Want to Join Us?</h3>
                <p class="text-coco-mid text-sm mb-5">We're always looking for people who share our passion for sustainability and Filipino craftsmanship.</p>
                <a href="mailto:hello@cocoir.ph"
                   class="inline-flex items-center gap-2 bg-coco-orange text-white font-bold px-7 py-3 rounded-full hover:bg-coco-dark transition-all duration-300 shadow-md hover:scale-105 transform text-sm">
                    <i class="fas fa-envelope text-xs"></i> Get In Touch
                </a>
            </div>
        </div>
    </div>
</section>


<!-- ══════════════════════ CTA BANNER ══════════════════════ -->
<section class="py-20 bg-coco-brown relative overflow-hidden">
    <div class="absolute top-0 right-0 w-64 h-64 rounded-full bg-coco-orange/10 -translate-y-1/2 translate-x-1/3 pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 w-48 h-48 rounded-full bg-coco-leaf/10 translate-y-1/2 -translate-x-1/3 pointer-events-none"></div>

    <div class="max-w-3xl mx-auto px-4 text-center relative z-10 space-y-6 reveal">
        <h2 class="font-display font-black text-coco-cream leading-tight" style="font-size:clamp(2rem,5vw,3.5rem);">
            Ready to Go <span class="text-coco-amber italic">Eco?</span>
        </h2>
        <p class="text-coco-tan text-lg font-light max-w-xl mx-auto">
            Browse our full range of premium coconut coir products and join over 1,500 happy customers making a difference.
        </p>
        <div class="flex flex-wrap gap-4 justify-center pt-2">
            <a href="<?= site_url('products') ?>"
               class="bg-coco-orange text-white px-8 py-4 rounded-full font-bold hover:bg-coco-amber hover:text-coco-brown transition-all duration-300 shadow-lg hover:scale-105 transform inline-flex items-center gap-2">
                <i class="fas fa-leaf"></i> Shop Now
            </a>
            <a href="<?= site_url('/') ?>#contact"
               class="border-2 border-coco-tan/40 text-coco-cream px-8 py-4 rounded-full font-bold hover:border-coco-amber hover:text-coco-amber transition-all duration-300 inline-flex items-center gap-2">
                <i class="fas fa-envelope"></i> Contact Us
            </a>
        </div>
    </div>
</section>


<?= $this->include('components/footer') ?>

<script>
// Scroll reveal
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.10 });

document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(el => observer.observe(el));

// Smooth scroll anchors
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) { e.preventDefault(); target.scrollIntoView({ behavior:'smooth', block:'start' }); }
    });
});
</script>

</body>
</html>