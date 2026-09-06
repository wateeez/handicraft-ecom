# cPanel Git Deployment

This project can be deployed with cPanel's **Git Version Control** feature. The
repository includes a top-level `.cpanel.yml` file that runs
`deploy-cpanel.sh` after cPanel deploys a commit.

## cPanel setup

1. In **Files > Git Version Control**, clone this repository. Use a path outside
   `public_html`, for example:

   ```text
   /home/CPANEL_USER/repositories/handicraft-ecom
   ```

2. Set the domain's document root to the repository's `public` directory:

   ```text
   /home/CPANEL_USER/repositories/handicraft-ecom/public
   ```

   If the hosting plan cannot use a document root outside `public_html`, ask the
   host to configure the domain to point to this `public` directory. Do not copy
   the whole Laravel repository into a public web directory.

3. Select the required PHP version (8.2 or newer) and enable the PHP extensions
   required by Laravel and this project: `ctype`, `curl`, `fileinfo`, `mbstring`,
   `openssl`, `pdo_mysql`, `tokenizer`, and `xml`.

4. Create the production database and database user in cPanel. Create the
   repository's `.env` file on the server; it must not be committed to Git.
   Start from `.env.example` and set at least:

   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://example.com
   APP_KEY=base64:GENERATE_THIS_WITH_ARTISAN
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=CPANEL_DATABASE
   DB_USERNAME=CPANEL_DATABASE_USER
   DB_PASSWORD=DATABASE_PASSWORD
   ```

   Generate the key once from the repository directory if `APP_KEY` is empty:

   ```bash
   php artisan key:generate --force
   ```

5. Ensure Composer, Node.js/npm, and the PHP CLI are available to cPanel's Git
   deployment shell. The deployment hook runs `composer install`, `npm ci`, and
   `npm run build`.

6. Click **Manage > Deploy HEAD Commit** for the first deployment. Later changes
   can be deployed manually from cPanel, or automatically by pushing to the
   cPanel-managed repository's configured remote.

## Important production notes

- Do not commit `.env`, `vendor/`, `node_modules/`, or `public/build/`.
- The deployment hook runs migrations with `--force` but does not seed demo
  users or passwords.
- Uploaded files are stored under `storage/app/public`; the hook creates the
  `public/storage` link.
- cPanel's PHP process must be able to write to `storage/` and `bootstrap/cache/`.
  Set those permissions in cPanel's File Manager or ask the host to do so; the
  deployment hook intentionally does not run `chown www-data`.
- Configure queue workers and scheduled tasks separately in cPanel. For the
  database queue, add a cron job such as:

  ```cron
  * * * * * cd /home/CPANEL_USER/repositories/handicraft-ecom && php artisan schedule:run >/dev/null 2>&1
  ```

  A persistent queue worker generally requires the host's supervisor or queue
  worker feature.