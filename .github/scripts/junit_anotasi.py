"""Terbitkan kegagalan dan galat JUnit PHPUnit sebagai anotasi GitHub Actions.

Log job hanya terbaca admin repo, sedangkan anotasi terbaca siapa pun lewat API check-runs, jadi penyebab
kegagalan suite PostgreSQL dapat diperiksa tanpa kredensial (R-024). Paling banyak 10 anotasi (batas GitHub
per jenis per langkah); pesan dipotong 900 karakter.
"""

import sys
import xml.etree.ElementTree as ET


def data(teks: str) -> str:
    return teks.replace('%', '%25').replace('\r', '%0D').replace('\n', '%0A')


def properti(teks: str) -> str:
    return data(teks).replace(':', '%3A').replace(',', '%2C')


def main(path: str) -> int:
    try:
        akar = ET.parse(path).getroot()
    except (OSError, ET.ParseError) as galat:
        print(f'::error title={properti("JUnit tidak terbaca")}::{data(str(galat))}')
        return 0

    jumlah = 0
    for kasus in akar.iter('testcase'):
        for jenis in ('error', 'failure'):
            for simpul in kasus.findall(jenis):
                if jumlah >= 10:
                    return 0
                judul = f"{kasus.get('class') or kasus.get('classname')}::{kasus.get('name')}"
                pesan = (simpul.text or simpul.get('message') or '').strip()[:900]
                print(f'::error title={properti(judul)}::{data(pesan)}')
                jumlah += 1

    return 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1] if len(sys.argv) > 1 else 'spatial-junit.xml'))
