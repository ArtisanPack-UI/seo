{{-- Basic Meta Tags --}}
<title>{{ $meta['title'] }}</title>

@if($meta['description'])
    <meta name="description" content="{{ $meta['description'] }}">
@endif

@if($meta['canonical'])
    <link rel="canonical" href="{{ $meta['canonical'] }}">
@endif

@if($meta['robots'] ?? null)
    <meta name="robots" content="{{ $meta['robots'] }}">
@endif

{{-- Additional Meta --}}
@foreach($meta['additionalMeta'] ?? [] as $name => $content)
    @if($content)
        <meta name="{{ $name }}" content="{{ $content }}">
    @endif
@endforeach

{{-- Open Graph --}}
@if($includeOpenGraph)
    <meta property="og:title" content="{{ $openGraph['title'] }}">
    @if($openGraph['description'])
        <meta property="og:description" content="{{ $openGraph['description'] }}">
    @endif
    @if($openGraph['image'])
        <meta property="og:image" content="{{ $openGraph['image'] }}">
    @endif
    @if($openGraph['url'])
        <meta property="og:url" content="{{ $openGraph['url'] }}">
    @endif
    <meta property="og:type" content="{{ $openGraph['type'] }}">
    @if($openGraph['siteName'])
        <meta property="og:site_name" content="{{ $openGraph['siteName'] }}">
    @endif
    @if($openGraph['locale'])
        <meta property="og:locale" content="{{ $openGraph['locale'] }}">
    @endif
@endif

{{-- Twitter Card --}}
@if($includeTwitterCard)
    <meta name="twitter:card" content="{{ $twitterCard['card'] }}">
    @if($twitterCard['title'])
        <meta name="twitter:title" content="{{ $twitterCard['title'] }}">
    @endif
    @if($twitterCard['description'])
        <meta name="twitter:description" content="{{ $twitterCard['description'] }}">
    @endif
    @if($twitterCard['image'])
        <meta name="twitter:image" content="{{ $twitterCard['image'] }}">
    @endif
    @if($twitterCard['site'])
        <meta name="twitter:site" content="{{ $twitterCard['site'] }}">
    @endif
    @if($twitterCard['creator'])
        <meta name="twitter:creator" content="{{ $twitterCard['creator'] }}">
    @endif
@endif

{{-- Hreflang --}}
@if($includeHreflang && !empty($hreflang))
    @foreach($hreflang as $tag)
        <link rel="alternate" hreflang="{{ $tag['hreflang'] }}" href="{{ $tag['href'] }}">
    @endforeach
@endif
