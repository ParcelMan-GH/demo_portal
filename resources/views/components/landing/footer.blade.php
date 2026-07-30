@props([
    'phone' => '+233 55 406 4189',
    'email' => 'support@parcelmanexpress.com'
])

<footer class="pm-footer">
    <div class="pm-footer-main">
        <div class="pm-footer-brand">
            <a href="{{ route('web.landing') }}">
                <img src="{{ asset('pm-logo-horinzontal.png') }}" alt="ParcelMan Express">
            </a>
            <p>App-led parcel pickup and delivery for Ghanaian vendors, shops, and growing businesses.</p>
            <div class="pm-social-links">
                <a href="#" aria-label="Instagram"><img src="{{ asset('instagram.png') }}" alt="Instagram"></a>
                <a href="#" aria-label="Facebook"><img src="{{ asset('facebook.png') }}" alt="Facebook"></a>
                <a href="#" aria-label="TikTok"><img src="{{ asset('tiktok.png') }}" alt="TikTok"></a>
            </div>
        </div>

        <div class="pm-footer-col">
            <h4>Services</h4>
            <a href="#services">Store Pickup</a>
            <a href="#services">Customer Delivery</a>
            <a href="#services">Bulk Parcels</a>
            <a href="#services">App Tracking</a>
        </div>

        <div class="pm-footer-col">
            <h4>Company</h4>
            <a href="#faq">FAQs</a>
            <a href="#how">How It Works</a>
            <a href="{{ route('web.driver.login') }}">Rider Login</a>
            <a href="{{ route('web.vendor.login') }}">Vendor Portal</a>
        </div>

        <div class="pm-footer-col">
            <h4>Contact</h4>
            <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a>
            <a href="mailto:{{ $email }}">{{ $email }}</a>
            <a href="#coverage">Check Service Areas</a>
        </div>
    </div>

    <div class="pm-footer-bottom">
        <span>&copy; {{ date('Y') }} Parcelman Express. All rights reserved.</span>
        <div class="pm-footer-legal">
            <a href="{{ route('web.privacy') }}">Privacy Policy</a>
            <a href="{{ route('web.terms') }}">Terms of Service</a>
        </div>
        <span>Built for businesses moving parcels across Ghana.</span>
    </div>
</footer>