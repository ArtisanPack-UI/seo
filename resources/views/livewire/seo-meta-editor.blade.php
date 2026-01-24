<div class="seo-meta-editor">
	{{-- Flash Messages --}}
	@if ( session()->has( 'success' ) )
		<x-artisanpack-alert type="success" class="mb-4" dismissible>
			{{ session( 'success' ) }}
		</x-artisanpack-alert>
	@endif

	@if ( session()->has( 'error' ) )
		<x-artisanpack-alert type="error" class="mb-4" dismissible>
			{{ session( 'error' ) }}
		</x-artisanpack-alert>
	@endif

	{{-- Tab Navigation --}}
	<x-artisanpack-tabs wire:model.live="activeTab" class="mb-6">
		<x-artisanpack-tab name="basic" :label="__( 'Basic SEO' )" />
		<x-artisanpack-tab name="social" :label="__( 'Social Media' )" />
		<x-artisanpack-tab name="schema" :label="__( 'Schema' )" />
		<x-artisanpack-tab name="advanced" :label="__( 'Advanced' )" />
	</x-artisanpack-tabs>

	<form wire:submit="save">
		{{-- Basic SEO Tab --}}
		<div x-show="$wire.activeTab === 'basic'" x-cloak>
			<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
				{{-- Left Column: Form Fields --}}
				<div class="space-y-6">
					<x-artisanpack-card>
						<x-slot:header>
							<h3 class="text-lg font-semibold">{{ __( 'Meta Information' ) }}</h3>
						</x-slot:header>

						<div class="space-y-4">
							{{-- Meta Title --}}
							<div>
								<x-artisanpack-input
									wire:model.live.debounce.500ms="metaTitle"
									:label="__( 'Meta Title' )"
									:placeholder="__( 'Enter meta title...' )"
									:hint="__( 'Recommended: 50-60 characters' )"
									:error="$errors->first( 'metaTitle' )"
								/>
								<div class="text-sm mt-1 {{ $this->titleCharCount > 60 ? 'text-warning' : 'text-base-content/60' }}">
									{{ $this->titleCharCount }} / 60 {{ __( 'characters' ) }}
								</div>
							</div>

							{{-- Meta Description --}}
							<div>
								<x-artisanpack-textarea
									wire:model.live.debounce.500ms="metaDescription"
									:label="__( 'Meta Description' )"
									:placeholder="__( 'Enter meta description...' )"
									:hint="__( 'Recommended: 150-160 characters' )"
									:error="$errors->first( 'metaDescription' )"
									rows="3"
								/>
								<div class="text-sm mt-1 {{ $this->descriptionCharCount > 160 ? 'text-warning' : 'text-base-content/60' }}">
									{{ $this->descriptionCharCount }} / 160 {{ __( 'characters' ) }}
								</div>
							</div>

							{{-- Focus Keyword --}}
							<x-artisanpack-input
								wire:model.live.debounce.500ms="focusKeyword"
								:label="__( 'Focus Keyword' )"
								:placeholder="__( 'Enter your main keyword...' )"
								:hint="__( 'The primary keyword you want to rank for' )"
								:error="$errors->first( 'focusKeyword' )"
							/>

							{{-- Secondary Keywords --}}
							<x-artisanpack-input
								wire:model="secondaryKeywords"
								:label="__( 'Secondary Keywords' )"
								:placeholder="__( 'keyword1, keyword2, keyword3' )"
								:hint="__( 'Comma-separated list of additional keywords' )"
								:error="$errors->first( 'secondaryKeywords' )"
							/>

							{{-- Canonical URL --}}
							<x-artisanpack-input
								wire:model="canonicalUrl"
								type="url"
								:label="__( 'Canonical URL' )"
								:placeholder="__( 'https://example.com/page' )"
								:hint="__( 'Leave empty to use the default URL' )"
								:error="$errors->first( 'canonicalUrl' )"
							/>
						</div>
					</x-artisanpack-card>

					{{-- Analysis Button --}}
					<x-artisanpack-button
						type="button"
						wire:click="runAnalysis"
						color="secondary"
						class="w-full"
					>
						{{ __( 'Run SEO Analysis' ) }}
					</x-artisanpack-button>

					{{-- Analysis Results --}}
					@if ( ! empty( $analysisResult ) )
						<x-artisanpack-card>
							<x-slot:header>
								<h3 class="text-lg font-semibold">{{ __( 'SEO Analysis' ) }}</h3>
							</x-slot:header>

							<div class="space-y-2">
								@foreach ( $analysisResult as $check )
									<div class="flex items-start gap-2">
										@if ( 'success' === $check['status'] )
											<x-artisanpack-icon name="o-check-circle" class="w-5 h-5 text-success flex-shrink-0" />
										@elseif ( 'warning' === $check['status'] )
											<x-artisanpack-icon name="o-exclamation-triangle" class="w-5 h-5 text-warning flex-shrink-0" />
										@else
											<x-artisanpack-icon name="o-information-circle" class="w-5 h-5 text-info flex-shrink-0" />
										@endif
										<span class="text-sm">{{ $check['message'] }}</span>
									</div>
								@endforeach
							</div>
						</x-artisanpack-card>
					@endif
				</div>

				{{-- Right Column: Preview --}}
				<div class="space-y-6">
					{{-- SERP Preview --}}
					<x-artisanpack-card>
						<x-slot:header>
							<h3 class="text-lg font-semibold">{{ __( 'Search Preview' ) }}</h3>
						</x-slot:header>

						<div class="serp-preview p-4 bg-base-200 rounded-lg">
							<div class="text-primary text-xl hover:underline cursor-pointer truncate">
								{{ $this->previewTitle ?: __( 'Page Title' ) }}
							</div>
							<div class="text-success text-sm truncate">
								{{ $this->previewUrl }}
							</div>
							<div class="text-base-content/80 text-sm line-clamp-2">
								{{ $this->previewDescription ?: __( 'Add a meta description to see how it will appear in search results.' ) }}
							</div>
						</div>
					</x-artisanpack-card>
				</div>
			</div>
		</div>

		{{-- Social Media Tab --}}
		<div x-show="$wire.activeTab === 'social'" x-cloak>
			<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
				{{-- Open Graph --}}
				<x-artisanpack-card>
					<x-slot:header>
						<div class="flex items-center justify-between">
							<h3 class="text-lg font-semibold">{{ __( 'Open Graph (Facebook, LinkedIn)' ) }}</h3>
							<x-artisanpack-button
								type="button"
								wire:click="copyTitleToOg"
								size="sm"
								color="ghost"
							>
								{{ __( 'Copy from Meta' ) }}
							</x-artisanpack-button>
						</div>
					</x-slot:header>

					<div class="space-y-4">
						{{-- OG Title --}}
						<div>
							<x-artisanpack-input
								wire:model.live.debounce.500ms="ogTitle"
								:label="__( 'OG Title' )"
								:placeholder="__( 'Title for social sharing...' )"
								:error="$errors->first( 'ogTitle' )"
							/>
							<div class="text-sm mt-1 {{ $this->ogTitleCharCount > 95 ? 'text-warning' : 'text-base-content/60' }}">
								{{ $this->ogTitleCharCount }} / 95 {{ __( 'characters' ) }}
							</div>
						</div>

						{{-- OG Description --}}
						<div>
							<x-artisanpack-textarea
								wire:model.live.debounce.500ms="ogDescription"
								:label="__( 'OG Description' )"
								:placeholder="__( 'Description for social sharing...' )"
								:error="$errors->first( 'ogDescription' )"
								rows="2"
							/>
							<div class="text-sm mt-1 {{ $this->ogDescriptionCharCount > 200 ? 'text-warning' : 'text-base-content/60' }}">
								{{ $this->ogDescriptionCharCount }} / 200 {{ __( 'characters' ) }}
							</div>
						</div>

						{{-- OG Image --}}
						<div>
							<label class="label">
								<span class="label-text font-medium">{{ __( 'OG Image' ) }}</span>
							</label>
							@if ( $this->resolvedOgImage )
								<div class="relative">
									<img src="{{ $this->resolvedOgImage }}" alt="{{ __( 'OG Image' ) }}" class="w-full h-40 object-cover rounded-lg" />
									<x-artisanpack-button
										type="button"
										wire:click="clearImage( 'og' )"
										size="sm"
										color="error"
										class="absolute top-2 right-2"
									>
										<x-artisanpack-icon name="o-x-mark" class="w-4 h-4" />
									</x-artisanpack-button>
								</div>
							@else
								<x-artisanpack-input
									wire:model="ogImage"
									type="url"
									:placeholder="__( 'Image URL or select from media library' )"
									:error="$errors->first( 'ogImage' )"
								/>
								@if ( class_exists( 'ArtisanPackUI\MediaLibrary\Livewire\Components\MediaModal' ) )
									<x-artisanpack-button
										type="button"
										x-on:click="Livewire.dispatch( 'open-media-modal', { context: 'og-image' } )"
										size="sm"
										color="secondary"
										class="mt-2"
									>
										{{ __( 'Select from Media Library' ) }}
									</x-artisanpack-button>
								@endif
							@endif
							<p class="text-sm text-base-content/60 mt-1">{{ __( 'Recommended: 1200x630 pixels' ) }}</p>
						</div>

						{{-- OG Type --}}
						<x-artisanpack-select
							wire:model="ogType"
							:label="__( 'OG Type' )"
							:options="$ogTypes"
							option-value="value"
							option-label="label"
							:error="$errors->first( 'ogType' )"
						/>

						{{-- OG Site Name --}}
						<x-artisanpack-input
							wire:model="ogSiteName"
							:label="__( 'Site Name' )"
							:placeholder="config( 'app.name' )"
							:error="$errors->first( 'ogSiteName' )"
						/>

						{{-- OG Locale --}}
						<x-artisanpack-input
							wire:model="ogLocale"
							:label="__( 'Locale' )"
							:placeholder="__( 'en_US' )"
							:hint="__( 'Format: language_TERRITORY (e.g., en_US)' )"
							:error="$errors->first( 'ogLocale' )"
						/>
					</div>
				</x-artisanpack-card>

				{{-- Twitter Card --}}
				<x-artisanpack-card>
					<x-slot:header>
						<div class="flex items-center justify-between">
							<h3 class="text-lg font-semibold">{{ __( 'Twitter Card' ) }}</h3>
							<x-artisanpack-button
								type="button"
								wire:click="copyOgToTwitter"
								size="sm"
								color="ghost"
							>
								{{ __( 'Copy from OG' ) }}
							</x-artisanpack-button>
						</div>
					</x-slot:header>

					<div class="space-y-4">
						{{-- Twitter Card Type --}}
						<x-artisanpack-select
							wire:model="twitterCard"
							:label="__( 'Card Type' )"
							:options="$twitterCardTypes"
							option-value="value"
							option-label="label"
							:error="$errors->first( 'twitterCard' )"
						/>

						{{-- Twitter Title --}}
						<div>
							<x-artisanpack-input
								wire:model.live.debounce.500ms="twitterTitle"
								:label="__( 'Title' )"
								:placeholder="__( 'Title for Twitter...' )"
								:error="$errors->first( 'twitterTitle' )"
							/>
							<div class="text-sm mt-1 {{ $this->twitterTitleCharCount > 70 ? 'text-warning' : 'text-base-content/60' }}">
								{{ $this->twitterTitleCharCount }} / 70 {{ __( 'characters' ) }}
							</div>
						</div>

						{{-- Twitter Description --}}
						<div>
							<x-artisanpack-textarea
								wire:model.live.debounce.500ms="twitterDescription"
								:label="__( 'Description' )"
								:placeholder="__( 'Description for Twitter...' )"
								:error="$errors->first( 'twitterDescription' )"
								rows="2"
							/>
							<div class="text-sm mt-1 {{ $this->twitterDescriptionCharCount > 200 ? 'text-warning' : 'text-base-content/60' }}">
								{{ $this->twitterDescriptionCharCount }} / 200 {{ __( 'characters' ) }}
							</div>
						</div>

						{{-- Twitter Image --}}
						<div>
							<label class="label">
								<span class="label-text font-medium">{{ __( 'Image' ) }}</span>
							</label>
							@if ( $this->resolvedTwitterImage )
								<div class="relative">
									<img src="{{ $this->resolvedTwitterImage }}" alt="{{ __( 'Twitter Image' ) }}" class="w-full h-40 object-cover rounded-lg" />
									<x-artisanpack-button
										type="button"
										wire:click="clearImage( 'twitter' )"
										size="sm"
										color="error"
										class="absolute top-2 right-2"
									>
										<x-artisanpack-icon name="o-x-mark" class="w-4 h-4" />
									</x-artisanpack-button>
								</div>
							@else
								<x-artisanpack-input
									wire:model="twitterImage"
									type="url"
									:placeholder="__( 'Image URL or select from media library' )"
									:error="$errors->first( 'twitterImage' )"
								/>
								@if ( class_exists( 'ArtisanPackUI\MediaLibrary\Livewire\Components\MediaModal' ) )
									<x-artisanpack-button
										type="button"
										x-on:click="Livewire.dispatch( 'open-media-modal', { context: 'twitter-image' } )"
										size="sm"
										color="secondary"
										class="mt-2"
									>
										{{ __( 'Select from Media Library' ) }}
									</x-artisanpack-button>
								@endif
							@endif
						</div>

						{{-- Twitter Site --}}
						<x-artisanpack-input
							wire:model="twitterSite"
							:label="__( 'Site @username' )"
							:placeholder="__( '@yoursite' )"
							:error="$errors->first( 'twitterSite' )"
						/>

						{{-- Twitter Creator --}}
						<x-artisanpack-input
							wire:model="twitterCreator"
							:label="__( 'Creator @username' )"
							:placeholder="__( '@author' )"
							:error="$errors->first( 'twitterCreator' )"
						/>
					</div>
				</x-artisanpack-card>
			</div>

			{{-- Pinterest & Slack (Collapsible) --}}
			<x-artisanpack-collapse class="mt-6">
				<x-slot:title>
					{{ __( 'Additional Platforms (Pinterest, Slack)' ) }}
				</x-slot:title>

				<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 p-4">
					{{-- Pinterest --}}
					<div class="space-y-4">
						<h4 class="font-semibold">{{ __( 'Pinterest' ) }}</h4>

						<x-artisanpack-textarea
							wire:model="pinterestDescription"
							:label="__( 'Description' )"
							:placeholder="__( 'Description for Pinterest pins...' )"
							:error="$errors->first( 'pinterestDescription' )"
							rows="2"
						/>

						<x-artisanpack-input
							wire:model="pinterestImage"
							type="url"
							:label="__( 'Image URL' )"
							:placeholder="__( 'Image URL for Pinterest' )"
							:error="$errors->first( 'pinterestImage' )"
						/>
					</div>

					{{-- Slack --}}
					<div class="space-y-4">
						<h4 class="font-semibold">{{ __( 'Slack' ) }}</h4>

						<x-artisanpack-input
							wire:model="slackTitle"
							:label="__( 'Title' )"
							:placeholder="__( 'Title for Slack unfurls...' )"
							:error="$errors->first( 'slackTitle' )"
						/>

						<x-artisanpack-textarea
							wire:model="slackDescription"
							:label="__( 'Description' )"
							:placeholder="__( 'Description for Slack unfurls...' )"
							:error="$errors->first( 'slackDescription' )"
							rows="2"
						/>

						<x-artisanpack-input
							wire:model="slackImage"
							type="url"
							:label="__( 'Image URL' )"
							:placeholder="__( 'Image URL for Slack' )"
							:error="$errors->first( 'slackImage' )"
						/>
					</div>
				</div>
			</x-artisanpack-collapse>
		</div>

		{{-- Schema Tab --}}
		<div x-show="$wire.activeTab === 'schema'" x-cloak>
			<x-artisanpack-card>
				<x-slot:header>
					<h3 class="text-lg font-semibold">{{ __( 'Schema Markup (Structured Data)' ) }}</h3>
				</x-slot:header>

				<div class="space-y-4">
					{{-- Schema Type --}}
					<x-artisanpack-select
						wire:model="schemaType"
						:label="__( 'Schema Type' )"
						:options="$schemaTypes"
						option-value="value"
						option-label="label"
						:hint="__( 'Select a predefined schema type or add custom JSON-LD below' )"
						:error="$errors->first( 'schemaType' )"
					/>

					{{-- Custom Schema Markup --}}
					<div>
						<x-artisanpack-textarea
							wire:model="schemaMarkup"
							:label="__( 'Custom Schema Markup (JSON-LD)' )"
							:placeholder="__( '{\n  \"@context\": \"https://schema.org\",\n  \"@type\": \"Article\",\n  ...\n}' )"
							:hint="__( 'Enter valid JSON-LD markup. This will override the selected schema type.' )"
							:error="$errors->first( 'schemaMarkup' )"
							rows="12"
							class="font-mono text-sm"
						/>
					</div>

					<x-artisanpack-alert type="info">
						{{ __( 'Schema markup helps search engines understand your content better and can enable rich results in search.' ) }}
					</x-artisanpack-alert>
				</div>
			</x-artisanpack-card>
		</div>

		{{-- Advanced Tab --}}
		<div x-show="$wire.activeTab === 'advanced'" x-cloak>
			<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
				{{-- Robots Settings --}}
				<x-artisanpack-card>
					<x-slot:header>
						<h3 class="text-lg font-semibold">{{ __( 'Robot Directives' ) }}</h3>
					</x-slot:header>

					<div class="space-y-4">
						<x-artisanpack-toggle
							wire:model="noIndex"
							:label="__( 'No Index' )"
							:hint="__( 'Prevent search engines from indexing this page' )"
						/>

						<x-artisanpack-toggle
							wire:model="noFollow"
							:label="__( 'No Follow' )"
							:hint="__( 'Prevent search engines from following links on this page' )"
						/>

						<x-artisanpack-input
							wire:model="robotsMeta"
							:label="__( 'Additional Robot Directives' )"
							:placeholder="__( 'noarchive, noimageindex' )"
							:hint="__( 'Comma-separated additional directives' )"
							:error="$errors->first( 'robotsMeta' )"
						/>
					</div>
				</x-artisanpack-card>

				{{-- Sitemap Settings --}}
				<x-artisanpack-card>
					<x-slot:header>
						<h3 class="text-lg font-semibold">{{ __( 'Sitemap Settings' ) }}</h3>
					</x-slot:header>

					<div class="space-y-4">
						<x-artisanpack-toggle
							wire:model="excludeFromSitemap"
							:label="__( 'Exclude from Sitemap' )"
							:hint="__( 'Do not include this page in the XML sitemap' )"
						/>

						<x-artisanpack-range
							wire:model="sitemapPriority"
							:label="__( 'Sitemap Priority' )"
							min="0"
							max="1"
							step="0.1"
							:hint="__( 'Priority: ' ) . $sitemapPriority"
						/>

						<x-artisanpack-select
							wire:model="sitemapChangefreq"
							:label="__( 'Change Frequency' )"
							:options="$changefreqOptions"
							option-value="value"
							option-label="label"
							:hint="__( 'How frequently the page is likely to change' )"
							:error="$errors->first( 'sitemapChangefreq' )"
						/>
					</div>
				</x-artisanpack-card>

				{{-- Hreflang Settings --}}
				<x-artisanpack-card class="lg:col-span-2">
					<x-slot:header>
						<h3 class="text-lg font-semibold">{{ __( 'International Targeting (Hreflang)' ) }}</h3>
					</x-slot:header>

					<div class="space-y-4">
						<x-artisanpack-textarea
							wire:model="hreflang"
							:label="__( 'Hreflang Tags (JSON)' )"
							:placeholder="__( '{\n  \"en\": \"https://example.com/page\",\n  \"es\": \"https://example.com/es/pagina\",\n  \"x-default\": \"https://example.com/page\"\n}' )"
							:hint="__( 'Enter hreflang mappings as JSON. Keys are language codes, values are URLs.' )"
							:error="$errors->first( 'hreflang' )"
							rows="6"
							class="font-mono text-sm"
						/>

						<x-artisanpack-alert type="info">
							{{ __( 'Hreflang tags help search engines understand which language and regional versions of a page to show to users.' ) }}
						</x-artisanpack-alert>
					</div>
				</x-artisanpack-card>
			</div>
		</div>

		{{-- Save Button --}}
		<div class="mt-6 flex justify-end">
			<x-artisanpack-button
				type="submit"
				color="primary"
				wire:loading.attr="disabled"
				wire:loading.class="loading"
			>
				<span wire:loading.remove>{{ __( 'Save SEO Settings' ) }}</span>
				<span wire:loading>{{ __( 'Saving...' ) }}</span>
			</x-artisanpack-button>
		</div>
	</form>

	{{-- Media Library Modal (if available) --}}
	@if ( class_exists( 'ArtisanPackUI\MediaLibrary\Livewire\Components\MediaModal' ) )
		<livewire:media::media-modal />
	@endif
</div>
