{{--
Meta Preview Component.

Displays a Google SERP preview showing how content will appear in search results.

@package    ArtisanPack_UI
@subpackage SEO

@since      1.0.0
--}}

<x-artisanpack-card class="meta-preview bg-white">
	<p class="text-xs text-gray-500 dark:text-gray-400 mb-3 font-medium uppercase tracking-wide">
		{{ __( 'Google Search Preview' ) }}
	</p>

	<div class="space-y-1">
		{{-- URL (Google-style green/gray text) --}}
		<div class="text-sm text-green-700 dark:text-green-500 truncate flex items-center gap-1">
			<span class="text-gray-600 dark:text-gray-400">{{ parse_url( $this->displayUrl, PHP_URL_HOST ) ?: $this->displayUrl }}</span>
			@if ( $path = parse_url( $this->displayUrl, PHP_URL_PATH ) )
				<span class="text-gray-400 dark:text-gray-500">{{ $path }}</span>
			@endif
		</div>

		{{-- Title (Google-style blue clickable text) --}}
		<h3 class="text-xl text-blue-800 dark:text-blue-400 hover:underline cursor-pointer leading-tight">
			{{ $this->displayTitle }}
		</h3>

		{{-- Description (Gray text, truncated) --}}
		<p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed line-clamp-2">
			{{ $this->displayDescription }}
		</p>
	</div>

	{{-- Character count indicators --}}
	<div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-4 text-xs">
		<span class="{{ $this->isTitleTruncated ? 'text-warning' : 'text-gray-500 dark:text-gray-400' }}">
			{{ __( 'Title:' ) }}
			<span class="font-medium">{{ $this->titleCharCount }}/60</span>
			@if ( $this->isTitleTruncated )
				<span class="text-warning">{{ __( '(truncated)' ) }}</span>
			@endif
		</span>

		<span class="{{ $this->isDescriptionTruncated ? 'text-warning' : 'text-gray-500 dark:text-gray-400' }}">
			{{ __( 'Description:' ) }}
			<span class="font-medium">{{ $this->descriptionCharCount }}/160</span>
			@if ( $this->isDescriptionTruncated )
				<span class="text-warning">{{ __( '(truncated)' ) }}</span>
			@endif
		</span>
	</div>
</x-artisanpack-card>
