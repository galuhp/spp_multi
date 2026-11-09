package router_test

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/gin-gonic/gin"

	"spp-backend/internal/auth"
	"spp-backend/internal/cache"
	"spp-backend/internal/config"
	"spp-backend/internal/license"
	"spp-backend/internal/router"
)

const routerJWTSecret = "rahasia-router-yang-cukup-panjang-2026-abcdef"

func init() {
	gin.SetMode(gin.TestMode)
}

func TestPublicLoginIsAccessibleWithoutAuth(t *testing.T) {
	engine := newRouter(t, "basic")

	recorder := perform(engine, httptest.NewRequest("POST", "/api/v1/public/login", nil), "", "")

	if recorder.Code != 200 {
		t.Fatalf("status = %d, ingin 200 (body=%s)", recorder.Code, recorder.Body.String())
	}
}

func TestPublicLoginRateLimitApplies(t *testing.T) {
	engine := newRouter(t, "basic")

	for attempt := 1; attempt <= 5; attempt++ {
		recorder := perform(engine, httptest.NewRequest("POST", "/api/v1/public/login", nil), "", "")
		if recorder.Code != 200 {
			t.Fatalf("percobaan %d status = %d, ingin 200", attempt, recorder.Code)
		}
	}

	recorder := perform(engine, httptest.NewRequest("POST", "/api/v1/public/login", nil), "", "")
	if recorder.Code != 429 {
		t.Fatalf("percobaan ke-6 status = %d, ingin 429", recorder.Code)
	}
}

func TestSiswaGroupRequiresSiswaRole(t *testing.T) {
	engine := newRouter(t, "basic")

	unauthenticated := perform(engine, httptest.NewRequest("GET", "/api/v1/siswa/dashboard", nil), "", "")
	assertError(t, unauthenticated, 401, "UNAUTHENTICATED")

	siswaToken := tokenFor(t, "siswa")
	if recorder := perform(engine, httptest.NewRequest("GET", "/api/v1/siswa/dashboard", nil), siswaToken, ""); recorder.Code != 200 {
		t.Fatalf("siswa status = %d, ingin 200", recorder.Code)
	}

	petugasToken := tokenFor(t, "petugas")
	assertError(t, perform(engine, httptest.NewRequest("GET", "/api/v1/siswa/dashboard", nil), petugasToken, ""), 403, "FORBIDDEN")
}

func TestWaliKelasGroupRequiresWaliRole(t *testing.T) {
	engine := newRouter(t, "basic")

	waliToken := tokenFor(t, "wali_kelas")
	if recorder := perform(engine, httptest.NewRequest("GET", "/api/v1/wali/tunggakan", nil), waliToken, ""); recorder.Code != 200 {
		t.Fatalf("wali status = %d, ingin 200", recorder.Code)
	}

	siswaToken := tokenFor(t, "siswa")
	assertError(t, perform(engine, httptest.NewRequest("GET", "/api/v1/wali/tunggakan", nil), siswaToken, ""), 403, "FORBIDDEN")
}

func TestKasirGroupRequiresRoleAndIdempotencyKey(t *testing.T) {
	engine := newRouter(t, "basic")
	petugasToken := tokenFor(t, "petugas")

	request := httptest.NewRequest("POST", "/api/v1/kasir/pembayaran", nil)
	assertError(t, performRequest(engine, request, petugasToken, ""), 400, "IDEMPOTENCY_KEY_REQUIRED")

	withKey := httptest.NewRequest("POST", "/api/v1/kasir/pembayaran", nil)
	if recorder := performRequest(engine, withKey, petugasToken, "kunci-kasir-001"); recorder.Code != 200 {
		t.Fatalf("kasir status = %d, ingin 200 (body=%s)", recorder.Code, recorder.Body.String())
	}

	getRequest := httptest.NewRequest("GET", "/api/v1/kasir/pembayaran/2026-0001", nil)
	if recorder := performRequest(engine, getRequest, petugasToken, ""); recorder.Code != 200 {
		t.Fatalf("GET kasir tanpa idempotency key status = %d, ingin 200", recorder.Code)
	}

	siswaToken := tokenFor(t, "siswa")
	blocked := httptest.NewRequest("POST", "/api/v1/kasir/pembayaran", nil)
	assertError(t, performRequest(engine, blocked, siswaToken, "kunci-kasir-002"), 403, "FORBIDDEN")
}

func TestAdminGroupRequiresAdminRole(t *testing.T) {
	engine := newRouter(t, "basic")

	petugasToken := tokenFor(t, "petugas")
	assertError(t, perform(engine, httptest.NewRequest("GET", "/api/v1/admin/users", nil), petugasToken, ""), 403, "FORBIDDEN")

	adminToken := tokenFor(t, "admin")
	if recorder := perform(engine, httptest.NewRequest("GET", "/api/v1/admin/users", nil), adminToken, ""); recorder.Code != 200 {
		t.Fatalf("admin status = %d, ingin 200", recorder.Code)
	}

	tarifRequest := httptest.NewRequest("POST", "/api/v1/admin/tarif", nil)
	if recorder := perform(engine, tarifRequest, adminToken, ""); recorder.Code != 200 {
		t.Fatalf("admin tarif tanpa idempotency key status = %d, ingin 200", recorder.Code)
	}
}

func TestProGroupRequiresProTier(t *testing.T) {
	basicEngine := newRouter(t, "basic")
	adminToken := tokenFor(t, "admin")

	assertError(
		t,
		perform(basicEngine, httptest.NewRequest("GET", "/api/v1/pro/rekap-lanjutan", nil), adminToken, ""),
		403,
		"TIER_UPGRADE_REQUIRED",
	)

	proEngine := newRouter(t, "pro")
	if recorder := perform(proEngine, httptest.NewRequest("GET", "/api/v1/pro/rekap-lanjutan", nil), adminToken, ""); recorder.Code != 200 {
		t.Fatalf("pro status = %d, ingin 200 (body=%s)", recorder.Code, recorder.Body.String())
	}
}

func TestUnknownRouteReturnsJSON404(t *testing.T) {
	engine := newRouter(t, "basic")

	recorder := perform(engine, httptest.NewRequest("GET", "/api/v1/tidak-ada", nil), "", "")
	assertError(t, recorder, 404, "NOT_FOUND")
}

func newRouter(t *testing.T, tier string) *gin.Engine {
	t.Helper()

	return router.New(router.Deps{
		JWTSecret:       routerJWTSecret,
		Cache:           cache.NewMemoryStore(),
		License:         license.NewStaticChecker(tier, config.DefaultFeatures),
		Handlers:        testHandlers(),
		RateLimitWindow: time.Minute,
		IdempotencyTTL:  time.Minute,
	})
}

func testHandlers() router.Handlers {
	ok := func(c *gin.Context) {
		c.JSON(200, gin.H{"success": true, "message": "ok", "data": gin.H{"route": c.FullPath()}})
	}

	return router.Handlers{
		Login:            ok,
		PublicReceipt:    ok,
		StudentDashboard: ok,
		StudentCard:      ok,
		StudentReceipt:   ok,
		WaliTunggakan:    ok,
		WaliRekap:        ok,
		CreatePayment:    ok,
		GetPayment:       ok,
		CreateExpense:    ok,
		VoidPayment:      ok,
		AdminUsers:       ok,
		AdminTariffs:     ok,
		ProReport:        ok,
		ProExport:        ok,
	}
}

func tokenFor(t *testing.T, role string) string {
	t.Helper()

	manager := auth.NewTokenManager(routerJWTSecret, time.Hour)

	token, _, err := manager.Issue(1, "pengguna.uji", role, nil)
	if err != nil {
		t.Fatalf("gagal membuat token: %v", err)
	}

	return token
}

func perform(engine http.Handler, request *http.Request, token, idempotencyKey string) *httptest.ResponseRecorder {
	return performRequest(engine, request, token, idempotencyKey)
}

func performRequest(engine http.Handler, request *http.Request, token, idempotencyKey string) *httptest.ResponseRecorder {
	if token != "" {
		request.Header.Set("Authorization", "Bearer "+token)
	}

	if idempotencyKey != "" {
		request.Header.Set("X-Idempotency-Key", idempotencyKey)
	}

	recorder := httptest.NewRecorder()
	engine.ServeHTTP(recorder, request)

	return recorder
}

func assertError(t *testing.T, recorder *httptest.ResponseRecorder, status int, code string) {
	t.Helper()

	if recorder.Code != status {
		t.Fatalf("status = %d, ingin %d (body=%s)", recorder.Code, status, recorder.Body.String())
	}

	var body struct {
		Success bool   `json:"success"`
		Code    string `json:"code"`
		Message string `json:"message"`
		Data    any    `json:"data"`
	}

	if err := json.Unmarshal(recorder.Body.Bytes(), &body); err != nil {
		t.Fatalf("body bukan JSON valid: %v", err)
	}

	if body.Success {
		t.Fatalf("success = true, ingin false")
	}

	if body.Code != code {
		t.Fatalf("code = %q, ingin %q", body.Code, code)
	}

	if body.Message == "" {
		t.Fatalf("message kosong")
	}

	if body.Data != nil {
		t.Fatalf("data = %v, ingin null", body.Data)
	}
}
