<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/') }}</loc>
        <changefreq>hourly</changefreq>
        <priority>1.0</priority>
    </url>
@foreach($items as $item)
    <url>
        <loc>{{ $item['loc'] }}</loc>
        @if(!empty($item['lastmod']))
        <lastmod>{{ $item['lastmod'] }}</lastmod>
        @endif
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
@endforeach
</urlset>

