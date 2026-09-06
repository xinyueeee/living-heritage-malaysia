
#!/bin/bash

while [ true ]
do
    echo "Running Laravel scheduler..."
    php artisan schedule:run --verbose --no-interaction
    sleep 60
done