#!/bin/sh
# Baut ZIP + plattenkiste.json im Repo-Root. Aufruf (aus beliebigem Ordner):
#   sh release/make_release.sh "Änderungstext als HTML"
set -e
ROOT=$(cd "$(dirname "$0")/.." && pwd)
P=$ROOT/plugin/dj-kolja-one-plattenkiste
V=$(sed -n "s/^ \* Version: *//p" $P/dj-kolja-one-plattenkiste.php | tr -d ' \r')
grep -q "define( 'KJO_VERSION', '$V' )" $P/dj-kolja-one-plattenkiste.php || { echo "KJO_VERSION passt nicht zu $V"; exit 1; }
cd "$ROOT" && rm -f dj-kolja-one-plattenkiste-*.zip
(cd "$ROOT/plugin" && zip -qr "$ROOT/dj-kolja-one-plattenkiste-$V.zip" dj-kolja-one-plattenkiste)
S=$(sha256sum dj-kolja-one-plattenkiste-$V.zip | cut -d' ' -f1)
python3 - "$V" "$S" "$1" "$ROOT" <<'PY'
import json,sys
v,s,c,root=sys.argv[1:5]
json.dump({"version":v,"download_url":f"dj-kolja-one-plattenkiste-{v}.zip","sha256":s,"requires":"6.0","tested":"6.8","requires_php":"7.4","changelog":c},open(root+"/plattenkiste.json","w"),ensure_ascii=False,indent=1)
PY
echo "Release $V fertig"; ls -la "$ROOT"/*.zip "$ROOT/plattenkiste.json"
