<?php
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    #[Computed]
    public function activities()
    {
        return Activity::causedBy(Auth::user())
            ->latest()
            ->paginate(10);
    }

    public function getDaysSinceRegistration() {
        return Auth::user()->created_at->diffInDays(now());
    }
};
?>

<div>
    <div class="mb-6">
        <flux:heading level="2">Activité du compte</flux:heading>
        <flux:subheading>Historique de vos connexions et actions récentes.</flux:subheading>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm flex items-center gap-4">
            <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                <flux:icon.calendar variant="mini" />
            </div>
            <div>
                <p class="text-sm text-gray-500">Ancienneté</p>
                <p class="text-xl font-bold text-gray-900">{{ $this->getDaysSinceRegistration() }} jours</p>
            </div>
        </div>

        <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm flex items-center gap-4">
            <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                <flux:icon.clock variant="mini" />
            </div>
            <div>
                <p class="text-sm text-gray-500">Dernière connexion</p>
                {{-- Utilisation de translatedFormat pour le français --}}
                <p class="text-xl font-bold text-gray-900">
                    {{ Auth::user()->last_login_at ? Auth::user()->last_login_at->diffForHumans() : '-' }}
                </p>
            </div>
        </div>

        <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm flex items-center gap-4">
            <div class="p-3 bg-purple-50 text-purple-600 rounded-lg">
                <flux:icon.list-bullet variant="mini" />
            </div>
            <div>
                <p class="text-sm text-gray-500">Total actions</p>
                <p class="text-xl font-bold text-gray-900">{{ $this->activities->total() }}</p>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="flow-root">
            <ul role="list" class="-mb-8">
                @forelse($this->activities as $activity)
                    <li>
                        <div class="relative pb-8">
                            @if (!$loop->last)
                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center ring-8 ring-white">
                                        {{-- Logique d'icône plus robuste avec Str::contains --}}
                                        @if(Str::contains($activity->description, ['login', 'connexion']))
                                            <flux:icon.arrow-right-end-on-rectangle class="size-4 text-green-600" />
                                        @elseif(Str::contains($activity->description, ['update', 'modification', 'mise à jour']))
                                            <flux:icon.pencil class="size-4 text-blue-600" />
                                        @elseif(Str::contains($activity->description, ['delete', 'suppression']))
                                            <flux:icon.trash class="size-4 text-red-600" />
                                        @else
                                            <flux:icon.document-text class="size-4 text-gray-500" />
                                        @endif
                                    </span>
                                </div>
                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                    <div>
                                        <p class="text-sm text-gray-900">{{ $activity->description }}</p>
                                        {{-- Affichage JSON plus propre --}}
                                        @if($activity->properties && $activity->properties->count() > 0)
                                            <div class="mt-1 text-xs text-gray-500 font-mono bg-gray-50 p-1 rounded inline-block">
                                                {{ Str::limit($activity->properties, 60) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                        {{-- Formatage localisé --}}
                                        <time datetime="{{ $activity->created_at }}">
                                            {{ $activity->created_at->translatedFormat('d M H:i') }}
                                        </time>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <div class="flex flex-col items-center justify-center py-6 text-gray-500">
                        <flux:icon.inbox class="size-8 mb-2 opacity-50"/>
                        <p class="text-sm">Aucune activité enregistrée pour le moment.</p>
                    </div>
                @endforelse
            </ul>
        </div>

        <div class="pt-4">
            {{ $this->activities->links() }}
        </div>
    </div>
</div>