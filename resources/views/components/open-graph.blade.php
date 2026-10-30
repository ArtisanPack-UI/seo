<meta property="og:title" content="{{ $ogTitle }}">

@if($ogDescription)
    <meta property="og:description" content="{{ $ogDescription }}">
@endif

@if($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
@endif

@if($ogImageWidth)
    <meta property="og:image:width" content="{{ $ogImageWidth }}">
@endif

@if($ogImageHeight)
    <meta property="og:image:height" content="{{ $ogImageHeight }}">
@endif

@if($ogImageAlt)
    <meta property="og:image:alt" content="{{ $ogImageAlt }}">
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

@if($articlePublishedTime)
    <meta property="article:published_time" content="{{ $articlePublishedTime }}">
@endif

@if($articleModifiedTime)
    <meta property="article:modified_time" content="{{ $articleModifiedTime }}">
@endif
