<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('R00t7414'),
            'phone' => fake()->phoneNumber(),
            'country' => fake()->country(),
            'city' => fake()->city(),
            'address' => fake()->address(),
            'remember_token' => Str::random(10),
            'two_factor_secret' => Str::random(10),
            'two_factor_recovery_codes' => Str::random(10),
            'two_factor_confirmed_at' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model does not have two-factor authentication configured.
     */
    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Créer un utilisateur avec une date de création aléatoire dans les X derniers jours
     * Inclut des heures/minutes/secondes aléatoires
     */
    public function createdDaysAgo(int $minDays = 1, int $maxDays = 365): static
    {
        return $this->state(function (array $attributes) use ($minDays, $maxDays) {
            $createdAt = now()
                ->subDays(rand($minDays, $maxDays))
                ->setHour(rand(0, 23))
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59));
            
            $updatedAt = $createdAt->copy()
                ->addDays(rand(0, 30))
                ->addHours(rand(0, 23))
                ->addMinutes(rand(0, 59))
                ->addSeconds(rand(0, 59));
            
            return [
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ];
        });
    }

    /**
     * Créer un utilisateur récent (dernière semaine)
     */
    public function recent(): static
    {
        return $this->createdDaysAgo(1, 7);
    }

    /**
     * Créer un utilisateur du mois dernier
     */
    public function lastMonth(): static
    {
        return $this->createdDaysAgo(7, 30);
    }

    /**
     * Créer un utilisateur ancien (plus de 6 mois)
     */
    public function old(): static
    {
        return $this->createdDaysAgo(180, 730);
    }

    /**
     * Créer un utilisateur avec une date de création spécifique
     */
    public function createdAt(\DateTime|\Carbon\Carbon|string $date): static
    {
        return $this->state(function (array $attributes) use ($date) {
            $createdAt = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
            return [
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        });
    }

    /**
     * Créer un utilisateur suspendu
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'suspended_at' => now()->subDays(rand(1, 30)),
        ]);
    }

    /**
     * Créer un utilisateur inactif (pas de connexion récente)
     * Inclut des heures/minutes/secondes aléatoires
     */
    public function inactive(int $daysAgo = 90): static
    {
        return $this->state(function (array $attributes) use ($daysAgo) {
            $lastLogin = now()
                ->subDays(rand($daysAgo, $daysAgo * 2))
                ->setHour(rand(0, 23))
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59));
            
            return [
                'last_login_at' => $lastLogin,
                'last_login_ip' => fake()->ipv4(),
            ];
        });
    }

    /**
     * Créer un utilisateur actif (connexion récente)
     * Inclut des heures/minutes/secondes aléatoires
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $lastLogin = now()
                ->subDays(rand(0, 7))
                ->setHour(rand(0, 23))
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59));
            
            return [
                'last_login_at' => $lastLogin,
                'last_login_ip' => fake()->ipv4(),
            ];
        });
    }

    /**
     * Créer un utilisateur avec une distribution temporelle plus réaliste
     * (heures de bureau : 8h-18h, jours ouvrables)
     */
    public function realisticTiming(): static
    {
        return $this->state(function (array $attributes) {
            $createdAt = now()
                ->subDays(rand(1, 365))
                ->setHour(rand(8, 18))  // Heures de bureau
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59));
            
            // S'assurer que c'est un jour ouvrable (Lundi-Vendredi)
            while ($createdAt->isWeekend()) {
                $createdAt->subDay();
            }
            
            $updatedAt = $createdAt->copy()
                ->addDays(rand(0, 30))
                ->setHour(rand(8, 18))
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59));
            
            return [
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ];
        });
    }

    /**
     * Créer un utilisateur avec un timestamp très précis (incluant microsecondes)
     */
    public function withPreciseTimestamp(): static
    {
        return $this->state(function (array $attributes) {
            $createdAt = now()
                ->subDays(rand(1, 365))
                ->setHour(rand(0, 23))
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59))
                ->setMicrosecond(rand(0, 999999));
            
            return [
                'created_at' => $createdAt,
                'updated_at' => $createdAt->copy()->addMinutes(rand(1, 120)),
            ];
        });
    }

}