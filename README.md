# Electricity Billing

Record customers' monthly electricity usage and produce bills.
Laravel 13 · Inertia.js 3 · Vue 3 · Tailwind CSS 4.

| Requirement | Where |
| --- | --- |
| a) Save the form to the database | **Add record** (`/records/create`) |
| b) Delete a record | **Delete** on each row in **Records**, or on the edit page |
| c) Update a record | **Edit** on each row (`/records/{id}/edit`) |
| d) Display records, search by building type / state, order by building type or usability (ASC/DESC) | **Records** (`/records`) – use the filters, the *Order by* list, or click the column headers |
| e) Bill report by month and building type | **Bill report** (`/report`) |

## Tariff

| Building type | Block | Rate |
| --- | --- | --- |
| Residential | 1 – 200 kWh | RM 0.45/kWh |
| Residential | above 200 kWh | RM 0.97/kWh |
| Commercial | 1 – 200 kWh | RM 0.89/kWh |
| Commercial | above 200 kWh | RM 1.13/kWh |

Example: commercial, 210 kWh = 200 × 0.89 + 10 × 1.13 = **RM 189.30**.

Rates live in `config/tariff.php` (stored in sen, so there are no rounding errors).
The calculation is in `app/Services/TariffCalculator.php`. Change the config and every
bill and the report update automatically; the form's live estimate uses the same values.

## Project layout

```
app/Enums/                      BuildingType, Month, State (13 states + 3 federal territories)
app/Services/TariffCalculator   Block tariff calculation
app/Http/Controllers/           ElectricityRecordController (CRUD + list), BillReportController
app/Http/Requests/              Form validation, list/report filters
config/tariff.php               Rates
resources/js/Pages/             Records/Index, Create, Edit · Report/Index
resources/js/Components/        RecordForm, SortHeader, Pagination, TierBar, BuildingTag
tests/Unit, tests/Feature       PHPUnit
tests/js                        Vitest (Vue components and JS helpers)
```

## Run locally

Requires PHP 8.3+, Composer 2, Node 20+.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed      # seeds JOHN, AHMAD and MARY from the brief
npm install
npm run dev                     # in one terminal
php artisan serve               # in another → http://localhost:8000
```

## Tests

```bash
php artisan test   # 77 PHP tests: calculator, CRUD, validation, search, sorting, report
npm test           # 38 Vitest tests: form, estimate, sorting headers, pagination, helpers
```

A GitHub Actions workflow (`.github/workflows/tests.yml`) runs both on every push.

## Deploy to your server

Server needs: PHP 8.3+ with `mbstring, xml, ctype, curl, pdo_mysql` (or `pdo_sqlite`),
Composer, Node 20+ (only to build assets; you can also build locally and upload `public/build`),
and a web server (Nginx or Apache).

1. **Upload the code** (git clone or copy) to e.g. `/var/www/electricity-billing`.

2. **Environment**

   ```bash
   cp .env.example .env
   ```

   Edit `.env`:

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=electricity_billing
   DB_USERNAME=your_user
   DB_PASSWORD=your_password
   ```

   (Create the MySQL database and user first. SQLite also works: keep `DB_CONNECTION=sqlite`
   and `touch database/database.sqlite`.)

3. **Install and build**

   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --force
   php artisan db:seed --force          # optional: sample records
   npm ci && npm run build
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

4. **Permissions** – the web server user must be able to write to `storage` and `bootstrap/cache`:

   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```

5. **Point the web server at `public/`**. Nginx example:

   ```nginx
   server {
       listen 80;
       server_name your-domain.com;
       root /var/www/electricity-billing/public;
       index index.php;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location ~ \.php$ {
           fastcgi_pass unix:/run/php/php8.3-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

   Then add HTTPS, e.g. `sudo certbot --nginx -d your-domain.com`.

**Updating later:** pull the new code, then run
`composer install --no-dev -o && php artisan migrate --force && npm ci && npm run build && php artisan optimize`.

> The app has no login. If the server is public, put it behind authentication
> (e.g. Laravel's starter-kit auth, or HTTP basic auth in Nginx) before going live.
