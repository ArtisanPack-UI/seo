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
				{{ __( 'Suggest meta description' ) }}
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

		@if ( null !== $suggestion )
			<div class="seo-ai-suggestor__suggestion">
				<p class="seo-ai-suggestor__suggestion-text">{{ $suggestion }}</p>
				<div class="seo-ai-suggestor__suggestion-meta">
					<span class="seo-ai-suggestor__suggestion-count">{{ $characterCount }}/160</span>
					@if ( '' !== $rationale )
						<span class="seo-ai-suggestor__suggestion-rationale">{{ $rationale }}</span>
					@endif
				</div>
				<button
					type="button"
					wire:click="accept"
					class="seo-ai-suggestor__variant-apply"
				>
					{{ __( 'Use this description' ) }}
				</button>
			</div>
		@endif
	@endif
</div>
