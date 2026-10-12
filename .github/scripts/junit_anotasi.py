"""Terbitkan kegagalan dan galat JUnit PHPUnit sebagai anotasi GitHub Actions.

Log job hanya terbaca admin repo, sedangkan anotasi terbaca siapa pun lewat API check-runs, jadi penyebab
kegagalan suite dapat diperiksa tanpa hak admin (R-024). Berkas yang tidak ada dilewati, karena suite sesudah
suite yang gagal tidak pernah berjalan. Bila tidak ada kegagalan tetapi ada test yang dilewati, test itu yang
dilaporkan: --fail-on-skipped membuat suite Spatial merah tanpa satu pun kegagalan. Paling banyak 10 anotasi
(batas GitHub per jenis per langkah); pesan dipotong 900 karakter.
"""

import os
import sys
import xml.etree.ElementTree as ET

BATAS = 10


def data(teks: str) -> str:
    return teks.replace('%', '%25').replace('\r', '%0D').replace('\n', '%0A')


def properti(teks: str) -> str:
    return data(teks).replace(':', '%3A').replace(',', '%2C')


def anotasi(judul: str, pesan: str) -> None:
    print(f'::error title={properti(judul)}::{data(pesan)}')


def main(paths: list[str]) -> int:
    ada = [path for path in paths if os.path.exists(path)]
    if not ada:
        anotasi('JUnit tidak ada', 'Kegagalan terjadi sebelum suite test berjalan; lihat langkah yang merah.')
        return 0

    jumlah = 0
    dilewati = []
    for path in ada:
        try:
            akar = ET.parse(path).getroot()
        except ET.ParseError as galat:
            anotasi(f'JUnit tidak terbaca: {path}', str(galat))
            jumlah += 1
            continue

        for kasus in akar.iter('testcase'):
            judul = f"{kasus.get('class') or kasus.get('classname')}::{kasus.get('name')}"
            if kasus.find('skipped') is not None:
                dilewati.append(judul)
            for jenis in ('error', 'failure'):
                for simpul in kasus.findall(jenis):
                    if jumlah >= BATAS:
                        return 0
                    anotasi(judul, (simpul.text or simpul.get('message') or '').strip()[:900])
                    jumlah += 1

    if jumlah == 0 and dilewati:
        anotasi(f'{len(dilewati)} test dilewati', '\n'.join(dilewati[:20]))

    return 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1:] or ['spatial-junit.xml']))
