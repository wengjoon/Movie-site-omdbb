@extends('layouts.app')
@if(!isset($movie['title']))
    @php abort(404) @endphp
@endif
{{-- SEO Meta Tags: Dynamic for movie details with movie title first --}}
@php
    $movieTitle = $movie['title'] ?? 'Movie';
    $releaseYear = isset($movie['release_date']) ? ' (' . substr($movie['release_date'], 0, 4) . ')' : '';
    
    // Title format: "Movie Title (Year)" - site name at the end
    $seoTitle = $movieTitle . $releaseYear . ' - Watch Free HD';
    
    // Description focusing on the movie first
    $overview = isset($movie['overview']) ? str_replace('"', "'", mb_substr($movie['overview'], 0, 155)) . '...' : '';
    $seoDescription = "{$movieTitle}{$releaseYear}: {$overview} Watch free on 123 Movies Pro.";
    
    $genres = [];
    if(isset($movie['genres'])) {
        foreach($movie['genres'] as $genre) {
            $genres[] = $genre['name'];
        }
    }
    
    // Keywords with movie title first
    $keywords = "{$movieTitle}, " . implode(', ', $genres) . ", 123 movies pro, watch free, stream online, HD quality";
    
    $posterImg = isset($movie['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'] : asset(config('seo.default_og_image'));
    
    // Rating values for schema markup
    $ratingValue = number_format($movie['vote_average'] ?? 0, 1);
    $ratingCount = $movie['vote_count'] ?? 0;
@endphp

@section('seo_title', $seoTitle)
@section('seo_description', $seoDescription)
@section('seo_keywords', $keywords)
@section('og_type', 'video.movie')
@section('og_image', $posterImg)
@section('twitter_image', $posterImg)

{{-- Structured data for movie with enhanced AggregateRating --}}
@section('structured_data')
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Movie",
  "name": "{{ $movie['title'] ?? '' }}",
  "description": "{{ $movie['overview'] ?? 'Watch this movie online for free in HD quality.' }}",
  @if(!empty($movie['poster_path']))
  "image": "https://image.tmdb.org/t/p/w500{{ $movie['poster_path'] }}",
  @endif
  @if(!empty($movie['release_date']))
  "datePublished": "{{ $movie['release_date'] }}",
  @endif
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "{{ $ratingValue }}",
    "bestRating": "10",
    "worstRating": "0",
    "ratingCount": "{{ $ratingCount }}",
    "reviewCount": "{{ $ratingCount }}"
  },
  @if(!empty($movie['directors']))
  "director": [
    @foreach($movie['directors'] as $index => $director)
    {
      "@type": "Person",
      "name": "{{ $director }}"
    }@if($index < count($movie['directors']) - 1),@endif
    @endforeach
  ],
  @endif
  @if(!empty($movie['top_cast']))
  "actor": [
    @foreach($movie['top_cast'] as $index => $actor)
    {
      "@type": "Person",
      "name": "{{ $actor }}"
    }@if($index < count($movie['top_cast']) - 1),@endif
    @endforeach
  ],
  @endif
  @if(isset($movie['genres']) && count($movie['genres']) > 0)
  "genre": [
    @foreach($movie['genres'] as $index => $genre)
    "{{ $genre['name'] }}"@if($index < count($movie['genres']) - 1),@endif
    @endforeach
  ],
  @endif
  "potentialAction": {
    "@type": "WatchAction",
    "target": "{{ url()->current() }}"
  }
}
</script>

{{-- Additionally adding review schema if reviews exist --}}
@if(isset($movie['reviews']) && count($movie['reviews']) > 0)
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Movie",
  "name": "{{ $movie['title'] ?? '' }}",
  "review": [
    @foreach($movie['reviews'] as $index => $review)
    {
      "@type": "Review",
      "reviewBody": "{{ str_replace('"', "'", $review['content']) ?? '' }}",
      "author": {
        "@type": "Person",
        "name": "{{ $review['author'] ?? 'Anonymous Reviewer' }}"
      },
      "reviewRating": {
        "@type": "Rating",
        "ratingValue": "{{ $review['rating'] ?? $movie['vote_average'] ?? 5 }}",
        "bestRating": "10",
        "worstRating": "0"
      },
      "datePublished": "{{ $review['created_at'] ?? date('Y-m-d') }}"
    }@if($index < count($movie['reviews']) - 1),@endif
    @endforeach
  ]
}
</script>
@endif
@endsection

@section('content')
{{-- Document title is just the movie name for browsers --}}
<title>{{ $movieTitle }}{{ $releaseYear }}</title>

{{-- Breadcrumbs navigation --}}
<div class="container">
    <nav aria-label="breadcrumb" class="mt-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('movies.index') }}">Home</a></li>
            @if(isset($movie['genres']) && count($movie['genres']) > 0)
                <li class="breadcrumb-item">
                    <a href="{{ route('movies.search', ['query' => $movie['genres'][0]['name']]) }}">
                        {{ $movie['genres'][0]['name'] }}
                    </a>
                </li>
            @endif
            <li class="breadcrumb-item active" aria-current="page">{{ $movie['title'] }}</li>
        </ol>
    </nav>
</div>

<!-- Hero Section with Backdrop -->
<div class="movie-hero">
    @if($movie['backdrop_path'])
        <div class="backdrop-wrapper">
            <img src="https://image.tmdb.org/t/p/original{{ $movie['backdrop_path'] }}" alt="{{ $movie['title'] }}" class="backdrop-image" loading="lazy">
            <div class="backdrop-overlay"></div>
        </div>
    @else
        <div class="backdrop-wrapper no-backdrop">
            <div class="backdrop-overlay"></div>
        </div>
    @endif

    <div class="container hero-content">
        <div class="row">
            <div class="col-md-8 offset-md-2 text-center">
                <h1 class="movie-hero-title">{{ $movie['title'] }}</h1>
                @if(isset($movie['tagline']) && !empty($movie['tagline']))
                    <p class="movie-tagline">{{ $movie['tagline'] }}</p>
                @endif
                <button id="playTrailerBtn" class="btn btn-danger btn-lg play-button mt-4">
                    <i class="bi bi-play-fill me-2"></i> Watch Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Movie Details Section -->
<div class="container movie-details">
    <div class="row">
        <!-- Movie Poster Column -->
        <div class="col-md-4 mb-4">
            <div class="poster-container">
                @if($movie['poster_path'])
                    <img src="https://image.tmdb.org/t/p/w500{{ $movie['poster_path'] }}" class="img-fluid movie-poster" alt="{{ $movie['title'] }}" loading="lazy">
                @else
                    <div class="no-poster d-flex align-items-center justify-content-center">
                        <i class="bi bi-film" style="font-size: 5rem;"></i>
                    </div>
                @endif
                
                <!-- On-page aggregate rating markup with visible elements -->
                <div class="rating-container mt-3">
                    <div class="aggregate-rating" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                        <meta itemprop="worstRating" content="0">
                        <meta itemprop="bestRating" content="10">
                        <meta itemprop="ratingValue" content="{{ $ratingValue }}">
                        <meta itemprop="ratingCount" content="{{ $ratingCount }}">
                        <meta itemprop="reviewCount" content="{{ $ratingCount }}">
                        
                        <div class="rating-display text-center">
                            <div class="rating-stars">
                                @for ($i = 1; $i <= 5; $i++)
                                    @if ($i <= round($ratingValue / 2))
                                        <i class="bi bi-star-fill text-warning"></i>
                                    @elseif ($i - 0.5 <= $ratingValue / 2)
                                        <i class="bi bi-star-half text-warning"></i>
                                    @else
                                        <i class="bi bi-star text-warning"></i>
                                    @endif
                                @endfor
                            </div>
                            <div class="rating-value fs-4 fw-bold">
                                {{ $ratingValue }}/10
                            </div>
                            <div class="rating-count text-muted">
                                Based on {{ number_format($ratingCount) }} ratings
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Movie Info Column -->
        <div class="col-md-8">
            <div class="movie-info-card">
                <!-- Hidden schema markup for the movie as a whole -->
                <div itemscope itemtype="https://schema.org/Movie" style="display:none;">
                    <meta itemprop="name" content="{{ $movie['title'] ?? '' }}">
                    <meta itemprop="description" content="{{ $movie['overview'] ?? '' }}">
                    @if(!empty($movie['poster_path']))
                    <meta itemprop="image" content="https://image.tmdb.org/t/p/w500{{ $movie['poster_path'] }}">
                    @endif
                    @if(!empty($movie['release_date']))
                    <meta itemprop="datePublished" content="{{ $movie['release_date'] }}">
                    @endif
                    <!-- Linked to the visible aggregateRating above -->
                </div>
                
                <div class="movie-meta">
                    @if(isset($movie['release_date']) && !empty($movie['release_date']))
                        <span class="movie-year">{{ \Carbon\Carbon::parse($movie['release_date'])->format('Y') }}</span>
                    @endif
                    
                    @if(isset($movie['runtime']) && $movie['runtime'] > 0)
                        <span class="movie-runtime">{{ floor($movie['runtime'] / 60) }}h {{ $movie['runtime'] % 60 }}m</span>
                    @endif
                    
                    <span class="movie-rating-badge">
                        <i class="bi bi-star-fill me-1"></i> {{ $ratingValue }}
                    </span>
                </div>
                
                @if(isset($movie['genres']) && count($movie['genres']) > 0)
                    <div class="movie-genres mb-4">
                        @foreach($movie['genres'] as $genre)
                            <a href="{{ route('movies.search', ['query' => $genre['name']]) }}" class="genre-badge">{{ $genre['name'] }}</a>
                        @endforeach
                    </div>
                @endif
                
                @if(isset($movie['overview']) && !empty($movie['overview']))
                    <div class="movie-overview">
                        {{ $movie['overview'] }}
                    </div>
                @endif
                
                <div class="movie-credits">
                    @if(count($movie['directors']) > 0)
                        <div class="credit-row">
                            <span class="credit-title">Director{{ count($movie['directors']) > 1 ? 's' : '' }}:</span>
                            <span class="credit-people">{{ implode(', ', $movie['directors']) }}</span>
                        </div>
                    @endif
                    
                    @if(count($movie['top_cast']) > 0)
                        <div class="credit-row">
                            <span class="credit-title">Cast:</span>
                            <span class="credit-people">{{ implode(', ', $movie['top_cast']) }}</span>
                        </div>
                    @endif
                </div>
                
                <div class="movie-actions mt-4">
                    <button id="playTrailerBtnAlt" class="btn btn-danger me-2">
                        <i class="bi bi-play-fill me-2"></i> Watch Now
                    </button>
                    
                    <a href="#" class="btn btn-outline-light">
                        <i class="bi bi-plus-lg me-2"></i> Add to List
                    </a>
                </div>
                
                <!-- Social Sharing Buttons -->
                <div class="mt-4 social-share">
                    <p class="text-muted mb-2">Share this movie:</p>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                        <i class="bi bi-facebook"></i> Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?text=Watch {{ urlencode($movie['title']) }}&url={{ urlencode(url()->current()) }}" target="_blank" class="btn btn-sm btn-outline-info me-2">
                        <i class="bi bi-twitter"></i> Twitter
                    </a>
                    <a href="https://wa.me/?text=Watch {{ urlencode($movie['title']) }}: {{ urlencode(url()->current()) }}" target="_blank" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User reviews section if available -->
    @if(isset($movie['reviews']) && count($movie['reviews']) > 0)
    <div class="row mt-5">
        <div class="col-12">
            <h2 class="mb-4">User Reviews</h2>
            
            <div class="reviews-container">
                @foreach($movie['reviews'] as $review)
                <div class="review-card mb-4" itemprop="review" itemscope itemtype="https://schema.org/Review">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <span itemprop="author" itemscope itemtype="https://schema.org/Person">
                                    <span itemprop="name" class="fw-bold">{{ $review['author'] ?? 'Anonymous' }}</span>
                                </span>
                                <meta itemprop="datePublished" content="{{ $review['created_at'] ?? date('Y-m-d') }}">
                                <span class="text-muted ms-2">{{ isset($review['created_at']) ? \Carbon\Carbon::parse($review['created_at'])->format('M d, Y') : date('M d, Y') }}</span>
                            </div>
                            <div class="review-rating" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
                                <meta itemprop="worstRating" content="0">
                                <meta itemprop="bestRating" content="10">
                                <meta itemprop="ratingValue" content="{{ $review['rating'] ?? $movie['vote_average'] ?? 5 }}">
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-star-fill me-1"></i>
                                    {{ number_format($review['rating'] ?? $movie['vote_average'] ?? 5, 1) }}/10
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <p itemprop="reviewBody" class="mb-0">{{ $review['content'] ?? 'No content available.' }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Trailer Modal -->
<div class="modal fade" id="trailerModal" tabindex="-1" aria-labelledby="trailerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="trailerModalLabel">{{ $movie['title'] }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9">
                    <iframe id="trailerIframe" src="about:blank" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Hero Section */
    .movie-hero {
        position: relative;
        height: 80vh;
        min-height: 500px;
        max-height: 700px;
        margin-top: -24px;
    }
    
    .backdrop-wrapper {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -1;
    }
    
    .backdrop-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .backdrop-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to bottom, rgba(0,0,0,0.1) 0%, rgba(20,20,20,1) 100%);
    }
    
    .no-backdrop {
        background-color: #141414;
    }
    
    .hero-content {
        position: relative;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding-bottom: 5rem;
    }
    
    .movie-hero-title {
        font-size: 4rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }
    
    .movie-tagline {
        font-size: 1.5rem;
        color: #e5e5e5;
        margin-bottom: 2rem;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5);
    }
    
    .play-button {
        font-size: 1.2rem;
        padding: 0.8rem 2rem;
        border-radius: 4px;
    }
    
    /* Movie Details Section */
    .movie-details {
        padding: 3rem 0;
    }
    
    .poster-container {
        position: relative;
        margin-top: -7rem;
    }
    
    .movie-poster {
        border-radius: 8px;
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.7);
    }
    
    .no-poster {
        width: 100%;
        height: 450px;
        background-color: #202020;
        color: #555;
        border-radius: 8px;
    }
    
    /* Rating display */
    .rating-container {
        background-color: var(--netflix-light-dark);
        border-radius: 8px;
        padding: 1rem;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    
    .rating-stars {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    
    .rating-value {
        color: #FFC107;
    }
    
    .rating-count {
        font-size: 0.9rem;
    }
    
    .movie-info-card {
        padding: 0;
    }
    
    .movie-meta {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .movie-year, .movie-runtime {
        color: #e5e5e5;
        margin-right: 1.5rem;
    }
    
    .movie-rating-badge {
        background-color: var(--netflix-red);
        color: white;
        padding: 0.3rem 0.7rem;
        border-radius: 4px;
        font-weight: 600;
    }
    
    .movie-genres {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .genre-badge {
        background-color: rgba(255, 255, 255, 0.1);
        color: #e5e5e5;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .genre-badge:hover {
        background-color: var(--netflix-red);
        color: white;
    }
    
    .movie-overview {
        color: #e5e5e5;
        font-size: 1.1rem;
        line-height: 1.6;
        margin-bottom: 2rem;
    }
    
    .movie-credits {
        margin-bottom: 1.5rem;
    }
    
    .credit-row {
        margin-bottom: 0.5rem;
    }
    
    .credit-title {
        color: #999;
        margin-right: 0.5rem;
    }
    
    .credit-people {
        color: #e5e5e5;
    }
    
    .movie-actions .btn-danger {
        background-color: var(--netflix-red);
        border: none;
    }
    
    .movie-actions .btn-outline-light {
        border-color: #aaa;
    }
    
    .movie-actions .btn-outline-light:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    /* Social sharing buttons */
    .social-share a {
        transition: all 0.3s ease;
    }
    
    .social-share a:hover {
        transform: translateY(-3px);
    }
    
    /* User review section */
    .review-card .card {
        background-color: var(--netflix-light-dark);
        border: none;
    }
    
    .review-card .card-header {
        background-color: rgba(0, 0, 0, 0.2);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    /* Breadcrumbs */
    .breadcrumb {
        padding: 0.75rem 0;
        margin-bottom: 0;
        background-color: transparent;
    }
    
    .breadcrumb-item a {
        color: var(--netflix-light-gray);
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .breadcrumb-item a:hover {
        color: var(--netflix-red);
    }
    
    .breadcrumb-item.active {
        color: white;
    }
    
    /* Modal */
    #trailerModal .modal-content {
        background-color: #000;
        border: none;
    }
    
    #trailerModal .modal-header {
        border-bottom: 1px solid #333;
        background-color: #111;
        color: white;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 767px) {
        .movie-hero {
            height: 50vh;
            min-height: 400px;
        }
        
        .movie-hero-title {
            font-size: 2.5rem;
        }
        
        .movie-tagline {
            font-size: 1.2rem;
        }
        
        .poster-container {
            margin-top: 0;
            margin-bottom: 2rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const playTrailerBtn = document.getElementById('playTrailerBtn');
        const playTrailerBtnAlt = document.getElementById('playTrailerBtnAlt');
        const trailerIframe = document.getElementById('trailerIframe');
        const trailerModal = new bootstrap.Modal(document.getElementById('trailerModal'));
        
        const playTrailer = function() {
            // Set the iframe src with the TMDB ID
            trailerIframe.src = `https://autoembed.co/movie/tmdb/{{ $movie['id'] }}`;
            trailerModal.show();
        };
        
        playTrailerBtn.addEventListener('click', playTrailer);
        playTrailerBtnAlt.addEventListener('click', playTrailer);
        
        // When modal is hidden, stop the video by setting iframe src to blank
        document.getElementById('trailerModal').addEventListener('hidden.bs.modal', function() {
            trailerIframe.src = 'about:blank';
        });
    });
</script>
@endpush
@endsection