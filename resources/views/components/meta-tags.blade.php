<title>{{ $title }}</title>

@if($description)
    <meta name="description" content="{{ $description }}">
@endif

@if($canonical)
    <link rel="canonical" href="{{ $canonical }}">
@endif

<meta name="robots" content="{{ $robots }}">

@foreach($additionalMeta as $name => $content)
    @if($content)
        <meta name="{{ $name }}" content="{{ $content }}">
    @endif
@endforeach
