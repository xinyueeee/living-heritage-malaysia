<article class="experience-card">
    <img
        class="card-image"
        src="{{ $experience->experiences_image_url }}"
        alt="{{ $experience->experiences_title }}"
    >

    <div class="card-content">
        <span class="tag">{{ $experience->experiences_category }}</span>
        <h3>{{ $experience->experiences_title }}</h3>
        <p class="location">&#128205; {{ $experience->experiences_location }}</p>
        <p>{{ $experience->experiences_desc }}</p>
        <p class="price">RM {{ number_format((float) $experience->experiences_price, 2) }}</p>
        <p class="duration">&#9201; {{ $experience->experiences_duration }}</p>
    </div>
</article>
