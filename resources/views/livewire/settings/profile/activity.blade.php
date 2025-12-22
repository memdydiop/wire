<?php
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

new class extends Component {
    public function activities()
    {
        return Activity::causedBy(Auth::user())
            ->orWhere('subject_type', 'App\Models\User')
            ->where('subject_id', Auth::id())
            ->latest()
            ->take(20)
            ->get();
    }
}; 
?>

<div>
    <div class="mb-4">
        <flux:heading level="2" size="sm">Activité du compte</flux:heading>
        <p class="text-sm text-gray-500 mt-1">Historique de vos connexions et activités</p>
    </div>

    <div class="space-y-6">
        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <flux:icon.calendar variant="micro" class="text-blue-600" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Membre depuis</p>
                        <p class="text-lg font-semibold">{{ Auth::user()->getDaysSinceRegistration() }} jours</p>
                    </div>
                </div>
            </div>

            @if(Auth::user()->last_login_at)
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="size-10 rounded-full bg-green-100 flex items-center justify-center">
                            <flux:icon.clock variant="micro" class="text-green-600" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Dernière connexion</p>
                            <p class="text-lg font-semibold">{{ Auth::user()->last_login_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-full bg-purple-100 flex items-center justify-center">
                        <flux:icon.chart-bar variant="micro" class="text-purple-600" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Activités</p>
                        <p class="text-lg font-semibold">{{ $this->activities()->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Journal d'activité -->
        <div class="bg-gray-50 rounded-lg p-4">
            <flux:heading level="3" size="xs" class="mb-3">Journal d'activité récente</flux:heading>
            <div class="space-y-2">
                @forelse($this->activities() as $activity)
                    <div class="flex items-start gap-3 p-3 bg-white rounded border border-gray-200">
                        <div class="size-8 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                            <flux:icon.document-text variant="micro" class="text-gray-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">{{ $activity->description }}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $activity->created_at->format('d/m/Y à H:i') }}
                                • {{ $activity->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-500">
                        <flux:icon.document-text class="mx-auto text-gray-400 mb-2" />
                        <p class="text-sm">Aucune activité récente</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Avertissement inactivité -->
        @if(Auth::user()->isInactive(90))
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex gap-3">
                    <flux:icon.exclamation-triangle class="text-yellow-600 flex-shrink-0" />
                    <div>
                        <p class="text-sm font-medium text-yellow-900">Compte inactif</p>
                        <p class="text-xs text-yellow-700 mt-1">
                            Votre compte est inactif depuis plus de 90 jours. Pensez à vous connecter régulièrement pour
                            maintenir votre compte actif.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>