#!/bin/bash

#   this script is used to re-run the application if you cannot register it as system service
#   copy this script and call this script from Cron Job

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
responseStatus=$(curl  -so /dev/null -w '%{http_code}' 127.0.0.1:$1)

if [[ $responseStatus -eq 200 ]]; then
  echo "Process is running."
else
  echo "Process is not running. Run it now..."
  php -S 127.0.0.1:$1 -t $DIR
fi

# call this script via cron job with command below:
