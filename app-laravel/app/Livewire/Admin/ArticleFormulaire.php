<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Models\Categorie;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * Formulaire de creation et d'edition d'un article.
 *
 * DEUX MECANISMES DE LANGUE SE CROISENT ICI, ET ILS SONT INDEPENDANTS :
 *
 *   - le bouton FR/EN de l'en-tete pilote la langue de l'INTERFACE, via
 *     app()->getLocale() ;
 *   - les onglets « Français » et « English » pilotent la langue du CONTENU
 *     saisi, via $langueActive.
 *
 * Leur seul point de rencontre est l'etat initial : l'onglet ouvert au
 * chargement suit la langue de l'interface, par simple confort. Ensuite ils ne
 * se parlent plus — on peut lire l'interface en anglais tout en redigeant la
 * version francaise. Les confondre est le defaut le plus probable de cet
 * ecran ; c'etait consigne au plan avant meme de l'ecrire.
 */
#[Layout('layouts.app')]
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

    /** Langue du contenu en cours de saisie — sans rapport avec celle de l'interface. */
    public string $langueActive = 'fr';

    public function mount(?Article $article = null): void
    {
        $this->langueActive = app()->getLocale();

        if (! $article?->exists) {
            $this->datePublication = now()->format('Y-m-d');

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
            // Le format du slug est impose : il devient une adresse publique, et
            // un espace ou un accent produirait un lien casse que rien ne
            // signalerait avant le premier clic depuis le site.
            'slug' => [
                'required', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('articles', 'slug')->ignore($this->article?->id),
            ],
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

    protected function messages(): array
    {
        return [
            'slug.regex' => __("L'identifiant d'adresse n'accepte que des minuscules, des chiffres et des traits d'union, par exemple : acd-securiser-terrain."),
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

        session()->flash('message', __('Article enregistré.'));
        $this->redirectRoute('admin.articles.liste');
    }

    public function render(): View
    {
        return view('livewire.admin.article-formulaire', [
            'categories' => Categorie::orderBy('ordre')->get(),
            'langue' => app()->getLocale(),
        ])->title($this->article ? __('Modifier') : __('Nouvel article'));
    }
}
