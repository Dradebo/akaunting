#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

BASE=${BASE:-http://127.0.0.1:8000}

# Start server if missing
if ! pgrep -f "php artisan serve" >/dev/null 2>&1; then
  echo "Starting artisan serve..."
  nohup php artisan serve --host=127.0.0.1 --port=8000 > /tmp/artisan-serve.log 2>&1 &
  sleep 1
fi

echo "ARTISAN process:"
pgrep -af "php artisan serve" || true

echo
echo "=== PING ==="
curl -s -D - "$BASE/api/ping" | sed -n '1,120p' || true

# Register mobile user
PHONE="+2567$(date +%s | tail -c 6)"
PIN="1234"

echo
echo "=== REGISTER ($PHONE) ==="
# Try registration up to 3 times and extract token robustly
REG=''
TOKEN=''
for attempt in 1 2 3; do
    REG=$(curl -s -X POST "$BASE/api/mobile/register" -H "Content-Type: application/json" -d '{"phone":"'"$PHONE"'","name":"Smoke Runner","pin":"'"$PIN"'"}')
    echo "$REG"

    # try sed extraction first
    TOKEN=$(echo "$REG" | sed -nE 's/.*"token"[[:space:]]*:[[:space:]]*"([^"]+)".*/\1/p' || true)

    # fallback: python json parse
    if [ -z "$TOKEN" ]; then
        TOKEN=$(python3 - <<'PY'
import sys, json
try:
        o=json.loads(sys.stdin.read())
        print(o.get('token',''))
except Exception:
        print('')
PY
        <<<"$REG")
    fi

    if [ -n "$TOKEN" ]; then
        break
    fi

    echo "Register attempt $attempt failed to return a token, retrying..."
    sleep 1
done

if [ -z "$TOKEN" ]; then
    echo
    echo "ERROR: no token returned after retries. Paste the REGISTER responses above and run again."
    exit 1
fi

echo
echo "Token (truncated): ${TOKEN:0:24}..."

# UUID for client_id
CID=$(python3 - <<'PY'
import uuid
print(uuid.uuid4())
PY
)
echo "client_id: $CID"

# CREATE
echo
echo "=== SYNC CREATE ==="
CREATE_PAY=$(cat <<JSON
{"device_id":"smoke-runner","records":[{"client_id":"$CID","op":"create","payload":{"type":"income","amount_minor":12345,"currency":"UGX","date":"2025-09-02T12:00:00Z","notes":"smoke create"},"client_updated_at":"2025-09-02T12:00:00Z"}]}
JSON
)
CREATE_RESP=$(curl -s -X POST "$BASE/api/mobile/sync" -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d "$CREATE_PAY")
echo "$CREATE_RESP"

# Parse server_version if present for update
SERVER_VER=$(python3 - <<'PY'
import sys, json
try:
    o=json.loads(sys.stdin.read())
    a=o.get('applied',[])
    if a and isinstance(a,list):
        print(a[0].get('server_version','') or '')
    else:
        print('')
except Exception:
    print('')
PY
<<<"$CREATE_RESP")

# UPDATE
echo
echo "=== SYNC UPDATE ==="
UPDATE_PAY=$(cat <<JSON
{"device_id":"smoke-runner","records":[{"client_id":"$CID","op":"update","payload":{"type":"income","amount_minor":20000,"currency":"UGX","date":"2025-09-02T13:00:00Z","notes":"smoke update","server_version":${SERVER_VER:-1}},"client_updated_at":"2025-09-02T13:00:00Z"}]}
JSON
)
UPDATE_RESP=$(curl -s -X POST "$BASE/api/mobile/sync" -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d "$UPDATE_PAY")
echo "$UPDATE_RESP"

# DELETE
echo
echo "=== SYNC DELETE ==="
DELETE_PAY=$(cat <<JSON
{"device_id":"smoke-runner","records":[{"client_id":"$CID","op":"delete","payload":{},"client_updated_at":"2025-09-02T14:00:00Z"}]}
JSON
)
DELETE_RESP=$(curl -s -X POST "$BASE/api/mobile/sync" -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d "$DELETE_PAY")
echo "$DELETE_RESP"

echo
echo "=== LOGS (tail) ==="
echo "-- artisan serve log --"
tail -n 80 /tmp/artisan-serve.log || true
echo "-- mobile-client dev log --"
tail -n 80 /tmp/mobile-client-dev.log || true

echo
echo "SMOKE TEST COMPLETE"
