<div>
	<div data-test="analytics-available">{{ $analyticsAvailable ? 'true' : 'false' }}</div>
	<div data-test="period">{{ $period }}</div>
	<div data-test="clicks">{{ $this->clicks }}</div>
	<div data-test="impressions">{{ $this->impressions }}</div>
	<div data-test="avg-position">{{ $this->avgPosition }}</div>
	<div data-test="avg-ctr">{{ $this->avgCtr }}</div>
	<div data-test="has-top-pages">{{ $this->hasTopPages ? 'true' : 'false' }}</div>
	<div data-test="has-top-queries">{{ $this->hasTopQueries ? 'true' : 'false' }}</div>
	<div data-test="top-pages-count">{{ count( $this->topPages ) }}</div>
	<div data-test="top-queries-count">{{ count( $this->topQueries ) }}</div>
	<div data-test="formatted-clicks">{{ $this->formatNumber( $this->clicks ) }}</div>
	<div data-test="formatted-impressions">{{ $this->formatNumber( $this->impressions ) }}</div>
	@foreach ( $this->periodOptions as $option )
		<div data-test="period-option" data-value="{{ $option['value'] }}" data-label="{{ $option['label'] }}"></div>
	@endforeach
	@foreach ( $this->pageHeaders as $header )
		<div data-test="page-header" data-key="{{ $header['key'] }}" data-label="{{ $header['label'] }}"></div>
	@endforeach
	@foreach ( $this->queryHeaders as $header )
		<div data-test="query-header" data-key="{{ $header['key'] }}" data-label="{{ $header['label'] }}"></div>
	@endforeach
	@foreach ( $this->topPages as $page )
		<div data-test="top-page" data-url="{{ $page['url'] ?? '' }}" data-clicks="{{ $page['clicks'] ?? 0 }}"></div>
	@endforeach
	@foreach ( $this->topQueries as $query )
		<div data-test="top-query" data-query="{{ $query['query'] ?? '' }}" data-clicks="{{ $query['clicks'] ?? 0 }}"></div>
	@endforeach
</div>
