# Runtime templates

Files in this directory are safe starting points for mutable panel state. Installation should copy them only when the corresponding runtime file does not already exist.

Do not place credentials, production IP addresses, private contact information, blocklists, logs, or pending reload flags here. Runtime files belong outside Git and are covered by the repository `.gitignore`.

Recommended ownership after installation:

- source files: `root:root`, mode `0644` (shell helpers `0755`)
- panel state: `www-data:www-data`, mode `0664`
- Unbound-generated files: `unbound:unbound`, mode `0644`
- authentication database: outside the webroot, `/var/lib/trustng-auth/auth.db`
