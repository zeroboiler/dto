#!/usr/bin/env bash
set -euo pipefail
ERRORS=0
echo "=== File existence ==="
for f in \
  /opt/data/zeroboiler/enums/tests/ProductionReadinessV9StructuralAuditTest.php \
  /opt/data/zeroboiler/dto/tests/ProductionReadinessV9StructuralAuditTest.php \
  /opt/data/zeroboiler/enums/composer.json \
  /opt/data/zeroboiler/dto/composer.json; do
  [ -f "$f" ] && echo "OK: $(basename $f) ($(wc -c < "$f")b)" || { echo "FAIL: $f"; ERRORS=$((ERRORS+1)); }
done
echo "=== PHP structural markers ==="
for f in \
  /opt/data/zeroboiler/enums/tests/ProductionReadinessV9StructuralAuditTest.php \
  /opt/data/zeroboiler/dto/tests/ProductionReadinessV9StructuralAuditTest.php; do
  for m in 'declare(strict_types=1)' 'class ProductionReadinessV9' 'public function test_' '\$this->assert'; do
    grep -qF "$m" "$f" || { echo "FAIL: $(basename $f) missing: $m"; ERRORS=$((ERRORS+1)); }
  done
  echo "OK: $(basename $f) — $(grep -c 'public function test_' "$f") tests"
done
echo "=== Versions ==="
echo "  enums: $(grep 'version' /opt/data/zeroboiler/enums/composer.json | head -1)"
echo "  dto:   $(grep 'version' /opt/data/zeroboiler/dto/composer.json | head -1)"
echo "=== Badge consistency ==="
for pkg in enums dto; do
  badge=$(grep -o 'Tests-[0-9]*' /opt/data/zeroboiler/$pkg/README.md | head -1)
  stats=$(grep -o '[0-9]* test files' /opt/data/zeroboiler/$pkg/README.md | head -1)
  n1=$(echo "$badge" | grep -o '[0-9]*'); n2=$(echo "$stats" | grep -o '[0-9]*')
  [ "$n1" = "$n2" ] && echo "OK: $pkg counts=$n1" || { echo "FAIL: $pkg badge=$n1 stats=$n2"; ERRORS=$((ERRORS+1)); }
done
echo "=== Git clean ==="
for pkg in enums dto; do
  s=$(cd /opt/data/zeroboiler/$pkg && git status --porcelain)
  [ -z "$s" ] && echo "OK: $pkg" || { echo "WARN: $pkg dirty (may be verify script itself)"; }
done
echo "=== Test file counts ==="
EC=$(ls /opt/data/zeroboiler/enums/tests/*.php | wc -l | tr -d ' ')
DC=$(ls /opt/data/zeroboiler/dto/tests/*.php | wc -l | tr -d ' ')
echo "  enums=$EC (expect 234) dto=$DC (expect 259)"
[ "$EC" -eq 234 ] || { echo "FAIL: enums count"; ERRORS=$((ERRORS+1)); }
[ "$DC" -eq 259 ] || { echo "FAIL: dto count"; ERRORS=$((ERRORS+1)); }
echo ""
[ "$ERRORS" -eq 0 ] && echo "ALL PASSED" || echo "FAILED: $ERRORS error(s)"
exit $ERRORS
