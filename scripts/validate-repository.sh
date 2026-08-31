#!/usr/bin/env bash
# Validate a source checkout without contacting services or changing runtime state.
set -u

ROOT=$(CDPATH='' cd -- "$(dirname -- "$0")/.." && pwd)
errors=0

fail() { printf 'ERROR: %s\n' "$*" >&2; errors=$((errors + 1)); }

required=(AGENTS.md README.md .gitignore index.php manage.php includes/auth.php includes/auth_guard.php includes/state_store.php)
for file in "${required[@]}"; do
    [ -f "$ROOT/$file" ] || fail "required file missing: $file"
done

# Validate only tracked release files. Local runtime state may exist in a
# deployment checkout, but it must be removed from the Git index before release.
while IFS= read -r file; do
    case "$file" in
        *.php)
            if command -v php >/dev/null 2>&1; then
                php -l "$ROOT/$file" >/dev/null 2>&1 || fail "PHP syntax: $file"
            fi
            ;;
        *.sh)
            bash -n "$ROOT/$file" >/dev/null 2>&1 || fail "shell syntax: $file"
            ;;
    esac
done < <(cd "$ROOT" && git ls-files)

# These names must never be included in a release commit.
while IFS= read -r file; do
    base=${file##*/}
    case "$base" in
        .htpasswd|recovery.key|setup.mulai|auth.db|blacklist.db|blacklist.local.db|reload.lock|*.pending|*.new)
            fail "runtime/secret candidate tracked: $file"
            ;;
    esac
done < <(cd "$ROOT" && git ls-files)

# Reject obvious credential-bearing values in tracked text files. This is a
# release guard, not a replacement for a dedicated secret scanner.
if git -C "$ROOT" grep -I -n -E 'admin:\$apr1\$|BEGIN (RSA|OPENSSH|PRIVATE) KEY|password[[:space:]]*[:=]' -- ':!scripts/validate-repository.sh' ':!docs/*' >/dev/null 2>&1; then
    fail 'possible credential or private key found in tracked text'
fi

if [ "$errors" -ne 0 ]; then
    printf 'Repository validation failed with %d error(s).\n' "$errors" >&2
    exit 1
fi
printf 'Repository validation passed.\n'
