# leanig.nl

The LEANIG website. The site itself lives in `site/`.

## Publishing to Strato

1. In GitHub, open **Settings > Secrets and variables > Actions** and add two secrets:
   - `STRATO_SFTP_USER`: your Strato SFTP username
   - `STRATO_SFTP_PASSWORD`: your Strato SFTP password
2. Optional variables (same page, **Variables** tab): `STRATO_SFTP_HOST` (default `ssh.strato.de`) and `STRATO_REMOTE_DIR` (default `/`).
3. Open **Actions > Deploy to Strato > Run workflow**, type `DEPLOY` and start it.

Each run first downloads a backup of what is on the server and keeps it with the run for 90 days, then uploads `site/`. Existing files on the server are not deleted.
