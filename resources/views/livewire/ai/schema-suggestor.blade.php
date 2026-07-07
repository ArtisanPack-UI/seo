<div class="seo-ai-suggestor" data-feature="seo.generate_schema">
	@if ( ! $this->isEnabled )
		<p class="seo-ai-suggestor__disabled">
			{{ __( 'AI schema suggestions are currently disabled.' ) }}
		</p>
	@else
		<button
			type="button"
			wire:click="suggest"
			wire:loading.attr="disabled"
			wire:target="suggest"
			@disabled( $isLoading || '' === trim( $content ) || '' === trim( $title ) )
			class="seo-ai-suggestor__button"
		>
			<span wire:loading.remove wire:target="suggest">
				{{ __( 'Suggest schema' ) }}
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

		@if ( null !== $suggestedType )
			<div class="seo-ai-suggestor__suggestion">
				<div class="seo-ai-suggestor__suggestion-header">
					<strong>{{ $suggestedType }}</strong>
					<span>{{ __( 'Confidence' ) }}: {{ number_format( $confidence * 100, 0 ) }}%</span>
				</div>

				<pre class="seo-ai-suggestor__jsonld"><code>{{ json_encode( $jsonld, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) }}</code></pre>

				@if ( ! empty( $missingRequiredFields ) )
					<div class="seo-ai-suggestor__missing">
						<strong>{{ __( 'Missing required fields:' ) }}</strong>
						<ul>
							@foreach ( $missingRequiredFields as $field )
								<li>{{ $field }}</li>
							@endforeach
						</ul>
					</div>
				@endif

				<button
					type="button"
					wire:click="accept"
					class="seo-ai-suggestor__variant-apply"
				>
					{{ __( 'Populate schema builder' ) }}
				</button>
			</div>
		@endif
	@endif
</div>
