<div class="seo-ai-suggestor" data-feature="seo.suggest_meta_description">
	@if ( ! $this->isEnabled )
		<p class="seo-ai-suggestor__disabled">
			{{ __( 'AI meta description suggestions are currently disabled.' ) }}
		</p>
	@else
		<button
			type="button"
			wire:click="suggest"
			wire:loading.attr="disabled"
			wire:target="suggest"
			@disabled( $isLoading || '' === trim( $content ) )
			class="seo-ai-suggestor__button"
		>
			<span wire:loading.remove wire:target="suggest">
				{{ __( 'Suggest meta descriptions' ) }}
			</span>
			<span wire:loading wire:target="suggest">
				{{ __( 'Generating…' ) }}
			</span>
		</button>

		@if ( null !== $error )
			<p class="seo-ai-suggestor__error" role="alert">
				{{ $error }}
			</p>
		@endif

		@if ( ! empty( $variants ) )
			<ul class="seo-ai-suggestor__variants">
				@foreach ( $variants as $index => $variant )
					<li class="seo-ai-suggestor__variant" wire:key="description-variant-{{ $index }}">
						<div class="seo-ai-suggestor__variant-header">
							<p class="seo-ai-suggestor__variant-text">{{ $variant['meta_description'] }}</p>
							<span class="seo-ai-suggestor__variant-count">
								{{ $variant['character_count'] }}/160
							</span>
						</div>

						@if ( ! empty( $variant['rationale'] ) )
							<p class="seo-ai-suggestor__variant-rationale">{{ $variant['rationale'] }}</p>
						@endif

						<button
							type="button"
							wire:click="select({{ $index }})"
							class="seo-ai-suggestor__variant-apply"
						>
							{{ __( 'Use this description' ) }}
						</button>
					</li>
				@endforeach
			</ul>
		@endif
	@endif
</div>
