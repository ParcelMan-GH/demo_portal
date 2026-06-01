@extends('web.layouts.portal')

@section('title', 'Parcelman | Delivery for Ghanaian businesses')

@section('content')
<main
    class="pm-page"
    x-data="{
        mobileOpen: false,
        slide: 0,
        slides: [
            {
                image: 'https://images.unsplash.com/photo-1566576721346-d4a3b4eaeb55?auto=format&fit=crop&w=2400&q=85',
                kicker: 'Parcelman Express',
                title: 'Fast parcel delivery for Ghanaian businesses.',
                text: 'Book pickups, follow every order, and keep customers updated from one simple mobile experience.'
            },
            {
                image: 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=2400&q=85',
                kicker: 'Built for vendors',
                title: 'Move more orders with less back-and-forth.',
                text: 'From online sellers to growing shops, Parcelman helps you send packages without chasing updates all day.'
            },
            {
                image: 'https://images.unsplash.com/photo-1605902711622-cfb43c4437d5?auto=format&fit=crop&w=2400&q=85',
                kicker: 'Reliable customer delivery',
                title: 'Give your customers clearer delivery confidence.',
                text: 'Simple status updates and confirmed delivery records make every order easier to manage.'
            }
        ],
        next() { this.slide = (this.slide + 1) % this.slides.length },
        previous() { this.slide = (this.slide + this.slides.length - 1) % this.slides.length },
        start() { setInterval(() => this.next(), 6500) }
    }"
    x-init="start()"
>
    <header class="pm-header">
        <a href="{{ route('web.landing') }}" class="pm-logo" aria-label="Parcelman home">
            <img src="{{ asset('logo-2.png') }}" alt="Parcelman">
        </a>

        <nav class="pm-nav" aria-label="Primary navigation">
            <a href="#services">Services</a>
            <a href="#how">How it works</a>
            <a href="#coverage">Coverage</a>
            <a href="#contact">Contact</a>
        </nav>

        <a href="#" class="pm-header-cta">Get the app</a>

        <button type="button" class="pm-menu" @@click="mobileOpen = ! mobileOpen" aria-label="Toggle navigation">
            <svg x-show="!mobileOpen" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/>
            </svg>
            <svg x-show="mobileOpen" x-cloak width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
            </svg>
        </button>

        <div class="pm-mobile-nav" x-show="mobileOpen" x-cloak @@click.outside="mobileOpen = false">
            <a href="#services" @@click="mobileOpen = false">Services</a>
            <a href="#how" @@click="mobileOpen = false">How it works</a>
            <a href="#coverage" @@click="mobileOpen = false">Coverage</a>
            <a href="#contact" @@click="mobileOpen = false">Contact</a>
            <a href="#" @@click="mobileOpen = false">Get the app</a>
        </div>
    </header>

    <section class="pm-hero" aria-label="Parcelman highlights">
        <template x-for="(item, index) in slides" :key="item.title">
            <div class="pm-hero-slide" x-show="slide === index" x-transition.opacity.duration.500ms>
                <img :src="item.image" :alt="item.title">
            </div>
        </template>
        <div class="pm-hero-shade"></div>

        <div class="pm-hero-content">
            <p class="pm-kicker" x-text="slides[slide].kicker"></p>
            <h1 x-text="slides[slide].title"></h1>
            <p class="pm-hero-text" x-text="slides[slide].text"></p>
            <div class="pm-actions">
                <a href="#" class="pm-button pm-button-primary">Get the app</a>
                <a href="#services" class="pm-button pm-button-ghost">Explore services</a>
            </div>
        </div>

        <div class="pm-hero-controls" aria-label="Hero slider controls">
            <button type="button" @@click="previous()" aria-label="Previous slide">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                    <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <template x-for="(item, index) in slides" :key="index">
                <button type="button" class="pm-dot" :class="{ 'is-active': slide === index }" @@click="slide = index" :aria-label="`Go to slide ${index + 1}`"></button>
            </template>
            <button type="button" @@click="next()" aria-label="Next slide">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                    <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>

        <div class="pm-hero-card">
            <span>Today with Parcelman</span>
            <strong>Pickup requested</strong>
            <p>Vendor gets clear delivery progress without calling around for updates.</p>
        </div>
    </section>

    <section class="pm-trust" aria-label="Parcelman trust points">
        <div>
            <strong>Same-day pickup</strong>
            <span>Available in supported areas</span>
        </div>
        <div>
            <strong>Business-friendly delivery</strong>
            <span>Made for vendors and shops</span>
        </div>
        <div>
            <strong>Clear order updates</strong>
            <span>Know what is happening</span>
        </div>
        <div>
            <strong>Ghana-focused coverage</strong>
            <span>Built around local delivery needs</span>
        </div>
    </section>

    <section class="pm-section pm-services" id="services">
        <div class="pm-section-head">
            <p class="pm-kicker">Services</p>
            <h2>Delivery support for businesses that send parcels every day.</h2>
            <p>Parcelman helps vendors handle customer orders with cleaner pickup requests, better tracking, and dependable delivery communication.</p>
        </div>

        <div class="pm-service-grid">
            @foreach([
                ['Store pickup', 'Request parcel pickup from your shop, home office, or selling point.'],
                ['Customer delivery', 'Send orders to customers with clearer status updates from pickup to delivery.'],
                ['Bulk parcel movement', 'Move multiple customer packages without losing track of what belongs where.'],
                ['App tracking', 'Follow each parcel from your phone and reduce repeated customer calls.'],
                ['Delivery records', 'Keep a cleaner history of requests, destinations, charges and delivery status.'],
                ['Vendor support', 'Get a delivery flow that works for small shops, online sellers and growing businesses.'],
            ] as $service)
                <article class="pm-service-card">
                    <span></span>
                    <h3>{{ $service[0] }}</h3>
                    <p>{{ $service[1] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="pm-section pm-why">
        <div class="pm-why-image">
            <img src="https://images.unsplash.com/photo-1586880244406-556ebe35f282?auto=format&fit=crop&w=1500&q=85" alt="Packages prepared for delivery">
        </div>
        <div class="pm-why-copy">
            <p class="pm-kicker">Why Parcelman</p>
            <h2>Less delivery stress. More time to sell.</h2>
            <p>
                Customers expect quick answers after they pay. Parcelman gives your business a neater way to request delivery,
                track orders, and confirm when packages reach the right person.
            </p>
            <ul>
                <li>Reduce follow-up calls about package location.</li>
                <li>Keep parcel details and customer destinations organized.</li>
                <li>Give customers a more professional delivery experience.</li>
            </ul>
        </div>
    </section>

    <section class="pm-section pm-how" id="how">
        <div class="pm-section-head pm-section-head-narrow">
            <p class="pm-kicker">How it works</p>
            <h2>Simple enough for everyday orders.</h2>
        </div>

        <div class="pm-steps">
            @foreach([
                ['01', 'Request pickup', 'Add parcel details and pickup location from the Parcelman app.'],
                ['02', 'Parcel collected', 'A pickup is arranged so the parcel can start moving.'],
                ['03', 'Track progress', 'Follow delivery updates without repeatedly calling for status.'],
                ['04', 'Customer receives', 'Delivery is completed with a clear record for your business.'],
            ] as $step)
                <article class="pm-step">
                    <span>{{ $step[0] }}</span>
                    <h3>{{ $step[1] }}</h3>
                    <p>{{ $step[2] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="pm-section pm-app" id="app">
        <div class="pm-app-copy">
            <p class="pm-kicker">Mobile app</p>
            <h2>Run parcel requests from your phone.</h2>
            <p>The Parcelman app is where vendors request pickups, add package details, track delivery progress, and manage their parcel history.</p>
            <a href="#" class="pm-button pm-button-primary">Get the app</a>
        </div>

        <div class="pm-phone-wrap" aria-label="Parcelman app preview">
            <div class="pm-phone">
                <div class="pm-phone-top"></div>
                <div class="pm-phone-hero">
                    <span>Hi, Vendor</span>
                    <strong>Send Package</strong>
                </div>
                <div class="pm-phone-card">
                    <small>Pickup location</small>
                    <b>East Legon, Accra</b>
                </div>
                <div class="pm-phone-card">
                    <small>Package photos</small>
                    <b>3 items added</b>
                </div>
                <div class="pm-phone-row">
                    <span></span><span></span><span></span>
                </div>
                <button type="button">Request pickup</button>
            </div>
        </div>
    </section>

    <section class="pm-section pm-coverage" id="coverage">
        <div class="pm-map-copy">
            <p class="pm-kicker">Coverage</p>
            <h2>Serving vendors across key Ghanaian delivery areas.</h2>
            <p>
                Parcelman is built for Ghanaian businesses that need reliable pickup and customer delivery across busy commercial areas.
            </p>
            <div class="pm-city-list">
                <span>Accra</span>
                <span>Tema</span>
                <span>Kumasi</span>
                <span>Takoradi</span>
                <span>Expanding</span>
            </div>
        </div>

        <div class="pm-map-card" aria-label="Parcelman Ghana coverage map using OpenStreetMap">
            <div class="pm-map-tiles">
                @foreach([
                    [62, 61], [63, 61], [64, 61],
                    [62, 62], [63, 62], [64, 62],
                    [62, 63], [63, 63], [64, 63],
                ] as $tile)
                    <img src="https://tile.openstreetmap.org/7/{{ $tile[0] }}/{{ $tile[1] }}.png" alt="">
                @endforeach
                <span class="pm-map-marker pm-map-marker-accra">Accra</span>
                <span class="pm-map-marker pm-map-marker-tema">Tema</span>
                <span class="pm-map-marker pm-map-marker-kumasi">Kumasi</span>
                <span class="pm-map-marker pm-map-marker-takoradi">Takoradi</span>
                <small>&copy; OpenStreetMap contributors</small>
            </div>
        </div>
    </section>

    <section class="pm-section pm-contact" id="contact">
        <div class="pm-contact-info">
            <p class="pm-kicker">Contact us</p>
            <h2>Talk to Parcelman about your delivery needs.</h2>
            <p>Have a shop, online store, or business sending parcels often? Reach out and we will help you get started.</p>

            <div class="pm-contact-list">
                <a href="tel:+233200000000">+233 20 000 0000</a>
                <a href="mailto:support@parcelman.com">support@parcelman.com</a>
                <span>Accra, Ghana</span>
            </div>
        </div>

        <form class="pm-contact-form">
            <label>
                <span>Name</span>
                <input type="text" name="name" placeholder="Your name">
            </label>
            <label>
                <span>Phone</span>
                <input type="tel" name="phone" placeholder="024 000 0000">
            </label>
            <label>
                <span>Business name</span>
                <input type="text" name="business" placeholder="Your shop or company">
            </label>
            <label>
                <span>Message</span>
                <textarea name="message" rows="4" placeholder="Tell us what you need to deliver"></textarea>
            </label>
            <button type="button" class="pm-button pm-button-primary">Send message</button>
        </form>
    </section>

    <section class="pm-newsletter">
        <div>
            <p class="pm-kicker">Vendor updates</p>
            <h2>Get delivery tips and Parcelman announcements.</h2>
        </div>
        <form>
            <input type="email" placeholder="Email address" aria-label="Email address">
            <button type="button">Subscribe</button>
        </form>
    </section>

    <section class="pm-section pm-faq" id="faq">
        <div class="pm-section-head pm-section-head-narrow">
            <p class="pm-kicker">FAQ</p>
            <h2>Questions vendors usually ask.</h2>
        </div>
        <div class="pm-faq-grid">
            @foreach([
                ['Do I need the mobile app?', 'Yes. Parcelman is app-led so vendors can request pickup, track parcels and manage delivery records from their phone.'],
                ['Can I use Parcelman for my shop?', 'Yes. Parcelman is designed for online vendors, retail shops and businesses that send customer orders.'],
                ['Can customers receive delivery updates?', 'Parcelman helps keep delivery progress clear so vendors can answer customers with confidence.'],
                ['Which areas do you cover?', 'Parcelman supports key Ghanaian delivery areas and continues expanding coverage as operations grow.'],
            ] as $faq)
                <article>
                    <h3>{{ $faq[0] }}</h3>
                    <p>{{ $faq[1] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <footer class="pm-footer">
        <div class="pm-footer-main">
            <div class="pm-footer-brand">
                <img src="{{ asset('logo-2.png') }}" alt="Parcelman">
                <p>Reliable parcel delivery for Ghanaian vendors, shops and growing businesses.</p>
                <a href="#" class="pm-footer-app">Get the app</a>
            </div>

            <div>
                <h3>Services</h3>
                <a href="#services">Store pickup</a>
                <a href="#services">Customer delivery</a>
                <a href="#services">Bulk parcels</a>
                <a href="#services">App tracking</a>
            </div>

            <div>
                <h3>Company</h3>
                <a href="#how">How it works</a>
                <a href="#coverage">Coverage</a>
                <a href="#faq">FAQ</a>
                <a href="#contact">Contact</a>
            </div>

            <div>
                <h3>Contact</h3>
                <a href="tel:+233200000000">+233 20 000 0000</a>
                <a href="mailto:support@parcelman.com">support@parcelman.com</a>
                <span>Accra, Ghana</span>
            </div>

            <div class="pm-footer-news">
                <h3>Newsletter</h3>
                <p>Useful delivery updates for vendors.</p>
                <form>
                    <input type="email" placeholder="Email address" aria-label="Footer email address">
                    <button type="button">Join</button>
                </form>
            </div>
        </div>

        <div class="pm-footer-bottom">
            <span>&copy; {{ date('Y') }} Parcelman Express. All rights reserved.</span>
            <span>Built for businesses moving parcels across Ghana.</span>
        </div>
    </footer>
</main>

<style>
    :root {
        --pm-ink: #111827;
        --pm-dark: #121720;
        --pm-muted: #647084;
        --pm-line: #e8e1d9;
        --pm-paper: #fffdf8;
        --pm-warm: #f8f1e8;
        --pm-orange: #f97316;
        --pm-orange-dark: #c2410c;
        --pm-green: #15803d;
    }

    [x-cloak] { display: none !important; }

    .pm-page,
    .pm-page * { box-sizing: border-box; }

    .pm-page {
        min-height: 100vh;
        background: var(--pm-paper);
        color: var(--pm-ink);
        font-family: "Plus Jakarta Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .pm-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 50;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        min-height: 74px;
        padding: 0.7rem clamp(1rem, 4vw, 3.5rem);
        background: rgba(255, 253, 248, 0.9);
        border-bottom: 1px solid rgba(232, 225, 217, 0.8);
        backdrop-filter: blur(18px);
    }

    .pm-logo,
    .pm-nav a,
    .pm-header-cta,
    .pm-mobile-nav a,
    .pm-footer a {
        text-decoration: none;
    }

    .pm-logo img {
        display: block;
        width: 92px;
        height: auto;
    }

    .pm-nav {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .pm-nav a {
        color: #263241;
        font-size: 0.9rem;
        font-weight: 900;
    }

    .pm-nav a:hover { color: var(--pm-orange-dark); }

    .pm-header-cta {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        padding: 0.75rem 1rem;
        border-radius: 7px;
        background: var(--pm-ink);
        color: #fff;
        font-size: 0.9rem;
        font-weight: 900;
    }

    .pm-menu {
        display: none;
        width: 42px;
        height: 42px;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--pm-line);
        border-radius: 7px;
        background: #fff;
        color: var(--pm-ink);
    }

    .pm-mobile-nav {
        position: absolute;
        top: calc(100% + 0.5rem);
        right: 1rem;
        display: grid;
        width: min(320px, calc(100vw - 2rem));
        padding: 0.45rem;
        border: 1px solid var(--pm-line);
        border-radius: 9px;
        background: #fff;
        box-shadow: 0 24px 60px rgba(17, 24, 39, 0.16);
    }

    .pm-mobile-nav a {
        padding: 0.85rem 0.9rem;
        border-radius: 7px;
        color: #263241;
        font-weight: 900;
    }

    .pm-mobile-nav a:hover { background: var(--pm-warm); }

    .pm-hero {
        position: relative;
        min-height: 100vh;
        overflow: hidden;
        display: flex;
        align-items: center;
        padding: 8rem clamp(1rem, 5vw, 5rem) 5rem;
        color: #fff;
    }

    .pm-hero-slide,
    .pm-hero-slide img,
    .pm-hero-shade {
        position: absolute;
        inset: 0;
    }

    .pm-hero-slide img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: saturate(0.95);
    }

    .pm-hero-shade {
        background:
            linear-gradient(90deg, rgba(6, 10, 18, 0.86) 0%, rgba(6, 10, 18, 0.66) 45%, rgba(6, 10, 18, 0.24) 100%),
            linear-gradient(180deg, rgba(6, 10, 18, 0.35) 0%, rgba(6, 10, 18, 0.7) 100%);
        z-index: 1;
    }

    .pm-hero-content {
        position: relative;
        z-index: 2;
        max-width: 760px;
        padding-top: 2rem;
    }

    .pm-kicker {
        margin: 0 0 0.9rem;
        color: var(--pm-orange);
        font-size: 0.78rem;
        font-weight: 900;
        letter-spacing: 0.08em;
        line-height: 1.4;
        text-transform: uppercase;
    }

    .pm-hero h1,
    .pm-section-head h2,
    .pm-why-copy h2,
    .pm-app-copy h2,
    .pm-map-copy h2,
    .pm-contact-info h2,
    .pm-newsletter h2 {
        margin: 0;
        font-weight: 900;
        letter-spacing: 0;
    }

    .pm-hero h1 {
        font-size: clamp(3.4rem, 7vw, 6.9rem);
        line-height: 0.94;
        max-width: 820px;
    }

    .pm-hero-text {
        max-width: 620px;
        margin: 1.35rem 0 0;
        color: rgba(255, 255, 255, 0.82);
        font-size: clamp(1.05rem, 1.7vw, 1.24rem);
        line-height: 1.75;
    }

    .pm-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.85rem;
        margin-top: 2rem;
    }

    .pm-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 48px;
        padding: 0.9rem 1.25rem;
        border-radius: 8px;
        border: 0;
        font: inherit;
        font-size: 0.94rem;
        font-weight: 900;
        line-height: 1;
        text-decoration: none;
        transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease;
    }

    .pm-button:hover { transform: translateY(-1px); }

    .pm-button-primary {
        background: var(--pm-orange);
        color: #fff;
        box-shadow: 0 18px 40px rgba(249, 115, 22, 0.28);
    }

    .pm-button-primary:hover { background: #ea580c; }

    .pm-button-ghost {
        border: 1px solid rgba(255, 255, 255, 0.42);
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        backdrop-filter: blur(14px);
    }

    .pm-hero-controls {
        position: absolute;
        left: clamp(1rem, 5vw, 5rem);
        bottom: 2.2rem;
        z-index: 3;
        display: flex;
        align-items: center;
        gap: 0.55rem;
    }

    .pm-hero-controls button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border: 1px solid rgba(255, 255, 255, 0.28);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        backdrop-filter: blur(12px);
    }

    .pm-hero-controls .pm-dot {
        width: 10px;
        height: 10px;
        min-height: 10px;
        padding: 0;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.38);
    }

    .pm-hero-controls .pm-dot.is-active {
        width: 28px;
        background: var(--pm-orange);
    }

    .pm-hero-card {
        position: absolute;
        right: clamp(1rem, 5vw, 5rem);
        bottom: 2.2rem;
        z-index: 2;
        max-width: 310px;
        padding: 1.1rem;
        border: 1px solid rgba(255, 255, 255, 0.28);
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.13);
        backdrop-filter: blur(18px);
    }

    .pm-hero-card span {
        color: rgba(255, 255, 255, 0.65);
        font-size: 0.76rem;
        font-weight: 900;
        text-transform: uppercase;
    }

    .pm-hero-card strong {
        display: block;
        margin-top: 0.35rem;
        color: #fff;
        font-size: 1.05rem;
    }

    .pm-hero-card p {
        margin: 0.45rem 0 0;
        color: rgba(255, 255, 255, 0.72);
        font-size: 0.86rem;
        line-height: 1.55;
    }

    .pm-trust {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        max-width: 1180px;
        margin: -3.5rem auto 0;
        position: relative;
        z-index: 5;
        padding: 0 clamp(1rem, 4vw, 2rem);
    }

    .pm-trust div {
        min-height: 120px;
        padding: 1.15rem;
        border-top: 1px solid var(--pm-line);
        border-bottom: 1px solid var(--pm-line);
        border-left: 1px solid var(--pm-line);
        background: rgba(255, 253, 248, 0.96);
    }

    .pm-trust div:first-child { border-radius: 10px 0 0 10px; }
    .pm-trust div:last-child {
        border-right: 1px solid var(--pm-line);
        border-radius: 0 10px 10px 0;
    }

    .pm-trust strong {
        display: block;
        color: var(--pm-ink);
        font-size: 1.02rem;
        font-weight: 900;
    }

    .pm-trust span {
        display: block;
        margin-top: 0.45rem;
        color: var(--pm-muted);
        font-size: 0.88rem;
        line-height: 1.55;
    }

    .pm-section {
        max-width: 1180px;
        margin: 0 auto;
        padding: clamp(4rem, 8vw, 7rem) clamp(1rem, 4vw, 2rem);
    }

    .pm-section-head {
        max-width: 900px;
        margin-bottom: 2rem;
    }

    .pm-section-head-narrow {
        max-width: 720px;
    }

    .pm-section-head h2,
    .pm-why-copy h2,
    .pm-app-copy h2,
    .pm-map-copy h2,
    .pm-contact-info h2,
    .pm-newsletter h2 {
        color: var(--pm-ink);
        font-size: clamp(2rem, 4.6vw, 4rem);
        line-height: 1.03;
    }

    .pm-section-head p:not(.pm-kicker),
    .pm-why-copy p,
    .pm-app-copy p,
    .pm-map-copy p,
    .pm-contact-info p {
        margin: 1rem 0 0;
        color: var(--pm-muted);
        font-size: 1rem;
        line-height: 1.8;
    }

    .pm-section-head p:not(.pm-kicker) {
        max-width: 660px;
    }

    .pm-service-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .pm-service-card {
        min-height: 250px;
        padding: 1.25rem;
        border: 1px solid var(--pm-line);
        border-radius: 10px;
        background: #fff;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .pm-service-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 22px 50px rgba(17, 24, 39, 0.08);
    }

    .pm-service-card span {
        display: block;
        width: 38px;
        height: 4px;
        border-radius: 999px;
        background: var(--pm-orange);
    }

    .pm-service-card h3,
    .pm-step h3,
    .pm-faq h3 {
        margin: 0;
        color: var(--pm-ink);
        font-weight: 900;
        letter-spacing: 0;
    }

    .pm-service-card h3 {
        margin-top: 4.4rem;
        font-size: 1.35rem;
        line-height: 1.18;
    }

    .pm-service-card p,
    .pm-step p,
    .pm-faq p {
        margin: 0.75rem 0 0;
        color: var(--pm-muted);
        font-size: 0.93rem;
        line-height: 1.7;
    }

    .pm-why,
    .pm-app,
    .pm-coverage,
    .pm-contact {
        display: grid;
        grid-template-columns: minmax(0, 0.9fr) minmax(360px, 1fr);
        gap: clamp(2rem, 6vw, 5rem);
        align-items: center;
    }

    .pm-why-image {
        overflow: hidden;
        min-height: 520px;
        border-radius: 12px;
        background: var(--pm-warm);
    }

    .pm-why-image img {
        width: 100%;
        height: 100%;
        min-height: 520px;
        object-fit: cover;
        display: block;
    }

    .pm-why-copy p,
    .pm-app-copy p,
    .pm-map-copy p,
    .pm-contact-info p {
        margin-top: 1rem;
    }

    .pm-why-copy ul {
        list-style: none;
        padding: 0;
        margin: 1.5rem 0 0;
        display: grid;
        gap: 0.85rem;
    }

    .pm-why-copy li {
        position: relative;
        padding-left: 1.5rem;
        color: #344054;
        font-weight: 800;
        line-height: 1.6;
    }

    .pm-why-copy li::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0.55rem;
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: var(--pm-orange);
    }

    .pm-steps {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }

    .pm-step {
        min-height: 260px;
        padding: 1.25rem;
        border: 1px solid var(--pm-line);
        border-radius: 10px;
        background: var(--pm-warm);
    }

    .pm-step span {
        display: inline-flex;
        color: var(--pm-orange-dark);
        font-size: 0.85rem;
        font-weight: 900;
    }

    .pm-step h3 {
        margin-top: 5rem;
        font-size: 1.2rem;
    }

    .pm-app {
        border-radius: 18px;
        background: linear-gradient(135deg, #111827 0%, #1e293b 100%);
        color: #fff;
        margin-top: 2rem;
    }

    .pm-app-copy h2,
    .pm-app-copy p {
        color: #fff;
    }

    .pm-app-copy p {
        color: rgba(255, 255, 255, 0.72);
        max-width: 560px;
    }

    .pm-app-copy .pm-button {
        margin-top: 1.7rem;
    }

    .pm-phone-wrap {
        display: flex;
        justify-content: center;
        min-height: 560px;
    }

    .pm-phone {
        width: min(330px, 100%);
        min-height: 610px;
        padding: 0.9rem;
        border: 9px solid #0b111b;
        border-radius: 38px;
        background: #f8fafc;
        box-shadow: 0 35px 80px rgba(0, 0, 0, 0.3);
    }

    .pm-phone-top {
        width: 88px;
        height: 6px;
        margin: 0 auto 1rem;
        border-radius: 999px;
        background: #111827;
    }

    .pm-phone-hero {
        padding: 1.2rem;
        border-radius: 24px;
        background: linear-gradient(135deg, #7c2d12, #ea580c);
        color: #fff;
    }

    .pm-phone-hero span,
    .pm-phone-card small {
        display: block;
        font-size: 0.78rem;
        font-weight: 800;
        opacity: 0.74;
    }

    .pm-phone-hero strong {
        display: block;
        margin-top: 1.6rem;
        font-size: 1.55rem;
        line-height: 1.1;
    }

    .pm-phone-card {
        margin-top: 0.85rem;
        padding: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: #fff;
        color: var(--pm-ink);
    }

    .pm-phone-card b {
        display: block;
        margin-top: 0.35rem;
        font-size: 1rem;
    }

    .pm-phone-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.55rem;
        margin-top: 0.85rem;
    }

    .pm-phone-row span {
        height: 78px;
        border-radius: 16px;
        background: linear-gradient(135deg, #fed7aa, #fff7ed);
    }

    .pm-phone button {
        width: 100%;
        min-height: 48px;
        margin-top: 1rem;
        border: 0;
        border-radius: 999px;
        background: var(--pm-orange);
        color: #fff;
        font-weight: 900;
    }

    .pm-city-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        margin-top: 1.5rem;
    }

    .pm-city-list span {
        display: inline-flex;
        padding: 0.6rem 0.8rem;
        border: 1px solid var(--pm-line);
        border-radius: 999px;
        background: #fff;
        color: #344054;
        font-size: 0.84rem;
        font-weight: 900;
    }

    .pm-map-card {
        overflow: hidden;
        min-height: 480px;
        border: 1px solid var(--pm-line);
        border-radius: 14px;
        background: var(--pm-warm);
    }

    .pm-map-tiles {
        position: relative;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        width: 100%;
        height: 480px;
        overflow: hidden;
        background: #eadfce;
    }

    .pm-map-tiles img {
        width: 100%;
        height: 160px;
        object-fit: cover;
        display: block;
        filter: saturate(0.86) contrast(0.98);
    }

    .pm-map-tiles::after {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: linear-gradient(180deg, rgba(255, 253, 248, 0.08), rgba(255, 253, 248, 0.22));
    }

    .pm-map-marker {
        position: absolute;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.45rem 0.62rem;
        border-radius: 999px;
        background: var(--pm-orange);
        color: #fff;
        font-size: 0.78rem;
        font-weight: 900;
        box-shadow: 0 12px 28px rgba(194, 65, 12, 0.28);
    }

    .pm-map-marker::before {
        content: '';
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #fff;
    }

    .pm-map-marker-accra { left: 55%; top: 32%; }
    .pm-map-marker-tema { left: 63%; top: 31%; }
    .pm-map-marker-kumasi { left: 38%; top: 23%; }
    .pm-map-marker-takoradi { left: 30%; top: 34%; }

    .pm-map-tiles small {
        position: absolute;
        right: 0.75rem;
        bottom: 0.65rem;
        z-index: 2;
        padding: 0.32rem 0.5rem;
        border-radius: 6px;
        background: rgba(255, 255, 255, 0.86);
        color: #344054;
        font-size: 0.7rem;
        font-weight: 800;
    }

    .pm-contact {
        align-items: start;
        border-top: 1px solid var(--pm-line);
    }

    .pm-contact-list {
        display: grid;
        gap: 0.7rem;
        margin-top: 1.5rem;
    }

    .pm-contact-list a,
    .pm-contact-list span {
        color: #344054;
        font-weight: 900;
        text-decoration: none;
    }

    .pm-contact-form {
        display: grid;
        gap: 0.9rem;
        padding: 1.2rem;
        border: 1px solid var(--pm-line);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 22px 60px rgba(17, 24, 39, 0.07);
    }

    .pm-contact-form label {
        display: grid;
        gap: 0.4rem;
    }

    .pm-contact-form span {
        color: #344054;
        font-size: 0.84rem;
        font-weight: 900;
    }

    .pm-contact-form input,
    .pm-contact-form textarea,
    .pm-newsletter input,
    .pm-footer-news input {
        width: 100%;
        border: 1px solid #ddd6ce;
        border-radius: 8px;
        background: #fff;
        color: var(--pm-ink);
        font: inherit;
        font-size: 0.95rem;
        outline: none;
    }

    .pm-contact-form input {
        min-height: 48px;
        padding: 0 0.9rem;
    }

    .pm-contact-form textarea {
        resize: vertical;
        padding: 0.85rem 0.9rem;
    }

    .pm-newsletter {
        max-width: 1180px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 2rem;
        padding: clamp(2rem, 5vw, 3rem) clamp(1rem, 4vw, 2rem);
        border-radius: 18px;
        background: var(--pm-orange);
        color: #fff;
    }

    .pm-newsletter .pm-kicker,
    .pm-newsletter h2 {
        color: #fff;
    }

    .pm-newsletter h2 {
        max-width: 640px;
        font-size: clamp(1.8rem, 3.8vw, 3.2rem);
    }

    .pm-newsletter form {
        display: flex;
        gap: 0.65rem;
        width: min(470px, 100%);
    }

    .pm-newsletter input {
        min-height: 52px;
        padding: 0 1rem;
        border: 0;
    }

    .pm-newsletter button,
    .pm-footer-news button {
        min-height: 52px;
        border: 0;
        border-radius: 8px;
        background: var(--pm-ink);
        color: #fff;
        font: inherit;
        font-weight: 900;
        padding: 0 1rem;
    }

    .pm-faq-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .pm-faq article {
        padding: 1.25rem;
        border: 1px solid var(--pm-line);
        border-radius: 10px;
        background: #fff;
    }

    .pm-faq h3 {
        font-size: 1.08rem;
    }

    .pm-footer {
        margin-top: clamp(3rem, 7vw, 6rem);
        background: #111827;
        color: #fff;
    }

    .pm-footer-main {
        max-width: 1180px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1.35fr 0.7fr 0.7fr 0.85fr 1fr;
        gap: 2rem;
        padding: clamp(3rem, 7vw, 5rem) clamp(1rem, 4vw, 2rem);
    }

    .pm-footer img {
        display: block;
        width: 112px;
        height: auto;
        filter: brightness(0) invert(1);
    }

    .pm-footer p {
        margin: 1rem 0 0;
        color: rgba(255, 255, 255, 0.64);
        font-size: 0.92rem;
        line-height: 1.7;
    }

    .pm-footer h3 {
        margin: 0 0 1rem;
        color: #fff;
        font-size: 0.86rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .pm-footer-main > div:not(.pm-footer-brand),
    .pm-footer-news {
        display: flex;
        flex-direction: column;
        gap: 0.62rem;
    }

    .pm-footer a,
    .pm-footer span {
        color: rgba(255, 255, 255, 0.66);
        font-size: 0.9rem;
    }

    .pm-footer a:hover {
        color: #fff;
    }

    .pm-footer-app {
        display: inline-flex;
        width: fit-content;
        margin-top: 1.2rem;
        padding: 0.78rem 1rem;
        border-radius: 8px;
        background: var(--pm-orange);
        color: #fff !important;
        font-weight: 900;
    }

    .pm-footer-news form {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.6rem;
    }

    .pm-footer-news input {
        min-height: 44px;
        padding: 0 0.75rem;
        border: 1px solid rgba(255, 255, 255, 0.16);
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
    }

    .pm-footer-news input::placeholder {
        color: rgba(255, 255, 255, 0.52);
    }

    .pm-footer-news button {
        min-height: 44px;
        background: #fff;
        color: var(--pm-ink);
    }

    .pm-footer-bottom {
        max-width: 1180px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.4rem clamp(1rem, 4vw, 2rem);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    @media (max-width: 1040px) {
        .pm-service-grid,
        .pm-steps {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pm-trust {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .pm-trust div:first-child,
        .pm-trust div:last-child {
            border-radius: 0;
        }

        .pm-footer-main {
            grid-template-columns: 1.3fr 1fr 1fr;
        }
    }

    @media (max-width: 900px) {
        .pm-nav,
        .pm-header-cta {
            display: none;
        }

        .pm-menu {
            display: inline-flex;
        }

        .pm-hero-card {
            display: none;
        }

        .pm-why,
        .pm-app,
        .pm-coverage,
        .pm-contact {
            grid-template-columns: 1fr;
        }

        .pm-newsletter {
            display: block;
        }

        .pm-newsletter form {
            margin-top: 1.4rem;
        }
    }

    @media (max-width: 680px) {
        .pm-header {
            min-height: 64px;
        }

        .pm-logo img {
            width: 82px;
        }

        .pm-hero {
            min-height: 92vh;
            padding-top: 7rem;
        }

        .pm-hero h1 {
            font-size: clamp(2.7rem, 14vw, 4.1rem);
        }

        .pm-actions .pm-button {
            width: 100%;
        }

        .pm-hero-controls {
            bottom: 1.2rem;
        }

        .pm-trust,
        .pm-service-grid,
        .pm-steps,
        .pm-faq-grid {
            grid-template-columns: 1fr;
        }

        .pm-trust {
            margin-top: 0;
            padding-top: 1rem;
        }

        .pm-trust div,
        .pm-trust div:last-child {
            border-right: 1px solid var(--pm-line);
        }

        .pm-service-card h3,
        .pm-step h3 {
            margin-top: 2.5rem;
        }

        .pm-why-image,
        .pm-why-image img {
            min-height: 340px;
        }

        .pm-phone-wrap {
            min-height: auto;
        }

        .pm-phone {
            min-height: 560px;
        }

        .pm-map-card,
        .pm-map-tiles {
            min-height: 360px;
            height: 360px;
        }

        .pm-map-tiles img {
            height: 120px;
        }

        .pm-map-marker {
            font-size: 0.68rem;
            padding: 0.38rem 0.5rem;
        }

        .pm-newsletter form,
        .pm-footer-news form {
            display: grid;
        }

        .pm-footer-main {
            grid-template-columns: 1fr;
        }

        .pm-footer-bottom {
            display: grid;
        }
    }
</style>
@endsection
