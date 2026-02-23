#!/bin/bash
composer run pre-dev
npx concurrently -c \
"#93c5fd,#fdba74,#f5f242,#86efac" \
"php artisan serve" \
"npm run dev" \
"php artisan schedule:work" \
"php artisan queue:listen" \
--names=""\
"          server           ,"\
"           vite            ,"\
"         schedule          ,"\
"          queue            "
