<div>
	<div data-test="meta-title">{{ $metaTitle }}</div>
	<div data-test="meta-description">{{ $metaDescription }}</div>
	<div data-test="active-tab">{{ $activeTab }}</div>
	<div data-test="title-char-count">{{ $this->titleCharCount }}</div>
	<div data-test="description-char-count">{{ $this->descriptionCharCount }}</div>
	<div data-test="og-title-char-count">{{ $this->ogTitleCharCount }}</div>
	<div data-test="twitter-title-char-count">{{ $this->twitterTitleCharCount }}</div>
	<div data-test="preview-title">{{ $this->previewTitle }}</div>
	<div data-test="preview-description">{{ $this->previewDescription }}</div>
	<div data-test="preview-url">{{ $this->previewUrl }}</div>
	<div data-test="focus-keyword">{{ $focusKeyword }}</div>
	<div data-test="canonical-url">{{ $canonicalUrl }}</div>
	<div data-test="og-title">{{ $ogTitle }}</div>
	<div data-test="og-description">{{ $ogDescription }}</div>
	<div data-test="og-image">{{ $ogImage }}</div>
	<div data-test="twitter-title">{{ $twitterTitle }}</div>
	<div data-test="twitter-description">{{ $twitterDescription }}</div>
	<div data-test="twitter-image">{{ $twitterImage }}</div>
	<div data-test="twitter-card">{{ $twitterCard }}</div>
	<div data-test="no-index">{{ $noIndex ? 'true' : 'false' }}</div>
	<div data-test="no-follow">{{ $noFollow ? 'true' : 'false' }}</div>
	<div data-test="schema-type">{{ $schemaType }}</div>
	<div data-test="schema-markup">{{ $schemaMarkup }}</div>
	<div data-test="sitemap-priority">{{ $sitemapPriority }}</div>
	<div data-test="sitemap-changefreq">{{ $sitemapChangefreq }}</div>
	<div data-test="exclude-from-sitemap">{{ $excludeFromSitemap ? 'true' : 'false' }}</div>
	@if (!empty($analysisResult))
		@foreach ($analysisResult as $check)
			<div data-test="analysis-result" data-status="{{ $check['status'] }}">{{ $check['message'] }}</div>
		@endforeach
	@endif
</div>
