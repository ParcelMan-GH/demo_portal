@props(['faqs' => [
    ['Can I use Parcelman for my shop or social store?', 'Yes, Parcelman is built specifically for vendors, social media sellers, and growing businesses in Ghana.'],
    ['Do I need the mobile app?', 'Yes, Parcelman is app-led so vendors can request pickup, track parcels and manage delivery records from their phone.'],
    ['What details do I add to a pickup request?', 'You can add pickup locations, customer destinations, package descriptions, and delivery notes.'],
    ['Can I choose the kind of vehicle I need?', 'Yes, options include motorbikes, aboboyaa, vans, and trucks depending on your parcel size.'],
    ['Can customers receive delivery updates?', 'Yes, tracking progress is updated in real-time as the delivery moves.'],
    ['Do you support payment on delivery or same-day delivery?', 'We support same-day pickups and offer flexible delivery options for vendors.']
]])

<section class="pm-section pm-faq-section" id="faq" x-data="{ activeFaq: 1 }">
    <div class="pm-faq-grid">
        <!-- Left Side Header -->
        <div class="pm-faq-left">
            <h2>Frequently Asked Questions</h2>
            <p>We compile a list of answers to address your most pressing questions regarding our services</p>
        </div>

        <!-- Right Side Accordion Stack -->
        <div class="pm-faq-accordion">
            @foreach($faqs as $index => $faq)
                <div class="pm-faq-item" :class="{ 'is-open': activeFaq === {{ $index }} }">
                    <button type="button" class="pm-faq-trigger" @click="activeFaq = (activeFaq === {{ $index }} ? null : {{ $index }})">
                        <span>{{ $faq[0] }}</span>
                        <span class="pm-faq-icon" x-text="activeFaq === {{ $index }} ? '−' : '+'"></span>
                    </button>
                    <div class="pm-faq-body" x-show="activeFaq === {{ $index }}" x-collapse>
                        <p>{{ $faq[1] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>