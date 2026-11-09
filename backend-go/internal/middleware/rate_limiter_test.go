package middleware_test

import (
	"net/http/httptest"
	"strconv"
	"testing"
	"time"

	"github.com/gin-gonic/gin"

	"spp-backend/internal/middleware"
)

func TestRateLimiterAllowsUpToLimitThenRejects(t *testing.T) {
	engine := gin.New()
	engine.Use(middleware.RateLimiter(2, time.Minute))
	engine.POST("/api/v1/public/login", func(c *gin.Context) {
		c.Status(200)
	})

	for attempt := 1; attempt <= 2; attempt++ {
		request := newRequest("POST", "/api/v1/public/login")
		request.RemoteAddr = "203.0.113.10:4321"

		recorder := performRequest(engine, request)
		if recorder.Code != 200 {
			t.Fatalf("percobaan %d status = %d, ingin 200", attempt, recorder.Code)
		}

		wantRemaining := strconv.Itoa(2 - attempt)
		if remaining := recorder.Header().Get("X-RateLimit-Remaining"); remaining != wantRemaining {
			t.Fatalf("percobaan %d remaining = %q, ingin %q", attempt, remaining, wantRemaining)
		}
	}

	request := newRequest("POST", "/api/v1/public/login")
	request.RemoteAddr = "203.0.113.10:4321"

	recorder := performRequest(engine, request)
	body := assertEnvelopeError(t, recorder, 429, "TOO_MANY_REQUESTS")

	if body.Message == "" {
		t.Fatalf("message 429 kosong")
	}

	if recorder.Header().Get("Retry-After") == "" {
		t.Fatalf("header Retry-After wajib diisi")
	}

	if recorder.Header().Get("X-RateLimit-Remaining") != "0" {
		t.Fatalf("remaining pada 429 = %q, ingin 0", recorder.Header().Get("X-RateLimit-Remaining"))
	}
}

func TestRateLimiterIsolatesClients(t *testing.T) {
	engine := gin.New()
	engine.Use(middleware.RateLimiter(1, time.Minute))
	engine.GET("/public/bukti/:kode/:token", func(c *gin.Context) {
		c.Status(200)
	})

	firstRequest := newRequest("GET", "/public/bukti/SPP-1/token-a")
	firstRequest.RemoteAddr = "198.51.100.1:1111"
	if recorder := performRequest(engine, firstRequest); recorder.Code != 200 {
		t.Fatalf("klien pertama status = %d, ingin 200", recorder.Code)
	}

	secondRequest := newRequest("GET", "/public/bukti/SPP-1/token-a")
	secondRequest.RemoteAddr = "198.51.100.2:2222"
	if recorder := performRequest(engine, secondRequest); recorder.Code != 200 {
		t.Fatalf("klien kedua status = %d, ingin 200", recorder.Code)
	}

	blockedRequest := newRequest("GET", "/public/bukti/SPP-1/token-a")
	blockedRequest.RemoteAddr = "198.51.100.1:3333"
	if recorder := performRequest(engine, blockedRequest); recorder.Code != 429 {
		t.Fatalf("klien pertama seharusnya dibatasi, status = %d", recorder.Code)
	}
}

func TestRateLimiterResetsAfterWindow(t *testing.T) {
	engine := gin.New()
	engine.Use(middleware.RateLimiter(1, 40*time.Millisecond))
	engine.GET("/login", func(c *gin.Context) {
		c.Status(200)
	})

	first := httptest.NewRequest("GET", "/login", nil)
	first.RemoteAddr = "192.0.2.55:1000"
	if recorder := performRequest(engine, first); recorder.Code != 200 {
		t.Fatalf("request pertama status = %d, ingin 200", recorder.Code)
	}

	second := httptest.NewRequest("GET", "/login", nil)
	second.RemoteAddr = "192.0.2.55:1001"
	if recorder := performRequest(engine, second); recorder.Code != 429 {
		t.Fatalf("request kedua status = %d, ingin 429", recorder.Code)
	}

	time.Sleep(70 * time.Millisecond)

	third := httptest.NewRequest("GET", "/login", nil)
	third.RemoteAddr = "192.0.2.55:1002"
	if recorder := performRequest(engine, third); recorder.Code != 200 {
		t.Fatalf("request setelah jendela status = %d, ingin 200", recorder.Code)
	}
}
