package middleware_test

import (
	"encoding/json"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/gin-gonic/gin"

	"spp-backend/internal/middleware"
)

func TestAuthJWTSuccessStoresAllClaims(t *testing.T) {
	siswaID := uint(77)
	token := newToken(t, 12, "budi.santoso", "siswa", &siswaID, time.Hour)

	engine := gin.New()
	engine.Use(middleware.AuthJWT(testJWTSecret))
	engine.GET("/protected", func(c *gin.Context) {
		user, ok := middleware.CurrentUser(c)
		claims, claimsOK := middleware.CurrentClaims(c)

		response := gin.H{
			"user_ok":   ok,
			"claims_ok": claimsOK,
			"user_id":   user.UserID,
			"username":  user.Username,
			"role":      user.Role,
			"siswa_id":  nil,
			"raw_ctx":   c.GetUint(middleware.ContextUserID),
			"has_role":  c.GetString(middleware.ContextRole) == "siswa",
		}

		if user.SiswaID != nil {
			response["siswa_id"] = *user.SiswaID
		}

		if claims != nil {
			response["claims_subject"] = claims.Subject
		}

		c.JSON(200, response)
	})

	request := httptest.NewRequest("GET", "/protected", nil)
	request.Header.Set("Authorization", "Bearer "+token)

	recorder := performRequest(engine, request)
	if recorder.Code != 200 {
		t.Fatalf("status = %d, ingin 200 (body=%s)", recorder.Code, recorder.Body.String())
	}

	var body map[string]any
	if err := json.Unmarshal(recorder.Body.Bytes(), &body); err != nil {
		t.Fatalf("body bukan JSON valid: %v", err)
	}

	if body["user_ok"] != true || body["claims_ok"] != true || body["has_role"] != true {
		t.Fatalf("konteks auth tidak lengkap: %+v", body)
	}

	if body["user_id"].(float64) != 12 {
		t.Fatalf("user_id = %v, ingin 12", body["user_id"])
	}

	if body["username"] != "budi.santoso" || body["role"] != "siswa" {
		t.Fatalf("klaim tidak sesuai: %+v", body)
	}

	if body["siswa_id"].(float64) != 77 {
		t.Fatalf("siswa_id = %v, ingin 77", body["siswa_id"])
	}

	if body["raw_ctx"].(float64) != 12 {
		t.Fatalf("ContextUserID = %v, ingin 12", body["raw_ctx"])
	}

	if body["claims_subject"] != "12" {
		t.Fatalf("subject klaim = %v, ingin 12", body["claims_subject"])
	}
}

func TestAuthJWTRejectsInvalidTokens(t *testing.T) {
	expired := newExpiredToken(t)
	wrongSecretManager := newTokenForSecret(t, "secret-lain-yang-juga-cukup-panjang-1234567890")

	tests := []struct {
		name   string
		header string
	}{
		{name: "header kosong", header: ""},
		{name: "skema bukan bearer", header: "Basic abcdef"},
		{name: "token kosong", header: "Bearer "},
		{name: "token rusak", header: "Bearer bukan.token.valid"},
		{name: "tanda tangan salah", header: "Bearer " + wrongSecretManager},
		{name: "token kedaluwarsa", header: "Bearer " + expired},
	}

	for _, test := range tests {
		t.Run(test.name, func(t *testing.T) {
			engine := gin.New()
			engine.Use(middleware.AuthJWT(testJWTSecret))
			engine.GET("/protected", func(c *gin.Context) {
				c.Status(200)
			})

			request := httptest.NewRequest("GET", "/protected", nil)
			if test.header != "" {
				request.Header.Set("Authorization", test.header)
			}

			recorder := performRequest(engine, request)
			assertEnvelopeError(t, recorder, 401, "UNAUTHENTICATED")
		})
	}
}

func TestAuthJWTAllowsTokenWithoutSiswaID(t *testing.T) {
	token := newToken(t, 2, "petugas1", "petugas", nil, time.Hour)

	engine := gin.New()
	engine.Use(middleware.AuthJWT(testJWTSecret))
	engine.GET("/protected", func(c *gin.Context) {
		user, _ := middleware.CurrentUser(c)

		c.JSON(200, gin.H{"siswa_nil": user.SiswaID == nil})
	})

	request := httptest.NewRequest("GET", "/protected", nil)
	request.Header.Set("Authorization", "Bearer "+token)

	recorder := performRequest(engine, request)
	if recorder.Code != 200 {
		t.Fatalf("status = %d, ingin 200", recorder.Code)
	}

	var body map[string]bool
	if err := json.Unmarshal(recorder.Body.Bytes(), &body); err != nil {
		t.Fatalf("body bukan JSON valid: %v", err)
	}

	if !body["siswa_nil"] {
		t.Fatalf("siswa_id seharusnya nil")
	}
}
