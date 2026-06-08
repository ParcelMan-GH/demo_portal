{{--
    Shared chrome for Parcelman legal pages (Privacy Policy, Terms of Service).
    Child views provide @section('legal_content').

    NOTE FOR MAINTAINERS: The legal copy in the child views is a tailored,
    good-faith template grounded in how the app actually handles data. It is
    NOT legal advice and must be reviewed by qualified counsel (Ghana Data
    Protection Act, 2012 and Apple/Google app-store policies) before go-live.
--}}
@extends('web.layouts.portal')

@section('content')
@php($embedded = request()->boolean('app'))
<main class="legal-page {{ $embedded ? 'is-embedded' : '' }}">
    @unless($embedded)
    <header class="legal-header">
        <a href="{{ route('web.landing') }}" class="legal-logo" aria-label="Parcelman home">
            <img src="{{ asset('logo-2.png') }}" alt="Parcelman">
        </a>
        <a href="{{ route('web.landing') }}" class="legal-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Back to home
        </a>
    </header>
    @endunless

    <article class="legal-wrap">
        <p class="legal-kicker">@yield('legal_kicker', 'Legal')</p>
        <h1 class="legal-title">@yield('legal_heading')</h1>
        <p class="legal-updated">Last updated: @yield('legal_updated')</p>

        <div class="legal-prose">
            @yield('legal_content')
        </div>
    </article>

    @unless($embedded)
    <footer class="legal-footer">
        <span>&copy; {{ date('Y') }} Parcelman Express. All rights reserved.</span>
        <nav class="legal-footer-links" aria-label="Legal">
            <a href="{{ route('web.privacy') }}">Privacy Policy</a>
            <a href="{{ route('web.terms') }}">Terms of Service</a>
        </nav>
    </footer>
    @endunless
</main>

<style>
    .legal-page {
        --lg-ink: #111827;
        --lg-muted: #647084;
        --lg-line: #e8e1d9;
        --lg-paper: #fffdf8;
        --lg-orange: #f97316;
        --lg-orange-dark: #c2410c;

        min-height: 100vh;
        background: var(--lg-paper);
        color: var(--lg-ink);
        font-family: "Plus Jakarta Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .legal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        max-width: 880px;
        margin: 0 auto;
        padding: 1.25rem clamp(1rem, 4vw, 2rem);
        border-bottom: 1px solid var(--lg-line);
    }

    .legal-logo img {
        display: block;
        width: 92px;
        height: auto;
    }

    .legal-back {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        color: var(--lg-ink);
        font-size: 0.88rem;
        font-weight: 800;
        text-decoration: none;
    }

    .legal-back:hover { color: var(--lg-orange-dark); }

    .legal-wrap {
        max-width: 880px;
        margin: 0 auto;
        padding: clamp(2.5rem, 6vw, 4rem) clamp(1rem, 4vw, 2rem);
    }

    /* Embedded in the mobile app's in-app WebView (no web header/footer chrome). */
    .legal-page.is-embedded .legal-wrap {
        padding-top: clamp(1.5rem, 5vw, 2rem);
    }

    .legal-kicker {
        margin: 0 0 0.6rem;
        color: var(--lg-orange);
        font-size: 0.76rem;
        font-weight: 900;
        letter-spacing: 0.09em;
        text-transform: uppercase;
    }

    .legal-title {
        margin: 0;
        font-size: clamp(2.1rem, 5vw, 3.4rem);
        font-weight: 900;
        line-height: 1.05;
    }

    .legal-updated {
        margin: 0.85rem 0 0;
        color: var(--lg-muted);
        font-size: 0.9rem;
        font-weight: 700;
    }

    .legal-prose {
        margin-top: 2.5rem;
        color: #344054;
        font-size: 1rem;
        line-height: 1.8;
    }

    .legal-prose > p:first-child { margin-top: 0; }

    .legal-prose h2 {
        margin: 2.4rem 0 0.9rem;
        color: var(--lg-ink);
        font-size: clamp(1.3rem, 2.5vw, 1.65rem);
        font-weight: 900;
        line-height: 1.2;
    }

    .legal-prose h3 {
        margin: 1.6rem 0 0.6rem;
        color: var(--lg-ink);
        font-size: 1.05rem;
        font-weight: 800;
    }

    .legal-prose p { margin: 0 0 1rem; }

    .legal-prose ul {
        margin: 0 0 1.2rem;
        padding-left: 1.3rem;
        display: grid;
        gap: 0.5rem;
    }

    .legal-prose li { line-height: 1.7; }

    .legal-prose a {
        color: var(--lg-orange-dark);
        font-weight: 700;
        text-decoration: none;
    }

    .legal-prose a:hover { text-decoration: underline; }

    .legal-prose strong { color: var(--lg-ink); }

    .legal-prose .legal-intro {
        padding: 1.1rem 1.25rem;
        border: 1px solid var(--lg-line);
        border-left: 3px solid var(--lg-orange);
        border-radius: 10px;
        background: #fff;
    }

    .legal-footer {
        max-width: 880px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem;
        padding: 1.5rem clamp(1rem, 4vw, 2rem);
        border-top: 1px solid var(--lg-line);
        color: var(--lg-muted);
        font-size: 0.85rem;
    }

    .legal-footer-links {
        display: flex;
        gap: 1.25rem;
    }

    .legal-footer-links a {
        color: var(--lg-muted);
        font-weight: 800;
        text-decoration: none;
    }

    .legal-footer-links a:hover { color: var(--lg-orange-dark); }

    @media (max-width: 560px) {
        .legal-footer { flex-direction: column; align-items: flex-start; }
    }
</style>
@endsection
