<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Une adresse inscrite a la lettre d'information.
 *
 * Rien d'autre n'est conserve : ni nom, ni page d'origine, ni date de visite.
 * Le visiteur n'a saisi qu'une adresse, et c'est tout ce qu'on lui a demande.
 */
class AbonneNewsletter extends Model
{
    use HasFactory;

    protected $table = 'abonnes_newsletter';

    protected $fillable = ['email'];

    protected $casts = ['desinscrit_a' => 'datetime'];

    /** Les adresses qui recevront la prochaine lettre. */
    public function scopeActifs(Builder $requete): Builder
    {
        return $requete->whereNull('desinscrit_a');
    }

    public function estDesinscrit(): bool
    {
        return $this->desinscrit_a !== null;
    }

    /**
     * L'adresse a laquelle cet abonne peut se retirer lui-meme.
     *
     * Le jeton, et non l'adresse e-mail : une adresse dans un lien se retrouve
     * dans les journaux du serveur, dans l'historique du navigateur et chez
     * tout intermediaire — et un lien construit sur l'adresse permettrait de
     * desinscrire n'importe qui en la devinant.
     */
    public function lienDeDesinscription(): string
    {
        return route('newsletter.desinscription', $this->jeton);
    }

    /**
     * Le jeton est pose a la creation, jamais laisse au hasard d'un appelant.
     *
     * Un abonne sans jeton serait un abonne qui ne peut pas partir, et rien
     * dans le formulaire public ne le signalerait.
     */
    protected static function booted(): void
    {
        static::creating(function (self $abonne) {
            $abonne->jeton ??= Str::random(48);
        });
    }

    /**
     * Inscrit une adresse, ou la reinscrit si elle etait partie.
     *
     * Rend toujours le meme resultat vu du visiteur, qu'il s'agisse d'une
     * premiere inscription ou d'une adresse deja connue : repondre « vous etes
     * deja inscrit » dirait a un inconnu quelles adresses figurent dans la
     * liste.
     */
    public static function inscrire(string $email): self
    {
        $abonne = static::firstOrNew(['email' => mb_strtolower(trim($email))]);

        $abonne->desinscrit_a = null;
        $abonne->save();

        return $abonne;
    }
}
