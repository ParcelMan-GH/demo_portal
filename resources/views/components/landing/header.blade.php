<header class="pm-header">
    <a href="{{ route('web.landing') }}" class="pm-logo" aria-label="Parcelman home">
        <img src="{{ asset('logo-2.png') }}" alt="ParcelMan Express">
    </a>

    <nav class="pm-nav" aria-label="Primary navigation">
        <a href="#services">Service</a>
        <a href="#how">How it works</a>
        <!-- <a href="#coverage">Coverage</a> -->
        <a href="#contact">Contact</a>
        <a href="#faq">FAQs</a>
    </nav>

    <a href="#app" class="pm-download-btn">
        <img src="{{ asset('apple-android.svg') }}" alt="App Store and Google Play" class="pm-store-icon">
        <span>Download Now</span>
    </a>

    <button type="button" class="pm-menu-btn" @click="mobileOpen = ! mobileOpen" aria-label="Toggle navigation">
        <svg x-show="!mobileOpen" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/>
        </svg>
        <svg x-show="mobileOpen" x-cloak width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
        </svg>
    </button>

    <div class="pm-mobile-menu" x-show="mobileOpen" x-cloak @click.outside="mobileOpen = false">
        <a href="#services" @click="mobileOpen = false">Service</a>
        <a href="#how" @click="mobileOpen = false">How it works</a>
        <!-- <a href="#coverage" @click="mobileOpen = false">Coverage</a> -->
        <a href="#contact" @click="mobileOpen = false">Contact</a>
        <a href="#faq" @click="mobileOpen = false">FAQs</a>
        <a href="#app" @click="mobileOpen = false">Download App</a>
    </div>
</header>