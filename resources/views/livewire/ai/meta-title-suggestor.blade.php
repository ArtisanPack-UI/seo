<div class="seo-ai-suggestor" data-feature="seo.suggest_meta_title">
	@if ( ! $this->isEnabled )
		<p class="seo-ai-suggestor__disabled">
			{{ __( 'AI title suggestions are currently disabled.' ) }}
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
				{{ __( 'Suggest titles' ) }}
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
					<li class="seo-ai-suggestor__variant" wire:key="title-variant-{{ $index }}">
						<div class="seo-ai-suggestor__variant-header">
							<strong class="seo-ai-suggestor__variant-title">{{ $variant['title'] }}</strong>
							<span class="seo-ai-suggestor__variant-count">
								{{ $variant['char_count'] }}/60
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
							{{ __( 'Use this title' ) }}
						</button>
					</li>
				@endforeach
			</ul>
		@endif
	@endif
</div>
