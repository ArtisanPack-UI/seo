<div data-test="social-preview">
	{{-- Raw input values --}}
	<div data-test="title">{{ $title }}</div>
	<div data-test="description">{{ $description }}</div>
	<div data-test="image">{{ $image ?? '' }}</div>
	<div data-test="url">{{ $url }}</div>
	<div data-test="platform">{{ $platform }}</div>

	{{-- Computed display values --}}
	<div data-test="facebook-display-title">{{ $this->facebookDisplayTitle }}</div>
	<div data-test="facebook-display-description">{{ $this->facebookDisplayDescription }}</div>
	<div data-test="twitter-display-title">{{ $this->twitterDisplayTitle }}</div>
	<div data-test="twitter-display-description">{{ $this->twitterDisplayDescription }}</div>
	<div data-test="display-domain">{{ $this->displayDomain }}</div>
	<div data-test="display-url">{{ $this->displayUrl }}</div>

	{{-- Image indicator --}}
	<div data-test="has-image">{{ $this->hasImage ? 'true' : 'false' }}</div>

	{{-- Facebook truncation indicators --}}
	<div data-test="is-facebook-title-truncated">{{ $this->isFacebookTitleTruncated ? 'true' : 'false' }}</div>
	<div data-test="is-facebook-description-truncated">{{ $this->isFacebookDescriptionTruncated ? 'true' : 'false' }}</div>

	{{-- Twitter truncation indicators --}}
	<div data-test="is-twitter-title-truncated">{{ $this->isTwitterTitleTruncated ? 'true' : 'false' }}</div>
	<div data-test="is-twitter-description-truncated">{{ $this->isTwitterDescriptionTruncated ? 'true' : 'false' }}</div>

	{{-- Character counts --}}
	<div data-test="title-char-count">{{ $this->titleCharCount }}</div>
	<div data-test="description-char-count">{{ $this->descriptionCharCount }}</div>

	{{-- Constants --}}
	<div data-test="max-facebook-title-length">{{ \ArtisanPackUI\SEO\Livewire\Partials\SocialPreview::MAX_FACEBOOK_TITLE_LENGTH }}</div>
	<div data-test="max-facebook-description-length">{{ \ArtisanPackUI\SEO\Livewire\Partials\SocialPreview::MAX_FACEBOOK_DESCRIPTION_LENGTH }}</div>
	<div data-test="max-twitter-title-length">{{ \ArtisanPackUI\SEO\Livewire\Partials\SocialPreview::MAX_TWITTER_TITLE_LENGTH }}</div>
	<div data-test="max-twitter-description-length">{{ \ArtisanPackUI\SEO\Livewire\Partials\SocialPreview::MAX_TWITTER_DESCRIPTION_LENGTH }}</div>
</div>
