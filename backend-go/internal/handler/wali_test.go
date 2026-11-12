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

func TestWaliHandlers(t *testing.T) {
	gin.SetMode(gin.TestMode)
	secret := "test-secret-32-chars-long-123456"
	tm := auth.NewTokenManager(secret, time.Hour)

	r := gin.New()
	r.POST("/login", StudentLogin(nil, tm))

	waliGroup := r.Group("/wali", middleware.AuthJWT(secret), middleware.RequireRole(middleware.RoleWaliKelas))
	waliGroup.GET("/tunggakan", WaliTunggakan(nil))
	waliGroup.GET("/rekap-kelas/:kelas_id", WaliRekap(nil))

	// 1. Test Login sebagai Wali Kelas
	loginBody, _ := json.Marshal(map[string]string{
		"username": "walikelas_12rpl1",
		"password": "validpassword",
	})
	reqLogin, _ := http.NewRequest(http.MethodPost, "/login", bytes.NewReader(loginBody))
	reqLogin.Header.Set("Content-Type", "application/json")
	wLogin := httptest.NewRecorder()
	r.ServeHTTP(wLogin, reqLogin)

	if wLogin.Code != http.StatusOK {
		t.Fatalf("expected 200 for wali login, got %d: %s", wLogin.Code, wLogin.Body.String())
	}

	var loginRes struct {
		Success bool `json:"success"`
		Data    struct {
			Token string `json:"token"`
			Role  string `json:"role"`
			Name  string `json:"name"`
		} `json:"data"`
	}
	if err := json.Unmarshal(wLogin.Body.Bytes(), &loginRes); err != nil {
		t.Fatalf("failed to parse login response: %v", err)
	}
	if loginRes.Data.Role != middleware.RoleWaliKelas {
		t.Fatalf("expected role %s, got %s", middleware.RoleWaliKelas, loginRes.Data.Role)
	}

	token := loginRes.Data.Token

	// 2. Test WaliTunggakan
	reqTung, _ := http.NewRequest(http.MethodGet, "/wali/tunggakan", nil)
	reqTung.Header.Set("Authorization", "Bearer "+token)
	wTung := httptest.NewRecorder()
	r.ServeHTTP(wTung, reqTung)

	if wTung.Code != http.StatusOK {
		t.Fatalf("expected 200 for wali tunggakan, got %d: %s", wTung.Code, wTung.Body.String())
	}

	var tungRes struct {
		Success bool `json:"success"`
		Data    struct {
			TotalMenunggak int `json:"total_menunggak"`
			Siswa          []struct {
				Nama           string `json:"nama"`
				TotalTunggakan int64  `json:"total_tunggakan"`
			} `json:"siswa"`
		} `json:"data"`
	}
	if err := json.Unmarshal(wTung.Body.Bytes(), &tungRes); err != nil {
		t.Fatalf("failed to parse tunggakan response: %v", err)
	}
	if tungRes.Data.TotalMenunggak != 2 {
		t.Errorf("expected 2 siswa menunggak, got %d", tungRes.Data.TotalMenunggak)
	}

	// 3. Test WaliRekap
	reqRekap, _ := http.NewRequest(http.MethodGet, "/wali/rekap-kelas/1", nil)
	reqRekap.Header.Set("Authorization", "Bearer "+token)
	wRekap := httptest.NewRecorder()
	r.ServeHTTP(wRekap, reqRekap)

	if wRekap.Code != http.StatusOK {
		t.Fatalf("expected 200 for wali rekap, got %d: %s", wRekap.Code, wRekap.Body.String())
	}

	var rekapRes struct {
		Success bool `json:"success"`
		Data    struct {
			NamaKelas      string `json:"nama_kelas"`
			TotalSiswa     int    `json:"total_siswa"`
			SiswaLunas     int    `json:"siswa_lunas"`
			SiswaMenunggak int    `json:"siswa_menunggak"`
			TotalTunggakan int64  `json:"total_tunggakan"`
		} `json:"data"`
	}
	if err := json.Unmarshal(wRekap.Body.Bytes(), &rekapRes); err != nil {
		t.Fatalf("failed to parse rekap response: %v", err)
	}
	if rekapRes.Data.TotalSiswa != 32 {
		t.Errorf("expected 32 siswa, got %d", rekapRes.Data.TotalSiswa)
	}
	if rekapRes.Data.TotalTunggakan != 2250000 {
		t.Errorf("expected total tunggakan 2250000, got %d", rekapRes.Data.TotalTunggakan)
	}
}
