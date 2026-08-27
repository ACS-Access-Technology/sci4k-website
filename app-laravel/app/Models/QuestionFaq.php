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
        'rubrique_id', 'ordre', 'visible',
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

    public function rubrique(): BelongsTo
    {
        return $this->belongsTo(RubriqueFaq::class, 'rubrique_id');
    }
}
