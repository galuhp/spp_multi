// Package handler berisi handler contoh untuk skeleton backend Go.
// Logika bisnis pembayaran, tarif, dan laporan sengaja belum diimplementasikan;
// fokus paket ini adalah middleware dan struktur routing.
package handler

import (
	"context"
	"time"

	"github.com/gin-gonic/gin"
	"gorm.io/gorm"

	"spp-backend/internal/middleware"
	"spp-backend/internal/response"
)

// Health adalah liveness probe tanpa dependensi eksternal.
func Health(c *gin.Context) {
	response.Success(c, 200, "Layanan berjalan normal.", gin.H{"status": "ok"})
}

// Ready adalah readiness probe yang memeriksa database dan cache.
func Ready(db *gorm.DB, store middleware.CacheStore) gin.HandlerFunc {
	return func(c *gin.Context) {
		ctx, cancel := context.WithTimeout(c.Request.Context(), 2*time.Second)
		defer cancel()

		checks := gin.H{"database": "ok", "cache": "ok"}

		if db != nil {
			sqlDB, err := db.DB()
			if err != nil {
				checks["database"] = "error"
				response.Error(c, 503, response.CodeServiceUnavailable, "Layanan belum siap: database tidak tersedia.")
				return
			}

			if err := sqlDB.PingContext(ctx); err != nil {
				checks["database"] = "error"
				response.Error(c, 503, response.CodeServiceUnavailable, "Layanan belum siap: database tidak merespons.")
				return
			}
		}

		if pinger, ok := store.(interface {
			Ping(context.Context) error
		}); ok {
			if err := pinger.Ping(ctx); err != nil {
				checks["cache"] = "error"
				response.Error(c, 503, response.CodeServiceUnavailable, "Layanan belum siap: cache tidak merespons.")
				return
			}
		}

		response.Success(c, 200, "Layanan siap menerima permintaan.", checks)
	}
}

// NotImplemented adalah placeholder untuk endpoint bisnis yang belum dibuat.
func NotImplemented(c *gin.Context) {
	response.Error(
		c,
		501,
		response.CodeNotImplemented,
		"Endpoint ini belum diimplementasikan pada skeleton backend Go.",
	)
}
