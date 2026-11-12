package handler

import (
	"bytes"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	"github.com/gin-gonic/gin"

	"spp-backend/internal/auth"
	"spp-backend/internal/middleware"
)

func TestStudentHandlers(t *testing.T) {
	gin.SetMode(gin.TestMode)
	secret := "test-secret-32-chars-long-123456"
	tm := auth.NewTokenManager(secret, time.Hour)

	r := gin.New()
	r.POST("/login", StudentLogin(nil, tm))
	r.GET("/receipt/:kode/:token", PublicReceipt(secret))

	authGroup := r.Group("/siswa", middleware.AuthJWT(secret))
	authGroup.GET("/dashboard", StudentDashboard(nil))
	authGroup.GET("/card", StudentCard(nil, secret))

	// 1. Test Login
	body, _ := json.Marshal(map[string]string{
		"username": "0065432190",
		"password": "validpassword",
	})
	req, _ := http.NewRequest(http.MethodPost, "/login", bytes.NewReader(body))
	req.Header.Set("Content-Type", "application/json")
	w := httptest.NewRecorder()
	r.ServeHTTP(w, req)

	if w.Code != http.StatusOK {
		t.Fatalf("expected 200, got %d: %s", w.Code, w.Body.String())
	}

	var loginRes struct {
		Success bool `json:"success"`
		Data    struct {
			Token string `json:"token"`
		} `json:"data"`
	}
	if err := json.Unmarshal(w.Body.Bytes(), &loginRes); err != nil || loginRes.Data.Token == "" {
		t.Fatalf("expected token, got error %v", err)
	}

	token := loginRes.Data.Token

	// 2. Test Student Dashboard with Bearer token
	reqDash, _ := http.NewRequest(http.MethodGet, "/siswa/dashboard", nil)
	reqDash.Header.Set("Authorization", "Bearer "+token)
	wDash := httptest.NewRecorder()
	r.ServeHTTP(wDash, reqDash)

	if wDash.Code != http.StatusOK {
		t.Fatalf("expected 200 on dashboard, got %d: %s", wDash.Code, wDash.Body.String())
	}

	// 3. Test Student Card with Bearer token
	reqCard, _ := http.NewRequest(http.MethodGet, "/siswa/card", nil)
	reqCard.Header.Set("Authorization", "Bearer "+token)
	wCard := httptest.NewRecorder()
	r.ServeHTTP(wCard, reqCard)

	if wCard.Code != http.StatusOK {
		t.Fatalf("expected 200 on card, got %d: %s", wCard.Code, wCard.Body.String())
	}

	// 4. Test Public Receipt verification
	validToken := generateReceiptToken(secret, "SPP-20261012-0105")
	reqRcpt, _ := http.NewRequest(http.MethodGet, "/receipt/SPP-20261012-0105/"+validToken, nil)
	wRcpt := httptest.NewRecorder()
	r.ServeHTTP(wRcpt, reqRcpt)

	if wRcpt.Code != http.StatusOK {
		t.Fatalf("expected 200 on public receipt, got %d: %s", wRcpt.Code, wRcpt.Body.String())
	}
}
