import maplibregl from 'maplibre-gl';

/*
 * MapLibre di-bundle lewat Vite, bukan dimuat dari CDN saat runtime.
 *
 * Hike Mode dipakai di lapangan tempat sinyal sering tipis; menunggu unduhan
 * ratusan kilobita dari host pihak ketiga persis pada saat itu adalah kegagalan
 * yang dapat dihindari. Yang di-bundle juga tidak ikut hilang ketika CDN-nya
 * bermasalah.
 */
window.maplibregl = maplibregl;
