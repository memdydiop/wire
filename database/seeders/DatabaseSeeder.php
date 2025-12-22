<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $this->command->info('🌱 Création d\'utilisateurs avec horaires réalistes...');

        // ========== SCÉNARIO 1 : Utilisateurs créés aujourd'hui à différentes heures ==========
        $this->command->info('📅 Utilisateurs créés aujourd\'hui...');
        for ($i = 0; $i < 5; $i++) {
            User::factory()->withoutTwoFactor()->create()->each(function ($user) {
                $createdAt = now()
                    ->setHour(rand(8, 18))
                    ->setMinute(rand(0, 59))
                    ->setSecond(rand(0, 59));
                
                $user->created_at = $createdAt;
                $user->updated_at = $createdAt;
                $user->last_login_at = $createdAt->copy()->addMinutes(rand(5, 60));
                $user->last_login_ip = fake()->ipv4();
                $user->saveQuietly();
                
                $this->command->line("  ✓ Créé à {$createdAt->format('H:i:s')}");
            });
        }

        // ========== SCÉNARIO 2 : Utilisateurs cette semaine (distribution réaliste) ==========
        $this->command->info('📅 Utilisateurs de cette semaine...');
        User::factory(10)->withoutTwoFactor()->create()->each(function ($user) {
            $createdAt = now()
                ->subDays(rand(1, 7))
                ->setHour(rand(8, 20))  // Heures d'activité normales
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59));
            
            // Éviter les week-ends pour plus de réalisme
            while ($createdAt->isWeekend()) {
                $createdAt->subDay();
            }
            
            $updatedAt = $createdAt->copy()
                ->addHours(rand(1, 48))
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59));
            
            $user->created_at = $createdAt;
            $user->updated_at = $updatedAt;
            $user->last_login_at = now()->subHours(rand(1, 168));
            $user->last_login_ip = fake()->ipv4();
            $user->saveQuietly();
        });

        // ========== SCÉNARIO 3 : Utilisateurs du mois dernier (pattern d'inscription) ==========
        $this->command->info('📅 Utilisateurs du mois dernier...');
        User::factory(15)->withoutTwoFactor()->create()->each(function ($user) {
            // Simuler un pattern : plus d'inscriptions en début de mois
            $dayOfMonth = rand(1, 30);
            $weight = $dayOfMonth <= 10 ? rand(1, 10) : rand(15, 30);
            
            $createdAt = now()
                ->subMonth()
                ->setDay($weight)
                ->setHour(rand(0, 23))
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59));
            
            $updatedAt = $createdAt->copy()
                ->addDays(rand(1, 15))
                ->addHours(rand(0, 23))
                ->addMinutes(rand(0, 59))
                ->addSeconds(rand(0, 59));
            
            $user->created_at = $createdAt;
            $user->updated_at = $updatedAt;
            $user->saveQuietly();
        });

        // ========== SCÉNARIO 4 : Utilisateurs anciens avec activité variée ==========
        $this->command->info('📅 Utilisateurs anciens...');
        User::factory(20)->withoutTwoFactor()->create()->each(function ($user) {
            // Création entre 3 mois et 2 ans
            $createdAt = now()
                ->subDays(rand(90, 730))
                ->setHour(rand(0, 23))
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59));
            
            // Activité variable : certains actifs, d'autres non
            $isActive = rand(1, 100) > 30; // 70% actifs
            
            if ($isActive) {
                $lastLogin = now()
                    ->subDays(rand(1, 30))
                    ->setHour(rand(8, 22))
                    ->setMinute(rand(0, 59))
                    ->setSecond(rand(0, 59));
                
                $updatedAt = $lastLogin->copy()
                    ->subDays(rand(0, 10))
                    ->addHours(rand(0, 23));
            } else {
                $lastLogin = $createdAt->copy()->addDays(rand(1, 60));
                $updatedAt = $lastLogin;
            }
            
            $user->created_at = $createdAt;
            $user->updated_at = $updatedAt;
            $user->last_login_at = $lastLogin;
            $user->last_login_ip = fake()->ipv4();
            $user->saveQuietly();
        });

        // ========== SCÉNARIO 5 : Utilisateurs suspendus avec historique ==========
        $this->command->info('🔒 Utilisateurs suspendus...');
        User::factory(5)->withoutTwoFactor()->create()->each(function ($user) {
            $createdAt = now()
                ->subDays(rand(30, 365))
                ->setHour(rand(0, 23))
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59));
            
            // Suspension quelques semaines après la création
            $suspendedAt = $createdAt->copy()
                ->addDays(rand(14, 90))
                ->setHour(rand(9, 17))  // Suspensions pendant heures de bureau
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59));
            
            $user->created_at = $createdAt;
            $user->updated_at = $suspendedAt;  // Updated_at = date de suspension
            $user->suspended_at = $suspendedAt;
            $user->last_login_at = $createdAt->copy()->addDays(rand(1, 14));
            $user->last_login_ip = fake()->ipv4();
            $user->saveQuietly();
        });

        // ========== SCÉNARIO 6 : Vague d'inscriptions (simulation événement marketing) ==========
        $this->command->info('🎉 Vague d\'inscriptions (événement marketing)...');
        $eventDate = now()->subDays(45); // Il y a 45 jours
        
        User::factory(15)->withoutTwoFactor()->create()->each(function ($user) use ($eventDate) {
            // Inscriptions concentrées sur 3 jours
            $createdAt = $eventDate->copy()
                ->addDays(rand(0, 3))
                ->setHour(rand(10, 20))  // Pic d'activité
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59));
            
            $updatedAt = $createdAt->copy()
                ->addHours(rand(1, 72))
                ->addMinutes(rand(0, 59))
                ->addSeconds(rand(0, 59));
            
            $user->created_at = $createdAt;
            $user->updated_at = $updatedAt;
            $user->last_login_at = now()->subDays(rand(1, 45));
            $user->last_login_ip = fake()->ipv4();
            $user->saveQuietly();
        });

        // ========== Utilisateur Ghost ==========
        $this->command->info('👻 Création de l\'utilisateur Ghost...');
        $ghostUser = User::factory()->withoutTwoFactor()->create([
            'name' => 'Ghost User',
            'email' => 'ghost@user.com',
            'username' => 'systeme',
            'country' => 'Côte d\'Ivoire',
            'city' => 'Abidjan',
            'address' => 'Riviera Route Abatta',
            'phone' => '+2250789124977',
        ]);
        
        $ghostUser->assignRole('ghost');
        
        // Ghost créé il y a 3 ans à minuit précis (symbolique)
        $ghostCreated = now()
            ->subYears(3)
            ->setHour(0)
            ->setMinute(0)
            ->setSecond(0)
            ->setMicrosecond(0);
        
        $ghostUser->created_at = $ghostCreated;
        $ghostUser->updated_at = $ghostCreated;
        $ghostUser->last_login_at = now()
            ->setHour(rand(0, 23))
            ->setMinute(rand(0, 59))
            ->setSecond(rand(0, 59));
        $ghostUser->last_login_ip = '127.0.0.1';
        $ghostUser->saveQuietly();

        $this->command->info('✅ Seeding terminé !');
        $this->command->table(
            ['Métrique', 'Valeur'],
            [
                ['Total utilisateurs', User::count()],
                ['Actifs (7 derniers jours)', User::where('last_login_at', '>=', now()->subDays(7))->count()],
                ['Suspendus', User::whereNotNull('suspended_at')->count()],
                ['Créés aujourd\'hui', User::whereDate('created_at', today())->count()],
                ['Créés cette semaine', User::where('created_at', '>=', now()->subWeek())->count()],
            ]
        );
    }
}