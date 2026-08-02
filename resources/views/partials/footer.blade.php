<footer class="site-footer" id="footer">
    <div class="container footer-grid">
        <div class="footer-about">
            <a class="brand footer-brand" href="{{ route('home') }}">
                <span class="brand-mark" aria-hidden="true">LH</span>
                <span>Living Heritage<br><strong>Malaysia</strong></span>
            </a>
            <p>A community-driven platform celebrating and preserving Malaysia's living heritage.</p>
            <div class="footer-socials" aria-label="Social links preview">
                <span aria-disabled="true" aria-label="Facebook">f</span>
                <span aria-disabled="true" aria-label="Instagram">&#9678;</span>
                <span aria-disabled="true" aria-label="YouTube">&#9654;</span>
                <span aria-disabled="true" aria-label="TikTok">&#9835;</span>
            </div>
        </div>

        <div class="footer-column">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="{{ route('home') }}">Home</a></li>
                <li><a href="{{ route('experiences.index') }}">Cultural Experiences</a></li>
                <li><a href="#" aria-disabled="true">For You</a></li>
                <li><a href="#" aria-disabled="true">Community</a></li>
                <li><a href="#" aria-disabled="true">Passport &amp; Rewards</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h3>Support</h3>
            <ul>
                <li><a href="#" aria-disabled="true">About Us</a></li>
                <li><a href="#" aria-disabled="true">Help Center</a></li>
                <li><a href="#" aria-disabled="true">Contact Us</a></li>
                <li><a href="#" aria-disabled="true">Privacy Policy</a></li>
                <li><a href="#" aria-disabled="true">Terms of Use</a></li>
            </ul>
        </div>

        <div class="footer-column footer-newsletter">
            <h3>Newsletter</h3>
            <p>Subscribe to get updates on festivals and cultural experiences.</p>
            <form class="newsletter-form" onsubmit="return false">
                <label class="sr-only" for="newsletter-email">Email address</label>
                <input id="newsletter-email" type="email" placeholder="Enter your email" autocomplete="off">
                <button type="submit" aria-label="Subscribe">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>
                </button>
            </form>
        </div>
    </div>

    <div class="container footer-bottom">
        <p class="copyright">&copy; {{ now()->year }} Living Heritage Malaysia. All rights reserved.</p>
    </div>
</footer>
