<section class="min-h-screen bg-[#f3f3f9]">
    <div class="min-h-[calc(100vh-theme('spacing.12'))] space-y-4 p-4 relative">
        <div class="flex items-center justify-between">
            <flux:heading level="4" size="md" class="">{{ $heading ?? null }}</flux:heading>
                @isset($breadcrumbs)
            <flux:breadcrumbs>
                    {{ $breadcrumbs }}
            </flux:breadcrumbs>
                @endisset
        </div>
        
        {{ $slot }}
    </div>
    <div class="h-12 p-2 bg-white border-t flex items-center justify-center">
        <flux:text size="xs" class="">Copyright © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</flux:text>
    </div>
</section>