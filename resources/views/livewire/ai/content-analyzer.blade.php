<div class="seo-ai-analyzer" data-feature="seo.analyze_content">
	@if ( ! $this->isEnabled )
		<p class="seo-ai-analyzer__disabled">
			{{ __( 'AI content analysis is currently disabled.' ) }}
		</p>
	@else
		<button
			type="button"
			wire:click="analyze"
			wire:loading.attr="disabled"
			wire:target="analyze"
			@disabled( $isLoading || '' === trim( $content ) || '' === trim( $primaryKeyword ) )
			class="seo-ai-analyzer__button"
		>
			<span wire:loading.remove wire:target="analyze">
				{{ __( 'Analyze content' ) }}
			</span>
			<span wire:loading wire:target="analyze">
				{{ __( 'Analyzing…' ) }}
			</span>
		</button>

		@if ( null !== $error )
			<p class="seo-ai-analyzer__error" role="alert">
				{{ $error }}
			</p>
		@endif

		@if ( null !== $overallScore )
			<div class="seo-ai-analyzer__result">
				<div class="seo-ai-analyzer__overall">
					<span class="seo-ai-analyzer__overall-label">{{ __( 'Overall score' ) }}</span>
					<span class="seo-ai-analyzer__overall-value">{{ $overallScore }}/100</span>
				</div>

				<ul class="seo-ai-analyzer__dimensions">
					@foreach ( $dimensions as $key => $dimension )
						<li class="seo-ai-analyzer__dimension" wire:key="dim-{{ $key }}">
							<div class="seo-ai-analyzer__dimension-header">
								<span class="seo-ai-analyzer__dimension-name">
									{{ __( ucwords( str_replace( '_', ' ', (string) $key ) ) ) }}
								</span>
								<span class="seo-ai-analyzer__dimension-score">{{ $dimension['score'] }}/100</span>
							</div>

							@if ( ! empty( $dimension['recommendations'] ) )
								<ul class="seo-ai-analyzer__recommendations">
									@foreach ( $dimension['recommendations'] as $recIndex => $recommendation )
										<li wire:key="rec-{{ $key }}-{{ $recIndex }}">{{ $recommendation }}</li>
									@endforeach
								</ul>
							@endif
						</li>
					@endforeach
				</ul>
			</div>
		@endif
	@endif
</div>
