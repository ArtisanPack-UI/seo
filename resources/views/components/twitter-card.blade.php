<meta name="twitter:card" content="{{ $card }}">

@if($twitterTitle)
    <meta name="twitter:title" content="{{ $twitterTitle }}">
@endif

@if($twitterDescription)
    <meta name="twitter:description" content="{{ $twitterDescription }}">
@endif

@if($twitterImage)
    <meta name="twitter:image" content="{{ $twitterImage }}">
@endif

@if($site)
    <meta name="twitter:site" content="{{ $site }}">
@endif

@if($creator)
    <meta name="twitter:creator" content="{{ $creator }}">
@endif
