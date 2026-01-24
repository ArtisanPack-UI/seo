{{--
 * SeoDashboard Blade View.
 *
 * Displays SEO performance data from Google Search Console.
 *
 * @package    ArtisanPack_UI
 * @subpackage SEO
 *
 * @since      1.0.0
--}}
<div class="seo-dashboard space-y-6">
	{{-- Analytics Not Available Warning --}}
	@if ( ! $analyticsAvailable )
		<x-artisanpack-alert type="info" icon="o-information-circle">
			<p class="font-medium">{{ __( 'Analytics Integration Not Available' ) }}</p>
			<p class="text-sm mt-1">
				{{ __( 'Install the artisanpack-ui/analytics package and configure Google Search Console to view SEO performance data.' ) }}
			</p>
		</x-artisanpack-alert>
	@else
		{{-- Header with Period Selector --}}
		<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
			<div>
				<h2 class="text-xl font-semibold">{{ __( 'SEO Performance' ) }}</h2>
				<p class="text-sm text-base-content/70">{{ __( 'Data from Google Search Console' ) }}</p>
			</div>

			<div class="w-full sm:w-48">
				<x-artisanpack-select
					wire:model.live="period"
					:options="$this->periodOptions"
					option-value="value"
					option-label="label"
					:label="__( 'Time Period' )"
				/>
			</div>
		</div>

		{{-- Stats Grid --}}
		<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
			<x-artisanpack-stat
				:title="__( 'Clicks' )"
				:value="$this->formatNumber( $this->clicks )"
				icon="o-cursor-arrow-rays"
				color="primary"
			/>

			<x-artisanpack-stat
				:title="__( 'Impressions' )"
				:value="$this->formatNumber( $this->impressions )"
				icon="o-eye"
				color="secondary"
			/>

			<x-artisanpack-stat
				:title="__( 'Avg. Position' )"
				:value="$this->avgPosition"
				icon="o-chart-bar"
				color="accent"
			/>

			<x-artisanpack-stat
				:title="__( 'Avg. CTR' )"
				:value="$this->avgCtr . '%'"
				icon="o-arrow-trending-up"
				color="success"
			/>
		</div>

		@php
			$hasNoData = 0 === $this->clicks && 0 === $this->impressions && ! $this->hasTopPages && ! $this->hasTopQueries;
		@endphp

		{{-- Global Empty State for No Data (shown exclusively) --}}
		@if ( $hasNoData )
			<x-artisanpack-alert type="warning" icon="o-exclamation-triangle">
				<p class="font-medium">{{ __( 'No Search Console Data' ) }}</p>
				<p class="text-sm mt-1">
					{{ __( 'There is no Search Console data available for the selected period. This could mean your site is new to Google or there has been no search traffic.' ) }}
				</p>
			</x-artisanpack-alert>
		@else
			{{-- Tables Grid (only shown when there is some data) --}}
			<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
				{{-- Top Pages --}}
				<x-artisanpack-card>
					<x-slot:header>
						<div class="flex items-center gap-2">
							<x-artisanpack-icon name="o-document-text" class="w-5 h-5" />
							<h3 class="font-medium">{{ __( 'Top Pages' ) }}</h3>
						</div>
					</x-slot:header>

					@if ( $this->hasTopPages )
						<x-artisanpack-table
							:headers="$this->pageHeaders"
							:rows="$this->topPages"
							compact
						>
							@scope( 'cell_url', $page )
								<span class="text-sm truncate max-w-[200px] block" title="{{ $page['url'] ?? '' }}">
									{{ $page['url'] ?? '' }}
								</span>
							@endscope

							@scope( 'cell_clicks', $page )
								<span class="font-medium">{{ number_format( $page['clicks'] ?? 0 ) }}</span>
							@endscope

							@scope( 'cell_impressions', $page )
								{{ number_format( $page['impressions'] ?? 0 ) }}
							@endscope

							@scope( 'cell_position', $page )
								{{ number_format( $page['position'] ?? 0, 1 ) }}
							@endscope
						</x-artisanpack-table>
					@else
						<div class="text-center py-8 text-base-content/60">
							<x-artisanpack-icon name="o-document-magnifying-glass" class="w-12 h-12 mx-auto mb-2 opacity-50" />
							<p>{{ __( 'No page data available for this period.' ) }}</p>
						</div>
					@endif
				</x-artisanpack-card>

				{{-- Top Queries --}}
				<x-artisanpack-card>
					<x-slot:header>
						<div class="flex items-center gap-2">
							<x-artisanpack-icon name="o-magnifying-glass" class="w-5 h-5" />
							<h3 class="font-medium">{{ __( 'Top Queries' ) }}</h3>
						</div>
					</x-slot:header>

					@if ( $this->hasTopQueries )
						<x-artisanpack-table
							:headers="$this->queryHeaders"
							:rows="$this->topQueries"
							compact
						>
							@scope( 'cell_query', $query )
								<span class="font-medium text-sm">{{ $query['query'] ?? '' }}</span>
							@endscope

							@scope( 'cell_clicks', $query )
								<span class="font-medium">{{ number_format( $query['clicks'] ?? 0 ) }}</span>
							@endscope

							@scope( 'cell_impressions', $query )
								{{ number_format( $query['impressions'] ?? 0 ) }}
							@endscope

							@scope( 'cell_ctr', $query )
								{{ number_format( ( $query['ctr'] ?? 0 ) * 100, 1 ) }}%
							@endscope
						</x-artisanpack-table>
					@else
						<div class="text-center py-8 text-base-content/60">
							<x-artisanpack-icon name="o-magnifying-glass-circle" class="w-12 h-12 mx-auto mb-2 opacity-50" />
							<p>{{ __( 'No query data available for this period.' ) }}</p>
						</div>
					@endif
				</x-artisanpack-card>
			</div>
		@endif
	@endif
</div>
