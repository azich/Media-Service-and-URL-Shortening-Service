*INSTALLATION*
  -Move htaccess to .htaccess or add it to httpd.conf
  -Copy PHP files to HTTP root or other HTTP directory
  -Load tiny.sql into a MySQL database
  -Setup configuration file (config.php)
    -USE_CATPCHA: Require a CAPTCHA challenge/response when using pin
    -VERIFY_OAUTH: Require valid Twitter OAuth Echo credentials for media uploads
    -CAPTCHA_PUB: CAPTCHA public key
    -CAPTCHA_PRIV: CAPTCHA private key
    -ROOT: The path the short URLs begin at including trailing slash
    -PINS: An array that maps pins to API keys
    -API_KEYS: An array that maps API keys to an array of services
      *The API keys should be long and random for security but also URL-safe

*USING SERVICES*
  -Visit the URL for the short.php file to shorten URLs and upload media from a browser
  -Load the following URLs into the Twitter for iPhone Services Settings
    -Image Service: $ROOT/tiny.php?api_key=<API key>
    -URL Shortening: $ROOT/tiny.php?api_key=<API key>&url=%@
