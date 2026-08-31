# GitHub release checklist

## Before staging

- [ ] Review `git status`; preserve unrelated user changes.
- [ ] Confirm no `.htpasswd`, `recovery.key`, auth database, private owner/contact data, SNMP community, blocklist database, pending port, log, or generated metric is being staged.
- [ ] Confirm all bundled binaries, patches, fonts, images, and blocklist sources are licensed for redistribution.
- [ ] Ensure runtime state is represented by empty/default templates, not production values.
- [ ] Read the installer as root code and verify every external command/path.

## Automated checks

```sh
sh scripts/validate-repository.sh
php tests_port_config.php
php -l path/to/changed.php
bash -n path/to/changed.sh
 git diff --check
```

## Clean-host smoke test

- [ ] Install on a clean Debian VM.
- [ ] Confirm idempotent re-run does not overwrite existing config or state.
- [ ] Confirm first-login password setup and session invalidation.
- [ ] Confirm HTTPS and the configured panel port.
- [ ] Confirm dashboard, stats, request list, DNS inspector, and activity APIs.
- [ ] Confirm save → flag → maintenance reload behavior.
- [ ] Confirm Unbound `checkconf`, normal resolution, and test blocklist behavior.
- [ ] Confirm update failure leaves the previous blocklist/configuration usable.

## GitHub delivery

- [ ] Compare the target repository before pushing.
- [ ] Work on a dedicated non-default branch.
- [ ] Stage only the reviewed source/docs/templates and installer artifacts.
- [ ] Inspect `git diff --cached` and `git diff --cached --name-status`.
- [ ] Run a secret scan against staged content.
- [ ] Push only after the owner confirms the staged file set.
- [ ] Never force-push or rewrite remote history as part of packaging.
