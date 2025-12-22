<?php

use App\Models\User;
use Livewire\Volt\Component;

new class extends Component {
    public ?User $user = null;

    public function mount(User $user): void
    {
        $this->loadUser($user);
    }

    public function loadUser(User $user)
    {
        $this->user = User::with(['roles', 'permissions'])->findOrFail($user->id);
    }
}; ?>

<x-layouts.app.content heading="User Permissions" subheading="Profile utilisateur">
    
    <x-user.layout :user="$user">
        permissions
        
    </x-user.layout>

</x-layouts.app.content>