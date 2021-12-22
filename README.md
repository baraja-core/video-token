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

// or

$token = new \Baraja\VideoToken\VideoToken('_Gi_LQ0a8EA', 'youtube');

echo $token->getToken(); // _Gi_LQ0a8EA
echo $token->getProvider(); // youtube
echo $token->getThumbnailUrl(); // https://img.youtube.com/vi/_Gi_LQ0a8EA/maxresdefault.jpg
echo $token->getUrl(); // https://www.youtube.com/embed/_Gi_LQ0a8EA?rel=0
```

The input can be a URL from one of the supported video platforms, or a `token` and `provider`. The validity of the passed token will be automatically verified.

The operation of getting a preview image can be performance intensive, so always cache the result of the call.
