# Lot 2a — Socle des collections, services et FAQ

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rendre les six services et les douze questions de la FAQ modifiables depuis le backoffice, et servir `services.html` et `faq.html` depuis la base, dans les deux langues.

**Architecture:** Un trait `CollectionOrdonnable` porte l'ordre, la visibilité et le réordonnancement pour les sept collections du lot 2 ; un composant Livewire abstrait `ListeOrdonnable` porte l'écran de liste commun. Services et FAQ sont les deux premières entités à s'y brancher — elles valident le socle avant que les cinq autres l'utilisent. Les questions de la FAQ appartiennent à un service : sur le site, le titre de chaque groupe *est* le nom du service.

**Tech Stack:** Laravel 13.26, Livewire 4, Tailwind 4, Pest 4, MySQL 8 en production et SQLite en mémoire pour les tests.

**Spec:** `docs/superpowers/specs/2026-08-25-lot2-blocs-de-contenu-design.md`

## Global Constraints

- **Aucune dépendance sous licence non libre.** Le gabarit `backoffice/assets/css/styles.css` (LayoutDrop / NexLink) sert de référence visuelle et n'est jamais repris.
- **Chaque écran s'inspire de sa maquette** : `backoffice/service-list.html`, `backoffice/faq-list.html`. Structure et placement reproduits en Tailwind.
- **Bilingue par colonnes suffixées** `_fr` / `_en`, lues par `texteDansLaLangue($prefixe, $langue)` du trait `App\Models\Concerns\TraduitParColonnes`.
- **Toute clé de `__()` doit figurer dans `lang/en.json` ou `lang/fr.json`.** `tests/Feature/ClesDeTraductionTest.php` fait échouer la suite sinon. Quatre noms sont réservés par le framework : `auth`, `pagination`, `passwords`, `validation`.
- **Ne jamais décoder par `unicode_escape`.** Voir l'en-tête de `tools/extraction-articles.py`.
- **Ne jamais chercher une corruption d'encodage en SQL** : la collation du projet est insensible aux accents.
- **Aucune mention de co-auteur dans les messages de commit.**
- La suite doit passer sur SQLite **et** sur MySQL : `php artisan test`, puis avec `DB_CONNECTION=mysql DB_PORT=8889 DB_DATABASE=sci4k_test`.

---

## File Structure

| Fichier | Responsabilité |
|---|---|
| `app/Models/Concerns/CollectionOrdonnable.php` | portée `ordonnees()`, portée `visibles()`, réécriture des rangs |
| `app/Livewire/Admin/ListeOrdonnable.php` | composant abstrait : recherche, pagination, suppression, réordonnancement |
| `app/Models/Service.php` | les six services |
| `app/Models/QuestionFaq.php` | les douze questions, rattachées à un service |
| `app/Livewire/Admin/ServiceListe.php` / `ServiceFormulaire.php` | écrans des services |
| `app/Livewire/Admin/FaqListe.php` / `FaqFormulaire.php` | écrans de la FAQ |
| `app/Http/Controllers/PagePubliqueController.php` | rend `services` et `faq` depuis la base |
| `resources/views/components/admin/poignee-ordre.blade.php` | poignée de glisser-déposer, partagée |
| `tools/extraction-services-faq.py` | extraction ponctuelle du contenu existant |

---

### Task 1 : Trait CollectionOrdonnable

**Files:**
- Create: `app-laravel/app/Models/Concerns/CollectionOrdonnable.php`
- Create: `app-laravel/tests/Feature/CollectionOrdonnableTest.php`

**Interfaces:**
- Consumes: rien
- Produces: trait avec `scopeOrdonnees(Builder $r): Builder`, `scopeVisibles(Builder $r): Builder`, et la méthode statique `reordonner(array $idsDansLOrdre): void`

- [ ] **Step 1 : Écrire les tests qui échouent**

`app-laravel/tests/Feature/CollectionOrdonnableTest.php` :

```php
<?php

use App\Models\Service;
use App\Models\Categorie;

beforeEach(function () {
    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);
});

it('trie par ordre croissant', function () {
    Service::factory()->create(['nom_fr' => 'Troisieme', 'ordre' => 3, 'categorie_id' => $this->categorie->id]);
    Service::factory()->create(['nom_fr' => 'Premier', 'ordre' => 1, 'categorie_id' => $this->categorie->id]);
    Service::factory()->create(['nom_fr' => 'Second', 'ordre' => 2, 'categorie_id' => $this->categorie->id]);

    expect(Service::ordonnees()->pluck('nom_fr')->all())
        ->toBe(['Premier', 'Second', 'Troisieme']);
});

it('ne renvoie que les elements visibles', function () {
    Service::factory()->create(['nom_fr' => 'En ligne', 'visible' => true, 'categorie_id' => $this->categorie->id]);
    Service::factory()->create(['nom_fr' => 'Masque', 'visible' => false, 'categorie_id' => $this->categorie->id]);

    expect(Service::visibles()->pluck('nom_fr')->all())->toBe(['En ligne']);
});

it('reecrit les rangs dans l ordre recu', function () {
    $a = Service::factory()->create(['ordre' => 1, 'categorie_id' => $this->categorie->id]);
    $b = Service::factory()->create(['ordre' => 2, 'categorie_id' => $this->categorie->id]);
    $c = Service::factory()->create(['ordre' => 3, 'categorie_id' => $this->categorie->id]);

    Service::reordonner([$c->id, $a->id, $b->id]);

    expect(Service::ordonnees()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('ignore un identifiant etranger sans toucher aux autres', function () {
    // Un identifiant inconnu ne doit ni lever d'exception, ni decaler les rangs
    // des elements legitimes : la requete vient du navigateur.
    $a = Service::factory()->create(['ordre' => 1, 'categorie_id' => $this->categorie->id]);
    $b = Service::factory()->create(['ordre' => 2, 'categorie_id' => $this->categorie->id]);

    Service::reordonner([$b->id, 999999, $a->id]);

    expect(Service::ordonnees()->pluck('id')->all())->toBe([$b->id, $a->id]);
});

it('numerote a partir de un, sans trou', function () {
    $a = Service::factory()->create(['ordre' => 50, 'categorie_id' => $this->categorie->id]);
    $b = Service::factory()->create(['ordre' => 90, 'categorie_id' => $this->categorie->id]);

    Service::reordonner([$b->id, $a->id]);

    expect(Service::find($b->id)->ordre)->toBe(1);
    expect(Service::find($a->id)->ordre)->toBe(2);
});
```

- [ ] **Step 2 : Lancer les tests pour les voir échouer**

```bash
cd app-laravel && php artisan test --filter=CollectionOrdonnableTest
```

Attendu : ÉCHEC — classe `App\Models\Service` introuvable. C'est normal : le modèle arrive à la tâche 2. Ces tests resteront rouges jusque-là ; c'est le seul endroit du plan où un test attend la tâche suivante, parce qu'un trait ne s'éprouve que porté par un modèle.

- [ ] **Step 3 : Écrire le trait**

`app-laravel/app/Models/Concerns/CollectionOrdonnable.php` :

```php
<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Collection dont l'ordre d'affichage se regle a la main.
 *
 * Les sept collections du lot 2 partagent ce comportement. L'ecrire une fois
 * evite d'avoir sept endroits a corriger au premier defaut — meme raisonnement
 * que TraduitParColonnes au lot 1.
 */
trait CollectionOrdonnable
{
    /** Du premier au dernier rang. */
    public function scopeOrdonnees(Builder $requete): Builder
    {
        return $requete->orderBy('ordre')->orderBy('id');
    }

    /** Ce que le public voit. */
    public function scopeVisibles(Builder $requete): Builder
    {
        return $requete->where('visible', true);
    }

    /**
     * Reecrit les rangs dans l'ordre recu, en repartant de 1.
     *
     * Les identifiants viennent du navigateur : ceux qui ne correspondent a
     * rien sont ignores plutot que de faire echouer l'operation entiere, et
     * surtout sans decaler les rangs des elements legitimes.
     *
     * Une seule transaction : un reordonnancement interrompu a mi-chemin
     * laisserait des rangs en double.
     */
    public static function reordonner(array $idsDansLOrdre): void
    {
        $connus = static::query()
            ->whereIn('id', $idsDansLOrdre)
            ->pluck('id')
            ->all();

        $rang = 0;

        DB::transaction(function () use ($idsDansLOrdre, $connus, &$rang) {
            foreach ($idsDansLOrdre as $id) {
                if (! in_array($id, $connus)) {
                    continue;
                }

                static::query()->whereKey($id)->update(['ordre' => ++$rang]);
            }
        });
    }
}
```

- [ ] **Step 4 : Commit**

```bash
git add app-laravel/app/Models/Concerns/CollectionOrdonnable.php app-laravel/tests/Feature/CollectionOrdonnableTest.php
git commit -m "feat(collections): trait d'ordre et de visibilite partage par les blocs de contenu"
```

---

### Task 2 : Table et modèle Service

**Files:**
- Create: `app-laravel/database/migrations/XXXX_create_services_table.php`
- Create: `app-laravel/app/Models/Service.php`
- Create: `app-laravel/database/factories/ServiceFactory.php`
- Create: `app-laravel/tests/Feature/ServiceTest.php`

**Interfaces:**
- Consumes: `CollectionOrdonnable` (tâche 1), `TraduitParColonnes` et `Categorie` (lot 1)
- Produces: `Service` avec `nom(string $langue = 'fr'): string`, `accroche(...)`, `description(...)`, `atouts(string $langue = 'fr'): array`, `getRouteKeyName()` renvoyant `slug`

- [ ] **Step 1 : Écrire le test qui échoue**

`app-laravel/tests/Feature/ServiceTest.php` :

```php
<?php

use App\Models\Categorie;
use App\Models\Service;

beforeEach(function () {
    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);
});

it('rend ses textes dans la langue demandee', function () {
    $service = Service::factory()->create([
        'categorie_id' => $this->categorie->id,
        'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title',
        'accroche_fr' => 'Sécuriser vos terrains', 'accroche_en' => 'Secure your land',
    ]);

    expect($service->nom('fr'))->toBe('Foncier');
    expect($service->nom('en'))->toBe('Land & Title');
    expect($service->accroche('en'))->toBe('Secure your land');
});

it('replie sur le francais quand l anglais manque', function () {
    $service = Service::factory()->create([
        'categorie_id' => $this->categorie->id,
        'nom_fr' => 'Foncier', 'nom_en' => '',
    ]);

    expect($service->nom('en'))->toBe('Foncier');
});

it('rend ses atouts comme une liste, sans les vides', function () {
    $service = Service::factory()->create([
        'categorie_id' => $this->categorie->id,
        'atout1_fr' => 'Vérification ACD',
        'atout2_fr' => 'Bornage',
        'atout3_fr' => '',
    ]);

    expect($service->atouts('fr'))->toBe(['Vérification ACD', 'Bornage']);
});

it('refuse deux services de meme slug', function () {
    Service::factory()->create(['slug' => 'foncier', 'categorie_id' => $this->categorie->id]);

    expect(fn () => Service::factory()->create(['slug' => 'foncier', 'categorie_id' => $this->categorie->id]))
        ->toThrow(Illuminate\Database\QueryException::class);
});

it('se retrouve par son slug dans une adresse', function () {
    $service = Service::factory()->create(['slug' => 'foncier', 'categorie_id' => $this->categorie->id]);

    expect($service->getRouteKeyName())->toBe('slug');
});

it('appartient a une categorie', function () {
    $service = Service::factory()->create(['categorie_id' => $this->categorie->id]);

    expect($service->categorie->slug)->toBe('foncier');
});
```

- [ ] **Step 2 : Lancer le test pour le voir échouer**

```bash
cd app-laravel && php artisan test --filter=ServiceTest
```

Attendu : ÉCHEC — table `services` inexistante.

- [ ] **Step 3 : Créer la migration**

```bash
cd app-laravel && php artisan make:migration create_services_table
```

Contenu :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('categorie_id')->constrained('categories');

            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('visible')->default(true);

            $table->string('nom_fr');
            $table->string('nom_en');
            $table->string('accroche_fr');
            $table->string('accroche_en');
            $table->text('description_fr');
            $table->text('description_en');

            // Trois atouts au maximum, comme les etiquettes des tuiles du site.
            // Nullables : un service peut n'en avoir qu'un.
            foreach (['1', '2', '3'] as $n) {
                $table->string('atout'.$n.'_fr')->nullable();
                $table->string('atout'.$n.'_en')->nullable();
            }

            $table->string('libelle_bouton_fr')->nullable();
            $table->string('libelle_bouton_en')->nullable();

            // Icone : le trace SVG de la tuile, repris tel quel du site.
            $table->text('icone_svg')->nullable();
            // Visuel de fond de la tuile, chemin relatif comme image_source.
            $table->string('image_source')->nullable();

            $table->timestamps();
            $table->index(['visible', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
```

- [ ] **Step 4 : Créer le modèle**

`app-laravel/app/Models/Service.php` :

```php
<?php

namespace App\Models;

use App\Models\Concerns\CollectionOrdonnable;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use CollectionOrdonnable;
    use HasFactory;
    use TraduitParColonnes;

    protected $fillable = [
        'slug', 'categorie_id', 'ordre', 'visible',
        'nom_fr', 'nom_en', 'accroche_fr', 'accroche_en',
        'description_fr', 'description_en',
        'atout1_fr', 'atout1_en', 'atout2_fr', 'atout2_en', 'atout3_fr', 'atout3_en',
        'libelle_bouton_fr', 'libelle_bouton_en',
        'icone_svg', 'image_source',
    ];

    protected $casts = ['visible' => 'boolean', 'ordre' => 'integer'];

    protected $attributes = ['ordre' => 0, 'visible' => true];

    /** Nom du service dans la langue demandee, francais par defaut. */
    public function nom(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('nom', $langue);
    }

    /** Phrase courte affichee sur la tuile. */
    public function accroche(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('accroche', $langue);
    }

    /** Texte long affiche dans la fiche du service. */
    public function description(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('description', $langue);
    }

    /** Libelle du bouton d'appel a l'action, repli sur une valeur traduite. */
    public function libelleBouton(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('libelle_bouton', $langue) ?: __('En savoir plus');
    }

    /**
     * Les atouts, dans l'ordre, sans les emplacements laisses vides.
     *
     * Trois colonnes plutot qu'une table dediee : le site en affiche
     * exactement trois depuis toujours, et une table pour trois valeurs fixes
     * couterait une jointure a chaque affichage pour aucun gain.
     */
    public function atouts(string $langue = 'fr'): array
    {
        return array_values(array_filter([
            $this->texteDansLaLangue('atout1', $langue),
            $this->texteDansLaLangue('atout2', $langue),
            $this->texteDansLaLangue('atout3', $langue),
        ], fn ($a) => $a !== ''));
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function questionsFaq()
    {
        return $this->hasMany(QuestionFaq::class, 'service_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
```

- [ ] **Step 5 : Créer la fabrique**

`app-laravel/database/factories/ServiceFactory.php` :

```php
<?php

namespace Database\Factories;

use App\Models\Categorie;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        $nom = fake()->unique()->words(2, true);

        return [
            'slug' => Str::slug($nom).'-'.fake()->unique()->numberBetween(1, 99999),
            'categorie_id' => Categorie::factory(),
            'ordre' => 0,
            'visible' => true,
            'nom_fr' => $nom,
            'nom_en' => $nom.' (EN)',
            'accroche_fr' => fake()->sentence(),
            'accroche_en' => fake()->sentence().' (EN)',
            'description_fr' => fake()->paragraph(),
            'description_en' => fake()->paragraph().' (EN)',
        ];
    }
}
```

Si `Categorie` n'a pas de fabrique, la créer de la même façon avec `slug`, `nom_fr`, `nom_en`, `ordre`.

- [ ] **Step 6 : Migrer et lancer les tests**

```bash
cd app-laravel && php artisan migrate --force && php artisan test --filter='ServiceTest|CollectionOrdonnableTest'
```

Attendu : les 11 tests passent — les 6 de `ServiceTest` et les 5 de `CollectionOrdonnableTest`, restés rouges depuis la tâche 1.

- [ ] **Step 7 : Commit**

```bash
git add -A
git commit -m "feat(services): table, modele bilingue et fabrique"
```

---

### Task 3 : Table et modèle QuestionFaq

**Files:**
- Create: `app-laravel/database/migrations/XXXX_create_questions_faq_table.php`
- Create: `app-laravel/app/Models/QuestionFaq.php`
- Create: `app-laravel/database/factories/QuestionFaqFactory.php`
- Create: `app-laravel/tests/Feature/QuestionFaqTest.php`

**Interfaces:**
- Consumes: `CollectionOrdonnable`, `TraduitParColonnes`, `Service` (tâche 2)
- Produces: `QuestionFaq` avec `question(string $langue = 'fr'): string` et `reponse(string $langue = 'fr'): string`

- [ ] **Step 1 : Écrire le test qui échoue**

`app-laravel/tests/Feature/QuestionFaqTest.php` :

```php
<?php

use App\Models\Categorie;
use App\Models\QuestionFaq;
use App\Models\Service;

beforeEach(function () {
    $categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);
    $this->service = Service::factory()->create([
        'categorie_id' => $categorie->id, 'slug' => 'foncier',
        'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title',
    ]);
});

it('rend question et reponse dans la langue demandee', function () {
    $q = QuestionFaq::factory()->create([
        'service_id' => $this->service->id,
        'question_fr' => "Qu'est-ce qu'un ACD ?", 'question_en' => 'What is an ACD?',
        'reponse_fr' => 'Un arrêté officiel.', 'reponse_en' => 'An official order.',
    ]);

    expect($q->question('en'))->toBe('What is an ACD?');
    expect($q->reponse('fr'))->toBe('Un arrêté officiel.');
});

it('appartient a un service, qui sert de titre de groupe', function () {
    $q = QuestionFaq::factory()->create(['service_id' => $this->service->id]);

    expect($q->service->nom('fr'))->toBe('Foncier');
});

it('se groupe par service, dans l ordre des services', function () {
    $autre = Service::factory()->create(['slug' => 'construction', 'nom_fr' => 'Construction', 'ordre' => 2]);
    $this->service->update(['ordre' => 1]);

    QuestionFaq::factory()->create(['service_id' => $autre->id, 'question_fr' => 'B', 'ordre' => 1]);
    QuestionFaq::factory()->create(['service_id' => $this->service->id, 'question_fr' => 'A', 'ordre' => 1]);

    $groupes = QuestionFaq::visibles()->with('service')->get()
        ->sortBy(fn ($q) => [$q->service->ordre, $q->ordre])
        ->groupBy(fn ($q) => $q->service->nom('fr'));

    expect($groupes->keys()->all())->toBe(['Foncier', 'Construction']);
});

it('replie sur le francais quand l anglais manque', function () {
    $q = QuestionFaq::factory()->create([
        'service_id' => $this->service->id,
        'reponse_fr' => 'Réponse française.', 'reponse_en' => '',
    ]);

    expect($q->reponse('en'))->toBe('Réponse française.');
});
```

- [ ] **Step 2 : Lancer le test pour le voir échouer**

```bash
cd app-laravel && php artisan test --filter=QuestionFaqTest
```

Attendu : ÉCHEC — table `questions_faq` inexistante.

- [ ] **Step 3 : Créer la migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions_faq', function (Blueprint $table) {
            $table->id();

            // Le titre de groupe affiche sur faq.html EST le nom du service :
            // la question pointe donc le service, et non une categorie ou un
            // groupe invente pour l'occasion.
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();

            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('visible')->default(true);

            $table->string('question_fr', 500);
            $table->string('question_en', 500);
            $table->text('reponse_fr');
            $table->text('reponse_en');

            $table->timestamps();
            $table->index(['service_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions_faq');
    }
};
```

- [ ] **Step 4 : Créer le modèle**

```php
<?php

namespace App\Models;

use App\Models\Concerns\CollectionOrdonnable;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionFaq extends Model
{
    use CollectionOrdonnable;
    use HasFactory;
    use TraduitParColonnes;

    protected $table = 'questions_faq';

    protected $fillable = [
        'service_id', 'ordre', 'visible',
        'question_fr', 'question_en', 'reponse_fr', 'reponse_en',
    ];

    protected $casts = ['visible' => 'boolean', 'ordre' => 'integer'];

    protected $attributes = ['ordre' => 0, 'visible' => true];

    public function question(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('question', $langue);
    }

    public function reponse(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('reponse', $langue);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
```

- [ ] **Step 5 : Créer la fabrique**

```php
<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFaqFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'ordre' => 0,
            'visible' => true,
            'question_fr' => fake()->sentence().' ?',
            'question_en' => fake()->sentence().' ? (EN)',
            'reponse_fr' => fake()->paragraph(),
            'reponse_en' => fake()->paragraph().' (EN)',
        ];
    }
}
```

- [ ] **Step 6 : Migrer et lancer les tests**

```bash
cd app-laravel && php artisan migrate --force && php artisan test --filter=QuestionFaqTest
```

Attendu : les 4 tests passent.

- [ ] **Step 7 : Commit**

```bash
git add -A
git commit -m "feat(faq): table, modele bilingue et rattachement au service"
```

---

### Task 4 : Reprise du contenu existant

**Files:**
- Create: `tools/extraction-services-faq.py`
- Create: `app-laravel/database/data/services.json`
- Create: `app-laravel/database/data/questions-faq.json`
- Create: `app-laravel/database/seeders/ServiceFaqSeeder.php`
- Create: `app-laravel/tests/Feature/ImportServicesFaqTest.php`
- Modify: `app-laravel/database/seeders/DatabaseSeeder.php`

**Interfaces:**
- Consumes: `Service` (tâche 2), `QuestionFaq` (tâche 3), `Categorie` (lot 1)
- Produces: 6 services et 12 questions en base, seeder rejouable

- [ ] **Step 1 : Écrire le script d'extraction**

`tools/extraction-services-faq.py` :

```python
#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Extraction ponctuelle des services et de la FAQ du frontoffice.

    python3 tools/extraction-services-faq.py

PONCTUEL, PAS UN PIPELINE. A relancer seulement si frontoffice/services.html,
frontoffice/faq.html ou frontoffice/assets/main.js changent.

NE JAMAIS DECODER PAR unicode_escape : voir l'en-tete de
tools/extraction-articles.py. Les textes de main.js sont deja en UTF-8 et
seuls quelques echappements JavaScript sont a defaire ; un decodage
unicode_escape les relit en Latin-1 et corrompt tous les accents, sans qu'aucun
test ne le voie — les tests verifient que les champs ne sont pas vides, et un
champ corrompu ne l'est pas.
"""
import io
import json
import re

svc_html = io.open('frontoffice/services.html', encoding='utf-8').read()
faq_html = io.open('frontoffice/faq.html', encoding='utf-8').read()
js = io.open('frontoffice/assets/main.js', encoding='utf-8').read()


def denoter(s):
    """Defait les echappements JavaScript sans toucher a l'encodage."""
    return (s.replace('\\\\', '\x00')
             .replace("\\'", "'").replace('\\"', '"')
             .replace('\\n', '\n').replace('\\t', '\t')
             .replace('\x00', '\\'))


def texte(cle):
    m = re.search(r'"' + re.escape(cle) + r'":\s*\{\s*fr:\s*"((?:[^"\\]|\\.)*)"\s*,\s*en:\s*"((?:[^"\\]|\\.)*)"', js)
    return (denoter(m.group(1)), denoter(m.group(2))) if m else ('', '')


# --- services, dans l'ordre d'apparition sur la page ---
slugs = []
for m in re.finditer(r'data-svc="([a-z-]+)"', svc_html):
    if m.group(1) not in slugs:
        slugs.append(m.group(1))

services = []
for rang, slug in enumerate(slugs, start=1):
    nom = texte('svc.%s.name' % slug)
    accroche = texte('svc.%s.short' % slug)
    description = texte('svc.%s.desc' % slug)
    bouton = texte('svc.%s.cta' % slug)

    if not nom[0]:
        raise SystemExit('nom manquant pour le service ' + slug)

    entree = {
        'slug': slug, 'ordre': rang,
        'nom_fr': nom[0], 'nom_en': nom[1],
        'accroche_fr': accroche[0], 'accroche_en': accroche[1],
        'description_fr': description[0], 'description_en': description[1],
        'libelle_bouton_fr': bouton[0], 'libelle_bouton_en': bouton[1],
    }
    for n in (1, 2, 3):
        fr, en = texte('svc.%s.feat%d' % (slug, n))
        entree['atout%d_fr' % n] = fr
        entree['atout%d_en' % n] = en

    # Le trace SVG de la tuile, repris tel quel.
    bloc = re.search(r'data-svc="%s".*?</button>' % re.escape(slug), svc_html, re.S)
    icone = re.search(r'<svg .*?</svg>', bloc.group(0), re.S) if bloc else None
    entree['icone_svg'] = icone.group(0) if icone else None

    services.append(entree)

# --- questions, groupees par service ---
questions = []
for morceau in re.split(r'faq-group-title', faq_html)[1:]:
    groupe = re.search(r'data-i18n="svc\.([a-z-]+)\.name"', morceau)
    if not groupe:
        continue
    slug = groupe.group(1)
    rang = 0
    for q in re.finditer(r'data-i18n="(faq\.q\d+)\.q"', morceau):
        rang += 1
        cle = q.group(1)
        qf, qe = texte(cle + '.q')
        rf, re_ = texte(cle + '.a')
        if not qf or not rf:
            raise SystemExit('question ou reponse manquante : ' + cle)
        questions.append({
            'service_slug': slug, 'ordre': rang,
            'question_fr': qf, 'question_en': qe,
            'reponse_fr': rf, 'reponse_en': re_,
        })

if len(services) != 6:
    raise SystemExit('%d services extraits, 6 attendus' % len(services))
if len(questions) != 12:
    raise SystemExit('%d questions extraites, 12 attendues' % len(questions))

io.open('app-laravel/database/data/services.json', 'w', encoding='utf-8').write(
    json.dumps(services, ensure_ascii=False, indent=2))
io.open('app-laravel/database/data/questions-faq.json', 'w', encoding='utf-8').write(
    json.dumps(questions, ensure_ascii=False, indent=2))

print('%d services, %d questions' % (len(services), len(questions)))

# Controle de non-regression sur le decodage.
suspects = []
for lot, champs in ((services, ('nom_fr', 'nom_en', 'description_fr', 'description_en')),
                    (questions, ('question_fr', 'question_en', 'reponse_fr', 'reponse_en'))):
    for e in lot:
        for c in champs:
            t = e.get(c) or ''
            if 'Ã' in t or 'â€' in t or '\\u' in t:
                suspects.append(c)
print('textes suspects (encodage) :', len(suspects))
```

- [ ] **Step 2 : Lancer l'extraction**

```bash
python3 tools/extraction-services-faq.py
```

Attendu : `6 services, 12 questions` puis `textes suspects (encodage) : 0`.

- [ ] **Step 3 : Écrire le test d'import qui échoue**

`app-laravel/tests/Feature/ImportServicesFaqTest.php` :

```php
<?php

use App\Models\QuestionFaq;
use App\Models\Service;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'CategorieSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'ServiceFaqSeeder', '--force' => true]);
});

it('importe les six services', function () {
    expect(Service::count())->toBe(6);
});

it('importe les douze questions, deux par service', function () {
    expect(QuestionFaq::count())->toBe(12);

    Service::all()->each(function ($s) {
        expect($s->questionsFaq()->count())->toBe(2);
    });
});

it('n importe aucun texte vide', function () {
    foreach (Service::all() as $s) {
        foreach (['nom_fr', 'nom_en', 'accroche_fr', 'accroche_en', 'description_fr', 'description_en'] as $c) {
            expect($s->$c)->not->toBe('', "{$s->slug}.{$c} est vide");
        }
    }

    foreach (QuestionFaq::all() as $q) {
        foreach (['question_fr', 'question_en', 'reponse_fr', 'reponse_en'] as $c) {
            expect($q->$c)->not->toBe('', "question {$q->id}.{$c} est vide");
        }
    }
});

it('n importe aucun texte corrompu', function () {
    // Le controle se fait en PHP, jamais en SQL : la collation du projet est
    // insensible aux accents, un LIKE '%Ã%' matcherait tous les « a ».
    $suspects = [];

    foreach (Service::all() as $s) {
        foreach (['nom_fr', 'nom_en', 'description_fr', 'description_en'] as $c) {
            if (str_contains($s->$c, 'Ã') || str_contains($s->$c, 'â€')) {
                $suspects[] = $s->slug.'.'.$c;
            }
        }
    }

    expect($suspects)->toBe([]);
});

it('rattache chaque service a sa categorie', function () {
    expect(Service::whereNull('categorie_id')->count())->toBe(0);

    $service = Service::where('slug', 'foncier')->first();
    expect($service->categorie->slug)->toBe('foncier');
});

it('conserve l ordre d affichage du site', function () {
    expect(Service::ordonnees()->pluck('slug')->all())
        ->toBe(['foncier', 'construction', 'gestion', 'achat', 'vente', 'administration']);
});

it('est rejouable sans creer de doublon', function () {
    Artisan::call('db:seed', ['--class' => 'ServiceFaqSeeder', '--force' => true]);

    expect(Service::count())->toBe(6);
    expect(QuestionFaq::count())->toBe(12);
});
```

- [ ] **Step 4 : Lancer le test pour le voir échouer**

```bash
cd app-laravel && php artisan test --filter=ImportServicesFaqTest
```

Attendu : ÉCHEC — classe `ServiceFaqSeeder` introuvable.

- [ ] **Step 5 : Écrire le seeder**

`app-laravel/database/seeders/ServiceFaqSeeder.php` :

```php
<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\QuestionFaq;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Reprend les six services et les douze questions du site statique.
 *
 * Rejouable : le slug du service et le couple (service, ordre) de la question
 * servent de cle. Relancer corrige sans dupliquer.
 */
class ServiceFaqSeeder extends Seeder
{
    public function run(): void
    {
        $services = json_decode(file_get_contents(database_path('data/services.json')), true);
        $questions = json_decode(file_get_contents(database_path('data/questions-faq.json')), true);

        if (! $services || ! $questions) {
            throw new \RuntimeException('Donnees d import introuvables ou illisibles.');
        }

        foreach ($services as $s) {
            // Le slug du service correspond a celui de la categorie : c'est ce
            // qui avait motive la table categories au lot 1.
            $categorie = Categorie::where('slug', $s['slug'])->first();

            if (! $categorie) {
                throw new \RuntimeException("Categorie absente pour le service {$s['slug']}. Lancer CategorieSeeder d'abord.");
            }

            Service::updateOrCreate(
                ['slug' => $s['slug']],
                array_merge($s, ['categorie_id' => $categorie->id, 'visible' => true])
            );
        }

        foreach ($questions as $q) {
            $service = Service::where('slug', $q['service_slug'])->firstOrFail();

            QuestionFaq::updateOrCreate(
                ['service_id' => $service->id, 'ordre' => $q['ordre']],
                [
                    'question_fr' => $q['question_fr'], 'question_en' => $q['question_en'],
                    'reponse_fr' => $q['reponse_fr'], 'reponse_en' => $q['reponse_en'],
                    'visible' => true,
                ]
            );
        }

        $this->command?->info(sprintf(
            '%d services et %d questions en base.',
            Service::count(),
            QuestionFaq::count()
        ));
    }
}
```

- [ ] **Step 6 : Brancher le seeder**

Dans `app-laravel/database/seeders/DatabaseSeeder.php`, à la suite de `CategorieSeeder` :

```php
$this->call(ServiceFaqSeeder::class);
```

L'ordre compte : les services ont besoin des catégories.

- [ ] **Step 7 : Lancer les tests**

```bash
cd app-laravel && php artisan test --filter=ImportServicesFaqTest
```

Attendu : les 7 tests passent.

- [ ] **Step 8 : Vérifier le contenu à l'œil**

```bash
cd app-laravel && php artisan tinker --execute="
foreach (App\Models\Service::ordonnees()->get() as \$s) {
  echo \$s->slug, ' | ', \$s->nom('fr'), ' | ', \$s->nom('en'), ' | ', count(\$s->atouts('fr')), ' atouts', PHP_EOL;
}"
```

Attendu : six lignes, noms français et anglais différents, atouts entre 1 et 3.

- [ ] **Step 9 : Commit**

```bash
git add -A
git commit -m "feat(import): reprend les six services et les douze questions du site"
```

---

### Task 5 : Composant abstrait ListeOrdonnable

**Files:**
- Create: `app-laravel/app/Livewire/Admin/ListeOrdonnable.php`
- Create: `app-laravel/resources/views/components/admin/poignee-ordre.blade.php`
- Create: `app-laravel/tests/Feature/ListeOrdonnableTest.php`

**Interfaces:**
- Consumes: `CollectionOrdonnable` (tâche 1)
- Produces: classe abstraite avec `$recherche`, `$visibilite`, les méthodes abstraites `modele(): string` et `colonnesRecherchees(): array`, et les méthodes concrètes `supprimer(int $id): void`, `reordonner(array $ids): void`, `basculerVisibilite(int $id): void`

- [ ] **Step 1 : Écrire le test qui échoue**

`app-laravel/tests/Feature/ListeOrdonnableTest.php` :

```php
<?php

use App\Livewire\Admin\ServiceListe;
use App\Models\Categorie;
use App\Models\Service;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');
});

it('liste les elements dans leur ordre', function () {
    Service::factory()->create(['nom_fr' => 'Second', 'ordre' => 2, 'categorie_id' => $this->categorie->id]);
    Service::factory()->create(['nom_fr' => 'Premier', 'ordre' => 1, 'categorie_id' => $this->categorie->id]);

    $rendu = Livewire::actingAs($this->editeur)->test(ServiceListe::class)->html();

    expect(strpos($rendu, 'Premier'))->toBeLessThan(strpos($rendu, 'Second'));
});

it('cherche dans les deux langues', function () {
    Service::factory()->create(['nom_fr' => 'Foncier', 'nom_en' => 'Land title', 'categorie_id' => $this->categorie->id]);
    Service::factory()->create(['nom_fr' => 'Construction', 'nom_en' => 'Building', 'categorie_id' => $this->categorie->id]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->set('recherche', 'Land')
        ->assertSee('Foncier')
        ->assertDontSee('Construction');
});

it('filtre sur la visibilite', function () {
    Service::factory()->create(['nom_fr' => 'En ligne', 'visible' => true, 'categorie_id' => $this->categorie->id]);
    Service::factory()->create(['nom_fr' => 'Masque', 'visible' => false, 'categorie_id' => $this->categorie->id]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->set('visibilite', 'masques')
        ->assertSee('Masque')
        ->assertDontSee('En ligne');
});

it('bascule la visibilite d un element', function () {
    $s = Service::factory()->create(['visible' => true, 'categorie_id' => $this->categorie->id]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->call('basculerVisibilite', $s->id);

    expect($s->fresh()->visible)->toBeFalse();
});

it('reordonne depuis le navigateur', function () {
    $a = Service::factory()->create(['ordre' => 1, 'categorie_id' => $this->categorie->id]);
    $b = Service::factory()->create(['ordre' => 2, 'categorie_id' => $this->categorie->id]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->call('reordonner', [$b->id, $a->id]);

    expect(Service::ordonnees()->pluck('id')->all())->toBe([$b->id, $a->id]);
});

it('supprime un element', function () {
    $s = Service::factory()->create(['categorie_id' => $this->categorie->id]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->call('supprimer', $s->id);

    expect(Service::find($s->id))->toBeNull();
});

it('interdit a un lecteur d ecrire', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    $s = Service::factory()->create(['categorie_id' => $this->categorie->id]);

    Livewire::actingAs($lecteur)
        ->test(ServiceListe::class)
        ->call('supprimer', $s->id)
        ->assertForbidden();

    expect(Service::find($s->id))->not->toBeNull();

    Livewire::actingAs($lecteur)
        ->test(ServiceListe::class)
        ->call('reordonner', [$s->id])
        ->assertForbidden();

    Livewire::actingAs($lecteur)
        ->test(ServiceListe::class)
        ->call('basculerVisibilite', $s->id)
        ->assertForbidden();
});
```

- [ ] **Step 2 : Lancer le test pour le voir échouer**

```bash
cd app-laravel && php artisan test --filter=ListeOrdonnableTest
```

Attendu : ÉCHEC — `App\Livewire\Admin\ServiceListe` introuvable. Comme à la tâche 1, ces tests attendent la tâche suivante : un composant abstrait ne s'éprouve que par une de ses implémentations.

- [ ] **Step 3 : Écrire le composant abstrait**

`app-laravel/app/Livewire/Admin/ListeOrdonnable.php` :

```php
<?php

namespace App\Livewire\Admin;

use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ecran de liste commun aux collections ordonnables du lot 2.
 *
 * Les sept blocs partagent recherche, filtre de visibilite, reordonnancement,
 * bascule de visibilite et suppression. Chaque entite ne declare que son
 * modele et les colonnes ou porte la recherche.
 *
 * Le controle de role est refait dans chaque methode d'ecriture : la route
 * protege l'ecran, pas l'action, et un lecteur peut atteindre le composant.
 */
#[Layout('layouts.app')]
abstract class ListeOrdonnable extends Component
{
    public string $recherche = '';

    /** '' | 'visibles' | 'masques' */
    public string $visibilite = '';

    /** Classe du modele porte par cet ecran. */
    abstract protected function modele(): string;

    /** Colonnes ou porte la recherche, dans les deux langues. */
    abstract protected function colonnesRecherchees(): array;

    /** Vue Blade de l'ecran. */
    abstract protected function vue(): string;

    /** Titre affiche dans l'en-tete et l'onglet. */
    abstract protected function titre(): string;

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    public function updating($nom): void
    {
        if (in_array($nom, ['recherche', 'visibilite'], true) && method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function reordonner(array $ids): void
    {
        abort_unless($this->peutEcrire(), 403);

        ($this->modele())::reordonner($ids);
    }

    public function basculerVisibilite(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        $element = ($this->modele())::findOrFail($id);
        $element->update(['visible' => ! $element->visible]);
    }

    public function supprimer(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        ($this->modele())::findOrFail($id)->delete();

        session()->flash('message', __('Élément supprimé.'));
    }

    /** Les elements de l'ecran, filtres et ordonnes. */
    protected function elements(): Collection
    {
        return ($this->modele())::query()
            ->when($this->recherche !== '', function ($r) {
                $r->where(function ($q) {
                    foreach ($this->colonnesRecherchees() as $colonne) {
                        $q->orWhere($colonne, 'like', '%'.$this->recherche.'%');
                    }
                });
            })
            ->when($this->visibilite === 'visibles', fn ($r) => $r->where('visible', true))
            ->when($this->visibilite === 'masques', fn ($r) => $r->where('visible', false))
            ->ordonnees()
            ->get();
    }

    public function render()
    {
        return view($this->vue(), [
            'elements' => $this->elements(),
            'langue' => app()->getLocale(),
            'peutEcrire' => $this->peutEcrire(),
        ])->title($this->titre());
    }
}
```

Note : ces écrans ne paginent pas. Six services, douze questions, quatre valeurs — la pagination coûterait à écrire et à utiliser pour rien, et elle est incompatible avec un réordonnancement par glisser-déposer, qui a besoin de voir toute la liste.

- [ ] **Step 4 : Créer la poignée de réordonnancement**

`app-laravel/resources/views/components/admin/poignee-ordre.blade.php` :

```blade
{{--
    Poignee de glisser-deposer.

    Le rang se regle en deplacant la ligne, pas en saisissant un nombre :
    taper « 3 » puis « 4 » pour permuter deux elements produit des doublons et
    impose de renumeroter a chaque insertion.

    aria-hidden sur l'icone, mais le bouton reste atteignable au clavier et
    porte son intitule : un lecteur d'ecran annonce « Deplacer », pas « point
    point point ».
--}}
<button type="button"
        class="cursor-grab touch-none rounded-md p-2 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 active:cursor-grabbing dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
        aria-label="{{ __('Déplacer cet élément') }}"
        {{ $attributes }}>
    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
        <path d="M9 6h.01M9 12h.01M9 18h.01M15 6h.01M15 12h.01M15 18h.01"/>
    </svg>
</button>
```

- [ ] **Step 5 : Commit**

```bash
git add app-laravel/app/Livewire/Admin/ListeOrdonnable.php app-laravel/resources/views/components/admin/poignee-ordre.blade.php app-laravel/tests/Feature/ListeOrdonnableTest.php
git commit -m "feat(admin): ecran de liste commun aux collections ordonnables"
```

---

### Task 6 : Écran des services

**Files:**
- Create: `app-laravel/app/Livewire/Admin/ServiceListe.php`
- Create: `app-laravel/resources/views/livewire/admin/service-liste.blade.php`
- Modify: `app-laravel/routes/web.php`
- Modify: `app-laravel/resources/views/layouts/app/sidebar.blade.php`
- Modify: `app-laravel/lang/en.json`

**Interfaces:**
- Consumes: `ListeOrdonnable` (tâche 5), `Service` (tâche 2)
- Produces: route nommée `admin.services.liste` sur `/admin/services`

La maquette de référence est `backoffice/service-list.html` : colonnes **Service**, **Visuel sur l'accueil**, **Ordre**, **Statut**, **Actions**.

- [ ] **Step 1 : Écrire le composant**

`app-laravel/app/Livewire/Admin/ServiceListe.php` :

```php
<?php

namespace App\Livewire\Admin;

use App\Models\Service;

class ServiceListe extends ListeOrdonnable
{
    protected function modele(): string
    {
        return Service::class;
    }

    protected function colonnesRecherchees(): array
    {
        return ['nom_fr', 'nom_en', 'accroche_fr', 'accroche_en'];
    }

    protected function vue(): string
    {
        return 'livewire.admin.service-liste';
    }

    protected function titre(): string
    {
        return __('Services');
    }
}
```

- [ ] **Step 2 : Écrire la vue**

`app-laravel/resources/views/livewire/admin/service-liste.blade.php` :

```blade
@php($champ = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<div class="space-y-6">

    <x-admin.entete-page
        :titre="__('Services')"
        :fil="[__('Accueil') => route('dashboard'), __('Contenu') => null, __('Services') => null]"
        :resume="trans_choice(':nombre service|:nombre services', $elements->count(), ['nombre' => $elements->count()])">
        <x-slot:actions>
            <x-bascule-langue />
        </x-slot:actions>
    </x-admin.entete-page>

    @if (session('message'))
        <div role="status" class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-100">
            {{ session('message') }}
        </div>
    @endif

    <x-admin.barre-filtres>
        <x-admin.champ-filtre :intitule="__('Rechercher')" pour="recherche">
            <input type="search" id="recherche" wire:model.live.debounce.300ms="recherche"
                   placeholder="{{ __('Nom du service…') }}" class="{{ $champ }}">
        </x-admin.champ-filtre>

        <x-admin.champ-filtre :intitule="__('Visibilité')" pour="visibilite">
            <select id="visibilite" wire:model.live="visibilite" class="{{ $champ }}">
                <option value="">{{ __('Tous') }}</option>
                <option value="visibles">{{ __('Visibles') }}</option>
                <option value="masques">{{ __('Masqués') }}</option>
            </select>
        </x-admin.champ-filtre>
    </x-admin.barre-filtres>

    {{-- L'ordre se regle en deplaçant les lignes. wire:sortable est fourni par
         le petit script du pied de vue : aucune dependance ajoutee. --}}
    <x-admin.tableau :colonnes="['', __('Service'), __('Visuel'), __('Catégorie'), __('Statut'), __('Actions')]">
        <x-slot:pied>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __("Faites glisser une ligne par sa poignée pour changer l'ordre d'affichage sur le site.") }}
            </p>
        </x-slot:pied>

        @forelse ($elements as $element)
            <tr wire:key="service-{{ $element->id }}" data-id="{{ $element->id }}"
                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                <td class="w-10 px-2 py-3">
                    @if ($peutEcrire)
                        <x-admin.poignee-ordre class="poignee" />
                    @endif
                </td>

                {{-- Le nom n'est pas encore un lien : la route d'edition arrive
                     a la tache 8, qui enveloppera ce bloc. Le referencer ici
                     ferait echouer les tests de la tache 6 sur une route
                     inexistante. --}}
                <td class="px-4 py-3">
                    <span class="block font-medium text-zinc-900 dark:text-white">
                        {{ $element->nom($langue) }}
                    </span>
                    <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $element->accroche($langue) }}
                    </span>
                </td>

                <td class="px-4 py-3">
                    @if ($element->image_source)
                        <img src="{{ asset($element->image_source) }}" alt="" loading="lazy" class="h-11 w-16 rounded object-cover">
                    @else
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Aucun') }}</span>
                    @endif
                </td>

                <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-300">
                    {{ $element->categorie->nom($langue) }}
                </td>

                <td class="whitespace-nowrap px-4 py-3">
                    <button type="button" wire:click="basculerVisibilite({{ $element->id }})"
                            @disabled(! $peutEcrire)
                            class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium {{ $element->visible ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                        {{ $element->visible ? __('Visible') : __('Masqué') }}
                    </button>
                </td>

                {{-- La colonne Actions reste vide jusqu'a la tache 8, pour la
                     meme raison. --}}
                <td class="whitespace-nowrap px-4 py-3"></td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-zinc-600 dark:text-zinc-400">
                    {{ __('Aucun service ne correspond à votre recherche.') }}
                </td>
            </tr>
        @endforelse
    </x-admin.tableau>
</div>
```

Note : il n'y a **ni bouton de création ni suppression** sur cet écran. Les six services correspondent aux six métiers de l'agence et à la navigation du site ; en ajouter ou en retirer touche la structure des pages publiques, ce qui relève d'un développement, pas d'une saisie. Les rendre masquables suffit.

- [ ] **Step 3 : Déclarer la route**

Dans le groupe `admin` de `app-laravel/routes/web.php`, à la suite des routes d'articles :

```php
Route::get('/services', \App\Livewire\Admin\ServiceListe::class)->name('services.liste');
```

- [ ] **Step 4 : Ajouter l'entrée de barre latérale**

Dans `app-laravel/resources/views/layouts/app/sidebar.blade.php`, dans **les deux** navigations — bureau et mobile — à la suite de l'entrée Articles, en reprenant exactement son balisage et en remplaçant la route par `admin.services.liste`, la condition `routeIs` par `admin.services.*`, et l'intitulé par `{{ __('Services') }}`.

- [ ] **Step 5 : Ajouter les clés de traduction**

Dans `app-laravel/lang/en.json` :

```json
"Services": "Services",
"Nom du service…": "Service name…",
"Visibilité": "Visibility",
"Visibles": "Visible",
"Masqués": "Hidden",
"Visible": "Visible",
"Masqué": "Hidden",
"Tous": "All",
"Visuel": "Image",
"Aucun": "None",
"Déplacer cet élément": "Move this item",
"Faites glisser une ligne par sa poignée pour changer l'ordre d'affichage sur le site.": "Drag a row by its handle to change the display order on the site.",
"Aucun service ne correspond à votre recherche.": "No service matches your search.",
"Élément supprimé.": "Item deleted.",
"En savoir plus": "Learn more"
```

Dans `app-laravel/lang/fr.json`, les formes plurielles, en identité — sans quoi `trans_choice` retombe sur l'anglais :

```json
":nombre service|:nombre services": ":nombre service|:nombre services"
```

Et dans `en.json` :

```json
":nombre service|:nombre services": ":nombre service|:nombre services"
```

- [ ] **Step 6 : Lancer les tests**

```bash
cd app-laravel && php artisan test --filter='ListeOrdonnableTest|ClesDeTraductionTest'
```

Attendu : les 7 tests de `ListeOrdonnableTest`, restés rouges depuis la tâche 5, passent ; les 7 de `ClesDeTraductionTest` aussi.

- [ ] **Step 7 : Commit**

```bash
git add -A
git commit -m "feat(admin): ecran des services, ordonnable et bilingue"
```

---

### Task 7 : Glisser-déposer

**Files:**
- Create: `app-laravel/resources/js/ordre.js`
- Modify: `app-laravel/resources/js/app.js`
- Modify: `app-laravel/resources/views/livewire/admin/service-liste.blade.php`

**Interfaces:**
- Consumes: la méthode `reordonner(array $ids)` de `ListeOrdonnable` (tâche 5)
- Produces: tout `<tbody data-ordonnable>` devient réordonnable ; l'ordre est envoyé à Livewire

- [ ] **Step 1 : Écrire le script**

`app-laravel/resources/js/ordre.js` :

```js
/*
 * Reordonnancement des tableaux d'administration par glisser-deposer.
 *
 * Ecrit a la main plutot qu'avec une bibliotheque : l'API HTML5 de
 * glisser-deposer suffit pour des lignes de tableau, et une dependance de plus
 * pour quarante lignes ne se justifie pas.
 *
 * Le tableau declare `data-ordonnable` et chaque ligne son `data-id`. A la
 * depose, l'ordre des identifiants part vers la methode `reordonner` du
 * composant Livewire.
 */
function activerOrdre(tbody) {
    if (tbody.dataset.ordreActive === '1') return;
    tbody.dataset.ordreActive = '1';

    let ligneTiree = null;

    tbody.addEventListener('pointerdown', (e) => {
        const poignee = e.target.closest('.poignee');
        if (!poignee) return;
        const ligne = poignee.closest('tr');
        if (ligne) ligne.draggable = true;
    });

    tbody.addEventListener('dragstart', (e) => {
        ligneTiree = e.target.closest('tr');
        if (ligneTiree) ligneTiree.classList.add('opacity-50');
    });

    tbody.addEventListener('dragover', (e) => {
        e.preventDefault();
        const cible = e.target.closest('tr');
        if (!cible || !ligneTiree || cible === ligneTiree) return;

        const cadre = cible.getBoundingClientRect();
        const apres = e.clientY > cadre.top + cadre.height / 2;
        cible.parentNode.insertBefore(ligneTiree, apres ? cible.nextSibling : cible);
    });

    tbody.addEventListener('dragend', () => {
        if (!ligneTiree) return;
        ligneTiree.classList.remove('opacity-50');
        ligneTiree.draggable = false;
        ligneTiree = null;

        const ids = [...tbody.querySelectorAll('tr[data-id]')].map((tr) => Number(tr.dataset.id));
        const composant = tbody.closest('[wire\\:id]');
        if (composant && window.Livewire) {
            window.Livewire.find(composant.getAttribute('wire:id')).call('reordonner', ids);
        }
    });
}

function activerTout() {
    document.querySelectorAll('tbody[data-ordonnable]').forEach(activerOrdre);
}

document.addEventListener('DOMContentLoaded', activerTout);
// Livewire remplace le tableau apres chaque mise a jour : il faut reactiver.
document.addEventListener('livewire:navigated', activerTout);
document.addEventListener('livewire:update', activerTout);

export { activerTout };
```

- [ ] **Step 2 : Charger le script**

Dans `app-laravel/resources/js/app.js`, ajouter :

```js
import './ordre';
```

- [ ] **Step 3 : Marquer le tableau**

Le composant `x-admin.tableau` transmet ses attributs au conteneur, pas au `<tbody>`. Ajouter à `app-laravel/resources/views/components/admin/tableau.blade.php` une propriété facultative :

```blade
@props(['colonnes' => [], 'ordonnable' => false])
```

et sur la balise `<tbody>` :

```blade
<tbody @if ($ordonnable) data-ordonnable @endif class="divide-y divide-zinc-100 dark:divide-zinc-800">
```

Puis, dans `service-liste.blade.php`, passer `:ordonnable="$peutEcrire"` au composant `x-admin.tableau`.

- [ ] **Step 4 : Assembler et vérifier à l'œil**

```bash
cd app-laravel && npm run build && php artisan serve --port=8160
```

Se connecter, ouvrir `/admin/services`, déplacer une ligne par sa poignée, recharger la page : le nouvel ordre tient.

- [ ] **Step 5 : Lancer la suite complète**

```bash
cd app-laravel && php artisan test
```

Attendu : tous les tests passent. Le glisser-déposer lui-même n'est pas couvert par un test automatique — c'est du navigateur ; la méthode `reordonner` qu'il appelle l'est, aux tâches 1 et 5.

- [ ] **Step 6 : Commit**

```bash
git add -A
git commit -m "feat(admin): reordonnancement des listes par glisser-deposer"
```

---

### Task 8 : Formulaire d'édition d'un service

**Files:**
- Create: `app-laravel/app/Livewire/Admin/ServiceFormulaire.php`
- Create: `app-laravel/resources/views/livewire/admin/service-formulaire.blade.php`
- Create: `app-laravel/tests/Feature/ServiceFormulaireTest.php`
- Modify: `app-laravel/routes/web.php`
- Modify: `app-laravel/lang/en.json`, `app-laravel/lang/fr/validation.php`, `app-laravel/lang/en/validation.php`

**Interfaces:**
- Consumes: `Service` (tâche 2), le traducteur `App\Services\Traduction\Traducteur` (lot 1)
- Produces: route nommée `admin.services.edition` sur `/admin/services/{service}/edition`

Le formulaire reprend le motif de `ArticleFormulaire` : onglets **Français** / **English** pour la langue du contenu, bouton FR/EN pour la langue de l'interface, remplissage automatique de la langue laissée vide.

- [ ] **Step 1 : Écrire le test qui échoue**

`app-laravel/tests/Feature/ServiceFormulaireTest.php` :

```php
<?php

use App\Livewire\Admin\ServiceFormulaire;
use App\Models\Categorie;
use App\Models\Service;
use App\Models\User;
use App\Services\Traduction\Traducteur;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');

    $this->service = Service::factory()->create([
        'categorie_id' => $this->categorie->id,
        'slug' => 'foncier',
        'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title',
    ]);
});

it('charge les valeurs existantes', function () {
    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->assertSet('nomFr', 'Foncier')
        ->assertSet('nomEn', 'Land & Title');
});

it('enregistre les modifications', function () {
    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->set('nomFr', 'Foncier et titres')
        ->set('accrocheFr', 'Sécuriser vos terrains')
        ->set('accrocheEn', 'Secure your land')
        ->set('descriptionFr', 'Texte long.')
        ->set('descriptionEn', 'Long text.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($this->service->fresh()->nom_fr)->toBe('Foncier et titres');
});

it('exige le nom dans les deux langues', function () {
    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->set('nomFr', '')
        ->call('enregistrer')
        ->assertHasErrors(['nomFr' => 'required']);
});

it('ouvre l onglet de la langue de l interface', function () {
    app()->setLocale('en');

    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->assertSet('langueActive', 'en');
});

it('remplit la langue vide par traduction, sans ecraser', function () {
    app()->bind(Traducteur::class, fn () => new class implements Traducteur
    {
        public function disponible(): bool
        {
            return true;
        }

        public function traduire(array $textes, string $vers, ?string $depuis = null): ?array
        {
            return array_map(fn ($t) => '['.$vers.'] '.$t, $textes);
        }
    });

    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->set('accrocheFr', 'Sécuriser vos terrains')
        ->set('accrocheEn', '')
        ->set('descriptionFr', 'Texte long.')
        ->set('descriptionEn', 'Texte anglais déjà écrit.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $frais = $this->service->fresh();

    expect($frais->accroche_en)->toBe('[en] Sécuriser vos terrains');
    expect($frais->description_en)->toBe('Texte anglais déjà écrit.');
});

it('interdit a un lecteur d ouvrir le formulaire', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    $this->actingAs($lecteur)
        ->get(route('admin.services.edition', $this->service))
        ->assertForbidden();
});
```

- [ ] **Step 2 : Lancer le test pour le voir échouer**

```bash
cd app-laravel && php artisan test --filter=ServiceFormulaireTest
```

Attendu : ÉCHEC — `App\Livewire\Admin\ServiceFormulaire` introuvable.

- [ ] **Step 3 : Écrire le composant**

`app-laravel/app/Livewire/Admin/ServiceFormulaire.php` :

```php
<?php

namespace App\Livewire\Admin;

use App\Models\Categorie;
use App\Models\Service;
use App\Services\Traduction\Traducteur;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * Edition d'un service.
 *
 * Deux mecanismes de langue se croisent, et ils sont independants : le bouton
 * FR/EN pilote l'INTERFACE, les onglets pilotent le CONTENU saisi. Leur seul
 * point de rencontre est l'etat initial. C'est le motif retenu au lot 1 pour
 * ArticleFormulaire.
 */
#[Layout('layouts.app')]
class ServiceFormulaire extends Component
{
    public Service $service;

    public string $nomFr = '';
    public string $nomEn = '';
    public string $accrocheFr = '';
    public string $accrocheEn = '';
    public string $descriptionFr = '';
    public string $descriptionEn = '';
    public string $atout1Fr = '';
    public string $atout1En = '';
    public string $atout2Fr = '';
    public string $atout2En = '';
    public string $atout3Fr = '';
    public string $atout3En = '';
    public string $libelleBoutonFr = '';
    public string $libelleBoutonEn = '';
    public string $categorieId = '';
    public bool $visible = true;

    /** Langue du contenu saisi — sans rapport avec celle de l'interface. */
    public string $langueActive = 'fr';

    public function mount(Service $service): void
    {
        $this->service = $service;
        $this->langueActive = app()->getLocale();

        $this->nomFr = $service->nom_fr;
        $this->nomEn = $service->nom_en;
        $this->accrocheFr = $service->accroche_fr;
        $this->accrocheEn = $service->accroche_en;
        $this->descriptionFr = $service->description_fr;
        $this->descriptionEn = $service->description_en;
        $this->atout1Fr = $service->atout1_fr ?? '';
        $this->atout1En = $service->atout1_en ?? '';
        $this->atout2Fr = $service->atout2_fr ?? '';
        $this->atout2En = $service->atout2_en ?? '';
        $this->atout3Fr = $service->atout3_fr ?? '';
        $this->atout3En = $service->atout3_en ?? '';
        $this->libelleBoutonFr = $service->libelle_bouton_fr ?? '';
        $this->libelleBoutonEn = $service->libelle_bouton_en ?? '';
        $this->categorieId = (string) $service->categorie_id;
        $this->visible = (bool) $service->visible;
    }

    protected function rules(): array
    {
        return [
            'nomFr' => ['required', 'string', 'max:190'],
            'nomEn' => ['required', 'string', 'max:190'],
            'accrocheFr' => ['required', 'string', 'max:255'],
            'accrocheEn' => ['required', 'string', 'max:255'],
            'descriptionFr' => ['required', 'string'],
            'descriptionEn' => ['required', 'string'],
            'atout1Fr' => ['nullable', 'string', 'max:190'],
            'atout1En' => ['nullable', 'string', 'max:190'],
            'atout2Fr' => ['nullable', 'string', 'max:190'],
            'atout2En' => ['nullable', 'string', 'max:190'],
            'atout3Fr' => ['nullable', 'string', 'max:190'],
            'atout3En' => ['nullable', 'string', 'max:190'],
            'libelleBoutonFr' => ['nullable', 'string', 'max:120'],
            'libelleBoutonEn' => ['nullable', 'string', 'max:120'],
            'categorieId' => ['required', 'exists:categories,id'],
        ];
    }

    public function enregistrer(): void
    {
        // Avant la validation : les champs remplis par traduction doivent
        // satisfaire les regles « required » comme s'ils avaient ete saisis.
        $this->remplirParTraductionCeQuiEstVide();

        $this->validate();

        $this->service->update([
            'nom_fr' => $this->nomFr, 'nom_en' => $this->nomEn,
            'accroche_fr' => $this->accrocheFr, 'accroche_en' => $this->accrocheEn,
            'description_fr' => $this->descriptionFr, 'description_en' => $this->descriptionEn,
            'atout1_fr' => $this->atout1Fr ?: null, 'atout1_en' => $this->atout1En ?: null,
            'atout2_fr' => $this->atout2Fr ?: null, 'atout2_en' => $this->atout2En ?: null,
            'atout3_fr' => $this->atout3Fr ?: null, 'atout3_en' => $this->atout3En ?: null,
            'libelle_bouton_fr' => $this->libelleBoutonFr ?: null,
            'libelle_bouton_en' => $this->libelleBoutonEn ?: null,
            'categorie_id' => $this->categorieId,
            'visible' => $this->visible,
        ]);

        session()->flash('message', __('Service enregistré.'));
        $this->redirectRoute('admin.services.liste');
    }

    /**
     * On ne traduit QUE ce qui est vide. Jamais d'ecrasement : le contenu
     * anglais du site est une traduction humaine, dont la recuperation a coute
     * une investigation entiere au lot 1.
     */
    protected function remplirParTraductionCeQuiEstVide(): void
    {
        $traducteur = app(Traducteur::class);

        if (! $traducteur->disponible()) {
            return;
        }

        foreach (['nom', 'accroche', 'description', 'atout1', 'atout2', 'atout3', 'libelleBouton'] as $champ) {
            $fr = $champ.'Fr';
            $en = $champ.'En';

            if (blank($this->$en) && filled($this->$fr)) {
                $this->$en = $this->traduireTexte($traducteur, $this->$fr, 'en', 'fr') ?? $this->$en;
            } elseif (blank($this->$fr) && filled($this->$en)) {
                $this->$fr = $this->traduireTexte($traducteur, $this->$en, 'fr', 'en') ?? $this->$fr;
            }
        }
    }

    /** Traduit en preservant les paragraphes. */
    protected function traduireTexte(Traducteur $traducteur, string $texte, string $vers, string $depuis): ?string
    {
        $paragraphes = preg_split('/\R{2,}/u', trim($texte)) ?: [];
        $traduits = $traducteur->traduire($paragraphes, $vers, $depuis);

        return $traduits === null ? null : implode("\n\n", $traduits);
    }

    public function render(): View
    {
        return view('livewire.admin.service-formulaire', [
            'categories' => Categorie::orderBy('ordre')->get(),
            'langue' => app()->getLocale(),
            'traductionActive' => app(Traducteur::class)->disponible(),
        ])->title(__('Modifier le service'));
    }
}
```

- [ ] **Step 4 : Écrire la vue**

`app-laravel/resources/views/livewire/admin/service-formulaire.blade.php` :

```blade
@php($champ = 'mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<form wire:submit="enregistrer" class="max-w-3xl space-y-6">

    <x-admin.entete-page
        :titre="__('Modifier le service')"
        :fil="[__('Accueil') => route('dashboard'), __('Services') => route('admin.services.liste'), $service->nom($langue) => null]">
        <x-slot:actions>
            <x-bascule-langue />
        </x-slot:actions>
    </x-admin.entete-page>

    @if ($traductionActive)
        <p class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-100">
            {{ __("Vous pouvez ne remplir qu'une langue : l'autre sera traduite à l'enregistrement. Un texte déjà saisi n'est jamais remplacé.") }}
        </p>
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
            <span class="text-sm font-medium">{{ __('Catégorie') }}</span>
            <select wire:model="categorieId" class="{{ $champ }}">
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->nom($langue) }}</option>
                @endforeach
            </select>
            @error('categorieId') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="flex items-center gap-2 self-end pb-2">
            <input type="checkbox" wire:model="visible" class="rounded border-zinc-300">
            <span class="text-sm font-medium">{{ __('Visible sur le site') }}</span>
        </label>
    </div>

    {{-- Onglets de la langue du CONTENU. « Français » et « English » restent
         ecrits dans leur propre langue : ce sont des endonymes qui designent la
         version que l'on redige, pas la langue de l'interface. --}}
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="flex gap-4" aria-label="{{ __('Langue du contenu') }}">
            @foreach (['fr' => 'Français', 'en' => 'English'] as $code => $intitule)
                <button type="button" wire:click="$set('langueActive', '{{ $code }}')"
                        @class([
                            'border-b-2 px-1 py-2 text-sm',
                            'border-zinc-900 font-medium dark:border-white' => $langueActive === $code,
                            'border-transparent text-zinc-600 dark:text-zinc-400' => $langueActive !== $code,
                        ])>{{ $intitule }}</button>
            @endforeach
        </nav>
    </div>

    {{-- Les deux blocs restent dans le DOM, masques par `hidden` : une erreur de
         validation sur la langue inactive est ainsi conservee, et visible des
         qu'on revient sur son onglet. --}}
    @foreach (['fr' => 'Fr', 'en' => 'En'] as $code => $suffixe)
        <div class="space-y-4" @if ($langueActive !== $code) hidden @endif>
            <label class="block">
                <span class="text-sm font-medium">{{ __('Nom') }}</span>
                <input type="text" wire:model="nom{{ $suffixe }}" class="{{ $champ }}">
                @error('nom'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">{{ __('Accroche') }}</span>
                <input type="text" wire:model="accroche{{ $suffixe }}" class="{{ $champ }}">
                <span class="mt-1 block text-xs text-zinc-600 dark:text-zinc-400">{{ __('Phrase courte affichée sur la tuile.') }}</span>
                @error('accroche'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">{{ __('Description') }}</span>
                <textarea wire:model="description{{ $suffixe }}" rows="8" class="{{ $champ }}"></textarea>
                @error('description'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>

            <fieldset class="space-y-2">
                <legend class="text-sm font-medium">{{ __('Atouts') }}</legend>
                <span class="block text-xs text-zinc-600 dark:text-zinc-400">{{ __('Trois au maximum, affichés sous le nom du service.') }}</span>
                @foreach ([1, 2, 3] as $n)
                    <input type="text" wire:model="atout{{ $n }}{{ $suffixe }}" class="{{ $champ }}"
                           aria-label="{{ __('Atout :rang', ['rang' => $n]) }}">
                    @error('atout'.$n.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                @endforeach
            </fieldset>

            <label class="block">
                <span class="text-sm font-medium">{{ __('Libellé du bouton') }}</span>
                <input type="text" wire:model="libelleBouton{{ $suffixe }}" class="{{ $champ }}">
                @error('libelleBouton'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>
        </div>
    @endforeach

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
            {{ __('Enregistrer') }}
        </button>
        <a href="{{ route('admin.services.liste') }}" class="text-sm text-zinc-600 hover:underline dark:text-zinc-400">
            {{ __('Annuler') }}
        </a>
    </div>
</form>
```

- [ ] **Step 5 : Déclarer la route**

Dans le groupe restreint aux administrateurs et éditeurs de `routes/web.php` :

```php
Route::get('/services/{service}/edition', \App\Livewire\Admin\ServiceFormulaire::class)->name('services.edition');
```

- [ ] **Step 5 bis : Relier la liste au formulaire**

La route existe désormais : `service-liste.blade.php` peut y mener. Remplacer le bloc du nom :

```blade
                <td class="px-4 py-3">
                    @if ($peutEcrire)
                        <a href="{{ route('admin.services.edition', $element) }}" wire:navigate
                           class="block font-medium text-zinc-900 hover:underline dark:text-white">
                            {{ $element->nom($langue) }}
                        </a>
                    @else
                        <span class="block font-medium text-zinc-900 dark:text-white">{{ $element->nom($langue) }}</span>
                    @endif
                    <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $element->accroche($langue) }}
                    </span>
                </td>
```

et la colonne Actions restée vide :

```blade
                <td class="whitespace-nowrap px-4 py-3">
                    <div class="flex items-center justify-end gap-1">
                        @if ($peutEcrire)
                            <a href="{{ route('admin.services.edition', $element) }}" wire:navigate
                               title="{{ __('Modifier') }}"
                               aria-label="{{ __('Modifier :nom', ['nom' => $element->nom($langue)]) }}"
                               class="rounded-md p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-zinc-800 dark:hover:text-white">
                                <x-admin.icone nom="crayon" />
                            </a>
                        @endif
                    </div>
                </td>
```

Ajouter `"Modifier :nom": "Edit :nom"` à `lang/en.json`.

- [ ] **Step 6 : Ajouter les clés de traduction**

Dans `en.json` : `"Modifier le service"`, `"Service enregistré."`, `"Nom"`, `"Accroche"`, `"Description"`, `"Atouts"`, `"Atout :rang"`, `"Libellé du bouton"`, `"Visible sur le site"`, `"Phrase courte affichée sur la tuile."`, `"Trois au maximum, affichés sous le nom du service."`.

Dans `lang/fr/validation.php` et `lang/en/validation.php`, section `attributes`, les noms lisibles : `nomFr`, `nomEn`, `accrocheFr`, `accrocheEn`, `descriptionFr`, `descriptionEn`, `categorieId`.

- [ ] **Step 7 : Lancer les tests**

```bash
cd app-laravel && php artisan test --filter='ServiceFormulaireTest|ClesDeTraductionTest'
```

Attendu : les 6 tests du formulaire et les 7 du garde-fou passent.

- [ ] **Step 8 : Commit**

```bash
git add -A
git commit -m "feat(admin): formulaire d'edition d'un service, bilingue"
```

---

### Task 9 : Écran et formulaire de la FAQ

**Files:**
- Create: `app-laravel/app/Livewire/Admin/FaqListe.php`
- Create: `app-laravel/app/Livewire/Admin/FaqFormulaire.php`
- Create: `app-laravel/resources/views/livewire/admin/faq-liste.blade.php`
- Create: `app-laravel/resources/views/livewire/admin/faq-formulaire.blade.php`
- Create: `app-laravel/tests/Feature/AdminFaqTest.php`
- Modify: `app-laravel/routes/web.php`, `sidebar.blade.php`, `lang/en.json`

**Interfaces:**
- Consumes: `ListeOrdonnable` (tâche 5), `QuestionFaq` (tâche 3), `Service` (tâche 2)
- Produces: routes `admin.faq.liste`, `admin.faq.creation`, `admin.faq.edition`

Contrairement aux services, la FAQ **accepte la création et la suppression** : ajouter une question ne touche pas la structure des pages.

- [ ] **Step 1 : Écrire le test qui échoue**

`app-laravel/tests/Feature/AdminFaqTest.php` :

```php
<?php

use App\Livewire\Admin\FaqFormulaire;
use App\Livewire\Admin\FaqListe;
use App\Models\Categorie;
use App\Models\QuestionFaq;
use App\Models\Service;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);
    $this->service = Service::factory()->create([
        'categorie_id' => $categorie->id, 'slug' => 'foncier', 'nom_fr' => 'Foncier',
    ]);

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');
});

it('liste les questions groupees par service', function () {
    QuestionFaq::factory()->create(['service_id' => $this->service->id, 'question_fr' => 'Une question ?']);

    Livewire::actingAs($this->editeur)
        ->test(FaqListe::class)
        ->assertSee('Une question ?')
        ->assertSee('Foncier');
});

it('cree une question', function () {
    Livewire::actingAs($this->editeur)
        ->test(FaqFormulaire::class)
        ->set('serviceId', (string) $this->service->id)
        ->set('questionFr', 'Nouvelle question ?')
        ->set('questionEn', 'New question?')
        ->set('reponseFr', 'La réponse.')
        ->set('reponseEn', 'The answer.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(QuestionFaq::where('question_fr', 'Nouvelle question ?')->exists())->toBeTrue();
});

it('exige un service', function () {
    Livewire::actingAs($this->editeur)
        ->test(FaqFormulaire::class)
        ->set('questionFr', 'Sans service ?')
        ->set('questionEn', 'Without service?')
        ->set('reponseFr', 'Réponse.')
        ->set('reponseEn', 'Answer.')
        ->call('enregistrer')
        ->assertHasErrors(['serviceId']);
});

it('modifie une question existante sans en creer une seconde', function () {
    $q = QuestionFaq::factory()->create(['service_id' => $this->service->id, 'question_fr' => 'Ancienne ?']);

    Livewire::actingAs($this->editeur)
        ->test(FaqFormulaire::class, ['question' => $q])
        ->set('questionFr', 'Nouvelle ?')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(QuestionFaq::count())->toBe(1);
    expect($q->fresh()->question_fr)->toBe('Nouvelle ?');
});

it('supprime une question', function () {
    $q = QuestionFaq::factory()->create(['service_id' => $this->service->id]);

    Livewire::actingAs($this->editeur)
        ->test(FaqListe::class)
        ->call('supprimer', $q->id);

    expect(QuestionFaq::find($q->id))->toBeNull();
});

it('interdit a un lecteur d ouvrir la creation', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    $this->actingAs($lecteur)->get(route('admin.faq.creation'))->assertForbidden();
});
```

- [ ] **Step 2 : Lancer le test pour le voir échouer**

```bash
cd app-laravel && php artisan test --filter=AdminFaqTest
```

Attendu : ÉCHEC — `App\Livewire\Admin\FaqListe` introuvable.

- [ ] **Step 3 : Écrire le composant de liste**

```php
<?php

namespace App\Livewire\Admin;

use App\Models\QuestionFaq;

class FaqListe extends ListeOrdonnable
{
    protected function modele(): string
    {
        return QuestionFaq::class;
    }

    protected function colonnesRecherchees(): array
    {
        return ['question_fr', 'question_en', 'reponse_fr', 'reponse_en'];
    }

    protected function vue(): string
    {
        return 'livewire.admin.faq-liste';
    }

    protected function titre(): string
    {
        return __('FAQ');
    }
}
```

- [ ] **Step 4 : Écrire la vue de liste**

Reprendre `service-liste.blade.php` en remplaçant :

- l'en-tête : titre `__('FAQ')`, fil `[__('Accueil') => route('dashboard'), __('Contenu') => null, __('FAQ') => null]` ;
- l'action : un bouton **Nouvelle question** vers `route('admin.faq.creation')`, visible sous `@hasanyrole('administrateur|editeur')`, sur le modèle du bouton « Nouvel article » de `article-liste.blade.php` ;
- les colonnes : `['', __('Question'), __('Service'), __('Statut'), __('Actions')]` ;
- la première cellule : `{{ $element->question($langue) }}` en lien vers `route('admin.faq.edition', $element)`, et sous le lien, en petit, `{{ Str::limit($element->reponse($langue), 90) }}` ;
- la colonne Service : `{{ $element->service->nom($langue) }}` ;
- les actions : le crayon d'édition, plus un bouton corbeille appelant `supprimer({{ $element->id }})` avec `wire:confirm`, sur le modèle exact de `article-liste.blade.php`.

- [ ] **Step 5 : Écrire le formulaire**

`app-laravel/app/Livewire/Admin/FaqFormulaire.php` :

```php
<?php

namespace App\Livewire\Admin;

use App\Models\QuestionFaq;
use App\Models\Service;
use App\Services\Traduction\Traducteur;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * Creation et edition d'une question de FAQ.
 *
 * Contrairement aux services, la creation est permise : ajouter une question
 * ne touche pas la structure des pages publiques.
 */
#[Layout('layouts.app')]
class FaqFormulaire extends Component
{
    public ?QuestionFaq $question = null;

    public string $serviceId = '';
    public string $questionFr = '';
    public string $questionEn = '';
    public string $reponseFr = '';
    public string $reponseEn = '';
    public bool $visible = true;

    /** Langue du contenu saisi — sans rapport avec celle de l'interface. */
    public string $langueActive = 'fr';

    public function mount(?QuestionFaq $question = null): void
    {
        $this->langueActive = app()->getLocale();

        if (! $question?->exists) {
            return;
        }

        $this->question = $question;
        $this->serviceId = (string) $question->service_id;
        $this->questionFr = $question->question_fr;
        $this->questionEn = $question->question_en;
        $this->reponseFr = $question->reponse_fr;
        $this->reponseEn = $question->reponse_en;
        $this->visible = (bool) $question->visible;
    }

    protected function rules(): array
    {
        return [
            'serviceId' => ['required', 'exists:services,id'],
            'questionFr' => ['required', 'string', 'max:500'],
            'questionEn' => ['required', 'string', 'max:500'],
            'reponseFr' => ['required', 'string'],
            'reponseEn' => ['required', 'string'],
        ];
    }

    public function enregistrer(): void
    {
        $this->remplirParTraductionCeQuiEstVide();

        $this->validate();

        $donnees = [
            'service_id' => $this->serviceId,
            'question_fr' => $this->questionFr, 'question_en' => $this->questionEn,
            'reponse_fr' => $this->reponseFr, 'reponse_en' => $this->reponseEn,
            'visible' => $this->visible,
        ];

        if ($this->question) {
            $this->question->update($donnees);
        } else {
            // Une question creee se range en fin de son groupe.
            $donnees['ordre'] = 1 + (int) QuestionFaq::where('service_id', $this->serviceId)->max('ordre');
            $this->question = QuestionFaq::create($donnees);
        }

        session()->flash('message', __('Question enregistrée.'));
        $this->redirectRoute('admin.faq.liste');
    }

    /** On ne traduit QUE ce qui est vide. Jamais d'ecrasement. */
    protected function remplirParTraductionCeQuiEstVide(): void
    {
        $traducteur = app(Traducteur::class);

        if (! $traducteur->disponible()) {
            return;
        }

        foreach (['question', 'reponse'] as $champ) {
            $fr = $champ.'Fr';
            $en = $champ.'En';

            if (blank($this->$en) && filled($this->$fr)) {
                $this->$en = $this->traduireTexte($traducteur, $this->$fr, 'en', 'fr') ?? $this->$en;
            } elseif (blank($this->$fr) && filled($this->$en)) {
                $this->$fr = $this->traduireTexte($traducteur, $this->$en, 'fr', 'en') ?? $this->$fr;
            }
        }
    }

    protected function traduireTexte(Traducteur $traducteur, string $texte, string $vers, string $depuis): ?string
    {
        $paragraphes = preg_split('/\R{2,}/u', trim($texte)) ?: [];
        $traduits = $traducteur->traduire($paragraphes, $vers, $depuis);

        return $traduits === null ? null : implode("\n\n", $traduits);
    }

    public function render(): View
    {
        return view('livewire.admin.faq-formulaire', [
            'services' => Service::ordonnees()->get(),
            'langue' => app()->getLocale(),
            'traductionActive' => app(Traducteur::class)->disponible(),
        ])->title($this->question ? __('Modifier la question') : __('Nouvelle question'));
    }
}
```

`app-laravel/resources/views/livewire/admin/faq-formulaire.blade.php` :

```blade
@php($champ = 'mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<form wire:submit="enregistrer" class="max-w-3xl space-y-6">

    <x-admin.entete-page
        :titre="$question ? __('Modifier la question') : __('Nouvelle question')"
        :fil="[__('Accueil') => route('dashboard'), __('FAQ') => route('admin.faq.liste'), ($question ? __('Modifier') : __('Nouvelle question')) => null]">
        <x-slot:actions>
            <x-bascule-langue />
        </x-slot:actions>
    </x-admin.entete-page>

    @if ($traductionActive)
        <p class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-100">
            {{ __("Vous pouvez ne remplir qu'une langue : l'autre sera traduite à l'enregistrement. Un texte déjà saisi n'est jamais remplacé.") }}
        </p>
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
            <span class="text-sm font-medium">{{ __('Service') }}</span>
            <select wire:model="serviceId" class="{{ $champ }}">
                <option value="">{{ __('Choisir…') }}</option>
                @foreach ($services as $s)
                    <option value="{{ $s->id }}">{{ $s->nom($langue) }}</option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs text-zinc-600 dark:text-zinc-400">
                {{ __("Le nom du service sert de titre de groupe sur la page FAQ.") }}
            </span>
            @error('serviceId') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="flex items-center gap-2 self-end pb-2">
            <input type="checkbox" wire:model="visible" class="rounded border-zinc-300">
            <span class="text-sm font-medium">{{ __('Visible sur le site') }}</span>
        </label>
    </div>

    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="flex gap-4" aria-label="{{ __('Langue du contenu') }}">
            @foreach (['fr' => 'Français', 'en' => 'English'] as $code => $intitule)
                <button type="button" wire:click="$set('langueActive', '{{ $code }}')"
                        @class([
                            'border-b-2 px-1 py-2 text-sm',
                            'border-zinc-900 font-medium dark:border-white' => $langueActive === $code,
                            'border-transparent text-zinc-600 dark:text-zinc-400' => $langueActive !== $code,
                        ])>{{ $intitule }}</button>
            @endforeach
        </nav>
    </div>

    @foreach (['fr' => 'Fr', 'en' => 'En'] as $code => $suffixe)
        <div class="space-y-4" @if ($langueActive !== $code) hidden @endif>
            <label class="block">
                <span class="text-sm font-medium">{{ __('Question') }}</span>
                <input type="text" wire:model="question{{ $suffixe }}" class="{{ $champ }}">
                @error('question'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">{{ __('Réponse') }}</span>
                <textarea wire:model="reponse{{ $suffixe }}" rows="8" class="{{ $champ }}"></textarea>
                @error('reponse'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>
        </div>
    @endforeach

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
            {{ __('Enregistrer') }}
        </button>
        <a href="{{ route('admin.faq.liste') }}" class="text-sm text-zinc-600 hover:underline dark:text-zinc-400">
            {{ __('Annuler') }}
        </a>
    </div>
</form>
```

- [ ] **Step 6 : Déclarer les routes et l'entrée de barre latérale**

```php
Route::get('/faq', \App\Livewire\Admin\FaqListe::class)->name('faq.liste');
```

et, dans le groupe restreint :

```php
Route::get('/faq/creation', \App\Livewire\Admin\FaqFormulaire::class)->name('faq.creation');
Route::get('/faq/{question}/edition', \App\Livewire\Admin\FaqFormulaire::class)->name('faq.edition');
```

Attention à l'ordre : `/faq/creation` avant `/faq/{question}/edition`.

- [ ] **Step 7 : Lancer les tests et commiter**

```bash
cd app-laravel && php artisan test --filter='AdminFaqTest|ClesDeTraductionTest'
git add -A
git commit -m "feat(admin): ecrans de la FAQ, groupes par service"
```

---

### Task 10 : Pages publiques services et FAQ

**Files:**
- Create: `app-laravel/app/Http/Controllers/PagePubliqueController.php`
- Create: `app-laravel/resources/views/public/services.blade.php`
- Create: `app-laravel/resources/views/public/faq.blade.php`
- Create: `app-laravel/tests/Feature/PagesPubliquesLot2Test.php`
- Modify: `app-laravel/routes/web.php`, `tools/sync-frontoffice.sh`

**Interfaces:**
- Consumes: `Service`, `QuestionFaq`
- Produces: routes nommées `services.index` sur `/services` et `faq.index` sur `/faq`

- [ ] **Step 1 : Écrire le test qui échoue**

`app-laravel/tests/Feature/PagesPubliquesLot2Test.php` :

```php
<?php

use App\Models\Categorie;
use App\Models\QuestionFaq;
use App\Models\Service;

beforeEach(function () {
    $categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);

    $this->service = Service::factory()->create([
        'categorie_id' => $categorie->id, 'slug' => 'foncier', 'ordre' => 1, 'visible' => true,
        'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title',
        'accroche_fr' => 'Sécuriser vos terrains', 'accroche_en' => 'Secure your land',
        'atout1_fr' => 'Vérification ACD', 'atout1_en' => 'ACD check',
    ]);
});

it('affiche les services publies', function () {
    $this->get('/services')->assertOk()->assertSee('Foncier')->assertSee('Sécuriser vos terrains');
});

it('ne montre pas un service masque', function () {
    Service::factory()->create(['nom_fr' => 'Service caché', 'visible' => false, 'categorie_id' => $this->service->categorie_id]);

    $this->get('/services')->assertOk()->assertDontSee('Service caché');
});

it('respecte l ordre d affichage', function () {
    Service::factory()->create(['nom_fr' => 'Deuxieme', 'ordre' => 2, 'categorie_id' => $this->service->categorie_id]);

    $corps = $this->get('/services')->assertOk()->getContent();

    expect(strpos($corps, 'Foncier'))->toBeLessThan(strpos($corps, 'Deuxieme'));
});

it('sert les services en anglais', function () {
    $this->get('/langue/en');

    $this->get('/services')->assertOk()->assertSee('Land &amp; Title', false)->assertSee('Secure your land');
});

it('affiche la FAQ groupee par service', function () {
    QuestionFaq::factory()->create([
        'service_id' => $this->service->id, 'ordre' => 1, 'visible' => true,
        'question_fr' => "Qu'est-ce qu'un ACD ?", 'reponse_fr' => 'Un arrêté officiel.',
    ]);

    $this->get('/faq')->assertOk()
        ->assertSee('Foncier')
        ->assertSee("Qu'est-ce qu'un ACD ?", false)
        ->assertSee('Un arrêté officiel.');
});

it('ne montre pas une question masquee', function () {
    QuestionFaq::factory()->create([
        'service_id' => $this->service->id, 'visible' => false, 'question_fr' => 'Question cachée ?',
    ]);

    $this->get('/faq')->assertOk()->assertDontSee('Question cachée ?', false);
});

it('redirige les anciennes adresses', function () {
    $this->get('/services.html')->assertRedirect('/services');
    $this->get('/faq.html')->assertRedirect('/faq');
});
```

- [ ] **Step 2 : Lancer le test pour le voir échouer**

```bash
cd app-laravel && php artisan test --filter=PagesPubliquesLot2Test
```

Attendu : ÉCHEC — 404, les routes n'existent pas.

- [ ] **Step 3 : Écrire le contrôleur**

`app-laravel/app/Http/Controllers/PagePubliqueController.php` :

```php
<?php

namespace App\Http\Controllers;

use App\Models\QuestionFaq;
use App\Models\Service;
use Illuminate\Contracts\View\View;

class PagePubliqueController extends Controller
{
    public function services(): View
    {
        $langue = app()->getLocale();

        return view('public.services', [
            'services' => Service::visibles()->ordonnees()->get(),
            'langue' => $langue,
            'noeudPage' => [
                '@type' => 'CollectionPage',
                '@id' => route('services.index').'#page',
                'url' => route('services.index'),
                'name' => __('Nos services').' — SCI4K',
                'inLanguage' => $langue,
                'isPartOf' => ['@id' => rtrim(url('/'), '/').'/#site'],
            ],
        ]);
    }

    public function faq(): View
    {
        $langue = app()->getLocale();

        // Groupees par service, dans l'ordre des services puis des questions :
        // sur le site, le titre de chaque groupe EST le nom du service.
        $groupes = QuestionFaq::visibles()
            ->with('service')
            ->get()
            ->sortBy(fn ($q) => [$q->service->ordre, $q->ordre])
            ->groupBy(fn ($q) => $q->service->id);

        return view('public.faq', [
            'groupes' => $groupes,
            'langue' => $langue,
            'noeudPage' => [
                '@type' => 'FAQPage',
                '@id' => route('faq.index').'#page',
                'url' => route('faq.index'),
                'inLanguage' => $langue,
                'mainEntity' => QuestionFaq::visibles()->get()->map(fn ($q) => [
                    '@type' => 'Question',
                    'name' => $q->question($langue),
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q->reponse($langue)],
                ])->all(),
            ],
        ]);
    }
}
```

Le nœud `FAQPage` n'est pas un ornement : c'est ce qui permet aux moteurs d'afficher les questions directement dans leurs résultats. La page statique ne l'avait pas.

- [ ] **Step 4 : Écrire les vues**

Les deux vues étendent `public.layout` (lot 1). Le balisage statique — bandeau, sections d'encadrement — est **copié tel quel** depuis `frontoffice/services.html` et `frontoffice/faq.html`, mêmes classes CSS. Seuls les blocs répétés deviennent des boucles.

Règle absolue : **ne pas conserver les attributs `data-i18n` sur un texte venant de la base.** `main.js` les réécrirait par-dessus le rendu serveur, faisant cohabiter deux mécanismes de traduction sur le même libellé — le défaut le plus probable de ces deux pages.

`app-laravel/resources/views/public/services.blade.php`, le cœur de la page :

```blade
@extends('public.layout')

@section('titre', __('Nos services'))
@section('description', __("Foncier, construction, gestion locative, achat, vente et administration de biens à Abidjan."))
@section('classe-page', 'page-services')

@section('contenu')

{{-- Bandeau : balisage copie de frontoffice/services.html, section
     .page-banner.pb-services. Ces textes ne viennent pas de la base et
     gardent donc leurs cles de traduction. --}}
<section class="page-banner pb-services">
  <div class="wrap">
    <div class="tag reveal">{{ __('Nos métiers') }}</div>
    <h1 class="reveal">{{ __('Nos services') }}</h1>
    <p class="reveal">{{ __("Six domaines d'expertise au service de vos projets immobiliers à Abidjan.") }}</p>
  </div>
</section>

<section class="services-detail">
  <div class="wrap">
    <div class="services-grid reveal-stagger">
      @foreach ($services as $service)
        <button type="button" class="service-tile reveal service-bg-{{ $service->slug }}"
                id="{{ $service->slug }}" data-svc="{{ $service->slug }}"
                aria-haspopup="dialog" aria-controls="svcModal">
          <span class="service-tile-veil"></span>
          <span class="service-tile-inner">
            @if ($service->icone_svg)
              <span class="service-icon-box">{!! $service->icone_svg !!}</span>
            @endif
            <span class="service-tile-title">{{ $service->nom($langue) }}</span>
            <span class="service-tile-tags">
              @foreach ($service->atouts($langue) as $atout)
                <span class="feature-tag">{{ $atout }}</span>
              @endforeach
            </span>
          </span>
        </button>
      @endforeach
    </div>
  </div>
</section>

{{-- Les descriptions alimentent la fenetre modale ouverte par les tuiles.
     Elles sont rendues cote serveur, masquees, et lues par main.js : la page
     statique les tirait du dictionnaire JavaScript. --}}
<div id="svcData" hidden>
  @foreach ($services as $service)
    <div data-svc-detail="{{ $service->slug }}">
      <h2>{{ $service->nom($langue) }}</h2>
      <p class="svc-short">{{ $service->accroche($langue) }}</p>
      <div class="svc-desc">
        @foreach (preg_split('/\R{2,}/u', trim($service->description($langue))) as $paragraphe)
          <p>{{ $paragraphe }}</p>
        @endforeach
      </div>
      <span class="svc-cta">{{ $service->libelleBouton($langue) }}</span>
    </div>
  @endforeach
</div>

{{-- Section « processus » : laissee telle quelle, copiee de
     frontoffice/services.html. Ses quatre etapes passent en base au plan 2b. --}}

@endsection
```

`app-laravel/resources/views/public/faq.blade.php` :

```blade
@extends('public.layout')

@section('titre', __('Questions fréquentes'))
@section('description', __("Vos questions sur le foncier, la construction, la gestion locative et l'achat immobilier à Abidjan."))
@section('classe-page', 'page-faq')

@section('contenu')

<section class="page-banner pb-faq">
  <div class="wrap">
    <div class="tag reveal">{{ __('Aide') }}</div>
    <h1 class="reveal">{{ __('Questions fréquentes') }}</h1>
    <p class="reveal">{{ __('Les réponses de nos conseillers aux questions qui reviennent le plus.') }}</p>
  </div>
</section>

<section class="faq-section">
  <div class="wrap">
    @foreach ($groupes as $questions)
      @php($service = $questions->first()->service)
      <div class="faq-group-title">{{ $service->nom($langue) }}</div>
      <div class="faq-list">
        @foreach ($questions as $question)
          <details class="faq-item reveal" @if ($loop->parent->first && $loop->first) open @endif>
            <summary>
              <span>{{ $question->question($langue) }}</span>
              <span class="plus">+</span>
            </summary>
            <div class="faq-answer">{{ $question->reponse($langue) }}</div>
          </details>
        @endforeach
      </div>
    @endforeach
  </div>
</section>

{{-- Section « poser une question » : copiee de frontoffice/faq.html,
     section .ask-section. Elle pointe le formulaire de contact, hors
     perimetre de ce lot. --}}

@endsection
```

- [ ] **Step 5 : Déclarer les routes et les redirections**

```php
Route::get('/services', [PagePubliqueController::class, 'services'])->name('services.index');
Route::get('/faq', [PagePubliqueController::class, 'faq'])->name('faq.index');

Route::permanentRedirect('/services.html', '/services');
Route::permanentRedirect('/faq.html', '/faq');
```

- [ ] **Step 6 : Exclure les pages statiques de la synchronisation**

Dans `tools/sync-frontoffice.sh`, ajouter `services.html` et `faq.html` au tableau `exclues`, aux côtés de `actualites.html` et `actualite-detail.html`. Sans cela, deux adresses serviraient deux versions divergentes de la même page.

- [ ] **Step 7 : Lancer les tests**

```bash
cd app-laravel && php artisan test --filter=PagesPubliquesLot2Test
```

Attendu : les 7 tests passent.

- [ ] **Step 8 : Commit**

```bash
git add -A
git commit -m "feat(public): sert les pages services et FAQ depuis la base"
```

---

### Task 11 : Vérification de bout en bout

**Files:**
- Aucun fichier modifié, sauf correctifs

- [ ] **Step 1 : Suite complète, sur les deux moteurs**

```bash
cd app-laravel && php artisan test
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=8889 DB_DATABASE=sci4k_test DB_USERNAME=root DB_PASSWORD=root php artisan test
```

Attendu : identique des deux côtés.

- [ ] **Step 2 : Contrôles de non-régression du site**

```bash
cd .. && python3 tools/verifier-site.py
```

Attendu : les 8 contrôles passent.

- [ ] **Step 3 : Vérifier à l'œil, sur un port neuf**

Le port doit être **neuf à chaque vérification visuelle** : le navigateur garde la feuille de style en cache par origine, et une correction CSS invisible a déjà coûté une fausse piste au lot 1.

```bash
cd app-laravel && npm run build && php artisan serve --port=8170
```

Contrôler :

- `/services` — six tuiles, dans l'ordre, avec leurs atouts ;
- `/faq` — douze questions en six groupes, les groupes portant les noms de services ;
- la bascule FR/EN sur les deux pages ;
- `/services.html` et `/faq.html` redirigent ;
- 375 pixels, thèmes clair et sombre ;
- `/admin/services` — glisser une ligne, recharger, l'ordre tient ;
- cycle complet : modifier l'accroche d'un service, la voir changer sur `/services`.

- [ ] **Step 4 : Mesurer les contrastes**

Sur chaque nouvel écran, en clair et en sombre, relever le contraste du texte discret et des pastilles. Peindre la couleur sur un canvas 1×1 et relire le pixel — Tailwind 4 exprime ses couleurs en `oklch()`, qu'une lecture directe interprète à tort comme du RGB. Remonter au premier ancêtre dont le fond n'est pas transparent : une cellule de tableau n'a pas de fond propre.

Attendu : aucun élément sous 4,5.

- [ ] **Step 5 : Commit final**

```bash
git add -A
git commit -m "chore: cloture du lot 2a, services et FAQ"
```

---

## Ce que ce plan ne fait pas

- **Équipe, valeurs, processus** et la page `presentation` — plan 2b.
- **Témoignages, partenaires, chiffres clés, encart, banderole, images de fond** et la page d'accueil — plan 2c.
- Le visuel de fond des tuiles de service : la colonne `image_source` existe et la vue l'affiche, mais aucun écran ne permet encore de le téléverser. Le motif est écrit dans `ArticleFormulaire` (lot 1) et sera repris au plan 2b, une fois que deux entités au moins en auront besoin.
