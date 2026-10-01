<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

/**
 * Cree un compte administrateur — le premier d'une installation neuve.
 *
 * Une base neuve n'a aucun compte, et l'administration n'en cree que par
 * invitation, depuis un compte administrateur : sans cette commande, personne
 * ne pouvait entrer. Le DatabaseSeeder comblait le vide avec
 * « test@example.com » / « password », identifiant public ; il ne cree plus
 * aucun compte.
 *
 * LE MOT DE PASSE N'EST JAMAIS UN ARGUMENT. Il est demande en saisie masquee :
 * passe sur la ligne de commande, il resterait dans l'historique du shell et
 * serait visible des autres processus. Nom et adresse peuvent, eux, etre
 * fournis en option.
 *
 * Il obeit a la meme regle que partout ailleurs, Password::default() : en
 * production, douze caracteres, majuscules, minuscules, chiffres, symboles, et
 * absent des fuites connues.
 *
 * Sans terminal pour repondre (--no-interaction, script), la commande echoue :
 * elle ne pose jamais de mot de passe par defaut.
 */
class CreeUnAdministrateur extends Command
{
    protected $signature = 'compte:creer-administrateur
        {--nom= : Nom affiche du compte}
        {--email= : Adresse de connexion}';

    protected $description = 'Cree un compte administrateur, mot de passe saisi de facon masquee.';

    public function handle(): int
    {
        if (! Role::where('name', 'administrateur')->where('guard_name', 'web')->exists()) {
            $this->error('Le rôle « administrateur » n\'existe pas. Lancez d\'abord : php artisan db:seed');

            return self::FAILURE;
        }

        if (! $this->input->isInteractive()) {
            $this->error('Commande interactive : le mot de passe doit être saisi au clavier.');

            return self::FAILURE;
        }

        $donnees = [
            'name' => $this->option('nom') ?: $this->ask('Nom'),
            'email' => $this->option('email') ?: $this->ask('Adresse e-mail'),
            'password' => $this->secret('Mot de passe'),
            'password_confirmation' => $this->secret('Confirmez le mot de passe'),
        ];

        // Memes bornes que l'invitation depuis l'ecran des utilisateurs.
        $validation = Validator::make($donnees, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);

        if ($validation->fails()) {
            foreach ($validation->errors()->all() as $erreur) {
                $this->error($erreur);
            }

            return self::FAILURE;
        }

        $compte = DB::transaction(function () use ($donnees) {
            // Le cast « hashed » du modele hache le mot de passe.
            $compte = User::create([
                'name' => $donnees['name'],
                'email' => $donnees['email'],
                'password' => $donnees['password'],
                'statut' => User::ACTIF,
            ]);
            $compte->forceFill(['email_verified_at' => now()])->save();
            $compte->assignRole('administrateur');

            return $compte;
        });

        $this->info("Administrateur créé : {$compte->email}");

        return self::SUCCESS;
    }
}
