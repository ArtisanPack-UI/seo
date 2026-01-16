{{--
 * SeoAnalysisPanel Blade View.
 *
 * Displays SEO analysis results with scoring and recommendations.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
--}}
<x-artisanpack-card class="seo-analysis-panel">
	{{-- Score Header (Always Visible) --}}
	<div
		class="flex items-center justify-between cursor-pointer"
		wire:click="toggle"
		role="button"
		tabindex="0"
		aria-expanded="{{ $expanded ? 'true' : 'false' }}"
		aria-controls="seo-analysis-details"
		@keydown.enter="$wire.toggle()"
		@keydown.space.prevent="$wire.toggle()"
	>
		<div class="flex items-center gap-3">
			{{-- Overall Score Circle --}}
			<x-artisanpack-progress
				type="radial"
				:value="$this->overallScore"
				:color="$this->scoreColor"
				size="sm"
			/>

			<div>
				<h4 class="font-medium">{{ __( 'SEO Score' ) }}</h4>
				<p class="text-sm text-base-content/70">
					{{ $this->scoreLabel }}
				</p>
			</div>
		</div>

		<x-artisanpack-icon
			name="o-chevron-down"
			class="w-5 h-5 transition-transform duration-200 {{ $expanded ? 'rotate-180' : '' }}"
		/>
	</div>

	{{-- Expanded Details --}}
	@if ( $expanded )
		<div id="seo-analysis-details" class="mt-4 space-y-4 border-t border-base-300 pt-4">
			{{-- Category Scores --}}
			<div class="grid grid-cols-2 gap-3">
				@foreach ( $this->categoryScores as $key => $category )
					<div class="flex items-center gap-2">
						<x-artisanpack-progress
							:value="$category['score']"
							:color="$category['color']"
							size="xs"
							class="flex-shrink-0"
						/>
						<span class="text-xs whitespace-nowrap">{{ $category['label'] }}</span>
					</div>
				@endforeach
			</div>

			{{-- Issues Section --}}
			@if ( $this->hasIssues )
				<div class="space-y-2">
					<h5 class="text-sm font-medium text-error flex items-center gap-1">
						<x-artisanpack-icon name="o-exclamation-circle" class="w-4 h-4" />
						{{ __( 'Issues' ) }} ({{ $this->issueCount }})
					</h5>
					<ul class="space-y-1 pl-1">
						@foreach ( $this->issues as $issue )
							<li class="text-sm text-base-content/80 flex items-start gap-2">
								<span class="text-error mt-0.5 flex-shrink-0">•</span>
								<span>{{ is_array( $issue ) ? ( $issue['message'] ?? '' ) : $issue }}</span>
							</li>
						@endforeach
					</ul>
				</div>
			@endif

			{{-- Suggestions Section --}}
			@if ( $this->hasSuggestions )
				<div class="space-y-2">
					<h5 class="text-sm font-medium text-warning flex items-center gap-1">
						<x-artisanpack-icon name="o-light-bulb" class="w-4 h-4" />
						{{ __( 'Suggestions' ) }} ({{ $this->suggestionCount }})
					</h5>
					<ul class="space-y-1 pl-1">
						@foreach ( $this->suggestions as $suggestion )
							<li class="text-sm text-base-content/80 flex items-start gap-2">
								<span class="text-warning mt-0.5 flex-shrink-0">•</span>
								<span>{{ is_array( $suggestion ) ? ( $suggestion['message'] ?? '' ) : $suggestion }}</span>
							</li>
						@endforeach
					</ul>
				</div>
			@endif

			{{-- Passed Checks Section --}}
			@if ( $this->hasPassedChecks )
				<div class="space-y-2">
					<h5 class="text-sm font-medium text-success flex items-center gap-1">
						<x-artisanpack-icon name="o-check-circle" class="w-4 h-4" />
						{{ __( 'Passed' ) }} ({{ $this->passedCheckCount }})
					</h5>
					<ul class="space-y-1 pl-1">
						@foreach ( $this->passedChecks as $check )
							<li class="text-sm text-base-content/80 flex items-start gap-2">
								<span class="text-success mt-0.5 flex-shrink-0">✓</span>
								<span>{{ is_array( $check ) ? ( $check['message'] ?? '' ) : $check }}</span>
							</li>
						@endforeach
					</ul>
				</div>
			@endif

			{{-- Empty State --}}
			@if ( ! $this->hasIssues && ! $this->hasSuggestions && ! $this->hasPassedChecks )
				<p class="text-sm text-base-content/60 text-center py-4">
					{{ __( 'No analysis data available. Add content and a focus keyword to see SEO recommendations.' ) }}
				</p>
			@endif
		</div>
	@endif
</x-artisanpack-card>
