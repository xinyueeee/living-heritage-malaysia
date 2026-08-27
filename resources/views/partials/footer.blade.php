<footer class="site-footer" id="footer">
    <div class="container footer-grid">
        <div class="footer-about">
            <a class="site-logo footer-logo" href="{{ route('home') }}">
                <img src="{{ asset('images/home/logo-transparent.png') }}" alt="Living Heritage Malaysia">
            </a>
            <p>A community-driven platform celebrating and preserving Malaysia's living heritage.</p>
        </div>

        <div class="footer-column">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="{{ route('home') }}">Home</a></li>
                <li><a href="{{ route('experiences.index') }}">Discover</a></li>
                <li><a href="{{ route('community.index') }}">Community</a></li>
                <li><a href="{{ route('festival.calendar') }}">Festival Alert</a></li>
                <li><a href="{{ route('engagement.index') }}">Engagement &amp; Rewards</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h3>Support</h3>
            <ul>
                <li><a href="{{ route('pages.about') }}">About Us</a></li>
                <li><a href="{{ route('pages.help-center') }}">Help Center</a></li>
                <li><a href="{{ route('pages.contact-us') }}">Contact Us</a></li>
                <li><a href="{{ route('pages.privacy-policy') }}">Privacy Policy</a></li>
                <li><a href="{{ route('pages.terms-of-use') }}">Terms of Use</a></li>
            </ul>
        </div>

        <div class="footer-column footer-newsletter">
            <h3>Festival Alerts</h3>
            @auth
                <p>Get notified by email when new experiences match your interests.</p>
                <a class="button-light-static" href="{{ route('alerts.create') }}">Set Up Alerts</a>
            @else
                <p>Log in to get personalized festival &amp; experience alerts by email.</p>
                <a class="button-light-static" href="{{ route('festival.login-required') }}">Log In for Alerts</a>
            @endauth
        </div>
    </div>

    <div class="container footer-bottom">
        <p class="copyright">&copy; {{ now()->year }} Living Heritage Malaysia. All rights reserved.</p>
    </div>
</footer>
