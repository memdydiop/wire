<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen ">
        <flux:sidebar sticky stashable class="border-e border-primary bg-primary">
            <div class="flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                    <x-app-logo />
                </a>
                <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />
            </div>

            <flux:navlist variant="outline">

                <flux:navlist.group :heading="__('Platform')" class="grid">
                    <flux:navlist.item icon="home" iconVariant="solid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>

                    <flux:navlist.group heading="Contrôle d'accès" icon="shield-check" iconVariant="solid" expandable :expanded="false">
                        <flux:navlist.item :href="route('admin.users.index')" :current="request()->routeIs('admin.users.index')" wire:navigate>Utilisateurs</flux:navlist.item>
                        <flux:navlist.item :href="route('admin.users.invitations')" :current="request()->routeIs('admin.users.invitations')" wire:navigate>Invitations</flux:navlist.item>
                        <flux:navlist.item :href="route('admin.roles.index')" :current="request()->routeIs('admin.roles.index')" wire:navigate>{{ __('Rôles et Permissions') }}</flux:navlist.item>
                    </flux:navlist.group>
                    
                </flux:navlist.group>

            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist>

            <!-- Desktop User Menu -->
            <flux:dropdown class="lg:block hidden" position="bottom" align="start">
            @if (auth()->user()->hasCustomAvatar())
                <flux:profile
                    :username="auth()->user()->getUsername()"
                    :avatar="Storage::url(auth()->user()->avatar_url)"
                    icon:trailing="chevrons-up-down"
                    data-test="sidebar-menu-button"
                />
            @else
                <flux:profile 
                    :username="auth()->user()->getUsername()"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down" 
                    data-test="sidebar-menu-button" />
            @endif

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    @if (auth()->user()->hasCustomAvatar())
                                    <flux:avatar :src="Storage::url(auth()->user()->avatar_url)" size="sm" />
                                    @else
                                    <flux:avatar :initials="auth()->user()->initials()" size="sm" />
                                    @endif
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-medium">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden  bg-primary shadow">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-3" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                @if (auth()->user()->hasCustomAvatar())
                <flux:profile
                    :username="auth()->user()->getUsername()"
                    :avatar="Storage::url(auth()->user()->avatar_url)"
                    icon:trailing="chevrons-up-down"
                    data-test="sidebar-menu-button"
                />
            @else
                <flux:profile 
                    :username="auth()->user()->getUsername()"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down" 
                    data-test="sidebar-menu-button" />
            @endif

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">

                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    @if (auth()->user()->hasCustomAvatar())
                                    <flux:avatar :src="Storage::url(auth()->user()->avatar_url)" size="sm" circle />
                                    @else
                                    <flux:avatar :initials="auth()->user()->initials()" size="sm" circle />
                                    @endif
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-medium">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
