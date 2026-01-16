{{--
Social Preview Component.

Displays social media sharing preview showing how content will appear on Facebook/Twitter.

@package    ArtisanPack_UI
@subpackage SEO

@since      1.0.0
--}}

<x-artisanpack-card class="social-preview bg-white">
	<div class="flex items-center justify-between mb-4">
		<p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">
			{{ __( 'Social Media Preview' ) }}
		</p>

		{{-- Platform Toggle --}}
		<div class="flex gap-1 p-1 bg-gray-100 dark:bg-gray-700 rounded-lg">
			<button
				type="button"
				wire:click="setPlatform('facebook')"
				class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $platform === 'facebook' ? 'bg-white dark:bg-gray-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}"
			>
				{{ __( 'Facebook' ) }}
			</button>
			<button
				type="button"
				wire:click="setPlatform('twitter')"
				class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $platform === 'twitter' ? 'bg-white dark:bg-gray-600 text-blue-400 dark:text-blue-300 shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}"
			>
				{{ __( 'Twitter' ) }}
			</button>
		</div>
	</div>

	{{-- Facebook Preview --}}
	@if ( $platform === 'facebook' )
		<div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-800">
			{{-- Image Container (1200x630 aspect ratio = 1.9:1) --}}
			@if ( $this->hasImage )
				<div class="relative w-full" style="aspect-ratio: 1200 / 630;">
					<img
						src="{{ $image }}"
						alt="{{ __( 'Open Graph preview image' ) }}"
						class="absolute inset-0 w-full h-full object-cover"
					/>
				</div>
			@else
				<div class="w-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 dark:text-gray-500" style="aspect-ratio: 1200 / 630;">
					<div class="text-center">
						<x-artisanpack-icon name="o-photo" class="w-12 h-12 mx-auto mb-2" />
						<span class="text-sm">{{ __( 'No image selected' ) }}</span>
						<p class="text-xs mt-1">{{ __( 'Recommended: 1200 x 630 pixels' ) }}</p>
					</div>
				</div>
			@endif

			{{-- Content Area --}}
			<div class="p-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
				<p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">
					{{ $this->displayDomain }}
				</p>
				<h4 class="font-semibold text-gray-900 dark:text-white leading-tight line-clamp-2 mb-1">
					{{ $this->facebookDisplayTitle }}
				</h4>
				<p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-1">
					{{ $this->facebookDisplayDescription }}
				</p>
			</div>
		</div>

		{{-- Facebook Character Counts --}}
		<div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-4 text-xs">
			<span class="{{ $this->isFacebookTitleTruncated ? 'text-warning' : 'text-gray-500 dark:text-gray-400' }}">
				{{ __( 'Title:' ) }}
				<span class="font-medium">{{ $this->titleCharCount }}/60</span>
				@if ( $this->isFacebookTitleTruncated )
					<span class="text-warning">{{ __( '(truncated)' ) }}</span>
				@endif
			</span>

			<span class="{{ $this->isFacebookDescriptionTruncated ? 'text-warning' : 'text-gray-500 dark:text-gray-400' }}">
				{{ __( 'Description:' ) }}
				<span class="font-medium">{{ $this->descriptionCharCount }}/155</span>
				@if ( $this->isFacebookDescriptionTruncated )
					<span class="text-warning">{{ __( '(truncated)' ) }}</span>
				@endif
			</span>
		</div>
	@endif

	{{-- Twitter Preview --}}
	@if ( $platform === 'twitter' )
		<div class="border border-gray-200 dark:border-gray-600 rounded-2xl overflow-hidden bg-white dark:bg-gray-800">
			{{-- Image Container (Twitter large image card uses 2:1 aspect ratio) --}}
			@if ( $this->hasImage )
				<div class="relative w-full" style="aspect-ratio: 2 / 1;">
					<img
						src="{{ $image }}"
						alt="{{ __( 'Twitter card preview image' ) }}"
						class="absolute inset-0 w-full h-full object-cover"
					/>
				</div>
			@else
				<div class="w-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 dark:text-gray-500" style="aspect-ratio: 2 / 1;">
					<div class="text-center">
						<x-artisanpack-icon name="o-photo" class="w-12 h-12 mx-auto mb-2" />
						<span class="text-sm">{{ __( 'No image selected' ) }}</span>
						<p class="text-xs mt-1">{{ __( 'Recommended: 1200 x 600 pixels' ) }}</p>
					</div>
				</div>
			@endif

			{{-- Content Area --}}
			<div class="p-3 border-t border-gray-200 dark:border-gray-600">
				<h4 class="font-bold text-gray-900 dark:text-white leading-tight line-clamp-2 mb-1">
					{{ $this->twitterDisplayTitle }}
				</h4>
				<p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mb-2">
					{{ $this->twitterDisplayDescription }}
				</p>
				<p class="text-sm text-gray-400 dark:text-gray-500 flex items-center gap-1">
					<x-artisanpack-icon name="o-link" class="w-3.5 h-3.5" />
					{{ $this->displayDomain }}
				</p>
			</div>
		</div>

		{{-- Twitter Character Counts --}}
		<div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-4 text-xs">
			<span class="{{ $this->isTwitterTitleTruncated ? 'text-warning' : 'text-gray-500 dark:text-gray-400' }}">
				{{ __( 'Title:' ) }}
				<span class="font-medium">{{ $this->titleCharCount }}/70</span>
				@if ( $this->isTwitterTitleTruncated )
					<span class="text-warning">{{ __( '(truncated)' ) }}</span>
				@endif
			</span>

			<span class="{{ $this->isTwitterDescriptionTruncated ? 'text-warning' : 'text-gray-500 dark:text-gray-400' }}">
				{{ __( 'Description:' ) }}
				<span class="font-medium">{{ $this->descriptionCharCount }}/200</span>
				@if ( $this->isTwitterDescriptionTruncated )
					<span class="text-warning">{{ __( '(truncated)' ) }}</span>
				@endif
			</span>
		</div>
	@endif
</x-artisanpack-card>
