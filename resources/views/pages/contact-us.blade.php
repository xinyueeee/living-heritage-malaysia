@extends('layouts.app')

@section('title', 'Contact Us - Living Heritage Malaysia')

@section('content')
    <section class="static-page-hero">
        <div class="container">
            <h1>Contact Us</h1>
            <p>Have a question, suggestion, or found an issue? We'd love to hear from you.</p>
        </div>
    </section>

    <section class="static-page-content">
        <div class="container">
            <p>
                We don't currently have social media channels, so email is the best way to reach the
                Living Heritage Malaysia team.
            </p>

            <div class="contact-methods">
                <div class="contact-method">
                    <span class="contact-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                    </span>
                    <h3>General Enquiries</h3>
                    <a href="mailto:hello@livingheritagemalaysia.my">hello@livingheritagemalaysia.my</a>
                </div>

                <div class="contact-method">
                    <span class="contact-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><circle cx="12" cy="12" r="9"/></svg>
                    </span>
                    <h3>Support &amp; Help</h3>
                    <a href="mailto:support@livingheritagemalaysia.my">support@livingheritagemalaysia.my</a>
                </div>
            </div>

        </div>
    </section>
@endsection
