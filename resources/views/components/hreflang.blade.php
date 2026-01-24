@php
	$hreflangTags = $hreflangTags ?? [];
@endphp
@foreach ( $hreflangTags as $tag )
	@if ( ! empty( $tag['hreflang'] ) && ! empty( $tag['href'] ) )
		<link rel="alternate" hreflang="{{ $tag['hreflang'] }}" href="{{ $tag['href'] }}" />
	@endif
@endforeach
