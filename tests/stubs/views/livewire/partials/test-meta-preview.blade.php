<div data-test="meta-preview">
	{{-- Raw input values --}}
	<div data-test="title">{{ $title }}</div>
	<div data-test="description">{{ $description }}</div>
	<div data-test="url">{{ $url }}</div>

	{{-- Computed display values --}}
	<div data-test="display-title">{{ $this->displayTitle }}</div>
	<div data-test="display-description">{{ $this->displayDescription }}</div>
	<div data-test="display-url">{{ $this->displayUrl }}</div>

	{{-- Truncation indicators --}}
	<div data-test="is-title-truncated">{{ $this->isTitleTruncated ? 'true' : 'false' }}</div>
	<div data-test="is-description-truncated">{{ $this->isDescriptionTruncated ? 'true' : 'false' }}</div>

	{{-- Character counts --}}
	<div data-test="title-char-count">{{ $this->titleCharCount }}</div>
	<div data-test="description-char-count">{{ $this->descriptionCharCount }}</div>

	{{-- Constants --}}
	<div data-test="max-title-length">{{ \ArtisanPackUI\SEO\Livewire\Partials\MetaPreview::MAX_TITLE_LENGTH }}</div>
	<div data-test="max-description-length">{{ \ArtisanPackUI\SEO\Livewire\Partials\MetaPreview::MAX_DESCRIPTION_LENGTH }}</div>
</div>
