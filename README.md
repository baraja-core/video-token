Video token
===========

Parse video token from user string or URL.

How to use
----------

Detect token from URL:

```php
$token = new \Baraja\VideoToken\VideoToken(
    'https://www.youtube.com/watch?v=_Gi_LQ0a8EA'
);

echo $token->getToken(); // _Gi_LQ0a8EA
```
