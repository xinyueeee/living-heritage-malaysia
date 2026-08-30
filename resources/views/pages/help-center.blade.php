@extends('layouts.app')

@section('title', 'Help Center - Living Heritage Malaysia')

@section('content')
    <section class="static-page-hero">
        <div class="container">
            <h1>Help Center</h1>
            <p>Answers to common questions about using Living Heritage Malaysia.</p>
        </div>
    </section>

    <section class="static-page-content">
        <div class="container">
            <div class="help-topics">
                <div class="help-topic">
                    <h3>Getting Started</h3>
                    <p>
                        Browse cultural experiences and festivals from the <strong>Discover</strong> page without
                        an account. To save experiences, join communities, or collect passport stamps, log in
                        with your Google account from the top navigation.
                    </p>
                </div>

                <div class="help-topic">
                    <h3>Account &amp; Profile</h3>
                    <p>
                        Update your personal information, interests, and profile photo from your
                        <a href="{{ route('profile') }}">Profile</a> page. Your cultural interests are used to
                        personalize your recommendations and homepage recommendations.
                    </p>
                </div>

                <div class="help-topic">
                    <h3>Digital Cultural Passport &amp; Stamps</h3>
                    <p>
                        Complete a cultural experience and share it in the Community to collect its category
                        stamp. Track your stamps and achievements from the
                        <a href="{{ route('engagement.index') }}">Engagement &amp; Rewards</a> page.
                    </p>
                </div>

                <div class="help-topic">
                    <h3>Saved Experiences &amp; Posts</h3>
                    <p>
                        Tap the save icon on any experience or community post to keep it for later. Find
                        everything you've saved under Profile &rarr; Saved Experiences or Saved Posts.
                    </p>
                </div>

                <div class="help-topic">
                    <h3>Festival Alerts</h3>
                    <p>
                        Choose the categories you're interested in and we'll email you when new matching
                        festivals or experiences are added. Set this up from the Festival Alert page.
                    </p>
                </div>

                <div class="help-topic">
                    <h3>Still Need Help?</h3>
                    <p>
                        Can't find what you're looking for? Reach out from our
                        <a href="{{ route('pages.contact-us') }}">Contact Us</a> page and we'll get back to you.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
