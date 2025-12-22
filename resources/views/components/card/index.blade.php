

<div {{ $attributes->merge(['class' => 'block rounded-lg bg-white shadow-card']) }}>
    @if(isset($header))
        {{ $header }}
    @endif

    @if(isset($title) || isset($body))
        <div>
            @isset($title)
                {{ $title }}
            @endisset

            @isset($body)
                {{ $body }}
            @endisset
        </div>
    @else
        {{ $slot }}
    @endif

    @isset($footer)
        {{ $footer }}
    @endisset
</div>