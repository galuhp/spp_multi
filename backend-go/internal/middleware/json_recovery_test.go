package middleware_test

import (
	"net/http"
	"net/http/httptest"
	"testing"

	"github.com/gin-gonic/gin"

	"spp-backend/internal/middleware"
)

func TestJSONRecoveryPassesNormalResponse(t *testing.T) {
	engine := gin.New()
	engine.Use(middleware.JSONRecovery())
	engine.GET("/normal", func(c *gin.Context) {
		c.JSON(200, gin.H{"success": true})
	})

	recorder := performRequest(engine, newRequest("GET", "/normal"))

	if recorder.Code != 200 {
		t.Fatalf("status = %d, ingin 200", recorder.Code)
	}
}

func TestJSONRecoveryTurnsPanicInto500(t *testing.T) {
	engine := gin.New()
	engine.Use(middleware.JSONRecovery())
	engine.GET("/panic", func(c *gin.Context) {
		panic("simulasi panic runtime")
	})

	recorder := performRequest(engine, newRequest("GET", "/panic"))
	body := assertEnvelopeError(t, recorder, 500, "INTERNAL_SERVER_ERROR")

	if body.Message != "Terjadi kesalahan internal pada server." {
		t.Fatalf("message = %q", body.Message)
	}
}

func TestJSONRecoveryDoesNotOverwriteWrittenResponse(t *testing.T) {
	engine := gin.New()
	engine.Use(middleware.JSONRecovery())
	engine.GET("/panic-after-write", func(c *gin.Context) {
		c.JSON(200, gin.H{"success": true})
		panic("panic setelah response")
	})

	recorder := performRequest(engine, newRequest("GET", "/panic-after-write"))

	if recorder.Code != 200 {
		t.Fatalf("status = %d, ingin 200", recorder.Code)
	}

	var body envelope
	if err := decodeJSON(recorder.Body.Bytes(), &body); err != nil {
		t.Fatalf("body bukan JSON valid: %v", err)
	}

	if !body.Success {
		t.Fatalf("body response awal tidak boleh ditimpa")
	}
}

func TestJSONRecoveryIgnoresAbortHandler(t *testing.T) {
	engine := gin.New()
	engine.Use(middleware.JSONRecovery())
	engine.GET("/abort", func(c *gin.Context) {
		panic(http.ErrAbortHandler)
	})

	recorder := httptest.NewRecorder()
	engine.ServeHTTP(recorder, newRequest("GET", "/abort"))

	if recorder.Body.Len() != 0 {
		t.Fatalf("body seharusnya kosong, dapat %q", recorder.Body.String())
	}
}
