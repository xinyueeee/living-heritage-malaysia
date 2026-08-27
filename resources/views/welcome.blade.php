@extends('layouts.app')

@section('title', 'Living Heritage Malaysia')

@section('content')
    <div class="home-page">
    <section class="hero home-hero" style="background-image: url('{{ asset('images/home/homepage.png') }}');">
            <div class="hero-overlay"></div>
            <div class="container hero-content">
                <h1>Celebrate Malaysia's Living Heritage</h1>
                <p class="hero-text">Discover cultural experiences, join vibrant communities, and stay connected to the traditions that make Malaysia unique.</p>

                <form class="search-bar" action="{{ route('experiences.index') }}" method="get">
                    <label class="sr-only" for="home-experience-search">Search cultural experiences</label>
                    <input id="home-experience-search" name="search" type="search" placeholder="Search cultural experiences, locations, festivals..." autocomplete="off">
                    <button type="submit">Search</button>
                </form>
            </div>
        </section>

        <section class="home-shortcuts" aria-label="Explore Living Heritage Malaysia">
            <div class="container shortcut-grid">
                <a class="shortcut-item" href="{{ route('experiences.index') }}">
                    <span class="shortcut-icon"><x-home-icon name="discover" /></span>
                    <strong>Discover</strong>
                    <span>Explore cultural experiences</span>
                </a>
                <span class="shortcut-item shortcut-disabled">
                    <span class="shortcut-icon"><x-home-icon name="community" /></span>
                    <strong>Community</strong>
                    <span>Join communities and discussions</span>
                </span>
                @if ($festivalType)
                    <a class="shortcut-item" href="{{ route('festival.calendar') }}">
                        <span class="shortcut-icon"><x-home-icon name="bell" /></span>
                        <strong>Festival Alert</strong>
                        <span>Browse upcoming festivals</span>
                    </a>
                @else
                    <span class="shortcut-item shortcut-disabled">
                        <span class="shortcut-icon"><x-home-icon name="bell" /></span>
                        <strong>Festival Alert</strong>
                        <span>Festival integration pending</span>
                    </span>
                @endif
                <a class="shortcut-item" href="{{ route('engagement.index') }}">
                    <span class="shortcut-icon"><x-home-icon name="gift" /></span>
                    <strong>Engagement &amp; Rewards</strong>
                    <span>Earn points and unlock rewards</span>
                </a>
                <a class="shortcut-item" href="{{ route('engagement.history') }}">
                    <span class="shortcut-icon"><x-home-icon name="heart" /></span>
                    <strong>My Activities</strong>
                    <span>See your cultural journey</span>
                </a>
            </div>
        </section>

        <section class="featured-section section" id="experiences">
            <div class="container">
                <div class="section-heading">
                    <h2>Featured Experiences</h2>
                    <a class="section-link" href="{{ route('experiences.index') }}">View All <span aria-hidden="true">&rarr;</span></a>
                </div>

                @if ($experiences->isEmpty())
                    <div class="no-data"><p>No cultural experiences are available at the moment.</p></div>
                @else
                    <div class="experience-grid home-experience-grid">
                        @foreach ($experiences as $experience)
                            @include('components.experience-card', ['experience' => $experience, 'variant' => 'home'])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="home-section home-festivals-section">
            <div class="container">
                <div class="section-heading">
                    <h2>Upcoming Festivals</h2>
                    @if ($festivalType)
                        <a class="section-link" href="{{ route('experiences.index', ['type' => $festivalType->type_id]) }}">View All <span aria-hidden="true">&rarr;</span></a>
                    @endif
                </div>

                @if ($festivals->isEmpty())
                    <div class="no-data"><p>No upcoming festivals are available at the moment.</p></div>
                @else
                    <div class="festival-grid">
                        @foreach ($festivals as $festival)
                            @include('components.experience-card', ['experience' => $festival, 'variant' => 'festival'])
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="home-section home-community-section" aria-labelledby="community-highlights-heading">
            <div class="container">
                <div class="section-heading">
                    <div class="heading-with-note">
                        <h2 id="community-highlights-heading">Community Highlights</h2>
                        <span class="sr-only">Presentation-only preview.</span>
                    </div>
                    <span class="section-link section-link-disabled" aria-disabled="true">View All Communities <span aria-hidden="true">&rarr;</span></span>
                </div>

                <div class="community-preview-grid">
                    <article class="community-preview-card">
                        <div class="community-preview-media community-preview-food"><x-home-icon name="food" /></div>
                        <div class="community-preview-content">
                            <span class="community-avatar"><x-home-icon name="community" /></span>
                            <h3>Heritage Food Enthusiasts</h3>
                            <p>Exploring and preserving Malaysia's traditional culinary heritage.</p>
                            <span class="outline-button" aria-disabled="true">Join Community</span>
                        </div>
                    </article>
                    <article class="community-preview-card">
                        <div class="community-preview-media community-preview-arts"><x-home-icon name="arts" /></div>
                        <div class="community-preview-content">
                            <span class="community-avatar"><x-home-icon name="community" /></span>
                            <h3>Wayang Kulit Fans</h3>
                            <p>Appreciating the art of traditional Malaysian shadow puppetry.</p>
                            <span class="outline-button" aria-disabled="true">Join Community</span>
                        </div>
                    </article>
                    <article class="community-preview-card">
                        <div class="community-preview-media community-preview-crafts"><x-home-icon name="craft" /></div>
                        <div class="community-preview-content">
                            <span class="community-avatar"><x-home-icon name="community" /></span>
                            <h3>Traditional Craft Lovers</h3>
                            <p>Preserving Malaysian traditional crafts, techniques, and skills.</p>
                            <span class="outline-button" aria-disabled="true">Join Community</span>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="home-section home-membership-section" aria-label="Passport and community benefits preview">
            <div class="container membership-grid">
                <article class="passport-preview">
                    <div class="passport-book" aria-hidden="true">
                        <span>Living Heritage<br>Malaysia</span>
                        <strong>&#10047;</strong>
                    </div>

                    <div class="explorer-preview">
                        <span>Digital Cultural Passport</span>

                        @auth
                            <h2>{{ $passportStampCount }} Stamps</h2>
                            <p>Continue exploring to collect more stamps.</p>

                            <a
                                class="button button-light-static"
                                href="{{ route('engagement.passport') }}"
                            >
                                View My Passport
                            </a>
                        @else
                            <h2>Start Exploring</h2>
                            <p>Log in to collect stamps and unlock badges.</p>

                            <a
                                class="button button-light-static"
                                href="{{ route('login') }}"
                            >
                                Log In to Begin
                            </a>
                        @endauth
                    </div>

                    <div class="badges-preview">
                        <span>Recent Stamps</span>

                        @auth
                            <div class="badge-preview-grid">
                                @forelse ($recentStamps as $userStamp)
                                    <img
                                        src="{{ asset($userStamp->stamp->stamp_image ?? 'images/default-stamp.png') }}"
                                        alt="{{ $userStamp->stamp->stamp_name ?? 'Passport stamp' }}"
                                        title="{{ $userStamp->stamp->stamp_name ?? 'Passport stamp' }}"
                                    >
                                @empty
                                    <small>No stamps collected yet.</small>
                                @endforelse
                            </div>
                        @else
                            <small>Log in to see your stamps.</small>
                        @endauth
                    </div>
                </article>

                <aside class="why-join-preview">
                    <span class="sr-only">Presentation-only community benefits preview.</span>
                    <h2>Why Join Our Community?</h2>
                    <ul>
                        <li><span><x-home-icon name="globe" /></span><div><strong>Discover Authentic Heritage</strong><small>Explore unique cultural experiences across Malaysia.</small></div></li>
                        <li><span><x-home-icon name="community" /></span><div><strong>Connect with Enthusiasts</strong><small>Meet people who share the same passion.</small></div></li>
                        <li><span><x-home-icon name="gift" /></span><div><strong>Earn Badges &amp; Rewards</strong><small>Participate, contribute, and unlock rewards.</small></div></li>
                        <li><span><x-home-icon name="bell" /></span><div><strong>Stay Updated with Festivals</strong><small>Never miss important cultural events.</small></div></li>
                    </ul>
                </aside>
            </div>
        </section>

        <section class="home-community-callout">
            <div class="container callout-inner">
                <span class="callout-icon"><x-home-icon name="community" /></span>
                <div>
                    <h2>Be Part of Malaysia's Living Heritage Movement</h2>
                    <p>Share your stories, explore cultural experiences, and help preserve our shared heritage for future generations.</p>
                </div>
                <span class="button button-primary button-disabled" aria-disabled="true">Join the Community Now <span aria-hidden="true">&rarr;</span></span>
            </div>
        </section>
    </div>
@endsection
