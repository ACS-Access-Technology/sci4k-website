# Lot 1 — Socle Laravel et actualités : plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rendre les actualités du site SCI4K éditables depuis une administration Laravel, dans les deux langues, sans casser les adresses existantes.

**Architecture:** Une application Laravel unique sert le site public en Blade et l'administration sous `/admin`. Le contenu quitte le HTML et le dictionnaire JavaScript pour rejoindre la base. Les langues sont stockées en colonnes suffixées (`titre_fr`, `titre_en`), sans bibliothèque de traduction.

**Tech Stack:** Laravel 13, starter kit Livewire (Livewire 4, Tailwind, Flux UI), MySQL, `spatie/laravel-permission`, `spatie/laravel-medialibrary`, Pest.

**Spec:** `docs/superpowers/specs/2026-08-23-lot1-actualites-design.md`

## Global Constraints

- PHP 8.3 minimum — disponible localement (8.3, 8.4, 8.5).
- Laravel 13.x.
- Toute colonne de texte affichée existe en deux versions : suffixe `_fr` et `_en`.
- Les slugs existants sont repris à l'identique ; aucune adresse d'article ne doit cesser de fonctionner.
- Aucune dépendance sous licence non libre. Vérifier Flux UI avant usage (édition Pro payante).
- Le CSS et le JavaScript de `frontoffice/assets/` sont repris sans réécriture.
- Messages de commit en français, sans mention de co-auteur.
- Le dossier de travail est `/Applications/MAMP/htdocs/Projects/EDANIlyasK`.

---

## Structure des fichiers

Le projet Laravel est créé dans un sous-dossier `app-laravel/` à la racine du dépôt, pour cohabiter avec `frontoffice/` et `backoffice/` pendant la transition.

| Fichier | Responsabilité |
|---|---|
| `app-laravel/app/Models/Article.php` | entité article, portées de requête, accesseurs de langue |
| `app-laravel/app/Models/Categorie.php` | entité catégorie |
| `app-laravel/app/Http/Controllers/ActualiteController.php` | pages publiques : liste et détail |
| `app-laravel/app/Livewire/Admin/ArticleListe.php` | tableau d'administration : tri, filtre, recherche |
| `app-laravel/app/Livewire/Admin/ArticleFormulaire.php` | création et édition, onglets FR/EN |
| `app-laravel/database/migrations/*_create_categories_table.php` | table catégories |
| `app-laravel/database/migrations/*_create_articles_table.php` | table articles |
| `app-laravel/database/seeders/CategorieSeeder.php` | les 7 catégories |
| `app-laravel/database/seeders/ArticleImportSeeder.php` | reprise des 12 articles existants |
| `app-laravel/resources/views/public/actualites/index.blade.php` | liste publique |
| `app-laravel/resources/views/public/actualites/detail.blade.php` | article public |
| `app-laravel/tests/Feature/*` | tests |

---

### Task 1 : Socle Laravel et base de données

**Files:**
- Create: `app-laravel/` (projet complet)
- Create: `app-laravel/.env`
- Modify: `.gitignore`

**Interfaces:**
- Consumes: rien
- Produces: une application Laravel 13 démarrable, connectée à une base MySQL nommée `sci4k`

- [ ] **Step 1: Vérifier les prérequis**

```bash
php -v          # attendu : 8.3 ou plus
composer -V     # attendu : 2.x
node -v         # attendu : 20 ou plus
```

- [ ] **Step 2: Créer le projet avec le starter kit Livewire**

```bash
cd /Applications/MAMP/htdocs/Projects/EDANIlyasK
composer global update laravel/installer
laravel new app-laravel --livewire
```

Si l'installateur pose des questions, répondre : starter kit **Livewire**, tests **Pest**, base de données **MySQL**.

- [ ] **Step 3: Créer la base de données**

```bash
/Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS sci4k CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

- [ ] **Step 4: Configurer la connexion**

Dans `app-laravel/.env` :

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=8889
DB_DATABASE=sci4k
DB_USERNAME=root
DB_PASSWORD=root
```

Le port 8889 est celui de MAMP. Le vérifier dans les réglages MAMP si la connexion échoue.

- [ ] **Step 5: Lancer les migrations initiales**

```bash
cd app-laravel && php artisan migrate
```

Attendu : les tables `users`, `sessions`, `cache`, `jobs` sont créées.

- [ ] **Step 6: Vérifier que l'application démarre**

```bash
cd app-laravel && npm install && npm run build
php artisan serve
```

Ouvrir `http://127.0.0.1:8000` : la page d'accueil du starter kit s'affiche, avec les liens de connexion et d'inscription.

- [ ] **Step 7: Exclure du dépôt ce qui ne doit pas y être**

Ajouter à `.gitignore` à la racine :

```
app-laravel/vendor/
app-laravel/node_modules/
app-laravel/.env
app-laravel/public/build/
app-laravel/storage/*.key
```

- [ ] **Step 8: Commit**

```bash
cd /Applications/MAMP/htdocs/Projects/EDANIlyasK
git add -A
git commit -m "feat(socle): initialise l'application Laravel avec le starter kit Livewire"
```

---

### Task 2 : Trois rôles et accès à l'administration

**Files:**
- Create: `app-laravel/database/seeders/RoleSeeder.php`
- Create: `app-laravel/tests/Feature/AccesAdminTest.php`
- Modify: `app-laravel/app/Models/User.php`
- Modify: `app-laravel/routes/web.php`

**Interfaces:**
- Consumes: l'application de la tâche 1
- Produces: trois rôles nommés `administrateur`, `editeur`, `lecteur` ; une route `/admin` protégée ; `User::hasRole(string $role): bool`

- [ ] **Step 1: Installer le paquet de permissions**

```bash
cd app-laravel
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

- [ ] **Step 2: Écrire le test qui échoue**

`app-laravel/tests/Feature/AccesAdminTest.php` :

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $nom) {
        Role::findOrCreate($nom);
    }
});

it('renvoie un visiteur non connecte vers la connexion', function () {
    $this->get('/admin')->assertRedirect('/login');
});

it('laisse entrer un editeur', function () {
    $user = User::factory()->create();
    $user->assignRole('editeur');

    $this->actingAs($user)->get('/admin')->assertOk();
});

it('laisse entrer un administrateur', function () {
    $user = User::factory()->create();
    $user->assignRole('administrateur');

    $this->actingAs($user)->get('/admin')->assertOk();
});

it('refuse un utilisateur sans role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});
```

- [ ] **Step 3: Lancer le test pour le voir échouer**

```bash
cd app-laravel && php artisan test --filter=AccesAdminTest
```

Attendu : ÉCHEC — la route `/admin` n'existe pas (404 au lieu des redirections attendues).

- [ ] **Step 4: Rendre le modèle utilisateur porteur de rôles**

Dans `app-laravel/app/Models/User.php`, ajouter le trait :

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;
```

- [ ] **Step 5: Déclarer la route protégée**

Dans `app-laravel/routes/web.php` :

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:administrateur|editeur|lecteur'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', fn () => view('admin.tableau-de-bord'))->name('tableau-de-bord');
    });
```

- [ ] **Step 6: Créer la vue du tableau de bord**

`app-laravel/resources/views/admin/tableau-de-bord.blade.php` :

```blade
<x-layouts.app title="Tableau de bord">
    <h1 class="text-2xl font-semibold">Administration SCI4K</h1>
    <p class="mt-2 text-sm text-zinc-500">Connecté en tant que {{ auth()->user()->name }}.</p>
</x-layouts.app>
```

- [ ] **Step 7: Enregistrer le middleware de rôle**

Dans `app-laravel/bootstrap/app.php`, dans `withMiddleware` :

```php
$middleware->alias([
    'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
]);
```

- [ ] **Step 8: Écrire le seeder des rôles**

`app-laravel/database/seeders/RoleSeeder.php` :

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['administrateur', 'editeur', 'lecteur'] as $nom) {
            Role::findOrCreate($nom);
        }
    }
}
```

- [ ] **Step 9: Lancer les tests**

```bash
cd app-laravel && php artisan test --filter=AccesAdminTest
```

Attendu : les 4 tests passent.

- [ ] **Step 10: Commit**

```bash
git add -A
git commit -m "feat(admin): protege l'administration par trois roles"
```

---

### Task 3 : Bascule de langue du backoffice

**Files:**
- Create: `app-laravel/lang/en.json`
- Create: `app-laravel/app/Http/Middleware/AppliqueLangue.php`
- Create: `app-laravel/app/Http/Controllers/LangueController.php`
- Create: `app-laravel/resources/views/components/bascule-langue.blade.php`
- Create: `app-laravel/tests/Feature/BasculeLangueTest.php`
- Modify: `app-laravel/bootstrap/app.php`
- Modify: `app-laravel/routes/web.php`
- Modify: `app-laravel/resources/views/admin/tableau-de-bord.blade.php`

**Interfaces:**
- Consumes: l'administration protégée de la tâche 2
- Produces: `app()->getLocale()` vaut `fr` ou `en` selon le choix de l'utilisateur, conservé en session ; helper Blade `<x-bascule-langue />` ; les tâches suivantes lisent la langue par `app()->getLocale()`

- [ ] **Step 1: Écrire le test qui échoue**

`app-laravel/tests/Feature/BasculeLangueTest.php` :

```php
<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('administrateur');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

it('sert l administration en francais par defaut', function () {
    $this->actingAs($this->admin)->get('/admin');

    expect(app()->getLocale())->toBe('fr');
});

it('bascule en anglais et le retient', function () {
    $this->actingAs($this->admin)->get('/langue/en')->assertRedirect();

    $this->actingAs($this->admin)->get('/admin');

    expect(app()->getLocale())->toBe('en');
});

it('revient au francais', function () {
    $this->actingAs($this->admin)->get('/langue/en');
    $this->actingAs($this->admin)->get('/langue/fr')->assertRedirect();

    $this->actingAs($this->admin)->get('/admin');

    expect(app()->getLocale())->toBe('fr');
});

it('refuse une langue inconnue et reste en francais', function () {
    $this->actingAs($this->admin)->get('/langue/de')->assertNotFound();

    $this->actingAs($this->admin)->get('/admin');

    expect(app()->getLocale())->toBe('fr');
});

it('affiche les libelles en anglais une fois bascule', function () {
    $this->actingAs($this->admin)->get('/langue/en');

    $this->actingAs($this->admin)->get('/admin')->assertSee('Dashboard');
});

it('affiche les libelles en francais par defaut', function () {
    $this->actingAs($this->admin)->get('/admin')->assertSee('Tableau de bord');
});
```

- [ ] **Step 2: Lancer le test pour le voir échouer**

```bash
cd app-laravel && php artisan test --filter=BasculeLangueTest
```

Attendu : ÉCHEC — la route `/langue/{code}` n'existe pas.

- [ ] **Step 3: Créer le dictionnaire anglais**

Laravel cherche `lang/en.json` quand la locale vaut `en`. Les clés sont les
textes français eux-mêmes : les vues écrivent `{{ __('Tableau de bord') }}`,
qui rend « Tableau de bord » en français et « Dashboard » en anglais.

`app-laravel/lang/en.json` :

```json
{
    "Tableau de bord": "Dashboard",
    "Administration SCI4K": "SCI4K administration",
    "Connecté en tant que :nom.": "Signed in as :nom.",
    "Articles": "Articles",
    "Actualités": "News",
    "Catégories": "Categories",
    "Utilisateurs": "Users",
    "Français": "French",
    "Anglais": "English",
    "Enregistrer": "Save",
    "Annuler": "Cancel",
    "Créer": "Create",
    "Modifier": "Edit",
    "Supprimer": "Delete",
    "Rechercher": "Search",
    "Rechercher un titre…": "Search a title…",
    "Toutes les catégories": "All categories",
    "Tous les statuts": "All statuses",
    "Titre": "Title",
    "Résumé": "Summary",
    "Contenu": "Content",
    "Catégorie": "Category",
    "Date": "Date",
    "Date de publication": "Publication date",
    "Statut": "Status",
    "Publié": "Published",
    "Brouillon": "Draft",
    "Identifiant d'adresse": "URL slug",
    "Image de couverture": "Cover image",
    "Description pour les moteurs (160 signes)": "Meta description (160 characters)",
    "Choisir…": "Choose…",
    "Article enregistré.": "Article saved.",
    "Aucun article pour le moment.": "No articles yet.",
    "Se déconnecter": "Sign out"
}
```

Aucun fichier `lang/fr.json` n'est nécessaire : le français est la langue de
repli, rendue telle quelle par `__()`.

- [ ] **Step 4: Créer le contrôleur de bascule**

`app-laravel/app/Http/Controllers/LangueController.php` :

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LangueController extends Controller
{
    /** Les seules langues servies par le site. */
    public const LANGUES = ['fr', 'en'];

    public function basculer(string $code): RedirectResponse
    {
        abort_unless(in_array($code, self::LANGUES, true), 404);

        session(['langue' => $code]);

        return back();
    }
}
```

- [ ] **Step 5: Créer le middleware qui applique la langue**

`app-laravel/app/Http/Middleware/AppliqueLangue.php` :

```php
<?php

namespace App\Http\Middleware;

use App\Http\Controllers\LangueController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppliqueLangue
{
    /** Applique la langue retenue en session, francais par defaut. */
    public function handle(Request $request, Closure $suite): Response
    {
        $code = session('langue', 'fr');

        if (in_array($code, LangueController::LANGUES, true)) {
            app()->setLocale($code);
        }

        return $suite($request);
    }
}
```

- [ ] **Step 6: Enregistrer le middleware**

Dans `app-laravel/bootstrap/app.php`, à l'intérieur de `withMiddleware` :

```php
$middleware->web(append: [
    \App\Http\Middleware\AppliqueLangue::class,
]);
```

- [ ] **Step 7: Déclarer la route**

Dans `app-laravel/routes/web.php` :

```php
use App\Http\Controllers\LangueController;

Route::get('/langue/{code}', [LangueController::class, 'basculer'])->name('langue.basculer');
```

- [ ] **Step 8: Créer le composant de bascule**

`app-laravel/resources/views/components/bascule-langue.blade.php` :

```blade
@php($courante = app()->getLocale())

<a href="{{ route('langue.basculer', $courante === 'fr' ? 'en' : 'fr') }}"
   class="inline-flex h-9 min-w-9 items-center justify-center rounded-full border border-zinc-300 px-3 text-xs font-semibold text-zinc-700 hover:bg-zinc-100"
   aria-label="{{ $courante === 'fr' ? __('Passer en anglais') : __('Passer en français') }}">
    {{ $courante === 'fr' ? 'EN' : 'FR' }}
</a>
```

Le bouton affiche la langue vers laquelle il mène, comme sur le site public.

- [ ] **Step 9: Poser le bouton et traduire le tableau de bord**

`app-laravel/resources/views/admin/tableau-de-bord.blade.php` :

```blade
<x-layouts.app :title="__('Tableau de bord')">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">{{ __('Administration SCI4K') }}</h1>
        <x-bascule-langue />
    </div>
    <p class="mt-2 text-sm text-zinc-500">
        {{ __('Connecté en tant que :nom.', ['nom' => auth()->user()->name]) }}
    </p>
</x-layouts.app>
```

- [ ] **Step 10: Lancer les tests**

```bash
cd app-laravel && php artisan test --filter=BasculeLangueTest
```

Attendu : les 6 tests passent.

- [ ] **Step 11: Vérifier à l'œil**

```bash
cd app-laravel && php artisan serve
```

Se connecter, cliquer sur `EN` : les libellés passent en anglais et le bouton
affiche `FR`. Recliquer : retour au français. Changer de page : la langue tient.

- [ ] **Step 12: Commit**

```bash
git add -A
git commit -m "feat(admin): bascule de langue de l'administration"
```

---

## Conséquences sur les tâches suivantes

Ces points sont à porter dans les tâches concernées au moment de leur dispatch.

**Tâche « Tableau d'administration des articles »** — la liste affiche le titre
dans la langue courante, pas le titre français en dur :

- `$article->titre(app()->getLocale())` au lieu de `$article->titre_fr`
- `$categorie->nom(app()->getLocale())` au lieu de `$categorie->nom_fr`
- la recherche cherche dans les deux langues, ce que le plan prévoit déjà
- tous les libellés passent par `__()`
- le composant `<x-bascule-langue />` est posé dans l'en-tête de la page

**Tâche « Formulaire d'édition bilingue »** — attention à ne pas confondre les
deux mécanismes :

- les onglets FR/EN du formulaire pilotent le **contenu saisi**, ils ne
  dépendent pas de la langue de l'interface
- l'onglet ouvert par défaut suit la langue courante : interface en anglais,
  onglet English ouvert en premier
- tous les libellés du formulaire passent par `__()`

**Tâche « Vérification de bout en bout »** — deux contrôles s'ajoutent :

- basculer l'administration en anglais, vérifier que menus, colonnes et boutons
  sont traduits
- vérifier que la langue tient d'une page à l'autre et après reconnexion

---

### Task 4 : Table des catégories

**Files:**
- Create: `app-laravel/database/migrations/*_create_categories_table.php`
- Create: `app-laravel/app/Models/Categorie.php`
- Create: `app-laravel/database/seeders/CategorieSeeder.php`
- Create: `app-laravel/tests/Feature/CategorieTest.php`

**Interfaces:**
- Consumes: l'application de la tâche 1
- Produces: modèle `Categorie` avec `slug`, `nom_fr`, `nom_en`, `ordre` ; 7 catégories en base

- [ ] **Step 1: Écrire le test qui échoue**

`app-laravel/tests/Feature/CategorieTest.php` :

```php
<?php

use App\Models\Categorie;
use Database\Seeders\CategorieSeeder;

it('cree les sept categories du site', function () {
    $this->seed(CategorieSeeder::class);

    expect(Categorie::count())->toBe(7);
});

it('donne le nom dans la langue demandee', function () {
    $c = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);

    expect($c->nom('fr'))->toBe('Foncier');
    expect($c->nom('en'))->toBe('Land & Title');
});

it('refuse deux categories de meme slug', function () {
    Categorie::create(['slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1]);

    expect(fn () => Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Autre', 'nom_en' => 'Other', 'ordre' => 2,
    ]))->toThrow(Illuminate\Database\QueryException::class);
});
```

- [ ] **Step 2: Lancer le test pour le voir échouer**

```bash
cd app-laravel && php artisan test --filter=CategorieTest
```

Attendu : ÉCHEC — classe `App\Models\Categorie` introuvable.

- [ ] **Step 3: Créer la migration**

```bash
cd app-laravel && php artisan make:migration create_categories_table
```

Contenu de la méthode `up()` :

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();
    $table->string('nom_fr');
    $table->string('nom_en');
    $table->unsignedSmallInteger('ordre')->default(0);
    $table->timestamps();
});
```

- [ ] **Step 4: Créer le modèle**

`app-laravel/app/Models/Categorie.php` :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{
    protected $fillable = ['slug', 'nom_fr', 'nom_en', 'ordre'];

    /** Nom dans la langue demandee, francais par defaut. */
    public function nom(string $langue = 'fr'): string
    {
        return $langue === 'en' ? $this->nom_en : $this->nom_fr;
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'categorie_id');
    }
}
```

- [ ] **Step 5: Écrire le seeder**

`app-laravel/database/seeders/CategorieSeeder.php` :

```php
<?php

namespace Database\Seeders;

use App\Models\Categorie;
use Illuminate\Database\Seeder;

class CategorieSeeder extends Seeder
{
    /** Les sept categories du site. Six correspondent aux six services. */
    public function run(): void
    {
        $categories = [
            ['slug' => 'foncier',        'nom_fr' => 'Foncier',                  'nom_en' => 'Land & Title',           'ordre' => 1],
            ['slug' => 'construction',   'nom_fr' => 'Construction',             'nom_en' => 'Construction',           'ordre' => 2],
            ['slug' => 'gestion',        'nom_fr' => 'Gestion / Location',       'nom_en' => 'Rental Management',      'ordre' => 3],
            ['slug' => 'achat',          'nom_fr' => 'Achat',                    'nom_en' => 'Buying',                 'ordre' => 4],
            ['slug' => 'vente',          'nom_fr' => 'Vente',                    'nom_en' => 'Selling',                'ordre' => 5],
            ['slug' => 'administration', 'nom_fr' => 'Administration de biens',  'nom_en' => 'Property Administration','ordre' => 6],
            ['slug' => 'marche',         'nom_fr' => 'Marché',                   'nom_en' => 'Market',                 'ordre' => 7],
        ];

        foreach ($categories as $c) {
            Categorie::updateOrCreate(['slug' => $c['slug']], $c);
        }
    }
}
```

- [ ] **Step 6: Migrer et lancer les tests**

```bash
cd app-laravel && php artisan migrate && php artisan test --filter=CategorieTest
```

Attendu : les 3 tests passent.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(categories): table, modele et sept categories du site"
```

---

### Task 5 : Table des articles

**Files:**
- Create: `app-laravel/database/migrations/*_create_articles_table.php`
- Create: `app-laravel/app/Models/Article.php`
- Create: `app-laravel/database/factories/ArticleFactory.php`
- Create: `app-laravel/tests/Feature/ArticleTest.php`

**Interfaces:**
- Consumes: `Categorie` de la tâche 4
- Produces: modèle `Article` avec `Article::publies()` (portée), `titre(string $langue): string`, `resume(string $langue): string`, `contenu(string $langue): string`

- [ ] **Step 1: Écrire le test qui échoue**

`app-laravel/tests/Feature/ArticleTest.php` :

```php
<?php

use App\Models\Article;
use App\Models\Categorie;

beforeEach(function () {
    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);
});

it('rend le titre dans la langue demandee', function () {
    $a = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'titre_fr' => 'Sécuriser un terrain',
        'titre_en' => 'Securing a plot',
    ]);

    expect($a->titre('fr'))->toBe('Sécuriser un terrain');
    expect($a->titre('en'))->toBe('Securing a plot');
});

it('refuse un article sans titre francais', function () {
    expect(fn () => Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'titre_fr' => null,
    ]))->toThrow(Illuminate\Database\QueryException::class);
});

it('refuse deux articles de meme slug', function () {
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'acd-securiser-terrain']);

    expect(fn () => Article::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'acd-securiser-terrain',
    ]))->toThrow(Illuminate\Database\QueryException::class);
});

it('ne compte pas les brouillons parmi les articles publies', function () {
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'statut' => 'publie']);
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'statut' => 'brouillon']);

    expect(Article::publies()->count())->toBe(1);
});

it('classe les articles publies du plus recent au plus ancien', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id, 'statut' => 'publie',
        'slug' => 'ancien', 'date_publication' => '2026-01-01',
    ]);
    Article::factory()->create([
        'categorie_id' => $this->categorie->id, 'statut' => 'publie',
        'slug' => 'recent', 'date_publication' => '2026-08-12',
    ]);

    expect(Article::publies()->first()->slug)->toBe('recent');
});
```

- [ ] **Step 2: Lancer le test pour le voir échouer**

```bash
cd app-laravel && php artisan test --filter=ArticleTest
```

Attendu : ÉCHEC — classe `App\Models\Article` introuvable.

- [ ] **Step 3: Créer la migration**

```bash
cd app-laravel && php artisan make:migration create_articles_table
```

Contenu de `up()` :

```php
Schema::create('articles', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();
    $table->foreignId('categorie_id')->constrained('categories');
    $table->date('date_publication');
    $table->enum('statut', ['brouillon', 'publie'])->default('brouillon');

    $table->string('titre_fr');
    $table->string('titre_en');
    $table->text('resume_fr');
    $table->text('resume_en');
    $table->longText('contenu_fr');
    $table->longText('contenu_en');

    $table->string('meta_titre_fr')->nullable();
    $table->string('meta_titre_en')->nullable();
    $table->string('meta_description_fr', 200)->nullable();
    $table->string('meta_description_en', 200)->nullable();

    $table->timestamps();

    $table->index(['statut', 'date_publication']);
});
```

- [ ] **Step 4: Créer le modèle**

`app-laravel/app/Models/Article.php` :

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'categorie_id', 'date_publication', 'statut',
        'titre_fr', 'titre_en', 'resume_fr', 'resume_en',
        'contenu_fr', 'contenu_en',
        'meta_titre_fr', 'meta_titre_en',
        'meta_description_fr', 'meta_description_en',
    ];

    protected $casts = ['date_publication' => 'date'];

    /** Articles visibles du public, du plus recent au plus ancien. */
    public function scopePublies(Builder $requete): Builder
    {
        return $requete->where('statut', 'publie')
            ->orderByDesc('date_publication');
    }

    public function titre(string $langue = 'fr'): string
    {
        return $langue === 'en' ? $this->titre_en : $this->titre_fr;
    }

    public function resume(string $langue = 'fr'): string
    {
        return $langue === 'en' ? $this->resume_en : $this->resume_fr;
    }

    public function contenu(string $langue = 'fr'): string
    {
        return $langue === 'en' ? $this->contenu_en : $this->contenu_fr;
    }

    public function categorie()
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
```

- [ ] **Step 5: Créer la fabrique**

`app-laravel/database/factories/ArticleFactory.php` :

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $titre = $this->faker->sentence(6);

        return [
            'slug' => $this->faker->unique()->slug(),
            'date_publication' => $this->faker->date(),
            'statut' => 'publie',
            'titre_fr' => $titre,
            'titre_en' => $titre . ' (EN)',
            'resume_fr' => $this->faker->paragraph(),
            'resume_en' => $this->faker->paragraph(),
            'contenu_fr' => $this->faker->paragraphs(4, true),
            'contenu_en' => $this->faker->paragraphs(4, true),
        ];
    }
}
```

- [ ] **Step 6: Migrer et lancer les tests**

```bash
cd app-laravel && php artisan migrate && php artisan test --filter=ArticleTest
```

Attendu : les 5 tests passent.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(articles): table, modele bilingue et portee des articles publies"
```

---

### Task 6 : Reprise des douze articles existants

**Files:**
- Create: `app-laravel/database/seeders/ArticleImportSeeder.php`
- Create: `app-laravel/tests/Feature/ArticleImportTest.php`

**Interfaces:**
- Consumes: `Article` (tâche 5), `Categorie` (tâche 4)
- Produces: 12 articles en base, repris de `frontoffice/`

- [ ] **Step 1: Écrire le test qui échoue**

`app-laravel/tests/Feature/ArticleImportTest.php` :

```php
<?php

use App\Models\Article;
use Database\Seeders\ArticleImportSeeder;
use Database\Seeders\CategorieSeeder;

beforeEach(function () {
    $this->seed(CategorieSeeder::class);
});

it('reprend les douze articles du site', function () {
    $this->seed(ArticleImportSeeder::class);

    expect(Article::count())->toBe(12);
});

it('reprend les deux langues de chaque article', function () {
    $this->seed(ArticleImportSeeder::class);

    expect(Article::whereNull('titre_en')->orWhere('titre_en', '')->count())->toBe(0);
    expect(Article::whereNull('contenu_en')->orWhere('contenu_en', '')->count())->toBe(0);
});

it('conserve les slugs existants', function () {
    $this->seed(ArticleImportSeeder::class);

    expect(Article::where('slug', 'acd-securiser-terrain')->exists())->toBeTrue();
});

it('ne duplique pas quand on le rejoue', function () {
    $this->seed(ArticleImportSeeder::class);
    $this->seed(ArticleImportSeeder::class);

    expect(Article::count())->toBe(12);
});
```

- [ ] **Step 2: Lancer le test pour le voir échouer**

```bash
cd app-laravel && php artisan test --filter=ArticleImportTest
```

Attendu : ÉCHEC — classe `ArticleImportSeeder` introuvable.

- [ ] **Step 3: Extraire les données depuis le frontoffice**

Écrire un script d'extraction ponctuel qui lit `frontoffice/actualites.html` et `frontoffice/assets/main.js`, et produit `app-laravel/database/data/articles.json`.

```bash
cd /Applications/MAMP/htdocs/Projects/EDANIlyasK
mkdir -p app-laravel/database/data
python3 - <<'PY'
import io, json, re, os

html = io.open('frontoffice/actualites.html', encoding='utf-8').read()
js   = io.open('frontoffice/assets/main.js', encoding='utf-8').read()

# Cartes : slug, categorie, date, couverture
cartes = []
for m in re.finditer(r'<a class="news-card reveal"(.*?)</a>', html, re.S):
    bloc = m.group(0)
    slug = re.search(r'\?id=([a-z0-9-]+)', bloc)
    cat  = re.search(r'data-cat="([^"]+)"', bloc)
    date = re.search(r'data-date="([^"]+)"', bloc)
    img  = re.search(r"background-image:url\('([^']+)'\)", bloc)
    cle  = re.search(r'data-i18n="news\.a(\d+)\.title"', bloc)
    if not (slug and cat and date and cle):
        raise SystemExit('carte incomplete : ' + bloc[:120])
    cartes.append({
        'slug': slug.group(1), 'categorie': cat.group(1), 'date': date.group(1),
        'image': img.group(1) if img else None, 'index': int(cle.group(1)),
    })

def texte(cle):
    m = re.search(r'"' + re.escape(cle) + r'":\s*\{\s*fr:\s*"((?:[^"\\]|\\.)*)"\s*,\s*en:\s*"((?:[^"\\]|\\.)*)"', js)
    if not m:
        return None
    dec = lambda s: s.encode().decode('unicode_escape')
    return dec(m.group(1)), dec(m.group(2))

articles = []
for c in cartes:
    i = c['index']
    titre = texte(f'news.a{i}.title')
    if not titre:
        raise SystemExit(f'titre manquant pour l article {i}')
    paras_fr, paras_en = [], []
    for p in range(1, 9):
        t = texte(f'news.a{i}.p{p}')
        if not t:
            break
        paras_fr.append(t[0]); paras_en.append(t[1])
    if not paras_fr:
        raise SystemExit(f'contenu manquant pour l article {i}')
    articles.append({
        'slug': c['slug'], 'categorie': c['categorie'], 'date': c['date'], 'image': c['image'],
        'titre_fr': titre[0], 'titre_en': titre[1],
        'resume_fr': paras_fr[0], 'resume_en': paras_en[0],
        'contenu_fr': "\n\n".join(paras_fr), 'contenu_en': "\n\n".join(paras_en),
    })

if len(articles) != 12:
    raise SystemExit(f'{len(articles)} articles extraits, 12 attendus')

io.open('app-laravel/database/data/articles.json', 'w', encoding='utf-8').write(
    json.dumps(articles, ensure_ascii=False, indent=2))
print(f'{len(articles)} articles extraits')
PY
```

Attendu : `12 articles extraits`. Toute anomalie arrête le script en nommant l'article fautif.

- [ ] **Step 4: Écrire le seeder**

`app-laravel/database/seeders/ArticleImportSeeder.php` :

```php
<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Categorie;
use Illuminate\Database\Seeder;
use RuntimeException;

class ArticleImportSeeder extends Seeder
{
    /** Correspondance entre le libelle affiche sur le site et le slug de categorie. */
    private const CATEGORIES = [
        'Foncier' => 'foncier',
        'Construction' => 'construction',
        'Gestion / Location' => 'gestion',
        'Achat' => 'achat',
        'Vente' => 'vente',
        'Administration de biens' => 'administration',
        'Marché' => 'marche',
    ];

    public function run(): void
    {
        $chemin = database_path('data/articles.json');

        if (! file_exists($chemin)) {
            throw new RuntimeException("Fichier d'import introuvable : {$chemin}");
        }

        $articles = json_decode(file_get_contents($chemin), true, 512, JSON_THROW_ON_ERROR);

        foreach ($articles as $a) {
            foreach (['slug', 'titre_fr', 'titre_en', 'resume_fr', 'resume_en', 'contenu_fr', 'contenu_en'] as $champ) {
                if (empty($a[$champ])) {
                    throw new RuntimeException("Champ « {$champ} » vide pour l'article « {$a['slug']} »");
                }
            }

            $slugCategorie = self::CATEGORIES[$a['categorie']] ?? null;
            if (! $slugCategorie) {
                throw new RuntimeException("Categorie inconnue : « {$a['categorie']} »");
            }

            $categorie = Categorie::where('slug', $slugCategorie)->firstOrFail();

            Article::updateOrCreate(
                ['slug' => $a['slug']],
                [
                    'categorie_id' => $categorie->id,
                    'date_publication' => $a['date'],
                    'statut' => 'publie',
                    'titre_fr' => $a['titre_fr'],
                    'titre_en' => $a['titre_en'],
                    'resume_fr' => $a['resume_fr'],
                    'resume_en' => $a['resume_en'],
                    'contenu_fr' => $a['contenu_fr'],
                    'contenu_en' => $a['contenu_en'],
                ]
            );
        }

        $total = Article::count();
        if ($total !== 12) {
            throw new RuntimeException("{$total} articles en base, 12 attendus");
        }
    }
}
```

- [ ] **Step 5: Lancer les tests**

```bash
cd app-laravel && php artisan test --filter=ArticleImportTest
```

Attendu : les 4 tests passent.

- [ ] **Step 6: Exécuter la reprise pour de vrai**

```bash
cd app-laravel && php artisan db:seed --class=CategorieSeeder && php artisan db:seed --class=ArticleImportSeeder
php artisan tinker --execute="echo App\Models\Article::count().' articles, '.App\Models\Article::whereNotNull('titre_en')->count().' avec titre anglais';"
```

Attendu : `12 articles, 12 avec titre anglais`.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(import): reprend les douze articles du site avec leurs deux langues"
```

---

### Task 7 : Liste publique des actualités

**Files:**
- Create: `app-laravel/app/Http/Controllers/ActualiteController.php`
- Create: `app-laravel/resources/views/public/actualites/index.blade.php`
- Create: `app-laravel/resources/views/public/layout.blade.php`
- Create: `app-laravel/tests/Feature/ActualitesPubliquesTest.php`
- Modify: `app-laravel/routes/web.php`

**Interfaces:**
- Consumes: `Article::publies()` (tâche 5)
- Produces: route nommée `actualites.index` sur `/actualites`

- [ ] **Step 1: Écrire le test qui échoue**

`app-laravel/tests/Feature/ActualitesPubliquesTest.php` :

```php
<?php

use App\Models\Article;
use App\Models\Categorie;

beforeEach(function () {
    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);
});

it('affiche la liste des actualites', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'titre_fr' => 'Sécuriser un terrain à Abidjan',
    ]);

    $this->get('/actualites')
        ->assertOk()
        ->assertSee('Sécuriser un terrain à Abidjan');
});

it('ne montre pas les brouillons', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'brouillon',
        'titre_fr' => 'Article en préparation',
    ]);

    $this->get('/actualites')->assertDontSee('Article en préparation');
});
```

- [ ] **Step 2: Lancer le test pour le voir échouer**

```bash
cd app-laravel && php artisan test --filter=ActualitesPubliquesTest
```

Attendu : ÉCHEC — 404, la route n'existe pas.

- [ ] **Step 3: Créer le contrôleur**

```bash
cd app-laravel && php artisan make:controller ActualiteController
```

```php
<?php

namespace App\Http\Controllers;

use App\Models\Article;

class ActualiteController extends Controller
{
    public function index()
    {
        return view('public.actualites.index', [
            'articles' => Article::publies()->with('categorie')->get(),
            'langue' => app()->getLocale(),
        ]);
    }
}
```

- [ ] **Step 4: Créer le gabarit public**

`app-laravel/resources/views/public/layout.blade.php` :

```blade
<!doctype html>
<html lang="{{ $langue ?? 'fr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre') — SCI4K</title>
    <meta name="description" content="@yield('description')">
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/images.css') }}">
    <script>
      (function(){try{var t=localStorage.getItem('sci4k-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();
    </script>
</head>
<body class="@yield('classe-page')">
    @include('public.partials.entete')
    @yield('contenu')
    @include('public.partials.pied')
    <script src="{{ asset('assets/main.js') }}" defer></script>
</body>
</html>
```

Le `defer` est indispensable : sans lui le bouton flottant reste inerte, comme corrigé en août.

- [ ] **Step 5: Créer la vue de liste**

`app-laravel/resources/views/public/actualites/index.blade.php` :

```blade
@extends('public.layout')

@section('titre', 'Actualités')
@section('description', "Conseils et actualités immobilières à Abidjan : foncier, marché, gestion locative.")
@section('classe-page', 'page-actualites')

@section('contenu')
<section class="news-section">
  <div class="wrap">
    <div class="news-grid reveal-stagger" id="newsGrid">
      @foreach ($articles as $article)
        <a class="news-card reveal"
           href="{{ route('actualites.detail', $article) }}"
           data-cat="{{ $article->categorie->nom($langue) }}"
           data-date="{{ $article->date_publication->format('Y-m-d') }}">
          <div class="news-card-cover" style="background-image:url('{{ asset($article->image ?? 'images/actualites/defaut.jpg') }}')"></div>
          <div class="news-card-body">
            <div class="news-card-meta">
              <span class="news-date">{{ $article->date_publication->translatedFormat('d F Y') }}</span>
            </div>
            <h3>{{ $article->titre($langue) }}</h3>
            <p>{{ $article->resume($langue) }}</p>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endsection
```

- [ ] **Step 6: Déclarer la route**

Dans `app-laravel/routes/web.php` :

```php
use App\Http\Controllers\ActualiteController;

Route::get('/actualites', [ActualiteController::class, 'index'])->name('actualites.index');
```

- [ ] **Step 7: Copier les ressources du frontoffice**

```bash
cd /Applications/MAMP/htdocs/Projects/EDANIlyasK
cp -R frontoffice/assets app-laravel/public/assets
cp -R frontoffice/images app-laravel/public/images
```

- [ ] **Step 8: Lancer les tests**

```bash
cd app-laravel && php artisan test --filter=ActualitesPubliquesTest
```

Attendu : les 2 tests passent.

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "feat(public): sert la liste des actualites depuis la base"
```

---

### Task 8 : Page d'article et anciennes adresses

**Files:**
- Create: `app-laravel/resources/views/public/actualites/detail.blade.php`
- Modify: `app-laravel/app/Http/Controllers/ActualiteController.php`
- Modify: `app-laravel/routes/web.php`
- Modify: `app-laravel/tests/Feature/ActualitesPubliquesTest.php`

**Interfaces:**
- Consumes: `Article` (tâche 5), route `actualites.index` (tâche 7)
- Produces: route nommée `actualites.detail` sur `/actualites/{article:slug}`

- [ ] **Step 1: Ajouter les tests qui échouent**

À la fin de `app-laravel/tests/Feature/ActualitesPubliquesTest.php` :

```php
it('affiche un article a son adresse propre', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'slug' => 'acd-securiser-terrain',
        'titre_fr' => 'ACD, titre foncier',
        'contenu_fr' => 'Le contenu complet de l article.',
    ]);

    $this->get('/actualites/acd-securiser-terrain')
        ->assertOk()
        ->assertSee('ACD, titre foncier')
        ->assertSee('Le contenu complet de l article.');
});

it('renvoie 404 sur un article inconnu', function () {
    $this->get('/actualites/article-inexistant')->assertNotFound();
});

it('renvoie 404 sur un brouillon', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'brouillon',
        'slug' => 'en-preparation',
    ]);

    $this->get('/actualites/en-preparation')->assertNotFound();
});

it('redirige les anciennes adresses vers la nouvelle', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'slug' => 'acd-securiser-terrain',
    ]);

    $this->get('/actualite-detail.html?id=acd-securiser-terrain')
        ->assertRedirect('/actualites/acd-securiser-terrain');
});
```

- [ ] **Step 2: Lancer les tests pour les voir échouer**

```bash
cd app-laravel && php artisan test --filter=ActualitesPubliquesTest
```

Attendu : ÉCHEC sur les 4 nouveaux tests — routes absentes.

- [ ] **Step 3: Ajouter les méthodes au contrôleur**

Dans `app-laravel/app/Http/Controllers/ActualiteController.php` :

```php
use App\Models\Article;
use Illuminate\Http\Request;

public function detail(Article $article)
{
    abort_if($article->statut !== 'publie', 404);

    return view('public.actualites.detail', [
        'article' => $article->load('categorie'),
        'langue' => app()->getLocale(),
    ]);
}

/** Ancienne adresse : /actualite-detail.html?id=slug */
public function ancienneAdresse(Request $requete)
{
    $slug = $requete->query('id');

    if (! $slug) {
        return redirect()->route('actualites.index', [], 301);
    }

    return redirect()->route('actualites.detail', $slug, 301);
}
```

- [ ] **Step 4: Créer la vue de détail**

`app-laravel/resources/views/public/actualites/detail.blade.php` :

```blade
@extends('public.layout')

@section('titre', $article->titre($langue))
@section('description', $article->meta_description_fr ?? $article->resume($langue))
@section('classe-page', 'page-actualite-detail')

@section('contenu')
<article class="article">
  <div class="wrap">
    <a class="article-back" href="{{ route('actualites.index') }}">← Actualités</a>
    <div class="article-meta">
      <span class="article-cat">{{ $article->categorie->nom($langue) }}</span>
      <span class="article-date">{{ $article->date_publication->translatedFormat('d F Y') }}</span>
    </div>
    <h1>{{ $article->titre($langue) }}</h1>
    <div class="article-body">
      @foreach (preg_split('/\n\n+/', $article->contenu($langue)) as $paragraphe)
        <p>{{ $paragraphe }}</p>
      @endforeach
    </div>
  </div>
</article>
@endsection
```

- [ ] **Step 5: Déclarer les routes**

Dans `app-laravel/routes/web.php` :

```php
Route::get('/actualites/{article:slug}', [ActualiteController::class, 'detail'])->name('actualites.detail');
Route::get('/actualite-detail.html', [ActualiteController::class, 'ancienneAdresse']);
```

L'ordre compte : la route de liste doit être déclarée avant celle du détail.

- [ ] **Step 6: Lancer les tests**

```bash
cd app-laravel && php artisan test --filter=ActualitesPubliquesTest
```

Attendu : les 6 tests passent.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(public): page d'article a adresse propre, anciennes adresses redirigees"
```

---

### Task 9 : Tableau d'administration des articles

**Files:**
- Create: `app-laravel/app/Livewire/Admin/ArticleListe.php`
- Create: `app-laravel/resources/views/livewire/admin/article-liste.blade.php`
- Create: `app-laravel/tests/Feature/AdminArticleListeTest.php`
- Modify: `app-laravel/routes/web.php`

**Interfaces:**
- Consumes: `Article` (tâche 5), middleware de rôle (tâche 2)
- Produces: composant `ArticleListe` avec propriétés publiques `$recherche`, `$categorieId`, `$statut`

- [ ] **Step 1: Écrire le test qui échoue**

`app-laravel/tests/Feature/AdminArticleListeTest.php` :

```php
<?php

use App\Livewire\Admin\ArticleListe;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('editeur');
    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1,
    ]);
    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');
});

it('liste les articles, brouillons compris', function () {
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Publié', 'statut' => 'publie']);
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Brouillon', 'statut' => 'brouillon']);

    Livewire::actingAs($this->editeur)
        ->test(ArticleListe::class)
        ->assertSee('Publié')
        ->assertSee('Brouillon');
});

it('filtre par recherche sur le titre', function () {
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Sécuriser un terrain']);
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Marché immobilier']);

    Livewire::actingAs($this->editeur)
        ->test(ArticleListe::class)
        ->set('recherche', 'terrain')
        ->assertSee('Sécuriser un terrain')
        ->assertDontSee('Marché immobilier');
});

it('filtre par statut', function () {
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Publié', 'statut' => 'publie']);
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Brouillon', 'statut' => 'brouillon']);

    Livewire::actingAs($this->editeur)
        ->test(ArticleListe::class)
        ->set('statut', 'brouillon')
        ->assertSee('Brouillon')
        ->assertDontSee('Publié');
});
```

- [ ] **Step 2: Lancer le test pour le voir échouer**

```bash
cd app-laravel && php artisan test --filter=AdminArticleListeTest
```

Attendu : ÉCHEC — classe `App\Livewire\Admin\ArticleListe` introuvable.

- [ ] **Step 3: Créer le composant**

```bash
cd app-laravel && php artisan make:livewire Admin/ArticleListe
```

`app-laravel/app/Livewire/Admin/ArticleListe.php` :

```php
<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Models\Categorie;
use Livewire\Component;
use Livewire\WithPagination;

class ArticleListe extends Component
{
    use WithPagination;

    public string $recherche = '';
    public string $categorieId = '';
    public string $statut = '';

    public function updating($nom): void
    {
        if (in_array($nom, ['recherche', 'categorieId', 'statut'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $articles = Article::query()
            ->with('categorie')
            ->when($this->recherche !== '', fn ($r) => $r->where(function ($q) {
                $q->where('titre_fr', 'like', '%' . $this->recherche . '%')
                  ->orWhere('titre_en', 'like', '%' . $this->recherche . '%');
            }))
            ->when($this->categorieId !== '', fn ($r) => $r->where('categorie_id', $this->categorieId))
            ->when($this->statut !== '', fn ($r) => $r->where('statut', $this->statut))
            ->orderByDesc('date_publication')
            ->paginate(20);

        return view('livewire.admin.article-liste', [
            'articles' => $articles,
            'categories' => Categorie::orderBy('ordre')->get(),
        ]);
    }
}
```

- [ ] **Step 4: Créer la vue**

`app-laravel/resources/views/livewire/admin/article-liste.blade.php` :

```blade
<div class="space-y-4">
    <div class="flex flex-wrap gap-3">
        <input type="search" wire:model.live.debounce.300ms="recherche"
               placeholder="Rechercher un titre…"
               class="rounded border border-zinc-300 px-3 py-2 text-sm">

        <select wire:model.live="categorieId" class="rounded border border-zinc-300 px-3 py-2 text-sm">
            <option value="">Toutes les catégories</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}">{{ $c->nom_fr }}</option>
            @endforeach
        </select>

        <select wire:model.live="statut" class="rounded border border-zinc-300 px-3 py-2 text-sm">
            <option value="">Tous les statuts</option>
            <option value="publie">Publié</option>
            <option value="brouillon">Brouillon</option>
        </select>
    </div>

    <table class="w-full text-sm">
        <thead class="text-left text-xs uppercase text-zinc-500">
            <tr>
                <th class="py-2">Titre</th>
                <th>Catégorie</th>
                <th>Date</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($articles as $article)
                <tr class="border-t border-zinc-200">
                    <td class="py-2">
                        <a href="{{ route('admin.articles.edition', $article) }}" class="font-medium hover:underline">
                            {{ $article->titre_fr }}
                        </a>
                    </td>
                    <td>{{ $article->categorie->nom_fr }}</td>
                    <td>{{ $article->date_publication->format('d/m/Y') }}</td>
                    <td>
                        <span class="rounded px-2 py-1 text-xs {{ $article->statut === 'publie' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ $article->statut === 'publie' ? 'Publié' : 'Brouillon' }}
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $articles->links() }}
</div>
```

- [ ] **Step 5: Déclarer la route**

Dans le groupe `admin` de `app-laravel/routes/web.php` :

```php
Route::get('/articles', \App\Livewire\Admin\ArticleListe::class)->name('articles.liste');
```

- [ ] **Step 6: Lancer les tests**

```bash
cd app-laravel && php artisan test --filter=AdminArticleListeTest
```

Attendu : les 3 tests passent.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(admin): tableau des articles avec recherche et filtres"
```

---

### Task 10 : Formulaire d'édition bilingue

**Files:**
- Create: `app-laravel/app/Livewire/Admin/ArticleFormulaire.php`
- Create: `app-laravel/resources/views/livewire/admin/article-formulaire.blade.php`
- Create: `app-laravel/tests/Feature/AdminArticleFormulaireTest.php`
- Modify: `app-laravel/routes/web.php`

**Interfaces:**
- Consumes: `Article` (tâche 5), `Categorie` (tâche 4)
- Produces: composant `ArticleFormulaire` avec méthode publique `enregistrer(): void`

- [ ] **Step 1: Écrire le test qui échoue**

`app-laravel/tests/Feature/AdminArticleFormulaireTest.php` :

```php
<?php

use App\Livewire\Admin\ArticleFormulaire;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');
    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1,
    ]);
    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');
});

it('cree un article dans les deux langues', function () {
    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class)
        ->set('slug', 'nouvel-article')
        ->set('categorieId', $this->categorie->id)
        ->set('datePublication', '2026-08-23')
        ->set('statut', 'publie')
        ->set('titreFr', 'Titre français')
        ->set('titreEn', 'English title')
        ->set('resumeFr', 'Résumé français')
        ->set('resumeEn', 'English summary')
        ->set('contenuFr', 'Contenu français')
        ->set('contenuEn', 'English content')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $article = Article::where('slug', 'nouvel-article')->first();

    expect($article)->not->toBeNull();
    expect($article->titre('fr'))->toBe('Titre français');
    expect($article->titre('en'))->toBe('English title');
});

it('refuse un enregistrement sans titre anglais', function () {
    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class)
        ->set('slug', 'incomplet')
        ->set('categorieId', $this->categorie->id)
        ->set('datePublication', '2026-08-23')
        ->set('titreFr', 'Titre français')
        ->set('titreEn', '')
        ->call('enregistrer')
        ->assertHasErrors(['titreEn' => 'required']);
});

it('refuse un slug deja pris', function () {
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'deja-pris']);

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class)
        ->set('slug', 'deja-pris')
        ->set('categorieId', $this->categorie->id)
        ->set('datePublication', '2026-08-23')
        ->set('titreFr', 'A')->set('titreEn', 'A')
        ->set('resumeFr', 'B')->set('resumeEn', 'B')
        ->set('contenuFr', 'C')->set('contenuEn', 'C')
        ->call('enregistrer')
        ->assertHasErrors(['slug' => 'unique']);
});

it('interdit a un lecteur d enregistrer', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    $this->actingAs($lecteur)
        ->get(route('admin.articles.creation'))
        ->assertForbidden();
});
```

- [ ] **Step 2: Lancer le test pour le voir échouer**

```bash
cd app-laravel && php artisan test --filter=AdminArticleFormulaireTest
```

Attendu : ÉCHEC — classe `ArticleFormulaire` introuvable.

- [ ] **Step 3: Créer le composant**

```bash
cd app-laravel && php artisan make:livewire Admin/ArticleFormulaire
```

`app-laravel/app/Livewire/Admin/ArticleFormulaire.php` :

```php
<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Models\Categorie;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ArticleFormulaire extends Component
{
    public ?Article $article = null;

    public string $slug = '';
    public string $categorieId = '';
    public string $datePublication = '';
    public string $statut = 'brouillon';

    public string $titreFr = '';
    public string $titreEn = '';
    public string $resumeFr = '';
    public string $resumeEn = '';
    public string $contenuFr = '';
    public string $contenuEn = '';
    public string $metaTitreFr = '';
    public string $metaTitreEn = '';
    public string $metaDescriptionFr = '';
    public string $metaDescriptionEn = '';

    public string $langueActive = 'fr';

    public function mount(?Article $article = null): void
    {
        if (! $article?->exists) {
            return;
        }

        $this->article = $article;
        $this->slug = $article->slug;
        $this->categorieId = (string) $article->categorie_id;
        $this->datePublication = $article->date_publication->format('Y-m-d');
        $this->statut = $article->statut;
        $this->titreFr = $article->titre_fr;
        $this->titreEn = $article->titre_en;
        $this->resumeFr = $article->resume_fr;
        $this->resumeEn = $article->resume_en;
        $this->contenuFr = $article->contenu_fr;
        $this->contenuEn = $article->contenu_en;
        $this->metaTitreFr = $article->meta_titre_fr ?? '';
        $this->metaTitreEn = $article->meta_titre_en ?? '';
        $this->metaDescriptionFr = $article->meta_description_fr ?? '';
        $this->metaDescriptionEn = $article->meta_description_en ?? '';
    }

    protected function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:190', Rule::unique('articles', 'slug')->ignore($this->article?->id)],
            'categorieId' => ['required', 'exists:categories,id'],
            'datePublication' => ['required', 'date'],
            'statut' => ['required', 'in:brouillon,publie'],
            'titreFr' => ['required', 'string', 'max:190'],
            'titreEn' => ['required', 'string', 'max:190'],
            'resumeFr' => ['required', 'string'],
            'resumeEn' => ['required', 'string'],
            'contenuFr' => ['required', 'string'],
            'contenuEn' => ['required', 'string'],
            'metaDescriptionFr' => ['nullable', 'string', 'max:160'],
            'metaDescriptionEn' => ['nullable', 'string', 'max:160'],
        ];
    }

    public function enregistrer(): void
    {
        $this->validate();

        $donnees = [
            'slug' => $this->slug,
            'categorie_id' => $this->categorieId,
            'date_publication' => $this->datePublication,
            'statut' => $this->statut,
            'titre_fr' => $this->titreFr,
            'titre_en' => $this->titreEn,
            'resume_fr' => $this->resumeFr,
            'resume_en' => $this->resumeEn,
            'contenu_fr' => $this->contenuFr,
            'contenu_en' => $this->contenuEn,
            'meta_titre_fr' => $this->metaTitreFr ?: null,
            'meta_titre_en' => $this->metaTitreEn ?: null,
            'meta_description_fr' => $this->metaDescriptionFr ?: null,
            'meta_description_en' => $this->metaDescriptionEn ?: null,
        ];

        if ($this->article) {
            $this->article->update($donnees);
        } else {
            $this->article = Article::create($donnees);
        }

        session()->flash('message', 'Article enregistré.');
        $this->redirectRoute('admin.articles.liste');
    }

    public function render()
    {
        return view('livewire.admin.article-formulaire', [
            'categories' => Categorie::orderBy('ordre')->get(),
        ]);
    }
}
```

- [ ] **Step 4: Créer la vue avec onglets de langue**

`app-laravel/resources/views/livewire/admin/article-formulaire.blade.php` :

```blade
<form wire:submit="enregistrer" class="space-y-6">

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
            <span class="text-sm font-medium">Identifiant d'adresse</span>
            <input type="text" wire:model="slug" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
            @error('slug') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium">Catégorie</span>
            <select wire:model="categorieId" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                <option value="">Choisir…</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->nom_fr }}</option>
                @endforeach
            </select>
            @error('categorieId') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium">Date de publication</span>
            <input type="date" wire:model="datePublication" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
            @error('datePublication') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium">Statut</span>
            <select wire:model="statut" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
                <option value="brouillon">Brouillon</option>
                <option value="publie">Publié</option>
            </select>
        </label>
    </div>

    <div class="border-b border-zinc-200">
        <nav class="flex gap-4">
            <button type="button" wire:click="$set('langueActive', 'fr')"
                    class="border-b-2 px-1 py-2 text-sm {{ $langueActive === 'fr' ? 'border-zinc-900 font-medium' : 'border-transparent text-zinc-500' }}">
                Français
            </button>
            <button type="button" wire:click="$set('langueActive', 'en')"
                    class="border-b-2 px-1 py-2 text-sm {{ $langueActive === 'en' ? 'border-zinc-900 font-medium' : 'border-transparent text-zinc-500' }}">
                English
            </button>
        </nav>
    </div>

    <div class="space-y-4" @if($langueActive !== 'fr') hidden @endif>
        <label class="block">
            <span class="text-sm font-medium">Titre</span>
            <input type="text" wire:model="titreFr" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
            @error('titreFr') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-medium">Résumé</span>
            <textarea wire:model="resumeFr" rows="3" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2"></textarea>
            @error('resumeFr') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-medium">Contenu</span>
            <textarea wire:model="contenuFr" rows="12" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2"></textarea>
            @error('contenuFr') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-medium">Description pour les moteurs (160 signes)</span>
            <input type="text" wire:model="metaDescriptionFr" maxlength="160" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
            @error('metaDescriptionFr') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
    </div>

    <div class="space-y-4" @if($langueActive !== 'en') hidden @endif>
        <label class="block">
            <span class="text-sm font-medium">Title</span>
            <input type="text" wire:model="titreEn" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
            @error('titreEn') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-medium">Summary</span>
            <textarea wire:model="resumeEn" rows="3" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2"></textarea>
            @error('resumeEn') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-medium">Content</span>
            <textarea wire:model="contenuEn" rows="12" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2"></textarea>
            @error('contenuEn') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <label class="block">
            <span class="text-sm font-medium">Meta description (160 characters)</span>
            <input type="text" wire:model="metaDescriptionEn" maxlength="160" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2">
            @error('metaDescriptionEn') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
    </div>

    <button type="submit" class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white">
        Enregistrer
    </button>
</form>
```

Les deux blocs de langue restent dans le DOM, masqués par `hidden` : les erreurs de validation de la langue inactive sont ainsi conservées et visibles au changement d'onglet.

- [ ] **Step 5: Déclarer les routes avec restriction de rôle**

Dans le groupe `admin` de `app-laravel/routes/web.php` :

```php
Route::middleware('role:administrateur|editeur')->group(function () {
    Route::get('/articles/creation', \App\Livewire\Admin\ArticleFormulaire::class)->name('articles.creation');
    Route::get('/articles/{article}/edition', \App\Livewire\Admin\ArticleFormulaire::class)->name('articles.edition');
});
```

- [ ] **Step 6: Lancer les tests**

```bash
cd app-laravel && php artisan test --filter=AdminArticleFormulaireTest
```

Attendu : les 4 tests passent.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(admin): formulaire d'article avec onglets francais et anglais"
```

---

### Task 11 : Couvertures d'article

**Files:**
- Modify: `app-laravel/app/Models/Article.php`
- Modify: `app-laravel/app/Livewire/Admin/ArticleFormulaire.php`
- Modify: `app-laravel/resources/views/livewire/admin/article-formulaire.blade.php`
- Create: `app-laravel/tests/Feature/ArticleCouvertureTest.php`

**Interfaces:**
- Consumes: `Article` (tâche 5), `ArticleFormulaire` (tâche 10)
- Produces: `Article::urlCouverture(): ?string`

- [ ] **Step 1: Installer la médiathèque**

```bash
cd app-laravel
composer require spatie/laravel-medialibrary
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan migrate
php artisan storage:link
```

- [ ] **Step 2: Écrire le test qui échoue**

`app-laravel/tests/Feature/ArticleCouvertureTest.php` :

```php
<?php

use App\Models\Article;
use App\Models\Categorie;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1,
    ]);
});

it('attache une couverture a un article', function () {
    $article = Article::factory()->create(['categorie_id' => $this->categorie->id]);

    $article->addMedia(UploadedFile::fake()->image('couverture.jpg', 1200, 800))
        ->toMediaCollection('couverture');

    expect($article->urlCouverture())->not->toBeNull();
});

it('rend null quand l article n a pas de couverture', function () {
    $article = Article::factory()->create(['categorie_id' => $this->categorie->id]);

    expect($article->urlCouverture())->toBeNull();
});

it('ne garde qu une seule couverture par article', function () {
    $article = Article::factory()->create(['categorie_id' => $this->categorie->id]);

    $article->addMedia(UploadedFile::fake()->image('une.jpg'))->toMediaCollection('couverture');
    $article->addMedia(UploadedFile::fake()->image('deux.jpg'))->toMediaCollection('couverture');

    expect($article->getMedia('couverture')->count())->toBe(1);
});
```

- [ ] **Step 3: Lancer le test pour le voir échouer**

```bash
cd app-laravel && php artisan test --filter=ArticleCouvertureTest
```

Attendu : ÉCHEC — méthode `addMedia` inconnue sur `Article`.

- [ ] **Step 4: Rendre le modèle porteur de médias**

Dans `app-laravel/app/Models/Article.php` :

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Article extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    // … le reste du modele inchange

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('couverture')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('carte')
            ->width(800)
            ->format('webp')
            ->nonQueued();
    }

    public function urlCouverture(): ?string
    {
        $media = $this->getFirstMedia('couverture');

        return $media?->getUrl();
    }
}
```

- [ ] **Step 5: Ajouter le champ au formulaire**

Dans `ArticleFormulaire.php` :

```php
use Livewire\WithFileUploads;

class ArticleFormulaire extends Component
{
    use WithFileUploads;

    public $couverture = null;
```

Dans `rules()` :

```php
'couverture' => ['nullable', 'image', 'max:4096'],
```

À la fin de `enregistrer()`, avant la redirection :

```php
if ($this->couverture) {
    $this->article->addMedia($this->couverture->getRealPath())
        ->usingFileName($this->couverture->getClientOriginalName())
        ->toMediaCollection('couverture');
}
```

Dans la vue, avant le bouton d'enregistrement :

```blade
<label class="block">
    <span class="text-sm font-medium">Image de couverture</span>
    <input type="file" wire:model="couverture" accept="image/*" class="mt-1 block w-full text-sm">
    @error('couverture') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
</label>
```

- [ ] **Step 6: Utiliser la couverture dans les vues publiques**

Dans `public/actualites/index.blade.php`, remplacer la ligne de la couverture :

```blade
<div class="news-card-cover" style="background-image:url('{{ $article->urlCouverture() ?? asset('images/actualites/defaut.jpg') }}')"></div>
```

- [ ] **Step 7: Lancer les tests**

```bash
cd app-laravel && php artisan test --filter=ArticleCouvertureTest
```

Attendu : les 3 tests passent.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat(articles): couverture d'article avec conversion WebP"
```

---

### Task 12 : Intégration continue

**Files:**
- Modify: `.github/workflows/verification.yml`
- Modify: `tools/verifier-site.py`

**Interfaces:**
- Consumes: les tests des tâches 2 à 11
- Produces: une CI qui exécute les tests Laravel en plus des contrôles existants

- [ ] **Step 1: Vérifier que la suite passe en local**

```bash
cd app-laravel && php artisan test
```

Attendu : tous les tests passent.

- [ ] **Step 2: Ajouter le travail Laravel à la CI**

Dans `.github/workflows/verification.yml`, après le travail existant :

```yaml
  tests-laravel:
    name: Tests Laravel
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: sci4k_test
        ports: ['3306:3306']
        options: >-
          --health-cmd="mysqladmin ping" --health-interval=10s
          --health-timeout=5s --health-retries=5

    steps:
      - uses: actions/checkout@v4

      - name: Installer PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo_mysql, gd

      - name: Installer les dependances
        working-directory: app-laravel
        run: composer install --no-interaction --prefer-dist

      - name: Preparer l'environnement
        working-directory: app-laravel
        run: |
          cp .env.example .env
          php artisan key:generate
          echo "DB_HOST=127.0.0.1" >> .env
          echo "DB_PORT=3306" >> .env
          echo "DB_DATABASE=sci4k_test" >> .env
          echo "DB_USERNAME=root" >> .env
          echo "DB_PASSWORD=root" >> .env

      - name: Migrer
        working-directory: app-laravel
        run: php artisan migrate --force

      - name: Tester
        working-directory: app-laravel
        run: php artisan test
```

- [ ] **Step 3: Adapter le contrôle des références**

Dans `tools/verifier-site.py`, la fonction `pages_html` ne doit plus parcourir les vues Blade portées. Modifier `controle_references` pour ignorer le dossier `app-laravel` :

```python
def pages_html(dossier):
    """Pages HTML statiques. Les vues Blade sont couvertes par les tests Laravel."""
    if not os.path.isdir(dossier):
        return []
    return sorted(glob(os.path.join(dossier, '*.html')))
```

- [ ] **Step 4: Vérifier les deux suites**

```bash
cd /Applications/MAMP/htdocs/Projects/EDANIlyasK
python3 tools/verifier-site.py
cd app-laravel && php artisan test
```

Attendu : les deux passent.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "ci: execute les tests Laravel a chaque envoi"
```

---

### Task 13 : Vérification de bout en bout

**Files:**
- Aucun fichier modifié : contrôle manuel avant clôture du lot

**Interfaces:**
- Consumes: tout le lot
- Produces: la confirmation que le lot 1 est terminé

- [ ] **Step 1: Préparer une base propre**

```bash
cd app-laravel
php artisan migrate:fresh
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=CategorieSeeder
php artisan db:seed --class=ArticleImportSeeder
```

- [ ] **Step 2: Créer un compte administrateur**

```bash
cd app-laravel && php artisan tinker --execute="
\$u = App\Models\User::factory()->create(['name' => 'Vincent', 'email' => 'vincent@sci4k.com']);
\$u->assignRole('administrateur');
echo 'compte cree';
"
```

- [ ] **Step 3: Vérifier le site public**

```bash
cd app-laravel && php artisan serve
```

Ouvrir et contrôler :

- `http://127.0.0.1:8000/actualites` — les 12 articles s'affichent
- `http://127.0.0.1:8000/actualites/acd-securiser-terrain` — l'article s'ouvre
- `http://127.0.0.1:8000/actualite-detail.html?id=acd-securiser-terrain` — redirige vers l'adresse propre
- La bascule FR/EN change bien les textes
- L'affichage en 375 px : cartes à deux colonnes, en-tête sur une rangée
- Les thèmes clair et sombre

- [ ] **Step 4: Vérifier le cycle complet d'édition**

- Se connecter avec le compte administrateur
- Créer un article, remplir les deux langues, enregistrer en brouillon
- Vérifier qu'il **n'apparaît pas** sur `/actualites`
- Le passer en publié
- Vérifier qu'il apparaît, dans les deux langues

- [ ] **Step 5: Vérifier les rôles**

```bash
cd app-laravel && php artisan tinker --execute="
\$l = App\Models\User::factory()->create(['email' => 'lecteur@sci4k.com']);
\$l->assignRole('lecteur');
echo 'lecteur cree';
"
```

Se connecter en lecteur : la liste est consultable, la création est refusée.

- [ ] **Step 6: Lancer toutes les vérifications**

```bash
cd /Applications/MAMP/htdocs/Projects/EDANIlyasK
python3 tools/verifier-site.py
cd app-laravel && php artisan test
```

- [ ] **Step 7: Commit final et demande de relecture**

```bash
git add -A
git commit -m "chore: cloture du lot 1, socle Laravel et actualites"
```

Ouvrir une pull request résumant : ce qui fonctionne, ce qui reste aux lots suivants, et les points à trancher avant mise en ligne (hébergement, mentions légales, photographies).

---

## Ce que ce lot ne fait pas

- Les neuf autres entités : biens, services, FAQ, témoignages, partenaires, équipe, valeurs, processus, réglages — lots 2 et 3.
- Les boîtes de réception : messages, demandes de visite, abonnés — lot 4.
- Le portage des onze autres pages publiques en Blade — seules `actualites` et `actualite-detail` le sont ici.
- Le choix de l'hébergement de production.
- Les points bloquants de la revue du frontoffice : mentions légales incomplètes, photographies provisoires, domaine déclaré avec `www`.
