@props([
	'value' => '',
	'color' => 'neutral',
])

<span {{ $attributes->class(['badge', 'badge-' . $color]) }}>{{ $value }}</span>
