package middleware_test

import (
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/gin-gonic/gin"

	"github.com/golang-jwt/jwt/v5"

	"spp-backend/internal/auth"
	"spp-backend/internal/middleware"
)

const testJWTSecret = "rahasia-uji-yang-cukup-panjang-untuk-hs256-2026"

func init() {
	gin.SetMode(gin.TestMode)
}

func newToken(t *testing.T, userID uint, username, role string, siswaID *uint, ttl time.Duration) string {
	t.Helper()

	manager := auth.NewTokenManager(testJWTSecret, ttl)

	token, _, err := manager.Issue(userID, username, role, siswaID)
	if err != nil {
		t.Fatalf("gagal membuat token: %v", err)
	}

	return token
}

func newExpiredToken(t *testing.T) string {
	t.Helper()

	claims := auth.Claims{
		UserID:   1,
		Username: "admin",
		Role:     "admin",
		RegisteredClaims: jwt.RegisteredClaims{
			IssuedAt:  jwt.NewNumericDate(time.Now().Add(-2 * time.Hour)),
			ExpiresAt: jwt.NewNumericDate(time.Now().Add(-time.Hour)),
		},
	}

	token, err := jwt.NewWithClaims(jwt.SigningMethodHS256, claims).SignedString([]byte(testJWTSecret))
	if err != nil {
		t.Fatalf("gagal membuat token kedaluwarsa: %v", err)
	}

	return token
}

func newTokenForSecret(t *testing.T, secret string) string {
	t.Helper()

	manager := auth.NewTokenManager(secret, time.Hour)

	token, _, err := manager.Issue(9, "penyusup", "admin", nil)
	if err != nil {
		t.Fatalf("gagal membuat token: %v", err)
	}

	return token
}

func newRequest(method, path string) *http.Request {
	return httptest.NewRequest(method, path, nil)
}

func performRequest(handler http.Handler, request *http.Request) *httptest.ResponseRecorder {
	recorder := httptest.NewRecorder()
	handler.ServeHTTP(recorder, request)

	return recorder
}

func decodeJSON(data []byte, target any) error {
	return json.Unmarshal(data, target)
}

func decodeEnvelope(t *testing.T, recorder *httptest.ResponseRecorder) envelope {
	t.Helper()

	var body envelope
	if err := json.Unmarshal(recorder.Body.Bytes(), &body); err != nil {
		t.Fatalf("body bukan JSON valid: %v (body=%q)", err, recorder.Body.String())
	}

	return body
}

type envelope struct {
	Success bool            `json:"success"`
	Message string          `json:"message"`
	Code    string          `json:"code"`
	Data    json.RawMessage `json:"data"`
}

func assertEnvelopeError(t *testing.T, recorder *httptest.ResponseRecorder, status int, code string) envelope {
	t.Helper()

	if recorder.Code != status {
		t.Fatalf("status = %d, ingin %d (body=%s)", recorder.Code, status, recorder.Body.String())
	}

	body := decodeEnvelope(t, recorder)
	if body.Success {
		t.Fatalf("success = true, ingin false")
	}

	if body.Code != code {
		t.Fatalf("code = %q, ingin %q", body.Code, code)
	}

	if body.Message == "" {
		t.Fatalf("message kosong, ingin pesan bahasa Indonesia")
	}

	if string(body.Data) != "null" {
		t.Fatalf("data = %s, ingin null", string(body.Data))
	}

	if contentType := recorder.Header().Get("Content-Type"); contentType != "application/json; charset=utf-8" {
		t.Fatalf("Content-Type = %q, ingin application/json; charset=utf-8", contentType)
	}

	return body
}

func withRole(role string) gin.HandlerFunc {
	return func(c *gin.Context) {
		if role != "" {
			c.Set(middleware.ContextRole, role)
		}

		c.Next()
	}
}
