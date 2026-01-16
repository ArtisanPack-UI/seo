<div>
	<div data-test="overall-score">{{ $this->overallScore }}</div>
	<div data-test="score-color">{{ $this->scoreColor }}</div>
	<div data-test="score-label">{{ $this->scoreLabel }}</div>
	<div data-test="expanded">{{ $expanded ? 'true' : 'false' }}</div>
	<div data-test="readability-score">{{ $this->readabilityScore }}</div>
	<div data-test="keyword-score">{{ $this->keywordScore }}</div>
	<div data-test="meta-score">{{ $this->metaScore }}</div>
	<div data-test="content-score">{{ $this->contentScore }}</div>
	<div data-test="issue-count">{{ $this->issueCount }}</div>
	<div data-test="suggestion-count">{{ $this->suggestionCount }}</div>
	<div data-test="passed-check-count">{{ $this->passedCheckCount }}</div>
	<div data-test="has-issues">{{ $this->hasIssues ? 'true' : 'false' }}</div>
	<div data-test="has-suggestions">{{ $this->hasSuggestions ? 'true' : 'false' }}</div>
	<div data-test="has-passed-checks">{{ $this->hasPassedChecks ? 'true' : 'false' }}</div>
	@foreach ( $this->issues as $issue )
		<div data-test="issue" data-message="{{ is_array( $issue ) ? ( $issue['message'] ?? '' ) : $issue }}"></div>
	@endforeach
	@foreach ( $this->suggestions as $suggestion )
		<div data-test="suggestion" data-message="{{ is_array( $suggestion ) ? ( $suggestion['message'] ?? '' ) : $suggestion }}"></div>
	@endforeach
	@foreach ( $this->passedChecks as $check )
		<div data-test="passed-check" data-message="{{ is_array( $check ) ? ( $check['message'] ?? '' ) : $check }}"></div>
	@endforeach
	@foreach ( $this->categoryScores as $key => $category )
		<div data-test="category-{{ $key }}" data-score="{{ $category['score'] }}" data-color="{{ $category['color'] }}" data-label="{{ $category['label'] }}"></div>
	@endforeach
</div>
