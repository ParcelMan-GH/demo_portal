{{--
    Privacy Policy — tailored to Parcelman's actual data practices.
    NOT legal advice: have qualified counsel review before publishing and
    update the "legal_updated" date below to the real publication date.
--}}
@extends('web.layouts.legal')

@php
    $email = trim((string) \App\Models\PlatformSetting::getValue('platform_email', '')) ?: 'support@parcelman.com';
    $phone = trim((string) \App\Models\PlatformSetting::getValue('platform_phone', ''));
    $address = trim((string) \App\Models\PlatformSetting::getValue('platform_address', ''));
@endphp

@section('title', 'Privacy Policy | Parcelman Express')
@section('meta_description', 'How Parcelman Express collects, uses, shares, and protects personal information across its parcel pickup and delivery service in Ghana.')

@section('legal_kicker', 'Privacy')
@section('legal_heading', 'Privacy Policy')
@section('legal_updated', '7 June 2026')

@section('legal_content')
    <p class="legal-intro">
        This Privacy Policy explains how Parcelman Express ("Parcelman", "we", "us", or "our") collects, uses,
        shares, and protects personal information when you use our mobile app, websites, and related parcel
        pickup and delivery services (together, the "Services"). By using the Services, you agree to the
        practices described here.
    </p>

    <h2>1. Who we are</h2>
    <p>
        Parcelman Express provides app-led parcel pickup and delivery for vendors, shops, and social sellers in
        Ghana. We are the controller responsible for the personal information processed through the Services.
        For any privacy question or request, contact us using the details in the "Contact us" section below.
    </p>

    <h2>2. Information we collect</h2>
    <h3>Information you provide</h3>
    <ul>
        <li><strong>Account details</strong> — your name, mobile phone number, and email address.</li>
        <li><strong>Verification data</strong> — one-time passcodes (OTPs) sent to your phone to confirm your identity at sign-in and registration.</li>
        <li><strong>Vendor / business profile</strong> — business name, pickup locations, and related profile information.</li>
        <li><strong>Parcel and recipient details</strong> — package descriptions, photos of packages, pickup and destination addresses, and the names and phone numbers of the recipients you ask us to deliver to.</li>
        <li><strong>Payment and payout information</strong> — amounts charged, payment status, and the mobile-money or bank details you provide to receive vendor payouts or to record recipient payment on delivery.</li>
        <li><strong>Communications</strong> — messages, support requests, and feedback you send us.</li>
    </ul>
    <h3>Information collected automatically</h3>
    <ul>
        <li><strong>Location data</strong> — with your permission, GPS location from riders' and vendors' devices to enable pickup, transport, and delivery (for example, navigating to a pickup or delivery point).</li>
        <li><strong>Device and usage data</strong> — device type, operating system, app version, and app activity used to operate and improve the Services.</li>
        <li><strong>Push notification tokens</strong> — identifiers used to send delivery and status notifications to your device.</li>
    </ul>

    <h2>3. How we use information</h2>
    <ul>
        <li>Create and manage your account and verify your identity by OTP.</li>
        <li>Accept, route, transport, and deliver parcels, and keep delivery records and history.</li>
        <li>Coordinate pickups and deliveries between vendors, riders, warehouse staff, and recipients.</li>
        <li>Process charges, record payments, and manage vendor payouts.</li>
        <li>Send transactional and delivery-status notifications.</li>
        <li>Provide customer support and respond to your requests.</li>
        <li>Maintain the security, integrity, and reliability of the Services, and prevent fraud and abuse.</li>
        <li>Comply with legal obligations and enforce our Terms of Service.</li>
    </ul>

    <h2>4. How we share information</h2>
    <p>We do not sell your personal information. We share it only as needed to provide the Services:</p>
    <ul>
        <li><strong>Operational parties</strong> — riders, warehouse staff, and recipients receive the details necessary to complete a pickup or delivery (for example, a recipient's name, phone number, and address are shared with the assigned rider).</li>
        <li><strong>Service providers</strong> — trusted vendors that support the Services, such as SMS/OTP gateways, cloud hosting and file storage (including Amazon Web Services), push-notification infrastructure (such as Firebase), and payment or mobile-money providers. They may process information only on our instructions.</li>
        <li><strong>Legal and safety</strong> — authorities or third parties where required by law, to protect our rights, or to prevent harm or fraud.</li>
        <li><strong>Business transfers</strong> — in connection with a merger, acquisition, or sale of assets, subject to this Policy.</li>
    </ul>

    <h2>5. Recipient information provided by vendors</h2>
    <p>
        Vendors enter information about their customers (recipients) so we can deliver parcels. If you are a
        vendor, you are responsible for ensuring you have a lawful basis to share recipient information with us
        and that recipients are aware their details will be used for delivery. We process recipient information
        only to perform the delivery and related communications.
    </p>

    <h2>6. Data retention</h2>
    <p>
        We keep personal information for as long as your account is active and as needed to provide the Services,
        maintain delivery and financial records, resolve disputes, and meet legal, accounting, or reporting
        requirements. When information is no longer needed, we delete or anonymise it.
    </p>

    <h2>7. Security</h2>
    <p>
        We use reasonable technical and organisational measures to protect personal information against
        unauthorised access, loss, or misuse. No method of transmission or storage is completely secure, so we
        cannot guarantee absolute security.
    </p>

    <h2>8. Your rights and choices</h2>
    <ul>
        <li><strong>Access and correction</strong> — view and update your profile information in the app, or contact us for assistance.</li>
        <li><strong>Account deletion</strong> — you can request deletion of your account directly in the app (Account &rarr; Delete Account) or by contacting us. Some records may be retained where required by law.</li>
        <li><strong>Location and notifications</strong> — you can control location and push-notification permissions through your device settings; disabling them may limit certain features.</li>
    </ul>

    <h2>9. Children</h2>
    <p>
        The Services are intended for adults operating or transacting with businesses. They are not directed to
        children, and we do not knowingly collect personal information from children. If you believe a child has
        provided us information, contact us so we can remove it.
    </p>

    <h2>10. Third-party links and services</h2>
    <p>
        The Services may link to or rely on third-party services (for example, maps or payment providers). Their
        use of your information is governed by their own privacy policies, and we are not responsible for their
        practices.
    </p>

    <h2>11. Changes to this Policy</h2>
    <p>
        We may update this Privacy Policy from time to time. When we do, we will revise the "Last updated" date
        above and, where appropriate, notify you through the Services. Your continued use after changes take
        effect means you accept the updated Policy.
    </p>

    <h2>12. Contact us</h2>
    <p>If you have questions or requests about this Policy or your personal information, contact us:</p>
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
