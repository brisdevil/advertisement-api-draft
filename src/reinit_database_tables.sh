#!/bin/sh
php -r 'include "/var/www/html/database/migrations/CreateInitialTables.php"; (new CreateInitialTables())->reinit(); (new CreateInitialTables())->forPhpUnit()->reinit();'