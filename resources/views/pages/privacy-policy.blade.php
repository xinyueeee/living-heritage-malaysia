@extends('layouts.app')

@section('title', 'Privacy Policy - Living Heritage Malaysia')

@section('content')
    <section class="static-page-hero">
        <div class="container">
            <h1>Privacy Policy</h1>
            <p>How Living Heritage Malaysia collects, uses, and protects your information.</p>
        </div>
    </section>

    <section class="static-page-content">
        <div class="container">
            <p class="static-page-updated">Last updated: {{ now()->format('d F Y') }}</p>

            <h2>1. Information We Collect</h2>
            <ul>
                <li>Account details from Google Sign-In, such as your name, email address, and profile photo</li>
                <li>Profile information you add, such as your bio, birthday, gender, and cultural interests</li>
                <li>Content you create, such as community posts, comments, photos, and albums</li>
                <li>Activity data, such as experiences you search for, view, save, or complete</li>
            </ul>

            <h2>2. How We Use Your Information</h2>
            <ul>
                <li>To personalize your recommendations and homepage content</li>
                <li>To track your Digital Cultural Passport stamps and achievements</li>
                <li>To send festival or experience alerts you've opted into</li>
                <li>To display your posts, comments, and photos within the community</li>
            </ul>

            <h2>3. Sharing of Information</h2>
            <p>
                We do not sell your personal information. Content you choose to post publicly (such as
                community posts and comments) is visible to other users. We do not share your data with third
                parties except as needed to operate the platform (for example, authentication via Google).
            </p>

            <h2>4. Data Retention</h2>
            <p>
                We keep your account and activity data for as long as your account is active. You can clear
                your recorded discovery activity at any time from your profile, or request account deletion by
                contacting us.
            </p>

            <h2>5. Your Choices</h2>
            <ul>
                <li>Update or remove your personal information from your Profile at any time</li>
                <li>Clear your recent search and view history from Recent Activity</li>
                <li>Unsubscribe from festival alerts from the Festival Alert page</li>
            </ul>

            <h2>6. Changes to This Policy</h2>
            <p>
                We may update this policy as the platform evolves. Continued use of Living Heritage Malaysia
                after changes are posted means you accept the updated policy.
            </p>

            <h2>7. Contact Us</h2>
            <p>
                Questions about this policy can be sent to
                <a href="mailto:hello@livingheritagemalaysia.my">hello@livingheritagemalaysia.my</a>.
            </p>

        </div>
    </section>
@endsection
