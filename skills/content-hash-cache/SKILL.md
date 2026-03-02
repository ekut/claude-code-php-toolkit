---
name: content-hash-cache
description: SHA-256 content-hash caching pattern for PHP — deterministic cache keys from file content, corruption handling, PSR-16 integration.
origin: claude-code-php-toolkit
---

# Content-Hash Cache Pattern

Use SHA-256 hashes of file content as cache keys to guarantee cache correctness. When file content changes, the hash changes, automatically invalidating stale entries. When content is identical, the cache always hits — regardless of filename, path, or timestamp.

## When to Activate

- Processing files that may be re-uploaded or re-processed (PDFs, images, CSVs)
- Building pipelines where the same input should always produce the same output
- Caching expensive computations (parsing, analysis, transformation) keyed on input content
- Avoiding stale cache bugs caused by path-based or timestamp-based keys

---

## 1. Core Pattern

```php
// Generate deterministic key from file content
$hash = hash_file('sha256', $filePath);

// Check cache
$cacheKey = "processed:{$hash}";
if ($cache->has($cacheKey)) {
    return $cache->get($cacheKey);
}

// Process and store
$result = $this->expensiveProcess($filePath);
$cache->set($cacheKey, $result);

return $result;
```

**Why SHA-256?**

- Deterministic: same content → same hash, always
- Collision-resistant: practically impossible for two different files to share a hash
- Fast: `hash_file()` streams the file, no memory issues with large files
- Built-in: no external dependencies needed

---

## 2. Cache Entry

```php
final readonly class CacheEntry
{
    public function __construct(
        public string $contentHash,
        public mixed $result,
        public \DateTimeImmutable $createdAt,
        public ?int $inputSizeBytes = null,
    ) {}

    public function toArray(): array
    {
        return [
            'content_hash' => $this->contentHash,
            'result' => $this->result,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'input_size_bytes' => $this->inputSizeBytes,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            contentHash: $data['content_hash'],
            result: $data['result'],
            createdAt: new \DateTimeImmutable($data['created_at']),
            inputSizeBytes: $data['input_size_bytes'] ?? null,
        );
    }
}
```

---

## 3. File-Based Storage

Simple file-based cache using `{hash}.json` — useful when Redis/Memcached is unavailable.

```php
final class FileHashCache
{
    public function __construct(
        private readonly string $cacheDir,
    ) {
        if (! is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0o755, true);
        }
    }

    public function get(string $hash): ?CacheEntry
    {
        $path = $this->path($hash);

        if (! file_exists($path)) {
            return null;
        }

        $json = file_get_contents($path);
        if ($json === false) {
            return null;
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            // Corrupted cache file — delete and return miss
            unlink($path);
            return null;
        }

        $entry = CacheEntry::fromArray($data);

        // Verify hash matches (corruption guard)
        if ($entry->contentHash !== $hash) {
            unlink($path);
            return null;
        }

        return $entry;
    }

    public function set(string $hash, CacheEntry $entry): void
    {
        $json = json_encode($entry->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

        // Atomic write — write to temp, then rename
        $tmpPath = $this->path($hash) . '.tmp';
        file_put_contents($tmpPath, $json, LOCK_EX);
        rename($tmpPath, $this->path($hash));
    }

    public function has(string $hash): bool
    {
        return file_exists($this->path($hash));
    }

    public function delete(string $hash): void
    {
        $path = $this->path($hash);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    private function path(string $hash): string
    {
        // Use first 2 chars as subdirectory to avoid too many files in one dir
        $subdir = substr($hash, 0, 2);
        $dir = $this->cacheDir . '/' . $subdir;

        if (! is_dir($dir)) {
            mkdir($dir, 0o755, true);
        }

        return $dir . '/' . $hash . '.json';
    }
}
```

---

## 4. Service Wrapper — CachedProcessor

Wraps any processing logic with content-hash caching. Follows SRP — the processor does processing, the wrapper does caching.

```php
interface FileProcessorInterface
{
    public function process(string $filePath): mixed;
}

final readonly class CachedProcessor implements FileProcessorInterface
{
    public function __construct(
        private FileProcessorInterface $inner,
        private FileHashCache $cache,
    ) {}

    public function process(string $filePath): mixed
    {
        $hash = hash_file('sha256', $filePath);

        if ($hash === false) {
            throw new \RuntimeException("Cannot hash file: {$filePath}");
        }

        $entry = $this->cache->get($hash);
        if ($entry !== null) {
            return $entry->result;
        }

        $result = $this->inner->process($filePath);

        $this->cache->set($hash, new CacheEntry(
            contentHash: $hash,
            result: $result,
            createdAt: new \DateTimeImmutable(),
            inputSizeBytes: filesize($filePath) ?: null,
        ));

        return $result;
    }
}

// Usage with DI
$processor = new CachedProcessor(
    inner: new PdfTextExtractor(),
    cache: new FileHashCache('/tmp/app-cache/pdf'),
);

$text = $processor->process('/uploads/invoice.pdf');
```

---

## 5. PSR-16 Alternative

Use `symfony/cache` or `phpfastcache/phpfastcache` for production-grade backends (Redis, Memcached, APCu).

```php
use Psr\SimpleCache\CacheInterface;

final readonly class Psr16CachedProcessor implements FileProcessorInterface
{
    public function __construct(
        private FileProcessorInterface $inner,
        private CacheInterface $cache,
        private int $ttl = 86400,  // 24 hours
    ) {}

    public function process(string $filePath): mixed
    {
        $hash = hash_file('sha256', $filePath);
        $key = 'content_hash_' . $hash;

        $cached = $this->cache->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $result = $this->inner->process($filePath);
        $this->cache->set($key, $result, $this->ttl);

        return $result;
    }
}
```

**Laravel integration:**

```php
// Use any Laravel cache store
$result = Cache::store('redis')->remember(
    'content_hash_' . hash_file('sha256', $path),
    now()->addDay(),
    fn () => $this->processor->process($path),
);
```

---

## 6. Decision Table

| Scenario | Use Content-Hash Cache? | Why |
|----------|------------------------|-----|
| PDF parsing, image thumbnails, CSV import | Yes | Same file = same result, processing is expensive |
| Database query results | No | Results depend on DB state, not file content |
| API responses | No | External data changes independently |
| Template rendering | Depends | Yes if template + data are both hashable; no if data is dynamic |
| Static asset pipelines (CSS/JS bundling) | Yes | Classic use case — content hash in filename for CDN busting |
| File deduplication | Yes | Hash identifies unique content regardless of filename |

---

## 7. Anti-Patterns

| Anti-Pattern | Problem | Fix |
|-------------|---------|-----|
| Path-based cache key (`md5($filePath)`) | Same file at different paths = cache miss; renamed file = stale cache | Use `hash_file('sha256', $path)` — key on content, not location |
| Timestamp-based key (`filemtime($path)`) | Clock skew, `touch` without change, copy with new mtime = wrong results | Content hash is the only reliable identity |
| Mixing cache logic into processor | Violates SRP, hard to test, hard to swap cache backend | Wrap with `CachedProcessor` decorator |
| No corruption handling | Truncated writes, disk errors → invalid cache entries served | Validate on read, atomic writes with `rename()` |
| Unbounded cache growth | Disk fills up over time | Set TTL, add periodic cleanup, or use eviction-capable backend (Redis LRU) |
| Using `md5_file()` | MD5 is not collision-resistant | Use `hash_file('sha256', ...)` — negligible performance difference |
