<div>
	<h3>Hreflang Editor Test View</h3>

	@if ( ! $this->isEnabled )
		<div class="disabled-notice">Hreflang is disabled</div>
	@else
		<div class="entry-count">{{ $this->entryCount }} entries</div>

		@foreach ( $hreflangEntries as $index => $entry )
			<div class="hreflang-entry" wire:key="entry-{{ $index }}">
				<input type="text" wire:model="hreflangEntries.{{ $index }}.locale" class="locale-input" />
				<input type="text" wire:model="hreflangEntries.{{ $index }}.url" class="url-input" />
				<button wire:click="removeHreflang({{ $index }})" class="remove-btn">Remove</button>
			</div>
		@endforeach

		<button wire:click="addHreflang" class="add-btn">Add Language</button>
		<button wire:click="duplicateEntry" class="duplicate-btn">Duplicate URL</button>
		<button wire:click="save" class="save-btn">Save</button>
		<button wire:click="clearAll" class="clear-btn">Clear All</button>

		@if ( $this->defaultLocale )
			<div class="default-locale">Default: {{ $this->defaultLocale }}</div>
		@endif

		<select class="available-locales">
			@foreach ( $this->availableLocales as $locale )
				<option value="{{ $locale['value'] }}">{{ $locale['label'] }}</option>
			@endforeach
		</select>
	@endif
</div>
