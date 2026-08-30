@extends('layouts.app')

@section('title', 'Terms of Use - Living Heritage Malaysia')

@section('content')
    <section class="static-page-hero">
        <div class="container">
            <h1>Terms of Use</h1>
            <p>The rules for using the Living Heritage Malaysia platform.</p>
        </div>
    </section>

    <section class="static-page-content">
        <div class="container">
            <p class="static-page-updated">Last updated: {{ now()->format('d F Y') }}</p>

            <h2>1. Acceptance of Terms</h2>
            <p>
                By creating an account or using Living Heritage Malaysia, you agree to these Terms of Use and
                our <a href="{{ route('pages.privacy-policy') }}">Privacy Policy</a>.
            </p>

            <h2>2. Your Account</h2>
            <p>
                You sign in using Google authentication. You are responsible for maintaining the security of
                your account and for all activity that happens under it.
            </p>

            <h2>3. Community Content</h2>
            <p>
                When you post experiences, comments, photos, or albums, you keep ownership of your content but
                grant us permission to display it within the platform. Do not post content that is illegal,
                offensive, or that you do not have the rights to share.
            </p>

            <h2>4. Acceptable Use</h2>
            <ul>
                <li>Do not misuse, disrupt, or attempt to gain unauthorized access to the platform</li>
                <li>Do not post spam, harassment, or misleading information</li>
                <li>Respect the cultural communities and content shared by other users</li>
            </ul>

            <h2>5. Intellectual Property</h2>
            <p>
                The Living Heritage Malaysia name, logo, and platform design belong to the project team.
                Cultural experience details are provided for informational purposes and may reference
                third-party sites and events.
            </p>

            <h2>6. Termination</h2>
            <p>
                We may suspend or remove accounts that violate these terms. You may stop using the platform and
                request account removal at any time.
            </p>

            <h2>7. Disclaimer</h2>
            <p>
                Living Heritage Malaysia is provided "as is". We do our best to keep experience and festival
                information accurate, but we cannot guarantee it is always complete or up to date.
            </p>

            <h2>8. Governing Law</h2>
            <p>These terms are governed by the laws of Malaysia.</p>

            <h2>9. Contact</h2>
            <p>
                Questions about these terms can be sent to
                <a href="mailto:hello@livingheritagemalaysia.my">hello@livingheritagemalaysia.my</a>.
            </p>

        </div>
    </section>
@endsection
