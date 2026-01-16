<div {{ $attributes->merge( [ 'class' => 'card' ] ) }}>{{ $header ?? '' }}{{ $slot }}{{ $footer ?? '' }}</div>
