package middleware_test

import (
	"context"
	"errors"
	"testing"

	"github.com/gin-gonic/gin"

	"spp-backend/internal/license"
	"spp-backend/internal/middleware"
)

func TestEnsureProTierAllowsProTier(t *testing.T) {
	engine := gin.New()
	engine.Use(middleware.EnsureProTier(license.NewStaticChecker("pro", nil)))
	engine.GET("/pro", func(c *gin.Context) {
		c.Status(200)
	})

	recorder := performRequest(engine, newRequest("GET", "/pro"))

	if recorder.Code != 200 {
		t.Fatalf("status = %d, ingin 200 (body=%s)", recorder.Code, recorder.Body.String())
	}
}

func TestEnsureProTierRejectsBasicTier(t *testing.T) {
	engine := gin.New()
	engine.Use(middleware.EnsureProTier(license.NewStaticChecker("basic", nil)))
	engine.GET("/pro", func(c *gin.Context) {
		c.Status(200)
	})

	recorder := performRequest(engine, newRequest("GET", "/pro"))
	assertEnvelopeError(t, recorder, 403, "TIER_UPGRADE_REQUIRED")
}

func TestEnsureProTierFeatureMatrix(t *testing.T) {
	checker := license.NewStaticChecker("basic", map[string]string{
		"laporan_standar":  "basic",
		"rekap_lanjutan":   "pro",
		"whatsapp_gateway": "pro",
	})

	tests := []struct {
		name       string
		feature    string
		wantStatus int
	}{
		{name: "fitur basic diizinkan", feature: "laporan_standar", wantStatus: 200},
		{name: "fitur pro ditolak", feature: "rekap_lanjutan", wantStatus: 403},
		{name: "fitur tidak dikenal dianggap pro", feature: "fitur_baru", wantStatus: 403},
		{name: "feature key kosong tetap memeriksa tier", feature: "   ", wantStatus: 403},
	}

	for _, test := range tests {
		t.Run(test.name, func(t *testing.T) {
			engine := gin.New()
			engine.Use(middleware.EnsureProTier(checker, test.feature))
			engine.GET("/pro", func(c *gin.Context) {
				c.Status(200)
			})

			recorder := performRequest(engine, newRequest("GET", "/pro"))

			if recorder.Code != test.wantStatus {
				t.Fatalf("status = %d, ingin %d (body=%s)", recorder.Code, test.wantStatus, recorder.Body.String())
			}

			if test.wantStatus == 403 {
				assertEnvelopeError(t, recorder, 403, "TIER_UPGRADE_REQUIRED")
			}
		})
	}
}

func TestEnsureProTierProAllowsEveryFeature(t *testing.T) {
	checker := license.NewStaticChecker("pro", map[string]string{"rekap_lanjutan": "pro"})

	engine := gin.New()
	engine.Use(middleware.EnsureProTier(checker, "rekap_lanjutan", "whatsapp_gateway"))
	engine.GET("/pro", func(c *gin.Context) {
		c.Status(200)
	})

	recorder := performRequest(engine, newRequest("GET", "/pro"))

	if recorder.Code != 200 {
		t.Fatalf("status = %d, ingin 200 (body=%s)", recorder.Code, recorder.Body.String())
	}
}

func TestEnsureProTierCheckFailureReturns500(t *testing.T) {
	engine := gin.New()
	engine.Use(middleware.EnsureProTier(failingLicenseChecker{}))
	engine.GET("/pro", func(c *gin.Context) {
		c.Status(200)
	})

	recorder := performRequest(engine, newRequest("GET", "/pro"))
	assertEnvelopeError(t, recorder, 500, "LICENSE_CHECK_FAILED")
}

func TestEnsureProTierNilCheckerReturns500(t *testing.T) {
	engine := gin.New()
	engine.Use(middleware.EnsureProTier(nil))
	engine.GET("/pro", func(c *gin.Context) {
		c.Status(200)
	})

	recorder := performRequest(engine, newRequest("GET", "/pro"))
	assertEnvelopeError(t, recorder, 500, "LICENSE_CHECK_FAILED")
}

type failingLicenseChecker struct{}

func (failingLicenseChecker) Tier(context.Context) (string, error) {
	return "", errors.New("lisensi tidak dapat dibaca")
}

func (failingLicenseChecker) HasFeature(context.Context, string) (bool, error) {
	return false, errors.New("lisensi tidak dapat dibaca")
}
