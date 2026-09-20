<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
{{-- Hanya jalur yang terbit dan belum diarsipkan. Mengirim mesin pencari ke halaman
     yang menjawab 404 memboroskan anggaran perayapan dan menurunkan kepercayaan
     terhadap sitemap berikutnya. --}}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/') }}</loc>
        <changefreq>weekly</changefreq>
    </url>
@foreach ($jalur as $t)
    <url>
        <loc>{{ route('public.trail', $t) }}</loc>
        <lastmod>{{ $t->updated_at->toAtomString() }}</lastmod>
        <changefreq>monthly</changefreq>
    </url>
@endforeach
</urlset>
