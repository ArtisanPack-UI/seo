<div class="seo-ai-suggestor" data-feature="seo.suggest_hreflang">
	@if ( ! $this->isEnabled )
		<p class="seo-ai-suggestor__disabled">
			{{ __( 'AI hreflang suggestions are currently disabled.' ) }}
		</p>
	@else
		<button
			type="button"
			wire:click="suggest"
			wire:loading.attr="disabled"
			wire:target="suggest"
			@disabled( $isLoading || empty( $pages ) )
			class="seo-ai-suggestor__button"
		>
			<span wire:loading.remove wire:target="suggest">
				{{ __( 'Check hreflang' ) }}
			</span>
			<span wire:loading wire:target="suggest">
				{{ __( 'Analyzing…' ) }}
			</span>
		</button>

		@if ( null !== $error )
			<p class="seo-ai-suggestor__error" role="alert">
				{{ $error }}
			</p>
		@endif

		@if ( ! empty( $issues ) )
			<ul class="seo-ai-suggestor__issues">
				@foreach ( $issues as $index => $issue )
					<li class="seo-ai-suggestor__issue" wire:key="hreflang-issue-{{ $index }}">
						<div class="seo-ai-suggestor__issue-header">
							<code>{{ $issue['page_url'] }}</code>
							<span class="seo-ai-suggestor__issue-type">{{ $issue['issue_type'] }}</span>
						</div>

						@if ( ! empty( $issue['suggested_hreflang'] ) )
							<ul class="seo-ai-suggestor__hreflang">
								@foreach ( $issue['suggested_hreflang'] as $entryIndex => $entry )
									<li wire:key="hreflang-{{ $index }}-{{ $entryIndex }}">
										<strong>{{ $entry['lang'] }}</strong>
										<code>{{ $entry['url'] }}</code>
									</li>
								@endforeach
							</ul>
						@endif

						<button
							type="button"
							wire:click="applyFix({{ $index }})"
							class="seo-ai-suggestor__variant-apply"
						>
							{{ __( 'Apply suggested tags' ) }}
						</button>
					</li>
				@endforeach
			</ul>
		@elseif ( ! $isLoading && null === $error )
			<p class="seo-ai-suggestor__empty">
				{{ __( 'Run the check to look for missing hreflang tags.' ) }}
			</p>
		@endif
	@endif
</div>
