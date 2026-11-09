package middleware

import (
	"context"
	"crypto/sha256"
	"encoding/hex"
	"fmt"
	"log/slog"
	"net/http"
	"strings"
	"time"
	"unicode/utf8"

	"github.com/gin-gonic/gin"

	"spp-backend/internal/response"
)

const idempotencyHeader = "X-Idempotency-Key"

// IdempotencyLock mencegah request kasir yang sama diproses dua kali secara
// bersamaan. Kunci dilepas setelah handler selesai sehingga percobaan ulang
// setelah transaksi selesai dapat diproses kembali.
//
// Middleware ini hanya aktif untuk metode mutasi (POST/PUT/PATCH/DELETE).
func IdempotencyLock(store CacheStore, ttl time.Duration) gin.HandlerFunc {
	if ttl <= 0 {
		ttl = 30 * time.Second
	}

	return func(c *gin.Context) {
		if !isMutatingMethod(c.Request.Method) {
			c.Next()

			return
		}

		if store == nil {
			slog.Error("idempotency store belum dikonfigurasi", "path", c.Request.URL.Path)
			abortIdempotencyUnavailable(c)

			return
		}

		rawKey := strings.TrimSpace(c.GetHeader(idempotencyHeader))
		if rawKey == "" {
			response.Abort(
				c,
				http.StatusBadRequest,
				response.CodeIdempotencyKeyMissing,
				"Header X-Idempotency-Key wajib dikirim untuk transaksi ini.",
			)

			return
		}

		if !validIdempotencyKey(rawKey) {
			response.Abort(
				c,
				http.StatusBadRequest,
				response.CodeIdempotencyKeyInvalid,
				"Header X-Idempotency-Key tidak valid. Gunakan 1-128 karakter ASCII tanpa spasi.",
			)

			return
		}

		cacheKey := buildIdempotencyCacheKey(c, rawKey)
		acquired, err := store.SetNX(c.Request.Context(), cacheKey, time.Now().UTC().Format(time.RFC3339Nano), ttl)
		if err != nil {
			slog.Error(
				"gagal memperoleh kunci idempotensi",
				"error", err,
				"path", c.Request.URL.Path,
			)
			abortIdempotencyUnavailable(c)

			return
		}

		if !acquired {
			response.Abort(
				c,
				http.StatusConflict,
				response.CodeIdempotencyConflict,
				"Permintaan dengan kunci idempotensi yang sama sedang diproses. Mohon tunggu sebentar.",
			)

			return
		}

		defer releaseIdempotencyLock(store, cacheKey)

		c.Next()
	}
}

func releaseIdempotencyLock(store CacheStore, key string) {
	// Request context dapat sudah dibatalkan ketika klien memutus koneksi.
	// Pelepasan kunci tetap harus dijalankan agar tidak menunggu TTL.
	ctx, cancel := context.WithTimeout(context.Background(), 2*time.Second)
	defer cancel()

	if err := store.Delete(ctx, key); err != nil {
		slog.Warn("gagal melepas kunci idempotensi", "error", err, "key", key)
	}
}

func buildIdempotencyCacheKey(c *gin.Context, rawKey string) string {
	raw := fmt.Sprintf("%s|%s|%s", c.Request.Method, c.Request.URL.Path, rawKey)
	sum := sha256.Sum256([]byte(raw))

	return "spp:idempotency:" + hex.EncodeToString(sum[:])
}

func validIdempotencyKey(key string) bool {
	if !utf8.ValidString(key) || len(key) == 0 || len(key) > 128 {
		return false
	}

	for _, char := range key {
		if char < 33 || char > 126 {
			return false
		}
	}

	return true
}

func abortIdempotencyUnavailable(c *gin.Context) {
	response.Abort(
		c,
		http.StatusServiceUnavailable,
		response.CodeIdempotencyUnavailable,
		"Layanan penguncian transaksi sedang tidak tersedia. Silakan coba lagi.",
	)
}
