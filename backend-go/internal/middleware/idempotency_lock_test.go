package middleware_test

import (
	"context"
	"errors"
	"net/http/httptest"
	"strings"
	"sync"
	"testing"
	"time"

	"github.com/gin-gonic/gin"

	"spp-backend/internal/cache"
	"spp-backend/internal/middleware"
)

func TestIdempotencyLockRejectsConcurrentDuplicate(t *testing.T) {
	store := cache.NewMemoryStore()
	started := make(chan struct{})
	release := make(chan struct{})
	var once sync.Once

	engine := gin.New()
	engine.POST("/api/v1/kasir/pembayaran", middleware.IdempotencyLock(store, time.Minute), func(c *gin.Context) {
		once.Do(func() { close(started) })
		<-release
		c.Status(200)
	})

	firstResponse := httptest.NewRecorder()
	firstRequest := newRequest("POST", "/api/v1/kasir/pembayaran")
	firstRequest.Header.Set("X-Idempotency-Key", "kuitansi-2026-0001")

	done := make(chan struct{})
	go func() {
		engine.ServeHTTP(firstResponse, firstRequest)
		close(done)
	}()

	<-started

	secondRequest := newRequest("POST", "/api/v1/kasir/pembayaran")
	secondRequest.Header.Set("X-Idempotency-Key", "kuitansi-2026-0001")
	secondResponse := performRequest(engine, secondRequest)
	assertEnvelopeError(t, secondResponse, 409, "IDEMPOTENCY_CONFLICT")

	close(release)
	<-done

	if firstResponse.Code != 200 {
		t.Fatalf("request pertama status = %d, ingin 200 (body=%s)", firstResponse.Code, firstResponse.Body.String())
	}

	if store.Len() != 0 {
		t.Fatalf("kunci idempotensi tidak dilepas setelah transaksi selesai")
	}

	thirdRequest := newRequest("POST", "/api/v1/kasir/pembayaran")
	thirdRequest.Header.Set("X-Idempotency-Key", "kuitansi-2026-0001")
	thirdResponse := performRequest(engine, thirdRequest)

	if thirdResponse.Code != 200 {
		t.Fatalf("request ulang setelah selesai status = %d, ingin 200", thirdResponse.Code)
	}
}

func TestIdempotencyLockRequiresHeader(t *testing.T) {
	engine := gin.New()
	engine.POST("/kasir", middleware.IdempotencyLock(cache.NewMemoryStore(), time.Minute), func(c *gin.Context) {
		c.Status(200)
	})

	recorder := performRequest(engine, newRequest("POST", "/kasir"))
	assertEnvelopeError(t, recorder, 400, "IDEMPOTENCY_KEY_REQUIRED")
}

func TestIdempotencyLockRejectsInvalidKeys(t *testing.T) {
	tests := []struct {
		name string
		key  string
	}{
		{name: "mengandung spasi", key: "kunci tidak valid"},
		{name: "mengandung karakter kontrol", key: "kunci\tbaru"},
		{name: "terlalu panjang", key: strings.Repeat("a", 129)},
	}

	for _, test := range tests {
		t.Run(test.name, func(t *testing.T) {
			engine := gin.New()
			engine.POST("/kasir", middleware.IdempotencyLock(cache.NewMemoryStore(), time.Minute), func(c *gin.Context) {
				c.Status(200)
			})

			request := newRequest("POST", "/kasir")
			request.Header.Set("X-Idempotency-Key", test.key)

			recorder := performRequest(engine, request)
			assertEnvelopeError(t, recorder, 400, "INVALID_IDEMPOTENCY_KEY")
		})
	}
}

func TestIdempotencyLockSkipsReadRequests(t *testing.T) {
	engine := gin.New()
	engine.Use(middleware.IdempotencyLock(cache.NewMemoryStore(), time.Minute))
	engine.GET("/kasir/pembayaran/1", func(c *gin.Context) {
		c.Status(200)
	})

	recorder := performRequest(engine, newRequest("GET", "/kasir/pembayaran/1"))

	if recorder.Code != 200 {
		t.Fatalf("status = %d, ingin 200", recorder.Code)
	}
}

func TestIdempotencyLockFailsClosedWhenStoreErrors(t *testing.T) {
	engine := gin.New()
	engine.POST("/kasir", middleware.IdempotencyLock(failingCacheStore{}, time.Minute), func(c *gin.Context) {
		c.Status(200)
	})

	request := newRequest("POST", "/kasir")
	request.Header.Set("X-Idempotency-Key", "kunci-valid-123")

	recorder := performRequest(engine, request)
	assertEnvelopeError(t, recorder, 503, "IDEMPOTENCY_UNAVAILABLE")
}

type failingCacheStore struct{}

func (failingCacheStore) SetNX(context.Context, string, string, time.Duration) (bool, error) {
	return false, errors.New("redis tidak tersedia")
}

func (failingCacheStore) Delete(context.Context, string) error {
	return nil
}
