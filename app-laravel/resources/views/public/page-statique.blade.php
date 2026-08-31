@extends('public.layout')

@section('titre', $page->titre($langue))
@section('description', Str::limit(strip_tags($page->contenu($langue)), 155))
@section('classe-page', 'page-statique')

@section('contenu')
<section class="page-banner"><div class="wrap"><h1 class="reveal">{{ $page->titre($langue) }}</h1></div></section>
<section class="properties-section"><div class="wrap prose max-w-4xl"><div class="whitespace-pre-line">{{ $page->contenu($langue) }}</div></div></section>
@endsection