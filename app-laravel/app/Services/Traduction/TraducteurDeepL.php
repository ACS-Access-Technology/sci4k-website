<?php

namespace App\Services\Traduction;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Traduction par l'API DeepL.
 *
 * Aucune dependance ajoutee : l'API se resume a un appel HTTP, et le paquet
 * officiel n'apporterait qu'une enveloppe autour de ce que fait le client HTTP
 * de Laravel.
 *
 * Les cles de l'offre gratuite se terminent par « :fx » et visent un autre
 * domaine que celles de l'offre payante ; le suffixe est detecte plutot que
 * demande en configuration, pour qu'une cle collee dans .env fonctionne sans
 * autre reglage.
 */
class TraducteurDeepL implements Traducteur
{
    public function __construct(
        protected ?string $cle,
        protected int $secondesAvantAbandon = 15,
    ) {}

    public function disponible(): bool
    {
        return filled($this->cle);
    }

    public function traduire(array $textes, string $vers, ?string $depuis = null): ?array
    {
        if (! $this->disponible() || $textes === []) {
            return null;
        }

        try {
            $reponse = Http::asForm()
                ->timeout($this->secondesAvantAbandon)
                ->withHeaders(['Authorization' => 'DeepL-Auth-Key '.$this->cle])
                ->post($this->pointDAcces(), array_filter([
                    'text' => array_values($textes),
                    'target_lang' => strtoupper($vers === 'en' ? 'en-GB' : $vers),
                    'source_lang' => $depuis ? strtoupper($depuis) : null,
                    // Le contenu est du texte brut : sans cela DeepL echappe
                    // les caracteres qu'il prend pour des balises.
                    'tag_handling' => null,
                ]));
        } catch (\Throwable $e) {
            Log::warning('Traduction DeepL injoignable : '.$e->getMessage());

            return null;
        }

        if ($reponse->failed()) {
            Log::warning('Traduction DeepL refusee', [
                'statut' => $reponse->status(),
                'corps' => $reponse->body(),
            ]);

            return null;
        }

        $traductions = $reponse->json('translations');

        // Un decompte different signifierait un appariement decale entre textes
        // envoyes et recus : mieux vaut ne rien rendre qu'un titre a la place
        // d'un resume.
        if (! is_array($traductions) || count($traductions) !== count($textes)) {
            Log::warning('Traduction DeepL incomplete', ['attendus' => count($textes)]);

            return null;
        }

        return array_map(fn ($t) => (string) ($t['text'] ?? ''), $traductions);
    }

    protected function pointDAcces(): string
    {
        return str_ends_with((string) $this->cle, ':fx')
            ? 'https://api-free.deepl.com/v2/translate'
            : 'https://api.deepl.com/v2/translate';
    }
}
