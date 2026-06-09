{{--
    Terms of Service / Terms & Conditions — tailored to Parcelman's operations.
    NOT legal advice: have qualified counsel review before publishing and
    update the "legal_updated" date below to the real publication date.
--}}
@extends('web.layouts.legal')

@php
    $email = trim((string) \App\Models\PlatformSetting::getValue('platform_email', '')) ?: 'support@parcelman.com';
    $phone = trim((string) \App\Models\PlatformSetting::getValue('platform_phone', ''));
    $address = trim((string) \App\Models\PlatformSetting::getValue('platform_address', ''));
@endphp

@section('title', 'Terms of Service | Parcelman Express')
@section('meta_description', 'The terms and conditions governing use of Parcelman Express parcel pickup and delivery services in Ghana.')

@section('legal_kicker', 'Legal')
@section('legal_heading', 'Terms of Service')
@section('legal_updated', '7 June 2026')

@section('legal_content')
    <p class="legal-intro">
        These Terms of Service ("Terms") govern your access to and use of the Parcelman Express mobile app,
        websites, and parcel pickup and delivery services (the "Services") provided by Parcelman Express
        ("Parcelman", "we", "us", or "our"). Please read them carefully.
    </p>

    <h2>1. Acceptance of these Terms</h2>
    <p>
        By creating an account or using the Services, you agree to these Terms and to our
        <a href="{{ route('web.privacy') }}{{ request()->boolean('app') ? '?app=1' : '' }}">Privacy Policy</a>. If you do not agree, do not use the Services.
        If you use the Services on behalf of a business, you confirm you are authorised to bind that business.
    </p>

    <h2>2. Definitions</h2>
    <ul>
        <li><strong>Vendor</strong> — a shop, online seller, or business that requests parcel pickup and delivery.</li>
        <li><strong>Rider</strong> — a rider or courier who picks up, transports, or delivers parcels.</li>
        <li><strong>Recipient</strong> — the person a vendor asks us to deliver a parcel to.</li>
        <li><strong>Parcel</strong> — the package(s) and contents submitted for pickup and delivery.</li>
    </ul>

    <h2>3. Eligibility and account registration</h2>
    <p>
        You must be at least 18 years old and able to enter a binding contract. You register using your mobile
        phone number and verify it with a one-time passcode (OTP). You are responsible for keeping your account
        and device secure and for all activity under your account. Provide accurate, current information and keep
        it up to date.
    </p>

    <h2>4. The Services</h2>
    <p>
        Parcelman is an app-led platform that lets vendors request parcel pickups and track delivery progress,
        and coordinates pickup, transport, and delivery through our network of riders and warehouses. Service
        availability, coverage areas, and options (such as vehicle type or same-day delivery) may vary and are
        confirmed during onboarding or at the time of a request.
    </p>

    <h2>5. Vendor responsibilities</h2>
    <ul>
        <li>Provide accurate pickup details, destination addresses, recipient contact details, and parcel descriptions.</li>
        <li>Ensure the contents are lawful, properly packaged, and accurately declared.</li>
        <li>Obtain any consent needed to share recipient information with us for delivery.</li>
        <li>Comply with all applicable laws in connection with the goods you send.</li>
    </ul>

    <h2>6. Prohibited and restricted items</h2>
    <p>You may not send, and we may refuse to carry, items that are unlawful or unsafe, including but not limited to:</p>
    <ul>
        <li>Illegal drugs, weapons, ammunition, or explosives.</li>
        <li>Hazardous, flammable, or perishable materials that are unsafe to transport.</li>
        <li>Counterfeit goods, stolen property, or items prohibited by law.</li>
        <li>Cash, or items whose carriage is otherwise restricted, except as expressly agreed.</li>
    </ul>
    <p>We may inspect, refuse, hold, or return parcels that we reasonably believe breach these Terms or the law.</p>

    <h2>7. Pricing, fees, and payments</h2>
    <p>
        Fees for pickup, delivery, and related services are shown or confirmed before or during a request and may
        change over time. Where applicable, recipients may pay on delivery, and vendor payouts are processed to
        the payment details you provide. You are responsible for the accuracy of payout details and for any taxes
        applicable to your transactions. Charges already incurred are non-refundable except as required by law or
        expressly stated.
    </p>

    <h2>8. Pickup, delivery, and timelines</h2>
    <p>
        Delivery times are estimates, not guarantees, and may be affected by traffic, weather, address accuracy,
        recipient availability, and other factors. If a delivery cannot be completed (for example, an unreachable
        recipient or incorrect address), we will attempt to contact the vendor or recipient and may return,
        hold, or reschedule the parcel.
    </p>

    <h2>9. Liability and claims</h2>
    <p>
        To the maximum extent permitted by law, Parcelman is not liable for indirect, incidental, or
        consequential losses, or for loss or damage arising from inaccurate information you provide, improper
        packaging, or prohibited contents. Any claim for loss of or damage to a parcel must be reported to us
        promptly and, in any case, within the period communicated during onboarding. Our total liability for a
        parcel is limited as permitted by applicable law.
    </p>

    <h2>10. Cancellations</h2>
    <p>
        You may cancel a pickup request in accordance with the options available in the app. Cancellations after
        a parcel has been collected or while in transit may be subject to charges or handling arrangements.
    </p>

    <h2>11. Intellectual property</h2>
    <p>
        The Services, including the Parcelman name, logo, software, and content, are owned by us or our licensors
        and are protected by law. We grant you a limited, non-exclusive, non-transferable right to use the
        Services for their intended purpose. You may not copy, modify, or distribute them without our permission.
    </p>

    <h2>12. Suspension and termination</h2>
    <p>
        We may suspend or terminate your access if you breach these Terms, misuse the Services, or create risk or
        legal exposure. You may stop using the Services and request account deletion at any time. Provisions that
        by their nature should survive termination will continue to apply.
    </p>

    <h2>13. Disclaimers</h2>
    <p>
        The Services are provided "as is" and "as available" without warranties of any kind, whether express or
        implied, to the maximum extent permitted by law, including warranties of merchantability, fitness for a
        particular purpose, and uninterrupted or error-free operation.
    </p>

    <h2>14. Governing law</h2>
    <p>
        These Terms are governed by the laws of the Republic of Ghana, and the courts of Ghana have jurisdiction
        over any dispute arising from them, without regard to conflict-of-laws principles.
    </p>

    <h2>15. Changes to these Terms</h2>
    <p>
        We may update these Terms from time to time. When we do, we will revise the "Last updated" date above and,
        where appropriate, notify you through the Services. Your continued use after changes take effect means you
        accept the updated Terms.
    </p>

    <h2>16. Contact us</h2>
    <p>Questions about these Terms? Contact us:</p>
    <ul>
        <li><strong>Email:</strong> <a href="mailto:{{ $email }}">{{ $email }}</a></li>
        @if($phone)
            <li><strong>Phone:</strong> <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}">{{ $phone }}</a></li>
        @endif
        @if($address)
            <li><strong>Address:</strong> {{ $address }}</li>
        @endif
    </ul>
@endsection
