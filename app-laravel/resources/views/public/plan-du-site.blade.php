{{-- Aucune ligne blanche avant la declaration XML : un octet de plus et les
     moteurs refusent le document. --}}
{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($entrees as $entree)
  <url>
    <loc>{{ $entree['url'] }}</loc>
    <lastmod>{{ $entree['date'] }}</lastmod>
    <changefreq>{{ $entree['frequence'] }}</changefreq>
    <priority>{{ $entree['priorite'] }}</priority>
  </url>
@endforeach
</urlset>
