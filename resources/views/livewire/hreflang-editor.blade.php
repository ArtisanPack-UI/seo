<div class="hreflang-editor">
	@if ( ! $this->isEnabled )
		<x-artisanpack-alert type="info">
			{{ __( 'Hreflang support is currently disabled. Enable it in the SEO configuration to manage alternate language URLs.' ) }}
		</x-artisanpack-alert>
	@else
		<x-artisanpack-card>
			<x-slot:header>
				<div class="flex items-center justify-between">
					<div>
						<h3 class="font-semibold">{{ __( 'Alternate Language URLs' ) }}</h3>
						<p class="text-sm text-base-content/70">
							{{ __( 'Add URLs for different language versions of this page.' ) }}
						</p>
					</div>
					@if ( $this->entryCount > 0 )
						<x-artisanpack-badge
							:value="trans_choice( ':count language|:count languages', $this->entryCount, [ 'count' => $this->entryCount ] )"
							color="info"
						/>
					@endif
				</div>
			</x-slot:header>

			<div class="space-y-4">
				{{-- Hreflang Entries --}}
				@forelse ( $hreflangEntries as $index => $entry )
					<div wire:key="hreflang-{{ $index }}" class="flex gap-2 items-start">
						<div class="flex-1">
							<x-artisanpack-select
								wire:model="hreflangEntries.{{ $index }}.locale"
								:label="$index === 0 ? __( 'Language' ) : null"
								:options="$this->availableLocales"
								:placeholder="__( 'Select language...' )"
								:error="$errors->first( 'hreflangEntries.' . $index . '.locale' )"
							/>
						</div>

						<div class="flex-[2]">
							<x-artisanpack-input
								wire:model="hreflangEntries.{{ $index }}.url"
								:label="$index === 0 ? __( 'URL' ) : null"
								:placeholder="__( 'https://example.com/page' )"
								:error="$errors->first( 'hreflangEntries.' . $index . '.url' )"
							/>
						</div>

						<div class="{{ $index === 0 ? 'pt-8' : '' }}">
							<x-artisanpack-button
								wire:click="removeHreflang({{ $index }})"
								color="error"
								size="sm"
								icon="o-trash"
								outline
								:title="__( 'Remove this language' )"
								:aria-label="__( 'Remove this language' )"
							/>
						</div>
					</div>
				@empty
					<div class="text-center py-8 text-base-content/50">
						<x-artisanpack-icon name="o-language" class="w-12 h-12 mx-auto mb-2 opacity-50" />
						<p>{{ __( 'No alternate language URLs configured.' ) }}</p>
						<p class="text-sm mt-1">{{ __( 'Add URLs to help search engines show the correct language version.' ) }}</p>
					</div>
				@endforelse

				{{-- Add/Duplicate Buttons --}}
				<div class="flex gap-2 pt-2">
					<x-artisanpack-button
						wire:click="addHreflang"
						size="sm"
						icon="o-plus"
						outline
					>
						{{ __( 'Add Language' ) }}
					</x-artisanpack-button>

					@if ( count( $hreflangEntries ) > 0 )
						<x-artisanpack-button
							wire:click="duplicateEntry"
							size="sm"
							icon="o-document-duplicate"
							outline
							:title="__( 'Add another entry with the same base URL' )"
						>
							{{ __( 'Duplicate URL' ) }}
						</x-artisanpack-button>
					@endif
				</div>

				{{-- Info Box --}}
				@if ( $this->defaultLocale )
					<x-artisanpack-alert type="info" class="mt-4">
						<div class="text-sm">
							<strong>{{ __( 'Tip:' ) }}</strong>
							{{ __( 'The default locale is ":locale". An x-default tag will automatically point to this URL if configured.', [ 'locale' => $this->defaultLocale ] ) }}
						</div>
					</x-artisanpack-alert>
				@endif
			</div>

			<x-slot:footer>
				<div class="flex justify-between items-center">
					<div>
						@if ( count( $hreflangEntries ) > 0 )
							<x-artisanpack-button
								wire:click="clearAll"
								wire:confirm="{{ __( 'Are you sure you want to remove all language URLs?' ) }}"
								size="sm"
								color="error"
								outline
							>
								{{ __( 'Clear All' ) }}
							</x-artisanpack-button>
						@endif
					</div>

					<x-artisanpack-button
						wire:click="save"
						color="primary"
						:disabled="$isSaving"
					>
						<span wire:loading.remove wire:target="save">
							{{ __( 'Save Language URLs' ) }}
						</span>
						<span wire:loading wire:target="save">
							{{ __( 'Saving...' ) }}
						</span>
					</x-artisanpack-button>
				</div>
			</x-slot:footer>
		</x-artisanpack-card>
	@endif
</div>
