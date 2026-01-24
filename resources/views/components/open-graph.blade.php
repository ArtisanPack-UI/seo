<meta property="og:title" content="{{ $ogTitle }}">

@if($ogDescription)
    <meta property="og:description" content="{{ $ogDescription }}">
@endif

@if($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
@endif

@if($ogUrl)
    <meta property="og:url" content="{{ $ogUrl }}">
@endif

<meta property="og:type" content="{{ $ogType }}">

@if($ogSiteName)
    <meta property="og:site_name" content="{{ $ogSiteName }}">
@endif

@if($ogLocale)
    <meta property="og:locale" content="{{ $ogLocale }}">
@endif
