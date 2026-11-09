package middleware

import (
	"math"
	"strconv"
	"sync"
	"time"

	"github.com/gin-gonic/gin"

	"spp-backend/internal/response"
)

// RateLimiter membatasi jumlah request per IP per rute dalam jendela waktu
// tetap (fixed window). Middleware ini cocok untuk proteksi brute-force login
// dan pemindaian kuitansi publik.
//
// Catatan deployment: limiter ini menyimpan hitungan di memori proses. Untuk
// deployment multi-instance, gunakan instance per proses di belakang load
// balancer dengan sticky session atau ganti dengan limiter berbasis Redis.
func RateLimiter(limit int, window time.Duration) gin.HandlerFunc {
	if window <= 0 {
		window = time.Minute
	}

	limiter := &rateLimiter{
		limit:   limit,
		window:  window,
		buckets: make(map[string]*rateBucket),
		now:     time.Now,
	}

	return limiter.handle
}

type rateBucket struct {
	count   int
	resetAt time.Time
}

type rateLimiter struct {
	mu      sync.Mutex
	limit   int
	window  time.Duration
	buckets map[string]*rateBucket
	now     func() time.Time
}

func (l *rateLimiter) handle(c *gin.Context) {
	if l.limit <= 0 {
		c.Next()

		return
	}

	key := l.bucketKey(c)
	now := l.now()

	l.mu.Lock()
	l.pruneLocked(now)

	bucket, exists := l.buckets[key]
	if !exists || !now.Before(bucket.resetAt) {
		bucket = &rateBucket{resetAt: now.Add(l.window)}
		l.buckets[key] = bucket
	}

	if bucket.count >= l.limit {
		retryAfter := int(math.Ceil(bucket.resetAt.Sub(now).Seconds()))
		if retryAfter < 1 {
			retryAfter = 1
		}

		c.Header("Retry-After", strconv.Itoa(retryAfter))
		c.Header("X-RateLimit-Limit", strconv.Itoa(l.limit))
		c.Header("X-RateLimit-Remaining", "0")
		c.Header("X-RateLimit-Reset", strconv.FormatInt(bucket.resetAt.Unix(), 10))
		l.mu.Unlock()

		response.Abort(
			c,
			429,
			response.CodeTooManyRequests,
			"Terlalu banyak permintaan. Silakan coba lagi setelah beberapa saat.",
		)

		return
	}

	bucket.count++

	remaining := l.limit - bucket.count
	c.Header("X-RateLimit-Limit", strconv.Itoa(l.limit))
	c.Header("X-RateLimit-Remaining", strconv.Itoa(remaining))
	c.Header("X-RateLimit-Reset", strconv.FormatInt(bucket.resetAt.Unix(), 10))
	l.mu.Unlock()

	c.Next()
}

func (l *rateLimiter) bucketKey(c *gin.Context) string {
	path := c.FullPath()
	if path == "" {
		path = c.Request.URL.Path
	}

	return c.ClientIP() + "|" + c.Request.Method + "|" + path
}

func (l *rateLimiter) pruneLocked(now time.Time) {
	if len(l.buckets) < 1024 {
		return
	}

	for key, bucket := range l.buckets {
		if !now.Before(bucket.resetAt) {
			delete(l.buckets, key)
		}
	}
}
