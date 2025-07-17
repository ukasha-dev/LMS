/temp folder
/application/controllers/config/database.php
/application/controllers/config/config.php

Make all the above folder writeable to www:data

sudo chown www-data:www-data application/config/config.php //give permission to www-data user
chmod 666 application/config/config.php //make it writeable
chmod 644 application/config/config.php //make it safe permission