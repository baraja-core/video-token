# 🎬 Video Token

A lightweight PHP library for parsing and normalizing video tokens from YouTube and Vimeo URLs. Automatically extracts video identifiers from various URL formats and provides a unified interface for generating embed URLs and thumbnail images.

## ✨ Key Features

- **Automatic provider detection** - Identifies YouTube or Vimeo based on URL structure or token format
- **Multiple URL format support** - Handles standard URLs, short URLs, embed codes, and URL-encoded strings
- **Token validation** - Validates token format and length for each provider
- **Embed URL generation** - Creates ready-to-use iframe embed URLs
- **Thumbnail retrieval** - Fetches video thumbnail URLs (direct for YouTube, via API for Vimeo)
- **Zero dependencies** - Pure PHP implementation with no external dependencies
- **Strict typing** - Full PHP 8.0+ strict type declarations

## 🏗️ Architecture

The library consists of a single immutable value object `VideoToken` that encapsulates:

```
┌─────────────────────────────────────────────────────────────┐
│                       VideoToken                            │
├─────────────────────────────────────────────────────────────┤
│  Input: URL or Token + Provider                             │
│    │                                                        │
│    ▼                                                        │
│  ┌─────────────────────┐                                    │
│  │  URL Parser         │ ─── Detects domain & extracts path │
│  └─────────────────────┘                                    │
│    │                                                        │
│    ▼                                                        │
│  ┌─────────────────────┐                                    │
│  │  Provider Resolver  │ ─── YouTube: 11-char alphanumeric  │
│  │                     │ ─── Vimeo: 8-digit numeric         │
│  └─────────────────────┘                                    │
│    │                                                        │
│    ▼                                                        │
│  ┌─────────────────────┐                                    │
│  │  Token Validator    │ ─── Max 32 characters              │
│  └─────────────────────┘                                    │
│    │                                                        │
│    ▼                                                        │
│  Output: Validated token + provider                         │
│    • getToken()        → Raw video identifier               │
│    • getProvider()     → 'youtube' | 'vimeo'                │
│    • getUrl()          → Embed URL                          │
│    • getThumbnailUrl() → Thumbnail image URL                │
└─────────────────────────────────────────────────────────────┘
```

## 🎯 Supported Providers

### YouTube

The library recognizes YouTube videos from the following domains:
- `youtube.com`
- `youtu.be`
- `youtube-nocookie.com`
- `yt.be`

**Supported URL formats:**
- Standard watch URL: `https://www.youtube.com/watch?v=VIDEO_ID`
- Short URL: `https://youtu.be/VIDEO_ID`
- Embed URL: `https://www.youtube.com/embed/VIDEO_ID`
- Various legacy formats: `/v/`, `/vi/`, `/e/`, `ytscreeningroom?v=`
- URL-encoded formats: `watch%3Fv%3D`
- Playlist URLs with video: `watchv=VIDEO_ID&list=...`

YouTube tokens are 11 characters long, containing alphanumeric characters, hyphens, and underscores.

### Vimeo

The library recognizes Vimeo videos from:
- `vimeo.com`
- `player.vimeo.com`

Vimeo tokens are 8-digit numeric identifiers.

## 📦 Installation

It's best to use [Composer](https://getcomposer.org) for installation, and you can also find the package on
[Packagist](https://packagist.org/packages/baraja-core/video-token) and
[GitHub](https://github.com/baraja-core/video-token).

To install, simply use the command:

```shell
$ composer require baraja-core/video-token
```

**Requirements:**
- PHP 8.0 or higher

## 🚀 Basic Usage

### Creating a VideoToken from URL

```php
use Baraja\VideoToken\VideoToken;

// From a YouTube URL
$token = new VideoToken('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

echo $token->getToken();    // dQw4w9WgXcQ
echo $token->getProvider(); // youtube
```

### Creating a VideoToken with explicit provider

```php
use Baraja\VideoToken\VideoToken;

// When you already have the token and know the provider
$token = new VideoToken('dQw4w9WgXcQ', 'youtube');
```

### Getting the embed URL

```php
use Baraja\VideoToken\VideoToken;

$token = new VideoToken('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

echo $token->getUrl();
// YouTube: https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0
// Vimeo:   https://player.vimeo.com/video/12345678
```

### Getting the thumbnail URL

```php
use Baraja\VideoToken\VideoToken;

// YouTube - direct URL, no API call needed
$youtube = new VideoToken('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
echo $youtube->getThumbnailUrl();
// https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg

// Vimeo - fetches from Vimeo oEmbed API
$vimeo = new VideoToken('https://vimeo.com/12345678');
echo $vimeo->getThumbnailUrl();
// Returns thumbnail URL from Vimeo API response
```

### Working with Vimeo

```php
use Baraja\VideoToken\VideoToken;

$token = new VideoToken('https://vimeo.com/76979871');

echo $token->getToken();    // 76979871
echo $token->getProvider(); // vimeo
echo $token->getUrl();      // https://player.vimeo.com/video/76979871
```

## 🔍 Static URL Parser

For cases where you only need to extract a YouTube token without creating a full `VideoToken` object:

```php
use Baraja\VideoToken\VideoToken;

// Parse various YouTube URL formats
$token = VideoToken::parseYouTubeTokenByUrl('watch?v=dQw4w9WgXcQ');
// Returns: dQw4w9WgXcQ

$token = VideoToken::parseYouTubeTokenByUrl('embed/dQw4w9WgXcQ');
// Returns: dQw4w9WgXcQ

$token = VideoToken::parseYouTubeTokenByUrl('dQw4w9WgXcQ');
// Returns: dQw4w9WgXcQ (exact match)

// User channels return null (no video token)
$token = VideoToken::parseYouTubeTokenByUrl('user/SomeChannel');
// Returns: null
```

## ⚠️ Error Handling

The library throws `\InvalidArgumentException` in the following cases:

```php
use Baraja\VideoToken\VideoToken;

// Token too long (max 32 characters)
try {
    new VideoToken('this_token_is_way_too_long_to_be_valid_video_id');
} catch (\InvalidArgumentException $e) {
    // Video token "..." is too long.
}

// Invalid URL format
try {
    new VideoToken('https://invalid-video-site.com/video/123');
} catch (\InvalidArgumentException $e) {
    // Token or URL "..." is invalid.
}

// Missing provider when token format is ambiguous
try {
    new VideoToken('ambiguous-token');
} catch (\InvalidArgumentException $e) {
    // Provider for token "..." is mandatory.
}
```

The `getUrl()` method throws `\LogicException` if called with an unsupported provider (should not happen with normal usage).

## ⚡ Performance Considerations

### Thumbnail URL Caching

**Important:** The `getThumbnailUrl()` method for Vimeo videos makes an HTTP request to the Vimeo oEmbed API. Always cache the result:

```php
$token = new VideoToken('https://vimeo.com/76979871');

// Cache this result!
$thumbnailUrl = $cache->get('vimeo_thumb_' . $token->getToken(), function () use ($token) {
    return $token->getThumbnailUrl();
});
```

YouTube thumbnails are generated using a predictable URL pattern and don't require API calls.

### Immutability

The `VideoToken` object is immutable. Once created, the token and provider cannot be changed. This makes it safe to pass around and cache without defensive copying.

## 🔧 Provider Constants

The library provides constants for provider identification:

```php
use Baraja\VideoToken\VideoToken;

VideoToken::PROVIDER_YOUTUBE; // 'youtube'
VideoToken::PROVIDER_VIMEO;   // 'vimeo'

// Use in comparisons
if ($token->getProvider() === VideoToken::PROVIDER_YOUTUBE) {
    // YouTube-specific logic
}
```

## 🌐 Use Cases

### Embedding videos in HTML

```php
$token = new VideoToken($_POST['video_url']);

$html = sprintf(
    '<iframe src="%s" frameborder="0" allowfullscreen></iframe>',
    htmlspecialchars($token->getUrl(), ENT_QUOTES, 'UTF-8')
);
```

### Storing normalized video references

```php
// User submits various URL formats
$input = 'https://youtu.be/dQw4w9WgXcQ?t=42';

$token = new VideoToken($input);

// Store only what you need
$database->insert('videos', [
    'token' => $token->getToken(),      // dQw4w9WgXcQ
    'provider' => $token->getProvider(), // youtube
]);
```

### Video preview cards

```php
$token = new VideoToken($videoUrl);

echo '<div class="video-card">';
echo '  <img src="' . htmlspecialchars($token->getThumbnailUrl()) . '" alt="Video thumbnail">';
echo '  <a href="' . htmlspecialchars($token->getUrl()) . '">Watch video</a>';
echo '</div>';
```

## 👤 Author

**Jan Barášek**
Website: [https://baraja.cz](https://baraja.cz)

## 📄 License

`baraja-core/video-token` is licensed under the MIT license. See the [LICENSE](https://github.com/baraja-core/video-token/blob/master/LICENSE) file for more details.
